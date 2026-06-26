<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AffanPayService
{
    protected $baseUrl;
    protected $email;
    protected $password;
    protected $token;

    public function __construct()
    {
        $env = $this->getEnvironment();
        $this->baseUrl = config("affanpay.{$env}.url");
        $this->email = Setting::get("affanpay_{$env}_email", '');
        $this->password = Setting::get("affanpay_{$env}_password", '');

        Log::info('AffanPayService initialized', [
            'env' => $env,
            'baseUrl' => $this->baseUrl,
            'has_credentials' => filled($this->email) && filled($this->password),
        ]);

        $this->authenticate();
    }

    protected function authenticate()
    {
        if (!$this->email || !$this->password) {
            Log::warning('AffanPayService: No credentials provided');
            return;
        }

        $authUrl = $this->baseUrl . '/api/token';
        Log::info('AffanPayService: Authenticating', ['url' => $authUrl]);

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->connectTimeout(5)
                ->post($authUrl, [
                'email' => $this->email,
                'password' => $this->password,
            ]);

            Log::info('AffanPayService: Authentication response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                $this->token = $response->json('token');
                Log::info('AffanPayService: Authentication successful');
            } else {
                Log::error('AffanPayService: Authentication failed', [
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('AffanPayService: Authentication exception', ['message' => $e->getMessage()]);
        }
    }

    public function createBill($order)
    {
        if (!$this->token) {
            return ['success' => false, 'error' => 'Authentication failed. Please check your AffanPay credentials in admin panel.'];
        }

        try {
            $apiUrl = $this->baseUrl . '/api/v1/bill';
            Log::info('AffanPayService: Creating bill', ['url' => $apiUrl, 'order_id' => $order->id]);

            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(10)
                ->connectTimeout(5)
                ->contentType('application/json')
                ->post($apiUrl, [
                    'name' => 'Order ' . strtoupper(substr($order->public_token, 0, 8)) . ' - ' . $order->product->name,
                    'description' => 'Purchase of ' . $order->product->name . ' (Quantity: ' . $order->quantity . ')',
                    'amount' => $order->total_amount,
                    'external_ref' => (string)$order->id,
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'customer_phone' => $order->customer_phone,
                    'redirect_url' => url('/orders/' . $order->id),
                    'callback_url' => route('webhook.affanpay.api', $this->webhookCallbackParameters()),
                ]);

            $responseData = $response->json() ?? [];
            Log::info('AffanPayService: Create bill response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'bill_reference' => $responseData['id'] ?? null,
            ]);

            return $responseData;
        } catch (\Exception $e) {
            Log::error('AffanPayService: Create bill exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkBillStatus($billId)
    {
        if (!$this->token) {
            return ['success' => false, 'error' => 'Authentication failed. Please check your AffanPay credentials in admin panel.'];
        }

        try {
            $apiUrl = $this->baseUrl . '/api/v1/bill/' . $billId;
            Log::info('AffanPayService: Checking bill status', ['url' => $apiUrl, 'bill_id' => $billId]);

            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(10)
                ->connectTimeout(5)
                ->get($apiUrl);

            $responseData = $response->json() ?? [];
            Log::info('AffanPayService: Check bill status response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'bill_reference' => data_get($responseData, 'data.id') ?? $responseData['id'] ?? null,
                'payment_reference' => data_get($responseData, 'data.payments.0.payment_ref'),
                'payment_status' => data_get($responseData, 'data.payments.0.status') ?? data_get($responseData, 'data.status'),
            ]);

            return $responseData;
        } catch (\Exception $e) {
            Log::error('AffanPayService: Check bill status exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getEnvironment()
    {
        return Setting::get('affanpay_environment', 'sandbox');
    }

    public function setEnvironment($environment)
    {
        Setting::set('affanpay_environment', $environment);
    }

    public function getCredentials($env)
    {
        $password = Setting::get("affanpay_{$env}_password", '');

        return [
            'email' => Setting::get("affanpay_{$env}_email", ''),
            'password' => '',
            'has_password' => filled($password),
        ];
    }

    public function setCredentials($env, $email, $password)
    {
        Setting::set("affanpay_{$env}_email", $email);

        if (filled($password)) {
            Setting::set("affanpay_{$env}_password", $password);
        }
    }

    protected function webhookCallbackParameters(): array
    {
        $secret = config('affanpay.webhook_secret');

        return filled($secret) ? ['token' => $secret] : [];
    }
}
