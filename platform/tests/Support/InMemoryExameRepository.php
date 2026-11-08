<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Catalogo\Domain\Exame\AnalitoId;
use Vidra\Catalogo\Domain\Exame\CodigoDoExame;
use Vidra\Catalogo\Domain\Exame\Exame;
use Vidra\Catalogo\Domain\Exame\ExameId;
use Vidra\Catalogo\Domain\Exame\ExameRepository;
use Vidra\Catalogo\Domain\Exame\Setor;

final class InMemoryExameRepository implements ExameRepository
{
    /** @var array<string,Exame> */
    public array $exames = [];

    private int $sequencia = 0;

    private function uuid(): string
    {
        return '019a5d70-0000-7000-8000-'.str_pad((string) ++$this->sequencia, 12, '0', STR_PAD_LEFT);
    }

    public function nextIdentity(): ExameId
    {
        return new ExameId($this->uuid());
    }

    public function nextAnalitoIdentity(): AnalitoId
    {
        return new AnalitoId($this->uuid());
    }

    public function save(Exame $exame): void
    {
        $this->exames[$exame->id->value] = clone $exame;
    }

    public function porId(ExameId $id): ?Exame
    {
        return isset($this->exames[$id->value]) ? clone $this->exames[$id->value] : null;
    }

    public function existeCodigo(CodigoDoExame $codigo, ?ExameId $exceto = null): ?Exame
    {
        foreach ($this->exames as $e) {
            if ($e->codigo->value === $codigo->value && ($exceto === null || ! $e->id->equals($exceto))) {
                return clone $e;
            }
        }

        return null;
    }

    public function listar(string $busca = '', ?Setor $setor = null, bool $mostrarInativos = false): array
    {
        return array_values(array_filter($this->exames, fn (Exame $e): bool => ($mostrarInativos || $e->ativo) && ($setor === null || $e->setor === $setor) && ($busca === '' || str_contains(mb_strtolower($e->codigo->value.' '.$e->nome), mb_strtolower($busca)))));
    }
}
