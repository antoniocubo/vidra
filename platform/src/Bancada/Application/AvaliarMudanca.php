<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use Vidra\Bancada\Domain\Decimal;
use Vidra\Bancada\Domain\DeltaCheck;

final readonly class AvaliarMudanca
{
    /**
     * @return array{alerta:bool,percentual:?int} */
    public function __invoke(?string $novo, ?string $anterior): array
    {
        if ($novo === null || $anterior === null) {
            return ['alerta' => false, 'percentual' => null];
        }
        $n = new Decimal($novo);
        $a = new Decimal($anterior);
        $motor = new DeltaCheck;

        return ['alerta' => $motor->alerta($n, $a), 'percentual' => $motor->percentual($n, $a)];
    }
}
