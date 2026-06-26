<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function affanpay(Request $request)
    {
        $payload = $request->all();
        if (!$this->hasValidWebhookSecret($request)) {
            Log::warning('AffanPay webhook rejected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['success' => false, 'error' => 'Unauthorized webhook request'], 401);
        }

        $paymentReference = $this->extractPaymentReference($payload);
        $billReference = $this->extractBillReference($payload);
        $externalRef = $request->input('external_ref');

        Log::info('AffanPay Webhook Accepted', [
            'bill_reference' => $billReference,
            'payment_reference' => $paymentReference,
            'has_external_ref' => filled($externalRef),
        ]);

        $order = $this->findOrder($paymentReference, $billReference, $externalRef);

        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Order not found'], 404);
        }

        $this->updateOrderStatus($order, $request->input('status'), $payload, $paymentReference, $billReference);

        return response()->json(['success' => true]);
    }

    protected function hasValidWebhookSecret(Request $request): bool
    {
        $configuredSecret = config('affanpay.webhook_secret');

        if (blank($configuredSecret)) {
            return app()->environment(['local', 'testing']);
        }

        $providedSecret = $request->query('token')
            ?? $request->header('X-AffanPay-Webhook-Secret')
            ?? $request->bearerToken();

        return is_string($providedSecret) && hash_equals((string) $configuredSecret, $providedSecret);
    }

    protected function findOrder(?string $paymentReference, ?string $billReference, ?string $externalRef): ?Order
    {
        if ($paymentReference) {
            $order = Order::whereHas('payment', function ($query) use ($paymentReference) {
                $query->where('payment_reference', $paymentReference)
                    ->orWhere('affanpay_transaction_id', $paymentReference);
            })->first();

            if ($order) {
                return $order;
            }
        }

        if ($billReference) {
            $order = Order::whereHas('payment', function ($query) use ($billReference) {
                $query->where('affanpay_transaction_id', $billReference);
            })->first();

            if ($order) {
                return $order;
            }
        }

        if ($externalRef) {
            return Order::find($externalRef);
        }

        return null;
    }

    protected function updateOrderStatus(Order $order, $status, array $payload, ?string $paymentReference, ?string $billReference): void
    {
        ['order_status' => $orderStatus, 'payment_status' => $paymentStatus] = $this->normalizeStatuses($status, $payload);

        $order->update(['status' => $orderStatus]);

        if ($order->payment) {
            $paymentUpdate = [
                'status' => $paymentStatus,
                'payment_response' => $payload,
            ];

            if ($paymentReference) {
                $paymentUpdate['payment_reference'] = $paymentReference;
            }

            if ($billReference && !$order->payment->affanpay_transaction_id) {
                $paymentUpdate['affanpay_transaction_id'] = $billReference;
            }

            $order->payment->update($paymentUpdate);
        }
    }

    protected function normalizeStatuses($status, array $payload): array
    {
        $normalizedStatus = $this->extractNormalizedPaymentStatus($payload) ?? ($payload['status'] ?? $status);

        if (data_get($payload, 'data.status') !== null) {
            $normalizedStatus = data_get($payload, 'data.status');
        }

        $paymentStatus = $this->extractNormalizedPaymentStatus($payload);
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
