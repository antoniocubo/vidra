<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use DateTimeImmutable;
use Vidra\Laudos\Domain\Laudo;

final readonly class LaudoDto
{
    public int $versao;

    public ?string $reabertoMotivo;

    /** @var list<\Vidra\Laudos\Domain\VersaoDoLaudo> */
    public array $versoes;

    public string $numero;

    public string $rtNome;

    public string $rtRegistro;

    public string $usuarioNome;

    public DateTimeImmutable $liberadoEm;

    public string $codigoAcesso;

    /** Valores críticos avisados: todo crítico é avisado antes de liberar. */
    public int $criticos;

    /** @param list<\Vidra\Laudos\Domain\VersaoDoLaudo> $versoes */
    public function __construct(Laudo $laudo, array $versoes)
    {
        $this->numero = $laudo->versaoFormatada();
        $this->versao = $laudo->versao;
        $this->reabertoMotivo = $laudo->reaberto?->motivo;
        $this->versoes = $versoes;
        $this->rtNome = $laudo->rtNome;
        $this->rtRegistro = $laudo->rtRegistro;
        $this->usuarioNome = $laudo->usuarioNome;
        $this->liberadoEm = $laudo->liberadoEm;
        $this->codigoAcesso = $laudo->codigoAcesso->value;
        $this->criticos = count($laudo->avisos);
    }
}
