<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Bancada\Domain\Analito;
use Vidra\Bancada\Domain\Referencias;
use Vidra\Bancada\Domain\Trabalho;
use Vidra\Bancada\Domain\Trabalhos;

final class PortasDeBancadaEmMemoria implements Referencias, Trabalhos
{
    /** @param list<Trabalho> $itens @param list<Analito> $analitos */
    public function __construct(public array $itens, public array $analitos) {}

    public function todos(): array
    {
        return $this->itens;
    }

    public function bloquear(string $pedidoId): void {}

    public function transicionar(string $pedidoId, string $itemId, string $situacao): void
    {
        $this->itens = array_map(fn (Trabalho $i): Trabalho => $i->id !== $itemId ? $i : new Trabalho($i->id, $i->pedidoId, $i->laboratorioId, $i->exameId, $i->exame, $i->setor, $situacao, $i->pacienteId, $i->paciente, $i->descricao, $i->especieCurta, $i->perfil, $i->protocolo, $i->urgente, $i->amostra, $i->chegada, $i->solicitante, $i->telefoneCritico), $this->itens);
    }

    public function descricaoDoPerfil(string $exameId, ?string $perfil): ?string
    {
        return $perfil;
    }

    public function doExame(string $exameId, ?string $perfil): array
    {
        return $this->analitos;
    }
}
