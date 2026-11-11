<?php

declare(strict_types=1);

namespace Vidra\Acesso\Infrastructure\Mail;

use Illuminate\Support\Facades\Mail;
use Vidra\Acesso\Domain\Assinatura\AvisosDeAssinatura;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaAssinatura;

final class AvisosDeAssinaturaPorEmail implements AvisosDeAssinatura
{
    public function enviar(array $emails, string $laboratorio, SituacaoDaAssinatura $situacao, int $diasRestantes, ?string $link): void
    {
        $mail = new AvisoDeAssinaturaMail($laboratorio, $situacao, $diasRestantes, $link ?? route('lab.assinatura.index'));
        foreach ($emails as $email) {
            Mail::to($email)->send($mail);
        }
    }
}
