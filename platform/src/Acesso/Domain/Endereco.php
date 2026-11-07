<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Uf;

final readonly class Endereco
{
    public string $cep;

    public function __construct(string $cep, public string $cidade, public Uf $uf, public string $endereco)
    {
        $this->cep = str_replace('-', '', trim($cep));
        if (! preg_match('/^\d{8}$/', $this->cep)) {
            throw InvalidValue::for('cep', 'informe os 8 dígitos do CEP');
        }
        if (trim($cidade) === '' || trim($endereco) === '') {
            throw InvalidValue::for('endereco', 'informe cidade e endereço');
        }
    }
}
