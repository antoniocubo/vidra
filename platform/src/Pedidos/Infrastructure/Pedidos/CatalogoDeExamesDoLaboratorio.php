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

        return $exame->ativo ? $this->converter($exame) : null;
    }

    public function buscar(string $termo, int $limite): array
    {
        return array_slice(array_map($this->converter(...), ($this->listar)($termo)), 0, $limite);
    }

    private function converter(ExameDto $exame): ExameDoPedido
    {
        return new ExameDoPedido($exame->id, $exame->codigo, $exame->nome, $exame->setor, $exame->material, $exame->preco_centavos);
    }
}
