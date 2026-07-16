<?php

namespace App\Mail;

use App\Interfaces\Settings\SettingsRepositoryInterface;
use App\Models\PasabuyOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $invoice;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public PasabuyOrder $order,
        array $invoice,
    ) {
        $this->invoice = $invoice;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Received for '.$this->order->pasabuySchedule->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-confirmation',
            with: [
                'businessName' => app(SettingsRepositoryInterface::class)->get()?->business_name,
            ],
        );
    }
}
