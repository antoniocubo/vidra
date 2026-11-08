<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\PainelDeAmostras;

final readonly class PainelDeAmostrasDto
{
    /**
     * @param  list<AmostraLinhaDto>  $recebidas
     * @param  list<AmostraLinhaDto>  $aguardando
     */
    public function __construct(public string $data, public array $recebidas, public array $aguardando, public ?AmostraSelecionadaDto $selecionada) {}
}
