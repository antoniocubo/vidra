<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

final readonly class PedidoParaConferencia
{
    /**
     * @param array<string,string> $itens situação de cada exame ativo, por id do item
     * @param list<ResultadoParaConferencia> $resultados
     * @param array<string,string> $nomes nome de cada exame, por id do item */
    public function __construct(public string $id, public string $laboratorioId, public string $protocolo, public string $paciente, public string $solicitante, public string $telefoneCritico, public array $itens, public array $resultados, public array $nomes = []) {}

    /** Já tem algum resultado para o RT olhar, mesmo que falte exame. */
    public function emConferencia(): bool
    {
        return in_array('resultado_digitado', $this->itens, true);
    }

    /** @return list<string> os exames que ainda impedem a liberação, com o motivo ("Bioquímico · aguardando amostra") */
    public function faltando(): array
    {
        $faltando = [];
        foreach ($this->itens as $item => $situacao) {
            if ($situacao !== 'resultado_digitado') {
                $faltando[] = ($this->nomes[$item] ?? 'Exame').' · '.match ($situacao) {
                    'aguardando_amostra' => 'aguardando amostra',
                    'em_analise' => 'em análise na bancada',
                    default => 'sem resultado',
                };
            }
        }

        return $faltando;
    }

    public function pronto(): bool
    {
        return $this->itens !== [] && array_filter($this->itens, fn (string $s): bool => $s !== 'resultado_digitado') === [];
    }
}
