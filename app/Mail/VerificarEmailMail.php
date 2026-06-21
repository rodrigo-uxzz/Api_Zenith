<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificarEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $codigo
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verifique seu email - Zenith');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verificar-email');
    }
}
