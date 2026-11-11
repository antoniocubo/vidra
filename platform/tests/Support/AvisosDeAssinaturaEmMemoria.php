<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Acesso\Domain\Assinatura\AvisosDeAssinatura;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaAssinatura;

final class AvisosDeAssinaturaEmMemoria implements AvisosDeAssinatura
{
    /** @var list<array{emails: list<string>, situacao: SituacaoDaAssinatura, dias: int, link: ?string}> */
    public array $enviados = [];

    public function enviar(array $emails, string $laboratorio, SituacaoDaAssinatura $situacao, int $diasRestantes, ?string $link): void
    {
        $this->enviados[] = ['emails' => $emails, 'situacao' => $situacao, 'dias' => $diasRestantes, 'link' => $link];
    }
}
