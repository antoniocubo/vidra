<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use Vidra\Bancada\Domain\Analito;
use Vidra\Bancada\Domain\DeltaCheck;
use Vidra\Bancada\Domain\Referencias;
use Vidra\Bancada\Domain\Resultado;
use Vidra\Bancada\Domain\ResultadoAnterior;
use Vidra\Bancada\Domain\ResultadoRepository;
use Vidra\Bancada\Domain\Trabalho;

final readonly class AbrirItemNaBancada
{
    public function __construct(private ObterTrabalho $obter, private Referencias $referencias, private ResultadoRepository $resultados) {}

    /**
     * @return array{item:Trabalho,perfilDescricao:?string,analitos:list<array{analito:Analito,resultado:?Resultado,anterior:?ResultadoAnterior,delta:bool,percentual:?int}>} */
    public function __invoke(string $itemId): array
    {
        $item = ($this->obter)($itemId);
        $salvos = [];
        foreach ($this->resultados->doItem($itemId) as $r) {
            $salvos[$r->analitoId] = $r;
        }
        $linhas = [];
        foreach ($this->referencias->doExame($item->exameId, $item->perfil) as $a) {
            $r = $salvos[$a->id] ?? null;
            $anterior = $this->resultados->anteriorLiberado($item->pacienteId, $a->id, $item->pedidoId);
            $delta = $r?->valor !== null && $anterior?->valor !== null && (new DeltaCheck)->alerta($r->valor, $anterior->valor);
            $linhas[] = ['analito' => $a, 'resultado' => $r, 'anterior' => $anterior, 'delta' => $delta, 'percentual' => $delta ? (new DeltaCheck)->percentual($r->valor, $anterior->valor) : null];
        }

        return ['item' => $item, 'perfilDescricao' => $this->referencias->descricaoDoPerfil($item->exameId, $item->perfil), 'analitos' => $linhas];
    }
}
