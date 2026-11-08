<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use Vidra\Bancada\Domain\Trabalho;
use Vidra\Bancada\Domain\Trabalhos;

final readonly class MapaDeTrabalho
{
    public function __construct(private Trabalhos $trabalhos) {}

    /**
     * @return array{itens:list<Trabalho>,setores:array<string,int>} */
    public function __invoke(?string $setor = null): array
    {
        $itens = array_values(array_filter($this->trabalhos->todos(), fn (Trabalho $i): bool => $i->situacao === 'em_analise' && $i->chegada !== null));
        usort($itens, fn (Trabalho $a, Trabalho $b): int => ($b->urgente <=> $a->urgente) ?: strcmp($a->chegada ?? '', $b->chegada ?? '') ?: strcmp($a->id, $b->id));
        $setores = array_fill_keys(['hematologia', 'bioquimica', 'urinalise', 'parasitologia', 'hormonios', 'microbiologia', 'outro'], 0);
        foreach ($itens as $i) {
            $setores[$i->setor] = ($setores[$i->setor] ?? 0) + 1;
        }

        return ['itens' => array_values(array_filter($itens, fn (Trabalho $i): bool => $setor === null || $i->setor === $setor)), 'setores' => $setores];
    }
}
