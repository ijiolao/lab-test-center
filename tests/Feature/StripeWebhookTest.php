<?php

namespace Tests\Feature;

use App\Mail\RefundProcessed;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Stripe\Webhook;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_charge_refunded_webhook_marks_order_refunded_and_sends_email(): void
    {
        Mail::fake();
        config(['cashier.webhook.secret' => 'whsec_test']);

        $order = Order::factory()->create([
            'payment_status' => 'paid',
            'payment_intent_id' => 'pi_test_123',
        ]);

        $charge = (object) [
            'id' => 'ch_test',
            'payment_intent' => $order->payment_intent_id,
            'amount_refunded' => 1550,
        ];

        $event = (object) [
            'id' => 'evt_test',
            'type' => 'charge.refunded',
            'data' => (object) ['object' => $charge],
        ];

        Webhook::shouldReceive('constructEvent')
            ->once()
            ->andReturn($event);

        $response = $this->withHeaders([
            'Stripe-Signature' => 't=1,v1=test',
        ])->postJson('/webhooks/stripe', [
            'id' => $event->id,
            'type' => $event->type,
            'data' => ['object' => $charge],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'refunded',
        ]);

        Mail::assertSent(RefundProcessed::class, function (RefundProcessed $mail) use ($order) {
            return $mail->order->is($order);
        });
    }
}
