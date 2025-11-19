<?php

namespace Tests\Unit\Mail;

use App\Mail\RefundProcessed;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class RefundProcessedTest extends TestCase
{
    public function test_subject_includes_order_number(): void
    {
        $user = User::factory()->make();
        $order = Order::factory()->make(['order_number' => 'ORD-2001']);
        $order->setRelation('user', $user);

        $mailable = new RefundProcessed($order);

        $this->assertSame('Refund processed for Order ORD-2001', $mailable->envelope()->subject);
    }

    public function test_markdown_view_receives_order_and_user(): void
    {
        $user = User::factory()->make(['first_name' => 'Jamie']);
        $order = Order::factory()->make();
        $order->setRelation('user', $user);

        $mailable = new RefundProcessed($order);

        $content = $mailable->content();

        $this->assertSame('emails.refund-processed', $content->view);
        $this->assertArrayHasKey('order', $content->data);
        $this->assertArrayHasKey('user', $content->data);
        $this->assertSame('Jamie', $content->data['user']->first_name);
    }
}
