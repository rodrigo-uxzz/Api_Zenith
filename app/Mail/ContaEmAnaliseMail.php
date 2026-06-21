<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContaEmAnaliseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $nome) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sua conta está em análise - Zenith');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.conta-em-analise');
    }
}
