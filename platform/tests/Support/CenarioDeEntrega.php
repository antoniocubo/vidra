<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Vidra\Laudos\Infrastructure\Jobs\EntregarLaudo;
use Vidra\Shared\Infrastructure\Tenancy\ComLaboratorio;

final class CenarioDeEntrega
{
    /** @return array{lab:string,pedido:string,laudo:string,codigo:string,tutor:string,vinculo:string} */
    public static function liberar(TestCase $teste): array
    {
        Queue::fake();
        $lab = Laboratorios::criar();
        $usuario = Laboratorios::usuarioEm($lab);
        $c = CenarioDeBancada::criar($lab);
        DB::table('laboratorio_usuario')->where('user_id', $usuario->id)->update(['e_rt' => true]);
        DB::table('tutores')->where('id', $c['tutor'])->update(['email_autorizado_em' => now(), 'email_autorizado_por' => $usuario->id]);
        $teste->actingAs($usuario)->withSession(['laboratorio_id' => $lab]);
        $teste->post(route('lab.pedidos.store'), ['paciente_id' => $c['animal'], 'solicitante_id' => $c['vinculo'], 'pagador' => 'responsavel', 'prioridade' => 'rotina', 'exame_ids' => [$c['bioquimico']]])->assertSessionHasNoErrors();
        $pedido = DB::table('pedidos')->where('laboratorio_id', $lab)->value('id');
        $item = DB::table('pedido_itens')->where('pedido_id', $pedido)->value('id');
        $amostra = DB::table('amostras')->where('pedido_id', $pedido)->value('codigo');
        $teste->post(route('lab.amostras.receber'), ['codigo' => $amostra])->assertSessionHasNoErrors();
        $teste->post(route('lab.bancada.salvar', ['item' => $item]), ['valores' => [$c['analito'] => ['valor' => '4,50', 'texto' => null]]])->assertSessionHasNoErrors();
        $teste->post(route('lab.bancada.conferir', ['item' => $item]))->assertSessionHasNoErrors();
        $teste->post(route('lab.liberacao.liberar', ['pedido' => $pedido]), ['observacao' => 'Conferido pelo RT.'])->assertSessionHasNoErrors()->assertRedirect(route('lab.pedidos.show', ['pedido' => $pedido]));
        $l = DB::table('laudos')->where('pedido_id', $pedido)->first();

        return ['lab' => $lab, 'pedido' => $pedido, 'laudo' => $l->id, 'codigo' => $l->codigo_acesso, 'tutor' => $c['tutor'], 'vinculo' => $c['vinculo']];
    }

    public static function executar(EntregarLaudo $job): void
    {
        app(ComLaboratorio::class)->handle($job, fn ($j) => app()->call([$j, 'handle']));
    }
}
