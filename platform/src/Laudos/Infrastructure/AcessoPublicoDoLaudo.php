<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vidra\Laudos\Application\MontarLaudo\MontarLaudo;
use Vidra\Laudos\Domain\AcessoPublico;
use Vidra\Laudos\Domain\CodigoDeAcesso;
use Vidra\Laudos\Domain\ConteudoDoLaudo;
use Vidra\Laudos\Domain\DownloadDoLaudo;
use Vidra\Laudos\Domain\PedidoNaoEncontrado;
use Vidra\Laudos\Infrastructure\Persistence\AcessoModel;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

final readonly class AcessoPublicoDoLaudo implements AcessoPublico
{
    public function __construct(private ContextoLaboratorio $contexto, private MontarLaudo $montar, private EntregaDoPedidoEloquent $entrega) {}

    public function abrir(CodigoDeAcesso $codigo, string $ip, string $agente): ConteudoDoLaudo
    {
        return $this->noLaboratorio($codigo, function (string $pedido, string $id) use ($ip, $agente): ConteudoDoLaudo {
            $c = ($this->montar)($pedido);
            AcessoModel::query()->create(['id' => (string) Str::uuid7(), 'laudo_id' => $id, 'quando' => now(), 'ip' => self::ipReduzido($ip), 'user_agent' => mb_substr($agente, 0, 200)]);

            return $c;
        });
    }

    public function pdf(CodigoDeAcesso $codigo, ?int $versao = null): DownloadDoLaudo
    {
        return $this->noLaboratorio($codigo, fn (string $pedido): DownloadDoLaudo => $this->entrega->pdf($pedido, $versao));
    }

    /** @template T
     * @param  Closure(string,string):T  $acao
     * @return T
     */
    private function noLaboratorio(CodigoDeAcesso $codigo, Closure $acao): mixed
    {
        $r = DB::connection(config()->string('vidra.conexao_sistema'))->table('laudos')->select(['id', 'pedido_id', 'laboratorio_id'])->where('codigo_acesso', $codigo->value)->first();
        if ($r === null) {
            throw new PedidoNaoEncontrado;
        }

        return $this->contexto->executarComo((string) $r->laboratorio_id, fn () => $acao((string) $r->pedido_id, (string) $r->id));
    }

    public static function ipReduzido(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip) ?? '';
        }
        // IPv6 é reduzido a /48, sem identificador do dispositivo.
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return '';
        }
        $binario = inet_pton($ip);

        return $binario === false ? '' : (inet_ntop(substr($binario, 0, 6).str_repeat("\0", 10)) ?: '');
    }
}
