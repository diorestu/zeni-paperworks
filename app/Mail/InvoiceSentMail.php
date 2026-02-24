<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public array $companyProfile = [],
        public ?string $companyLogoUrl = null
    ) {
    }

    public function envelope(): Envelope
    {
        $companyName = $this->companyProfile['name'] ?? config('app.name', 'Paperwork');

        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number} from {$companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-sent',
        );
    }
}
