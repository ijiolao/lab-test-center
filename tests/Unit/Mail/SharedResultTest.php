<?php

namespace Tests\Unit\Mail;

use App\Mail\SharedResult;
use App\Models\Order;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedResultTest extends TestCase
{
    public function test_mailable_attaches_pdf_when_it_exists(): void
    {
        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $user = User::factory()->make();
        $order = Order::factory()->make(['order_number' => 'ORD-1234']);
        $order->setRelation('user', $user);

        $result = Result::factory()->make(['pdf_path' => 'results/sample.pdf']);
        $result->setRelation('order', $order);

        Storage::put('results/sample.pdf', 'pdf-content');

        $mailable = new SharedResult($result, 'Dr. Smith', 'Thanks for reviewing.');

        $attachments = $mailable->attachments();

        $this->assertCount(1, $attachments);
        $this->assertSame('result-ORD-1234.pdf', $attachments[0]->name);
        $this->assertSame('application/pdf', $attachments[0]->contentType);
    }

    public function test_mailable_handles_missing_pdf_gracefully(): void
    {
        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        $user = User::factory()->make();
        $order = Order::factory()->make();
        $order->setRelation('user', $user);

        $result = Result::factory()->make(['pdf_path' => 'results/missing.pdf']);
        $result->setRelation('order', $order);

        $mailable = new SharedResult($result, 'Dr. Grey');

        $this->assertEmpty($mailable->attachments());
    }
}
