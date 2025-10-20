<?php

namespace App\Mail;

use App\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResultReady extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The result instance
     *
     * @var Result
     */
    public $result;

    /**
     * Create a new message instance
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    /**
     * Get the message envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Test Results Are Ready - ' . $this->result->order->order_number,
        );
    }

    /**
     * Get the message content definition
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.result-ready',
            with: [
                'result' => $this->result,
                'order' => $this->result->order,
                'user' => $this->result->order->user,
                'hasCriticalValues' => $this->result->has_critical_values,
            ],
        );
    }

    /**
     * Get the attachments for the message
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}