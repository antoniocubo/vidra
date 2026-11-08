<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use DateTimeImmutable;
use Vidra\Laudos\Domain\Laudo;

final readonly class LaudoDto
{
    public string $numero;

    public string $rtNome;

    public string $rtRegistro;

    public string $usuarioNome;

    public DateTimeImmutable $liberadoEm;

    public string $codigoAcesso;

    public function __construct(Laudo $laudo)
    {
        $this->numero = $laudo->numeroFormatado();
        $this->rtNome = $laudo->rtNome;
        $this->rtRegistro = $laudo->rtRegistro;
        $this->usuarioNome = $laudo->usuarioNome;
        $this->liberadoEm = $laudo->liberadoEm;
        $this->codigoAcesso = $laudo->codigoAcesso->value;
    }
}
