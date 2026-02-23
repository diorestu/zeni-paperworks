<?php

namespace App\Mail;

use App\Models\SubscriptionInvoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionPaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public SubscriptionInvoice $invoice
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription payment successful',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-payment-success',
        );
    }
}
