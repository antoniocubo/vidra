<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Vidra\Laudos\Application\MontarLaudo\MontarLaudo;
use Vidra\Laudos\Domain\ArquivoDoLaudo;
use Vidra\Laudos\Domain\GeradorDePdf;
use Vidra\Laudos\Infrastructure\HtmlDoLaudo;
use Vidra\Laudos\Infrastructure\Mail\LaudoLiberadoMail;
use Vidra\Laudos\Infrastructure\Persistence\EntregaModel;
use Vidra\Laudos\Infrastructure\Persistence\LaudoModel;
use Vidra\Shared\Infrastructure\Tenancy\ComLaboratorio;

final class EntregarLaudo implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public string $envioId;

    public function __construct(public string $laboratorioId, public string $pedidoId, public string $modo = 'completa')
    {
        $this->envioId = (string) Str::uuid7();
        $this->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [app(ComLaboratorio::class), (new WithoutOverlapping('laudo:'.$this->laboratorioId.':'.$this->pedidoId))->releaseAfter(30)->expireAfter(75)];
    }

    public function handle(MontarLaudo $montar, HtmlDoLaudo $html, GeradorDePdf $gerador, ArquivoDoLaudo $arquivos): void
    {
        $model = LaudoModel::query()->where('pedido_id', $this->pedidoId)->firstOrFail();
        $laudo = $montar($this->pedidoId);
        if ($model->pdf_caminho === null) {
            if ($this->modo === 'emails') {
                throw new RuntimeException('O PDF ainda não está pronto para reenviar os e-mails.');
            }
            $model->increment('pdf_tentativas');
            $model->update(['pdf_tentado_em' => now()]);
            try {
                $bytes = $gerador->deHtml($html($laudo));
                $caminho = $arquivos->guardar($this->laboratorioId, $model->id, $bytes);
                $model->update(['pdf_caminho' => $caminho, 'pdf_gerado_em' => now(), 'pdf_erro' => null]);
            } catch (Throwable $e) {
                $model->update(['pdf_erro' => mb_substr($e->getMessage(), 0, 2000)]);
                throw $e;
            }
        } else {
            $bytes = $arquivos->ler($model->pdf_caminho);
        }
        if ($laudo->resumoDiario) {
            Log::info('Laudo não enviado ao solicitante: resumo diário ainda não implementado.', ['laudo_id' => $model->id, 'laboratorio_id' => $this->laboratorioId]);
        }
        $falha = null;
        foreach ($laudo->destinatarios as $d) {
            $chave = ['laudo_id' => $model->id, 'envio_id' => $this->envioId, 'destinatario' => $d['email'], 'papel' => $d['papel']];
            if (EntregaModel::query()->where($chave)->where('situacao', 'enviado')->exists()) {
                continue;
            }
            $erro = null;
            try {
                Mail::to($d['email'])->send(new LaudoLiberadoMail($laudo, $bytes, $d['papel']));
            } catch (Throwable $e) {
                $erro = mb_substr($e->getMessage(), 0, 2000);
                $falha = $e;
            }
            EntregaModel::query()->create([...$chave, 'id' => (string) Str::uuid7(), 'canal' => 'email', 'situacao' => $erro === null ? 'enviado' : 'falhou', 'quando' => now(), 'erro' => $erro]);
        }
        if ($falha !== null) {
            throw $falha;
        }
    }
}
