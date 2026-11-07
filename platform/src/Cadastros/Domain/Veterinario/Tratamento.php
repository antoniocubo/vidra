<?php

declare(strict_types=1);

namespace Vidra\Cadastros\Domain\Veterinario;

enum Tratamento: string
{
    case Dra = 'dra';
    case Dr = 'dr';
    case Nenhum = 'nenhum';

    public function prefixo(): string
    {
        return match ($this) {
            self::Dra => 'Dra. ',
            self::Dr => 'Dr. ',
            self::Nenhum => '',
        };
    }
}
