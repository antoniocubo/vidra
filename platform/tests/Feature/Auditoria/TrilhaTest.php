<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\CenarioDeEntrega;
use Tests\Support\CenarioDeFatura;
use Tests\Support\CenarioDePedidos;
use Tests\Support\Laboratorios;
use Vidra\Auditoria\Application\HistoricoDoPedido;
use Vidra\Auditoria\Infrastructure\Persistence\RegistroDeAuditoriaModel;
use Vidra\Financeiro\Infrastructure\Jobs\EnviarFaturaPorEmail;
use Vidra\Laudos\Infrastructure\Jobs\EntregarLaudo;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

beforeEach(function (): void {
    Queue::fake();
    Mail::fake();
    Storage::fake('laudos');
    Http::preventStrayRequests();
    Http::fake(fn () => Http::response('%PDF-1.7 auditoria', 200, ['Content-Type' => 'application/pdf']));
});

function conferirEventoAuditado(string $acao, string $detalhe): void
{
    $eventos = DB::table('registros_auditoria')->where('acao', $acao)->get();
    expect($eventos)->toHaveCount(1)->and($eventos[0]->detalhe)->toBe($detalhe);
}

it('registra uma vez abrir incluir cancelar exame e cancelar pedido e não registra erros', function (): void {
    $lab = Laboratorios::criar();
    $usuario = Laboratorios::usuarioEm($lab);
    $c = CenarioDePedidos::criar($lab);
    $this->actingAs($usuario)->withSession(['laboratorio_id' => $lab]);
    $this->post(route('lab.pedidos.store'), ['paciente_id' => $c['animal'], 'solicitante_id' => $c['vinculo'], 'pagador' => 'responsavel', 'prioridade' => 'rotina', 'exame_ids' => [$c['bioquimico']]])->assertSessionHasNoErrors();
    $pedido = DB::table('pedidos')->value('id');
    conferirEventoAuditado('pedido_aberto', 'Abriu o pedido P-00001');
    $this->post(route('lab.pedidos.itens.store', ['pedido' => $pedido]), ['exame_id' => $c['hemograma']])->assertSessionHasNoErrors();
    conferirEventoAuditado('exame_incluido', 'Incluiu Hemograma completo');
    $item = DB::table('pedido_itens')->where('exame_id', $c['hemograma'])->value('id');
    $url = route('lab.pedidos.itens.cancelar', ['pedido' => $pedido, 'item' => $item]);
    $this->post($url, ['motivo' => 'Veterinário desistiu'])->assertSessionHasNoErrors();
    conferirEventoAuditado('exame_cancelado', 'Cancelou Hemograma completo: Veterinário desistiu');
    $this->post($url, ['motivo' => 'Repetido'])->assertSessionHasErrors();
    conferirEventoAuditado('exame_cancelado', 'Cancelou Hemograma completo: Veterinário desistiu');
    $this->post(route('lab.pedidos.cancelar', ['pedido' => $pedido]), ['motivo' => 'Desistiu da coleta'])->assertSessionHasNoErrors();
    conferirEventoAuditado('pedido_cancelado', 'Cancelou o pedido: Desistiu da coleta');
    $this->get(route('lab.pedidos.show', ['pedido' => $pedido]))->assertInertia(fn (Assert $p) => $p
        ->has('historico', 4)->where('historico.0.detalhe', 'Cancelou o pedido: Desistiu da coleta')->where('historico.0.quem', $usuario->name));
    $beta = Laboratorios::criar('Beta');
    app(ContextoLaboratorio::class)->executarComo($beta, function () use ($pedido): void {
        expect(app(HistoricoDoPedido::class)($pedido))->toBe([])
            ->and(RegistroDeAuditoriaModel::query()->count())->toBe(0);
    });
    $this->actingAs(Laboratorios::usuarioEm($beta))->withSession(['laboratorio_id' => $beta])
        ->get(route('lab.pedidos.show', ['pedido' => $pedido]))->assertNotFound();
});

it('registra digitação liberação reabertura retificação download reenvio e abertura pública', function (): void {
    $c = CenarioDeEntrega::liberar($this);
    conferirEventoAuditado('resultado_digitado', 'Digitou os resultados do exame');
    conferirEventoAuditado('laudo_liberado', 'Liberou o laudo');
    CenarioDeEntrega::executar(Queue::pushed(EntregarLaudo::class)->first());
    $this->get(route('lab.pedidos.laudo.pdf', ['pedido' => $c['pedido']]))->assertOk();
    conferirEventoAuditado('laudo_baixado', 'Baixou o laudo');
    $this->post(route('lab.pedidos.laudo.reenviar', ['pedido' => $c['pedido']]))->assertSessionHasNoErrors();
    conferirEventoAuditado('laudo_reenviado', 'Reenviou o laudo');
    $url = route('lab.pedidos.laudo.retificar', ['pedido' => $c['pedido']]);
    $this->post($url, ['motivo' => 'Correção do potássio'])->assertSessionHasNoErrors();
    conferirEventoAuditado('laudo_reaberto', 'Reabriu o laudo: Correção do potássio');
    $this->post($url, ['motivo' => 'Repetido'])->assertSessionHasErrors();
    conferirEventoAuditado('laudo_reaberto', 'Reabriu o laudo: Correção do potássio');
    $this->post(route('lab.liberacao.liberar', ['pedido' => $c['pedido']]))->assertSessionHasNoErrors();
    conferirEventoAuditado('laudo_retificado', 'Retificou o laudo · versão 2');
    conferirEventoAuditado('laudo_liberado', 'Liberou o laudo');
    // Mesmo com uma sessão web, o link é atribuído ao tutor, nunca ao laboratório.
    $this->get('/laudo/'.$c['codigo'])->assertOk();
    conferirEventoAuditado('laudo_aberto_pelo_tutor', 'Tutor abriu pelo link');
    $evento = DB::table('registros_auditoria')->where('acao', 'laudo_aberto_pelo_tutor')->first();
    expect($evento->usuario_id)->toBeNull()->and($evento->pedido_id)->toBe($c['pedido'])->and($evento->laboratorio_id)->toBe($c['lab']);
});

it('registra convite aceite troca de papel e remoção sem duplicar erros', function (): void {
    $lab = Laboratorios::criar();
    $admin = Laboratorios::usuarioEm($lab);
    $this->actingAs($admin)->withSession(['laboratorio_id' => $lab]);
    $this->post('/equipe/convites', ['nome' => 'Ana Souza', 'email' => 'ana@example.com', 'papel' => 'bancada'])->assertSessionHasNoErrors();
    conferirEventoAuditado('pessoa_convidada', 'Convidou Ana Souza (ana@example.com)');
    $token = basename(Mail::sent(Vidra\Acesso\Infrastructure\Mail\ConviteMail::class)->first()->link);
    auth()->logout();
    app(ContextoLaboratorio::class)->limpar();
    $aceito = app(Vidra\Acesso\Application\AceitarConvite::class)(new Vidra\Acesso\Application\AceitarConviteInput($token, 'Ana Souza', 'senha-segura-123', null, null));
    conferirEventoAuditado('convite_aceito', 'Aceitou o convite para a equipe');
    expect(app(ContextoLaboratorio::class)->definido())->toBeFalse();
    $this->actingAs($admin)->withSession(['laboratorio_id' => $lab]);
    $this->patch(route('lab.equipe.papel', ['usuario' => $aceito->usuarioId]), ['papel' => 'recepcao'])->assertSessionHasNoErrors();
    conferirEventoAuditado('papel_trocado', 'Trocou o papel de Ana Souza para Recepção');
    $this->delete(route('lab.equipe.remover', ['usuario' => $aceito->usuarioId]))->assertSessionHasNoErrors();
    conferirEventoAuditado('pessoa_removida', 'Removeu Ana Souza da equipe');
    $this->get('/equipe')->assertInertia(fn (Assert $p) => $p->has('mudancas', 4)->where('mudancas.0.detalhe', 'Removeu Ana Souza da equipe'));
});

it('registra envio pagamento e crédito e não registra pagamento recusado', function (): void {
    $lab = Laboratorios::criar();
    $admin = Laboratorios::usuarioEm($lab);
    $c = CenarioDePedidos::criar($lab);
    DB::table('clinicas')->where('id', $c['clinica'])->update(['dia_fechamento' => 5]);
    CenarioDeFatura::pedido($lab, $c, 1);
    $this->artisan('vidra:fechar-faturas', ['--dia' => '2026-12-05'])->assertSuccessful();
    $id = DB::table('faturas')->value('id');
    $this->actingAs($admin)->withSession(['laboratorio_id' => $lab]);
    $this->post(route('lab.financeiro.pagamento', ['fatura' => $id]))->assertSessionHasErrors();
    expect(DB::table('registros_auditoria')->count())->toBe(0);
    CenarioDeFatura::enviar(Queue::pushed(EnviarFaturaPorEmail::class)->first());
    conferirEventoAuditado('fatura_enviada', 'Enviou a fatura');
    $this->post(route('lab.financeiro.credito', ['fatura' => $id]), ['valor' => '10,00', 'motivo' => 'Exame repetido'])->assertSessionHasNoErrors();
    conferirEventoAuditado('credito_concedido', 'Concedeu crédito: Exame repetido');
    $this->post(route('lab.financeiro.pagamento', ['fatura' => $id]))->assertSessionHasNoErrors();
    conferirEventoAuditado('pagamento_registrado', 'Registrou o pagamento da fatura');
    $this->post(route('lab.financeiro.pagamento', ['fatura' => $id]))->assertSessionHasErrors();
    conferirEventoAuditado('pagamento_registrado', 'Registrou o pagamento da fatura');
});

it('lista somente as vinte mudanças mais recentes da equipe do laboratório atual', function (): void {
    $alfa = Laboratorios::criar();
    $beta = Laboratorios::criar('Beta');
    $contexto = app(ContextoLaboratorio::class);
    foreach ([$alfa, $beta] as $lab) {
        $contexto->executarComo($lab, function (): void {
            for ($i = 1; $i <= 22; $i++) {
                app(Vidra\Auditoria\Application\RegistrarNaTrilha::class)('pessoa_convidada', 'equipe', (string) $i, 'Convidou pessoa '.$i);
            }
        });
    }
    $contexto->executarComo($alfa, function (): void {
        $eventos = app(Vidra\Auditoria\Application\UltimasMudancasDaEquipe::class)();
        expect($eventos)->toHaveCount(20)
            ->and($eventos[0]['detalhe'])->toBe('Convidou pessoa 22')
            ->and($eventos[19]['detalhe'])->toBe('Convidou pessoa 3');
    });
});

it('registra abertura anônima sem sessão e restaura o contexto indefinido', function (): void {
    $c = CenarioDeEntrega::liberar($this);
    auth()->logout();
    app(ContextoLaboratorio::class)->limpar();
    $this->get('/laudo/'.$c['codigo'])->assertOk();
    $evento = DB::table('registros_auditoria')->where('acao', 'laudo_aberto_pelo_tutor')->sole();
    expect($evento->usuario_id)->toBeNull()->and($evento->usuario_nome)->toBe('Tutor')
        ->and($evento->detalhe)->toBe('Tutor abriu pelo link')
        ->and(app(ContextoLaboratorio::class)->definido())->toBeFalse();
    $this->get('/laudo/ZZZZZZZZ')->assertNotFound();
    conferirEventoAuditado('laudo_aberto_pelo_tutor', 'Tutor abriu pelo link');
});
