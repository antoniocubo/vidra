<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

use DateTimeImmutable;
use DateTimeZone;

/** Por que o RT reabriu um laudo liberado, quem e quando. Vai impresso na versão nova. */
final readonly class Retificacao
{
    public string $motivo;

    public DateTimeImmutable $em;

    public function __construct(string $motivo, public int $por, DateTimeImmutable $em)
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw ErroDeLiberacao::com('Diga o motivo da retificação. Ele sai impresso no laudo.');
        }
        if (mb_strlen($motivo) > 500) {
            throw ErroDeLiberacao::com('Resuma o motivo da retificação em até 500 caracteres.');
        }

        $this->motivo = $motivo;
        $this->em = $em->setTimezone(new DateTimeZone('UTC'));
    }
}
