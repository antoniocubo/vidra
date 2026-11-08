<?php

declare(strict_types=1);

namespace Vidra\Laudos\Infrastructure;

use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Vidra\Bancada\Application\ResultadoAnteriorDaBancada;
use Vidra\Bancada\Application\ResultadosParaConferencia;
use Vidra\Bancada\Infrastructure\Persistence\ResultadoModel;
use Vidra\Catalogo\Infrastructure\Persistence\AnalitoModel;
use Vidra\Catalogo\Infrastructure\Persistence\ExameModel;
use Vidra\Laudos\Application\MontarLaudo\FormatoDoLaudo as F;
use Vidra\Laudos\Domain\ConteudoDoLaudo;
use Vidra\Laudos\Domain\FonteDoLaudo;
use Vidra\Laudos\Domain\Laudo;
use Vidra\Laudos\Domain\PreferenciaDeEntrega;
use Vidra\Laudos\Infrastructure\Persistence\LaudoModel;
use Vidra\Pedidos\Application\ParaLaudo\LerPedidoParaLaudo;

final readonly class FonteDosPedidos implements FonteDoLaudo
{
    public function __construct(private LerPedidoParaLaudo $pedido, private ResultadosParaConferencia $resultados, private PreferenciaDeEntrega $preferencia) {}

    public function montar(Laudo $l): ConteudoDoLaudo
    {
        $p = ($this->pedido)($l->pedidoId);
        $lab = DB::table('laboratorios')->where('id', $l->laboratorioId)->firstOrFail();
        $exames = [];
        $resultados = ($this->resultados)($l->pedidoId);
        foreach ($p->itens as $i) {
            $exame = ExameModel::query()->find($i['exameId']);
            $linhas = [];
            foreach ($resultados as $r) {
                if ($r->itemId !== $i['id']) {
                    continue;
                }
                $analito = AnalitoModel::query()->find($r->analitoId);
                $casas = $analito->casas_decimais ?? strlen(explode('.', $r->valor ?? '')[1] ?? '');
                $a = $this->anterior($r->pacienteId, $r->analitoId, $l);
                $linhas[] = ['nome' => $r->nome, 'valor' => F::resultado($r->valor, $casas, $r->texto), 'unidade' => $r->unidade,
                    'referencia' => F::faixa($r->referencia), 'classificacao' => F::classificacao($r->classificacao),
                    'anterior' => $a === null ? null : F::resultado($a->valor, $casas, $a->texto), 'anteriorEm' => F::data($a?->liberadoEm, 'd/m/Y')];
            }
            $exames[] = ['nome' => $i['nome'], 'material' => $i['material'], 'metodo' => (string) ($exame?->getAttribute('metodo') ?? ''), 'resultados' => $linhas];
        }
        $ressalvas = [];
        $chegadas = [];
        foreach ($p->amostras as $a) {
            if ($a['recebidaEm'] !== null) {
                $chegadas[] = $a['recebidaEm'];
            }
            if ($a['ressalva'] !== null) {
                $ressalvas[] = 'Amostra aceita com ressalva: '.$a['ressalva'];
            }
        }
        sort($chegadas);
        $avisos = [];
        foreach ($l->avisos as $a) {
            $nome = User::query()->find($a->avisadoPor)->name ?? 'Atendente';
            $avisos[] = 'Valor crítico comunicado a '.$a->destinatario.' por telefone em '.F::data($a->avisadoEm, 'd/m/Y').' às '.F::data($a->avisadoEm, 'H:i').' por '.$nome;
        }
        $link = rtrim(config()->string('vidra.urls.laudo'), '/').'/'.str_replace('-', '', $l->codigoAcesso->value);
        $texto = F::whatsapp($p->responsavel, $p->paciente, $link, $l->codigoAcesso->value);
        $celular = F::celular($p->telefone);
        $destinatarios = [];
        if ($p->autorizaEmail && $p->emailResponsavel !== null && $p->emailResponsavel !== '') {
            $destinatarios[] = ['email' => $p->emailResponsavel, 'papel' => 'tutor'];
        }
        $preferencia = $this->preferencia->doSolicitante($p->solicitanteId);
        if ($preferencia === 'cada_laudo' && $p->emailSolicitante !== '') {
            $destinatarios[] = ['email' => $p->emailSolicitante, 'papel' => 'solicitante'];
        }
        $cnpj = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', (string) $lab->cnpj) ?? '';

        return new ConteudoDoLaudo($l->id, $l->pedidoId, $l->laboratorioId, $l->numeroFormatado(), $l->codigoAcesso->value, $link,
            ['nome' => (string) $lab->nome, 'endereco' => $lab->endereco.' · '.$lab->cidade.' / '.$lab->uf, 'telefone' => (new \Vidra\Shared\Domain\ValueObjects\Telefone((string) $lab->telefone))->formatted(), 'cnpj' => $cnpj],
            ['nome' => $p->paciente, 'descricao' => $p->descricao, 'responsavel' => $p->responsavel], ['nome' => $p->solicitante, 'registro' => $p->registro], $p->protocolo,
            ['pedido' => F::data($p->criadoEm) ?? '', 'recebimento' => F::data($chegadas[0] ?? null), 'liberacao' => F::data($l->liberadoEm) ?? ''],
            $exames, $ressalvas, $l->observacao, $avisos, $l->rtNome, $l->rtRegistro, $p->textos, $texto,
            $celular === null ? null : 'https://wa.me/'.$celular.'?text='.rawurlencode($texto), $destinatarios, $preferencia === 'resumo_diario', LaudoModel::query()->whereKey($l->id)->whereNotNull('pdf_caminho')->exists());
    }

    private function anterior(string $pacienteId, string $analitoId, Laudo $laudo): ?ResultadoAnteriorDaBancada
    {
        $quando = $laudo->liberadoEm->format('Y-m-d H:i:s.u');
        $r = ResultadoModel::query()->join('laudos', 'laudos.pedido_id', '=', 'resultados.pedido_id')
            ->where('laudos.laboratorio_id', $laudo->laboratorioId)
            ->where('resultados.paciente_id', $pacienteId)->where('resultados.analito_id', $analitoId)
            ->where('resultados.pedido_id', '!=', $laudo->pedidoId)
            ->where(function ($q) use ($laudo, $quando): void {
                $q->where('laudos.liberado_em', '<', $quando)->orWhere(function ($q) use ($laudo, $quando): void {
                    $q->where('laudos.liberado_em', $quando)->where('laudos.id', '<', $laudo->id);
                });
            })
            ->orderByDesc('laudos.liberado_em')->orderByDesc('laudos.id')
            ->select(['resultados.*', 'laudos.liberado_em as anterior_liberado_em'])->first();

        return $r === null ? null : new ResultadoAnteriorDaBancada($r->valor, $r->texto, new DateTimeImmutable((string) $r->getAttribute('anterior_liberado_em')));
    }
}
