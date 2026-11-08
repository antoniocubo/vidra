<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame;

use Vidra\Catalogo\Domain\Exame\Exceptions\FaixaInvalida;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

final readonly class Analito
{
    /** @param list<FaixaDeReferencia> $faixas */
    public function __construct(public AnalitoId $id, public string $nome, public ?string $unidade, public int $casasDecimais, public array $faixas = [])
    {
        if (trim($nome) === '' || mb_strlen($nome) > 255 || $casasDecimais < 0 || $casasDecimais > 4) {
            throw InvalidValue::for('analitos', 'Informe nome e de zero a quatro casas decimais.');
        }
        $perfis = [];
        foreach ($faixas as $faixa) {
            if (isset($perfis[$faixa->perfil])) {
                throw FaixaInvalida::para('perfil', 'O perfil está repetido neste analito.');
            }
            $perfis[$faixa->perfil] = true;
        }
    }

    /** @param list<FaixaDeReferencia> $faixas */
    public function comFaixas(array $faixas): self
    {
        return new self($this->id, $this->nome, $this->unidade, $this->casasDecimais, $faixas);
    }
}
