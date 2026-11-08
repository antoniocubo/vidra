<?php

declare(strict_types=1);

namespace Vidra\Painel\Infrastructure;

use Illuminate\Support\ServiceProvider;
use Vidra\Painel\Domain\LeituraDoPainel;

final class PainelServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [LeituraDoPainel::class => ConsultaDoPainel::class];
}
