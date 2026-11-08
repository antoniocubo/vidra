<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

use DateTimeImmutable;

final readonly class Laudo
{
    /**
     * @param list<AvisoDeValorCritico> $avisos */
    private function __construct(public string $id, public string $laboratorioId, public string $pedidoId, public int $numero, public int $liberadoPor, public string $usuarioNome, public string $rtNome, public string $rtRegistro, public DateTimeImmutable $liberadoEm, public ?string $observacao, public CodigoDeAcesso $codigoAcesso, public array $avisos) {}

    public static function liberar(string $id, PedidoParaConferencia $pedido, int $numero, int $por, string $usuarioNome, string $rtNome, string $rtRegistro, DateTimeImmutable $quando, ?string $observacao, CodigoDeAcesso $codigo, bool $ehRt): self
    {
        if (! $ehRt) {
            throw ErroDeLiberacao::com('Só o responsável técnico libera laudos.');
        }
        if (! $pedido->pronto() || $pedido->resultados === []) {
            throw ErroDeLiberacao::com('Todos os itens precisam de resultados digitados antes de liberar.');
        }
        $avisos = [];
        foreach ($pedido->resultados as $r) {
            if ($r->critico() && ! $r->avisado()) {
                throw ErroDeLiberacao::com("Registre o aviso do valor crítico de {$r->nome} antes de liberar.");
            }
            if ($r->critico() && $r->aviso !== null) {
                $avisos[] = $r->aviso;
            }
        }

        return new self($id, $pedido->laboratorioId, $pedido->id, $numero, $por, $usuarioNome, $rtNome, $rtRegistro, $quando, $observacao, $codigo, $avisos);
    }

    /**
     * @param list<AvisoDeValorCritico> $avisos */
    public static function restaurar(string $id, string $laboratorioId, string $pedidoId, int $numero, int $por, string $usuarioNome, string $rtNome, string $rtRegistro, DateTimeImmutable $quando, ?string $observacao, CodigoDeAcesso $codigo, array $avisos): self
    {
        return new self($id, $laboratorioId, $pedidoId, $numero, $por, $usuarioNome, $rtNome, $rtRegistro, $quando, $observacao, $codigo, $avisos);
    }

    public function numeroFormatado(): string
    {
        return 'L-'.str_pad((string) $this->numero, 5, '0', STR_PAD_LEFT);
    }
}
