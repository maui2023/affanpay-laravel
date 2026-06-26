<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_order_routes_use_an_opaque_token_instead_of_numeric_ids(): void
    {
        $product = Product::create([
            'name' => 'Security Test Product',
            'description' => 'Tokenized order route test',
            'price' => 49.99,
            'image' => 'https://example.test/product.png',
        ]);

        $order = Order::create([
            'customer_name' => 'Security Tester',
            'customer_email' => 'security@example.test',
            'customer_phone' => '0123456789',
            'product_id' => $product->id,
            'quantity' => 1,
            'total_amount' => 49.99,
            'status' => 'pending',
        ]);

        $order->payment()->create([
            'amount' => 49.99,
            'status' => 'pending',
        ]);

        $this->assertNotNull($order->public_token);
        $this->assertStringContainsString($order->public_token, route('orders.show', $order));

        $this->get(route('orders.show', $order))->assertOk();
        $this->get('/orders/'.$order->id)->assertNotFound();
    }

    public function test_webhook_requires_the_configured_shared_secret(): void
    {
        config(['affanpay.webhook_secret' => 'top-secret-token']);

        $product = Product::create([
            'name' => 'Webhook Product',
            'description' => 'Webhook verification test',
            'price' => 99.99,
            'image' => 'https://example.test/webhook.png',
        ]);

        $order = Order::create([
            'customer_name' => 'Webhook Tester',
            'customer_email' => 'webhook@example.test',
            'customer_phone' => '0198765432',
            'product_id' => $product->id,
            'quantity' => 1,
            'total_amount' => 99.99,
            'status' => 'pending',
        ]);

        $order->payment()->create([
            'amount' => 99.99,
            'status' => 'pending',
        ]);

        $payload = [
            'external_ref' => (string) $order->id,
            'status' => 'paid',
            'payment_reference' => 'AP-SECURITY-TEST',
        ];

        $this->postJson(route('webhook.affanpay.api'), $payload)->assertUnauthorized();

        $this->postJson(route('webhook.affanpay.api', ['token' => 'top-secret-token']), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
    }

    public function test_admin_area_requires_basic_auth_when_credentials_are_configured(): void
    {
        putenv('ADMIN_USERNAME=admin');
        putenv('ADMIN_PASSWORD=strong-password');
        $_ENV['ADMIN_USERNAME'] = 'admin';
        $_ENV['ADMIN_PASSWORD'] = 'strong-password';
        $_SERVER['ADMIN_USERNAME'] = 'admin';
        $_SERVER['ADMIN_PASSWORD'] = 'strong-password';

        $this->get('/admin')->assertStatus(401);

        $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('admin:strong-password'),
        ])->get('/admin')->assertOk();
    }
}
