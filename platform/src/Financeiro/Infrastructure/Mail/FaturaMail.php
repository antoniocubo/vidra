<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Vidra\Financeiro\Domain\Fatura;

final class FaturaMail extends Mailable
{
    public function __construct(public readonly Fatura $fatura, public readonly string $laboratorio, private readonly string $pdf) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Fatura {$this->fatura->numeroFormatado()} · {$this->fatura->competencia->rotulo()} · {$this->laboratorio}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.fatura');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->pdf, 'fatura-'.$this->fatura->numeroFormatado().'.pdf')->withMime('application/pdf')];
    }
}
