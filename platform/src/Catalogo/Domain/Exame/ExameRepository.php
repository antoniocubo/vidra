<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame;

interface ExameRepository
{
    public function nextIdentity(): ExameId;

    public function nextAnalitoIdentity(): AnalitoId;

    public function save(Exame $exame): void;

    public function porId(ExameId $id): ?Exame;

    public function existeCodigo(CodigoDoExame $codigo, ?ExameId $exceto = null): ?Exame;

    /** @return list<Exame> */
    public function listar(string $busca = '', ?Setor $setor = null, bool $mostrarInativos = false): array;
}
