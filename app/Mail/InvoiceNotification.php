<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->invoice->status) {
            'paid'    => "Fatura #{$this->invoice->number} paga com sucesso!",
            'overdue' => "⚠️ Fatura #{$this->invoice->number} em atraso",
            default   => "Nova fatura #{$this->invoice->number} — " . config('app.name'),
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice');
    }

    public function attachments(): array
    {
        return [];
    }
}
