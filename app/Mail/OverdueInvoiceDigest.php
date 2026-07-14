<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class OverdueInvoiceDigest extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $rows;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $seller,
        $rows,
    ) {
        $this->rows = collect($rows);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Overdue Invoice Digest',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.overdue-invoice-digest',
        );
    }
}
