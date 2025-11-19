<?php

namespace App\Mail;

use App\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SharedResult extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Result $result,
        public string $recipientName,
        public ?string $message = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Patient result shared: ' . $this->result->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.result-shared',
            with: [
                'result' => $this->result,
                'order' => $this->result->order,
                'patient' => $this->result->order->user,
                'recipientName' => $this->recipientName,
                'messageBody' => $this->message,
            ],
        );
    }

    public function attachments(): array
    {
        if (!$this->result->pdf_path || !Storage::exists($this->result->pdf_path)) {
            return [];
        }

        return [
            Attachment::fromStorage($this->result->pdf_path)
                ->as('result-' . $this->result->order->order_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
