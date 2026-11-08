<?php

declare(strict_types=1);

namespace Vidra\Shared\Infrastructure;

use Illuminate\Contracts\Events\Dispatcher;
use Vidra\Shared\Domain\Eventos;

final readonly class EventosLaravel implements Eventos
{
    public function __construct(private Dispatcher $dispatcher) {}

    public function publicar(object $evento): void
    {
        $this->dispatcher->dispatch($evento);
    }
}
