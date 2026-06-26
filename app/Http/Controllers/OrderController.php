<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\AffanPayService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $affanPay;

    public function __construct(AffanPayService $affanPay)
    {
        $this->affanPay = $affanPay;
    }

    public function create(Product $product)
    {
        return view('orders.create', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        $request->validate([
            'customer_name' => 'required|string|max:60',
            'customer_email' => 'required|email|max:50',
            'customer_phone' => 'nullable|string|max:20',
            'quantity' => 'required|integer|min:1',
        ]);

        $totalAmount = $product->price * $request->quantity;

        $order = Order::create([
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);

        // Create payment record
        $payment = $order->payment()->create([
            'amount' => $totalAmount,
            'status' => 'pending',
        ]);

        // Call AffanPay API to create bill
        $billResponse = $this->affanPay->createBill($order);

        // Update payment with response
        if (isset($billResponse['success']) && $billResponse['success'] && isset($billResponse['url'])) {
            $payment->update([
                'affanpay_transaction_id' => $billResponse['id'] ?? null,
                'payment_reference' => $this->extractPaymentReference($billResponse),
                'status' => 'processing',
                'payment_response' => $billResponse,
            ]);

            // Redirect to AffanPay payment page
            return redirect()->to($billResponse['url']);
        } else {
            $payment->update([
                'status' => 'failed',
                'payment_response' => $billResponse,
            ]);

            return redirect()->route('orders.show', $order)->with('payment_response', $billResponse);
        }
    }

    public function show(Order $order, Request $request)
    {
        $order->load('product', 'payment');

        // Use the return params only as a signal to verify with AffanPay.
        if ($this->shouldVerifyPaymentOnReturn($request, $order)) {
            $verification = $this->verifyPaymentStatus($order, $request->all());

            return redirect()
                ->route('orders.show', $order)
                ->with('return_verification', $verification);
        }

        return view('orders.show', compact('order'));
    }

    public function retryPayment(Order $order)
    {
        $order->load('product', 'payment');

        // Call AffanPay API to create bill
        $billResponse = $this->affanPay->createBill($order);

        // Update payment with response
        if (isset($billResponse['success']) && $billResponse['success'] && isset($billResponse['url'])) {
            $order->payment->update([
                'affanpay_transaction_id' => $billResponse['id'] ?? null,
                'payment_reference' => $this->extractPaymentReference($billResponse),
                'status' => 'processing',
                'payment_response' => $billResponse,
            ]);

            // Redirect to AffanPay payment page
            return redirect()->to($billResponse['url']);
        } else {
            $order->payment->update([
                'status' => 'failed',
                'payment_response' => $billResponse,
            ]);

            return redirect()->route('orders.show', $order)->with('payment_response', $billResponse);
        }
    }

    public function checkStatus(Order $order)
    {
        $order->load('product', 'payment');

        $verification = $this->verifyPaymentStatus($order);

        return redirect()->route('orders.show', $order)->with('return_verification', $verification);
    }

    public function status(Order $order)
    {
        $order->load('product', 'payment');

        $verification = null;
        if ($order->payment && in_array($order->payment->status, ['pending', 'processing'], true)) {
            $verification = $this->verifyPaymentStatus($order);
            $order->refresh()->load('product', 'payment');
        }

        return response()->json([
            'order_reference' => strtoupper(substr($order->public_token, 0, 8)),
            'order_status' => $order->status,
            'payment_status' => $order->payment?->status ?? 'pending',
            'payment_reference' => $order->payment?->payment_reference,
            'bill_reference' => $order->payment?->affanpay_transaction_id,
            'amount' => number_format((float) $order->total_amount, 2, '.', ''),
            'terminal' => in_array($order->payment?->status, ['paid', 'failed'], true),
            'message' => $verification['message'] ?? $this->buildVerificationMessage($order->payment?->status ?? 'pending'),
            'verification_type' => $verification['type'] ?? null,
            'payment_url' => data_get($order->payment?->payment_response, 'url'),
        ]);
    }

    protected function updateOrderStatus(Order $order, $status, $responseData)
    {
        ['order_status' => $orderStatus, 'payment_status' => $paymentStatus] = $this->normalizeStatuses($status, $responseData);

        $order->update(['status' => $orderStatus]);

        if ($order->payment) {
            $paymentUpdate = [
                'status' => $paymentStatus,
                'payment_response' => $responseData,
            ];

            $paymentReference = $this->extractPaymentReference($responseData);
            if ($paymentReference) {
                $paymentUpdate['payment_reference'] = $paymentReference;
            }

            $billReference = $this->extractBillReference($responseData);
            if ($billReference && !$order->payment->affanpay_transaction_id) {
                $paymentUpdate['affanpay_transaction_id'] = $billReference;
            }

            $order->payment->update($paymentUpdate);
        }
    }

    protected function shouldVerifyPaymentOnReturn(Request $request, Order $order): bool
    {
        if (!$order->payment) {
            return false;
        }

        return collect([
            'status',
            'success',
            'payment_reference',
            'payment_ref',
            'paymentReference',
            'reference_no',
            'id',
        ])->contains(fn ($key) => $request->has($key));
    }

    protected function verifyPaymentStatus(Order $order, array $returnPayload = []): array
    {
        if (!$order->payment) {
            return [
                'type' => 'error',
                'message' => 'No payment record was found for this order.',
            ];
        }

        $reference = $this->extractPaymentReference($returnPayload)
            ?? $order->payment->payment_reference
            ?? $this->extractBillReference($returnPayload)
            ?? $order->payment->affanpay_transaction_id;
        $referenceLabel = $this->extractPaymentReference($returnPayload) || $order->payment->payment_reference
            ? 'Payment Reference'
            : 'Bill Reference';

        if (!$reference) {
            return [
                'type' => 'warning',
                'message' => 'Unable to verify this payment because no AffanPay reference is available yet.',
            ];
        }

        $statusResponse = $this->affanPay->checkBillStatus($reference);

        if (!$this->hasUsableStatusResponse($statusResponse)) {
            return [
                'type' => 'warning',
                'message' => 'Return received, but the latest payment status could not be verified with AffanPay yet.',
                'status_response' => $statusResponse,
                'reference' => $reference,
                'reference_label' => $referenceLabel,
            ];
        }

        $this->updateOrderStatus($order, $statusResponse['status'] ?? null, array_merge($returnPayload, $statusResponse));

        $order->refresh()->load('payment');
        $finalStatus = $order->payment?->status ?? 'pending';

        return [
            'type' => $finalStatus === 'paid' ? 'success' : ($finalStatus === 'failed' ? 'error' : 'warning'),
            'message' => $this->buildVerificationMessage($finalStatus),
            'status_response' => $statusResponse,
            'reference' => $order->payment?->payment_reference
                ?? $this->extractPaymentReference($statusResponse)
                ?? $order->payment?->affanpay_transaction_id
                ?? $reference,
            'reference_label' => $order->payment?->payment_reference || $this->extractPaymentReference($statusResponse)
                ? 'Payment Reference'
                : 'Bill Reference',
        ];
    }

    protected function normalizeStatuses($status, array $responseData): array
    {
        $normalizedStatus = $this->extractNormalizedPaymentStatus($responseData) ?? $status;

        if (isset($responseData['status']) && $responseData['status'] !== null) {
            $normalizedStatus = $responseData['status'];
        }

        if (data_get($responseData, 'data.status') !== null) {
            $normalizedStatus = data_get($responseData, 'data.status');
        }

        $paymentStatus = $this->extractNormalizedPaymentStatus($responseData);
        if ($paymentStatus) {
            $normalizedStatus = $paymentStatus;
        }

        if ($normalizedStatus === true) {
            $normalizedStatus = 'paid';
        }

        $normalizedStatus = strtolower((string) $normalizedStatus);

        if (in_array($normalizedStatus, ['paid', 'success', 'succeeded', 'completed'], true)) {
            return ['order_status' => 'paid', 'payment_status' => 'paid'];
        }

        if (in_array($normalizedStatus, ['failed', 'fail', 'cancelled', 'canceled', 'expired', 'rejected'], true)) {
            return ['order_status' => 'cancelled', 'payment_status' => 'failed'];
        }

        return ['order_status' => 'pending', 'payment_status' => 'processing'];
    }

    protected function buildVerificationMessage(string $status): string
    {
        return match ($status) {
            'paid' => 'Payment verified successfully with AffanPay.',
            'failed' => 'Payment failed or was cancelled in AffanPay.',
            default => 'Payment is still processing in AffanPay.',
        };
    }

    protected function extractPaymentReference(array $payload): ?string
    {
        return $this->extractReferenceValue($payload, [
            'payment_reference',
            'payment_ref',
            'paymentReference',
            'reference_no',
            'referenceNo',
        ]) ?? data_get($payload, 'data.payments.0.payment_ref');
    }

    protected function extractBillReference(array $payload): ?string
    {
        return $this->extractReferenceValue($payload, [
            'bill_id',
            'bill_reference',
            'billReference',
            'id',
        ]);
    }

    protected function extractReferenceValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if (filled($value)) {
                return (string) $value;
            }

            $nestedValue = data_get($payload, 'data.' . $key);
            if (filled($nestedValue)) {
                return (string) $nestedValue;
            }
        }

        return null;
    }

    protected function hasUsableStatusResponse(array $statusResponse): bool
    {
        return isset($statusResponse['success'])
            ? (bool) $statusResponse['success']
            : filled(data_get($statusResponse, 'data.id'))
                || filled(data_get($statusResponse, 'data.status'))
                || filled(data_get($statusResponse, 'data.payments.0.status'));
    }

    protected function extractNormalizedPaymentStatus(array $payload): ?string
    {
        $paymentStatus = data_get($payload, 'data.payments.0.status');
        $paymentStatusCode = data_get($payload, 'data.payments.0.status_code');

        if ($paymentStatusCode === 1 || strtolower((string) $paymentStatus) === 'success') {
            return 'paid';
        }

        if (in_array(strtolower((string) $paymentStatus), ['failed', 'fail', 'cancelled', 'canceled', 'expired', 'rejected'], true)) {
            return 'failed';
        }

        return null;
    }
}
