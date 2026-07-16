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

class OverdueReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $invoice;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public PasabuyOrder $order,
        array $invoice,
        public int $daysOverdue,
    ) {
        $this->invoice = $invoice;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Your balance for '.$this->order->pasabuySchedule->title.' is still overdue',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.overdue-reminder',
            with: [
                'businessName' => app(SettingsRepositoryInterface::class)->get()?->business_name,
                'daysOverdue' => $this->daysOverdue,
            ],
        );
    }
}
