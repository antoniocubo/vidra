<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Infrastructure\Pedidos;

use Vidra\Catalogo\Application\DetalharExame;
use Vidra\Catalogo\Application\ExameDto;
use Vidra\Catalogo\Application\ListarExames;
use Vidra\Catalogo\Domain\Exame\Exceptions\ExameNaoEncontrado;
use Vidra\Pedidos\Domain\Portas\CatalogoDeExames;
use Vidra\Pedidos\Domain\Portas\ExameDoPedido;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

/** Lê o catálogo pelos casos de uso do módulo Catalogo, nunca pelo domínio dele. */
final readonly class CatalogoDeExamesDoLaboratorio implements CatalogoDeExames
{
    public function __construct(private DetalharExame $detalhar, private ListarExames $listar) {}

    public function ativo(string $exameId): ?ExameDoPedido
    {
        try {
            $exame = ($this->detalhar)($exameId)['exame'];
        } catch (ExameNaoEncontrado|InvalidValue) {
            return null;
        }

        return self::pedivel($exame) ? $this->converter($exame) : null;
    }

    public function buscar(string $termo, int $limite): array
    {
        return array_slice(array_map($this->converter(...), array_values(array_filter(($this->listar)($termo), self::pedivel(...)))), 0, $limite);
    }

    /** Exame sem parâmetros não tem onde receber resultado: o pedido ficaria preso para sempre. */
    private static function pedivel(ExameDto $exame): bool
    {
        return $exame->ativo && $exame->contagemAnalitos > 0;
    }

    private function converter(ExameDto $exame): ExameDoPedido
    {
        return new ExameDoPedido($exame->id, $exame->codigo, $exame->nome, $exame->setor, $exame->material, $exame->preco_centavos);
    }
}
