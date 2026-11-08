<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\AceitarAmostraComRessalva;

use Vidra\Pedidos\Domain\Amostra\AmostraId;
use Vidra\Pedidos\Domain\Amostra\AmostraRepository;
use Vidra\Pedidos\Domain\Amostra\Exceptions\AmostraNaoEncontrada;

final readonly class AceitarAmostraComRessalva
{
    public function __construct(private AmostraRepository $amostras) {}

    public function __invoke(string $amostraId, string $ressalva): void
    {
        $amostra = $this->amostras->porId(new AmostraId($amostraId)) ?? throw AmostraNaoEncontrada::para();
        $amostra->aceitarComRessalva($ressalva);
        $this->amostras->save($amostra);
    }
}
