<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Vidra\Acesso\Domain\Convite;

final class ConviteMail extends Mailable
{
    public function __construct(public readonly Convite $convite, public readonly string $quemConvidou, public readonly string $laboratorio, public readonly string $link) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->quemConvidou.' convidou você para o '.$this->laboratorio.' no Vidra');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.convite');
    }
}
