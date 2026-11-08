<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Application\PainelDeAmostras;

final readonly class AmostraSelecionadaDto
{
    /**
     * @param  list<string>  $exames
     * @param  array<string, string>  $mensagens  texto para a clínica por motivo; o do motivo "outro" traz {texto} no lugar do detalhe
     */
    public function __construct(
        public AmostraLinhaDto $linha,
        public string $pedidoId,
        public string $protocolo,
        public string $pacienteDescricao,
        public string $solicitante,
        public array $exames,
        public array $mensagens,
        public ?string $motivoChave,
        public ?string $motivoTexto,
        public ?string $ressalva,
        public ?string $recoleta,
    ) {}
}
