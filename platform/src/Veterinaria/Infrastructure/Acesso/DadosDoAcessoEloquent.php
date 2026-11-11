<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Acesso;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Vidra\Laudos\Infrastructure\Persistence\LaudoModel;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;
use Vidra\Veterinaria\Domain\Acesso\DadosDoAcesso;
use Vidra\Veterinaria\Domain\Acesso\LaboratorioDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\PedidosVisiveis;

final readonly class DadosDoAcessoEloquent implements DadosDoAcesso
{
    public function __construct(private ContextoLaboratorio $contexto, private PedidosVisiveis $visiveis) {}

    public function identidade(string $veterinario): array
    {
        $r = DB::connection(config()->string('vidra.conexao_sistema'))->table('veterinarios')->where('id', $veterinario)->first();

        return ['nome' => (string) ($r->nome ?? ''), 'email' => (string) ($r->email ?? ''), 'crmv' => 'CRMV-'.($r->crmv_uf ?? '').' '.($r->crmv_numero ?? '')];
    }

    public function novidades(LaboratorioDoVeterinario $acesso): array
    {
        $ultimo = Cache::get('vet:ultimo-acesso:'.$acesso->vinculoId);
        $desde = is_string($ultimo) ? $ultimo : null;

        return $this->contexto->executarComo($acesso->laboratorioId, function () use ($acesso, $desde): array {
            $consulta = LaudoModel::query()->whereIn('pedido_id', array_keys($this->visiveis->doLaboratorio($acesso)));
            if ($desde !== null) {
                $consulta->where('liberado_em', '>', $desde);
            }
            $pedidos = $consulta->pluck('pedido_id');
            // Todo valor crítico é avisado antes da liberação: contar avisos basta.
            $criticos = DB::table('avisos_valor_critico')->where('laboratorio_id', $acesso->laboratorioId)->whereIn('pedido_id', $pedidos)->distinct()->count('pedido_id');
            $novos = $pedidos->count();

            return ['novos' => $novos, 'criticos' => $criticos];
        });
    }

    public function registrarAcesso(LaboratorioDoVeterinario $acesso): void
    {
        // O marco é por vínculo e sobrevive ao logout, sem criar outra tabela de acesso.
        // No segundo, arredondado para cima: liberado_em é gravado sem fração e o Postgres arredonda.
        Cache::forever('vet:ultimo-acesso:'.$acesso->vinculoId, now()->utc()->ceilSecond()->format('Y-m-d H:i:s'));
    }
}
