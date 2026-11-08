<?php

declare(strict_types=1);

namespace Vidra\Shared\Domain;

/** Publica eventos de domínio (ex.: LaudoLiberado) sem que o domínio conheça o Laravel. */
interface Eventos
{
    public function publicar(object $evento): void;
}
