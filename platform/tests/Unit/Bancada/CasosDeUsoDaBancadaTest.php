<?php

declare(strict_types=1);

use Tests\Support\InMemoryResultadoBancadaRepository;
use Tests\Support\LaboratorioAtualFixo;
use Tests\Support\PortasDeBancadaEmMemoria;
use Tests\Support\TransacaoEmMemoria;
use Vidra\Bancada\Application\DigitarResultados;
use Vidra\Bancada\Application\DigitarResultadosInput;
use Vidra\Bancada\Application\EnviarParaConferencia;
use Vidra\Bancada\Application\MapaDeTrabalho;
use Vidra\Bancada\Application\ObterTrabalho;
use Vidra\Bancada\Application\ReabrirItem;
use Vidra\Bancada\Domain\Analito;
use Vidra\Bancada\Domain\Classificacao;
use Vidra\Bancada\Domain\Decimal;
use Vidra\Bancada\Domain\Faixa;
use Vidra\Bancada\Domain\Trabalho;

function trabalhoUnit(string $id = 'item', bool $urgente = false, ?string $chegada = '2026-11-08T12:00:00Z', string $setor = 'hematologia'): Trabalho
{
    return new Trabalho($id, 'pedido', 'lab', 'exame', 'Hemograma', $setor, 'em_analise', 'paciente', 'Thor', 'Descrição', 'Paciente', 'perfil', 'P-00001', $urgente, 'A-0001-1', $chegada, 'Dr. Rafael', '1932410099');
}
it('digita atualiza envia e reabre usando portas em memória sem banco', function (): void {
    $portas = new PortasDeBancadaEmMemoria([trabalhoUnit()], [new Analito('analito', 'Parâmetro', 'g/L', 2, new Faixa('perfil', new Decimal('3'), new Decimal('5')))]);
    $repo = new InMemoryResultadoBancadaRepository;
    $obter = new ObterTrabalho($portas);
    $tx = new TransacaoEmMemoria;
    $digitar = new DigitarResultados($obter, $portas, $portas, $repo, new LaboratorioAtualFixo('lab'), $tx, Tests\Support\AuditoriaFixa::registrar());
    $digitar(new DigitarResultadosInput('item', ['analito' => ['valor' => '6,00', 'texto' => null]], 1, 'Bruno'));
    expect($repo->doItem('item')[0]->classificacao)->toBe(Classificacao::Alto)->and($portas->todos()[0]->situacao)->toBe('em_analise');
    $digitar(new DigitarResultadosInput('item', ['analito' => ['valor' => '4', 'texto' => null]], 2, 'Marina'));
    expect($repo->resultados)->toHaveCount(1)->and($repo->doItem('item')[0]->digitadoPor)->toBe(2);
    (new EnviarParaConferencia($obter, $portas, $portas, $repo, $tx))('item');
    expect($portas->todos()[0]->situacao)->toBe('resultado_digitado');
    (new ReabrirItem($obter, $portas, $tx))('item');
    expect($portas->todos()[0]->situacao)->toBe('em_analise');
});
it('ordena urgentes antes da chegada e conta por setor somente amostras recebidas', function (): void {
    $portas = new PortasDeBancadaEmMemoria([trabalhoUnit('rotina'), trabalhoUnit('urgente', true, '2026-11-08T13:00:00Z'), trabalhoUnit('cedo', false, '2026-11-08T11:00:00Z'), trabalhoUnit('sem-amostra', true, null), trabalhoUnit('outro', false, '2026-11-08T11:00:00Z', 'bioquimica')], []);
    $mapa = (new MapaDeTrabalho($portas))('hematologia');
    expect(array_map(fn ($i) => $i->id, $mapa['itens']))->toBe(['urgente', 'cedo', 'rotina'])->and($mapa['setores']['hematologia'])->toBe(3)->and($mapa['setores']['bioquimica'])->toBe(1);
});
