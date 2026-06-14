<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContaReprovadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $nome) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sua conta não foi aprovada - Zenith');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.conta-reprovada');
    }
}
