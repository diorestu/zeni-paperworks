<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $planName,
        public string $renewalDate,
        public float $amount
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your subscription will expire in 14 days',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-expiring-soon',
        );
    }
}
