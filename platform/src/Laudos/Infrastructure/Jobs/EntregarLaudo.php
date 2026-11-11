<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure\Jobs;

use DateTimeImmutable;
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
use Vidra\Laudos\Domain\ConteudoDoLaudo;
use Vidra\Laudos\Domain\GeradorDePdf;
use Vidra\Laudos\Domain\Retificacao;
use Vidra\Laudos\Infrastructure\HtmlDoLaudo;
use Vidra\Laudos\Infrastructure\Mail\LaudoLiberadoMail;
use Vidra\Laudos\Infrastructure\Persistence\EntregaModel;
use Vidra\Laudos\Infrastructure\Persistence\LaudoModel;
use Vidra\Laudos\Infrastructure\Persistence\VersaoLaudoModel;
use Vidra\Shared\Infrastructure\Tenancy\ComLaboratorio;

final class EntregarLaudo implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public string $envioId;

    public function __construct(public string $laboratorioId, public string $pedidoId, public string $modo = 'completa', public ?int $versao = null)
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

    private function guardarVersaoAnterior(string $laudoId, HtmlDoLaudo $html, GeradorDePdf $gerador, ArquivoDoLaudo $arquivos): void
    {
        $versao = VersaoLaudoModel::query()->where('laudo_id', $laudoId)->where('versao', $this->versao)->firstOrFail();
        if ($versao->pdf_caminho !== null) {
            return;
        }
        $conteudo = $versao->conteudo === null ? null : unserialize($versao->conteudo, ['allowed_classes' => [ConteudoDoLaudo::class, Retificacao::class, \Vidra\Laudos\Domain\VersaoDoLaudo::class, DateTimeImmutable::class]]);
        if (! $conteudo instanceof ConteudoDoLaudo) {
            throw new RuntimeException('O conteúdo da versão anterior do laudo não foi preservado.');
        }
        $bytes = $gerador->deHtml($html($conteudo));
        $caminho = $arquivos->guardar($this->laboratorioId, $laudoId.'-v'.$versao->versao, $bytes);
        $versao->update(['pdf_caminho' => $caminho]);
    }

    public function handle(MontarLaudo $montar, HtmlDoLaudo $html, GeradorDePdf $gerador, ArquivoDoLaudo $arquivos): void
    {
        $model = LaudoModel::query()->where('pedido_id', $this->pedidoId)->firstOrFail();
        if ($this->versao !== null && $this->versao !== $model->versao) {
            $this->guardarVersaoAnterior($model->id, $html, $gerador, $arquivos);

            return;
        }
        $laudo = $montar($this->pedidoId);
        if ($model->pdf_caminho === null) {
            if ($this->modo === 'emails') {
                throw new RuntimeException('O PDF ainda não está pronto para reenviar os e-mails.');
            }
            $model->increment('pdf_tentativas');
            $model->update(['pdf_tentado_em' => now()]);
            try {
                $bytes = $gerador->deHtml($html($laudo));
                $caminho = $arquivos->guardar($this->laboratorioId, $model->id.'-v'.$model->versao, $bytes);
                $model->update(['pdf_caminho' => $caminho, 'pdf_gerado_em' => now(), 'pdf_erro' => null]);
                VersaoLaudoModel::query()->where('laudo_id', $model->id)->where('versao', $model->versao)->whereNull('pdf_caminho')->update(['pdf_caminho' => $caminho]);
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
        $destinatarios = $laudo->destinatarios;
        if ($model->versao > 1) {
            foreach (EntregaModel::query()->where('laudo_id', $model->id)->where('situacao', 'enviado')->get() as $anterior) {
                $destinatarios[] = ['email' => $anterior->destinatario, 'papel' => $anterior->papel];
            }
        }
        $unicos = [];
        foreach ($destinatarios as $d) {
            $unicos[mb_strtolower(trim($d['email']))] ??= $d;
        }
        foreach ($unicos as $d) {
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
