<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class AcessoDoVeterinarioMail extends Mailable
{
    public function __construct(public readonly string $nome, public readonly string $laboratorio, public readonly string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->laboratorio.' liberou seu acesso ao Vidra');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.acesso-veterinario');
    }
}
