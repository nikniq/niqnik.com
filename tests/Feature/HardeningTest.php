<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Product;
use App\Models\User;
use App\Services\StripeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\PaymentIntent;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_management_is_admin_only(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)->get('/media')->assertNotFound();
        $this->actingAs($user)->get('/admin/media')->assertForbidden();
        $this->actingAs($admin)->get('/admin/media')->assertOk();
    }

    public function test_whois_lookup_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/whois/lookup', ['domain' => 'bad!domain'])->assertStatus(422);
        }

        $this->postJson('/whois/lookup', ['domain' => 'bad!domain'])->assertStatus(429);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_app_boots_without_stripe_secret(): void
    {
        config(['stripe.secret' => null]);

        $client = StripeClient::make();

        $this->expectException(\RuntimeException::class);
        $client->retrievePaymentIntent('pi_x');
    }

    public function test_stripe_payment_intent_cannot_be_reused(): void
    {
        config(['stripe.currency' => 'USD']);
        $user = User::factory()->create();
        $product = Product::create([
            'name' => 'Pro', 'product_code' => 'PRO-1', 'vendor' => 'Nikniq', 'category' => 'Tools',
            'price' => 10.00, 'duration_months' => 12,
        ]);

        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'amount' => 1000,
            'currency' => 'usd',
            'metadata' => ['user_id' => (string) $user->id, 'product_id' => (string) $product->id, 'domain' => ''],
        ]);

        $stripe = Mockery::mock(StripeClient::class);
        $stripe->shouldReceive('retrievePaymentIntent')->with('pi_test_123')->andReturn($intent);
        $this->app->instance(StripeClient::class, $stripe);

        $payload = ['product_id' => $product->id, 'payment_intent_id' => 'pi_test_123'];

        $first = $this->actingAs($user)->post('/stripe/complete', $payload);
        $first->assertRedirect();
        $this->assertSame(1, License::count());
        $this->assertSame('pi_test_123', License::first()->payment_reference);

        $second = $this->actingAs($user)->from('/dashboard')->post('/stripe/complete', $payload);
        $second->assertRedirect('/dashboard')->assertSessionHasErrors('payment');
        $this->assertSame(1, License::count());
    }
}
