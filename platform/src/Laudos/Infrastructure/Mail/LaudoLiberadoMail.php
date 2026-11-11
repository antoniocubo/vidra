<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Vidra\Laudos\Domain\ConteudoDoLaudo;

final class LaudoLiberadoMail extends Mailable
{
    public function __construct(public readonly ConteudoDoLaudo $laudo, private readonly string $pdf, public readonly string $papel) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->laudo->versao > 1 ? 'Laudo retificado de ' : 'Laudo do ').$this->laudo->paciente['nome'].' · '.$this->laudo->laboratorio['nome'].($this->laudo->versao > 1 ? ' · '.$this->laudo->retificacao?->motivo : ''));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.laudo-liberado');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->pdf, $this->laudo->nomeArquivo())->withMime('application/pdf')];
    }
}
