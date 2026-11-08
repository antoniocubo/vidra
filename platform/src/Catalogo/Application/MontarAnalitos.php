<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

use Vidra\Catalogo\Domain\Exame\Analito;
use Vidra\Catalogo\Domain\Exame\AnalitoId;
use Vidra\Catalogo\Domain\Exame\Exame;
use Vidra\Catalogo\Domain\Exame\ExameRepository;

final readonly class MontarAnalitos
{
    public function __construct(private ExameRepository $exames) {}

    /** @return list<Analito> */
    public function __invoke(DadosExameInput $input, ?Exame $exame = null): array
    {
        $analitos = [];
        foreach ($input->analitos as $a) {
            $anterior = isset($a['id']) ? $exame?->analito(new AnalitoId($a['id'])) : null;
            if (isset($a['id']) && $anterior === null) {
                throw \Vidra\Shared\Domain\ValueObjects\InvalidValue::for('analitos', 'Não informe IDs no cadastro.');
            }
            $analitos[] = new Analito($anterior->id ?? $this->exames->nextAnalitoIdentity(), trim($a['nome']), $a['unidade'], $a['casas_decimais'], $anterior->faixas ?? []);
        }

        return $analitos;
    }
}
