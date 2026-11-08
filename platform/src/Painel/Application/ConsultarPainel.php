<?php

declare(strict_types=1);

namespace Vidra\Painel\Application;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Painel\Domain\LeituraDoPainel;
use Vidra\Pedidos\Application\DiaLocal;
use Vidra\Pedidos\Application\HoraDoLaboratorio;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class ConsultarPainel
{
    public function __construct(private LeituraDoPainel $leitura, private HoraDoLaboratorio $relogio, private LaboratorioAtual $laboratorio) {}

    /** @return array{data:string, atualizadoEm:string, saudacao:string, indicadores:array<string,int>, atencao:list<array{id:string,pedidoId:string,protocolo:int,tipo:string,detalhe:string,destino:string,criadoEm:string,prazo:int}>, andamento:list<array{id:string,protocolo:int,situacao:string}>} */
    public function __invoke(): array
    {
        $this->laboratorio->id();
        $agora = ($this->relogio)();
        $local = $agora->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $dia = DiaLocal::de(null, $agora);
        $atencao = $this->leitura->atencao($agora);
        foreach ($atencao as &$linha) {
            if ($linha['tipo'] === 'atrasado') {
                $abertura = new DateTimeImmutable($linha['criadoEm'], new DateTimeZone('UTC'));
                $segundos = $agora->getTimestamp() - $abertura->getTimestamp() - $linha['prazo'] * 3600;
                $linha['detalhe'] .= ' · Atrasado há '.($segundos >= 3600 ? (int) floor($segundos / 3600).' h' : max(1, (int) floor($segundos / 60)).' min');
            }
        }
        unset($linha);

        return [
            'data' => $dia->data,
            'atualizadoEm' => $local->format('d/m/Y H:i'),
            'saudacao' => match (true) {
                (int) $local->format('H') < 12 => 'Bom dia', (int) $local->format('H') < 18 => 'Boa tarde', default => 'Boa noite'
            },
            'indicadores' => $this->leitura->indicadores($dia->inicio, $dia->fim),
            'atencao' => $atencao,
            'andamento' => $this->leitura->andamento($dia->inicio, $dia->fim),
        ];
    }
}
