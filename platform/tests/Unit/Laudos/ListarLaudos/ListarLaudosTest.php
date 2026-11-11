<?php

declare(strict_types=1);

use Tests\Support\LaboratorioAtualFixo;
use Vidra\Laudos\Application\ListarLaudos\LinhaDeLaudoDto;
use Vidra\Laudos\Application\ListarLaudos\ListarLaudos;
use Vidra\Laudos\Application\ListarLaudos\ListarLaudosInput;
use Vidra\Laudos\Domain\ConsultaDeLaudos;

it('converte o período de São Paulo para UTC com fim exclusivo', function (string $dia, string $inicio, string $fim): void {
    $consulta = Mockery::mock(ConsultaDeLaudos::class);
    $consulta->shouldReceive('pagina')->once()->withArgs(fn ($lab, $busca, $de, $ate, $entrega, $pagina): bool => $lab === 'lab' && $busca === 'Thor' && $de->format('Y-m-d H:i:s') === $inicio && $ate->format('Y-m-d H:i:s') === $fim && $de->getTimezone()->getName() === 'UTC' && $entrega === 'todas' && $pagina === 1)->andReturn([]);
    $lista = (new ListarLaudos($consulta, new LaboratorioAtualFixo('lab')))(new ListarLaudosInput(' Thor ', $dia, $dia));
    expect($lista->laudos)->toBe([])->and($lista->temProxima)->toBeFalse();
    Mockery::close();
})->with([
    ['2026-11-10', '2026-11-10 03:00:00', '2026-11-11 03:00:00'],
    ['2018-11-04', '2018-11-04 03:00:00', '2018-11-05 02:00:00'],
]);

it('limita a página a vinte e cinco laudos e sinaliza a próxima', function (): void {
    $linha = new LinhaDeLaudoDto('id', 'pedido', 'L-00001', 'P-00001', 'Thor', 'Rafael', '10/11/2026 12:00', 'Marina', false, false, false, false, false, 0, null);
    $consulta = Mockery::mock(ConsultaDeLaudos::class);
    $consulta->shouldReceive('pagina')->once()->with('lab', '', null, null, 'todas', 2)->andReturn(array_fill(0, 26, get_object_vars($linha)));
    $lista = (new ListarLaudos($consulta, new LaboratorioAtualFixo('lab')))(new ListarLaudosInput(pagina: 2));
    expect($lista->laudos)->toHaveCount(25)->and($lista->temProxima)->toBeTrue()->and($lista->pagina)->toBe(2);
    Mockery::close();
});
