<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaAssinatura;

final class AvisoDeAssinaturaMail extends Mailable
{
    public function __construct(
        public readonly string $laboratorio,
        public readonly SituacaoDaAssinatura $situacao,
        public readonly int $diasRestantes,
        public readonly string $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->situacao) {
            SituacaoDaAssinatura::EmTeste => 'Seu teste do Vidra está acabando',
            SituacaoDaAssinatura::Ativa => 'Sua assinatura do Vidra vence em breve',
            SituacaoDaAssinatura::Atrasada => 'O pagamento do Vidra está atrasado',
            SituacaoDaAssinatura::SomenteConsulta => 'Seu laboratório está só em consulta no Vidra',
        });
    }

    public function content(): Content
    {
        return new Content(view: 'mail.aviso-assinatura');
    }
}
