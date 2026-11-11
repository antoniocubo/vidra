<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Infrastructure;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Vidra\Financeiro\Domain\ArquivosDeFatura;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

/** No mesmo disco privado dos laudos, na pasta faturas/. */
final readonly class ArquivosDeFaturaLocal implements ArquivosDeFatura
{
    public function __construct(private LaboratorioAtual $atual) {}

    public function guardar(string $faturaId, string $bytes): void
    {
        $caminho = 'faturas/'.$this->atual->id().'/'.$faturaId.'.pdf';
        Storage::disk('laudos')->put($caminho, $bytes);
        DB::table('faturas')->where('id', $faturaId)->where('laboratorio_id', $this->atual->id())->update(['pdf_caminho' => $caminho]);
    }
}
