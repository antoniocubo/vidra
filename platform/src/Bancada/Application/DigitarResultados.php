<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Bancada\Domain\ErroDeBancada;
use Vidra\Bancada\Domain\Referencias;
use Vidra\Bancada\Domain\Resultado;
use Vidra\Bancada\Domain\ResultadoRepository;
use Vidra\Bancada\Domain\Trabalhos;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;

final readonly class DigitarResultados
{
    public function __construct(private ObterTrabalho $obter, private Trabalhos $trabalhos, private Referencias $referencias, private ResultadoRepository $resultados, private LaboratorioAtual $laboratorio, private Transacao $transacao) {}

    public function __invoke(DigitarResultadosInput $input): void
    {
        $this->transacao->executar(function () use ($input): void {
            $item = ($this->obter)($input->itemId);
            $this->trabalhos->bloquear($item->pedidoId);
            $item = ($this->obter)($input->itemId);
            if ($item->situacao !== 'em_analise' || $item->chegada === null) {
                throw ErroDeBancada::com('Reabra o item para corrigir os resultados. Só itens em análise podem ser digitados.');
            }
            $salvos = [];
            foreach ($this->resultados->doItem($item->id) as $r) {
                $salvos[$r->analitoId] = $r;
            }
            $analitos = $this->referencias->doExame($item->exameId, $item->perfil);
            $permitidos = array_map(fn ($a): string => $a->id, $analitos);
            foreach (array_keys($input->valores) as $id) {
                if (! in_array($id, $permitidos, true)) {
                    throw ErroDeBancada::com('O parâmetro não pertence a este exame.');
                }
            }
            foreach ($analitos as $a) {
                if (! isset($input->valores[$a->id])) {
                    continue;
                }
                $v = $input->valores[$a->id];
                if ($v['valor'] !== null && strlen(explode('.', str_replace(',', '.', $v['valor']))[1] ?? '') > $a->casasDecimais) {
                    throw ErroDeBancada::com("Respeite as casas decimais de {$a->nome}.");
                }
                $this->resultados->save(Resultado::digitar($salvos[$a->id]->id ?? $this->resultados->nextIdentity(), $this->laboratorio->id(), $item->pedidoId, $item->id, $item->pacienteId, $a->id, $a->nome, $a->unidade, $v['valor'], $v['texto'], $input->por, $input->nome, new DateTimeImmutable('now', new DateTimeZone('UTC')), $a->faixa));
            }
        });
    }
}
