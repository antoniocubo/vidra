<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\Laboratorios;

it('entra direto no painel quando há um laboratório e guarda a sessão', function (): void {
    $lab = Laboratorios::criar();
    $this->actingAs(Laboratorios::usuarioEm($lab))->get('/dashboard')->assertOk()->assertSessionHas('laboratorio_id', $lab);
});

it('pede a escolha e lista os laboratórios em ordem alfabética', function (): void {
    $beta = Laboratorios::criar('Beta');
    $alfa = Laboratorios::criar('Alfa');
    $usuario = Laboratorios::usuarioEm($beta);
    Laboratorios::usuarioEm($alfa, 'rt', $usuario);
    $this->actingAs($usuario)->get('/dashboard')->assertRedirect(route('laboratorio.escolher'));
    $this->get(route('laboratorio.escolher'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('acesso/escolher-laboratorio')->has('laboratorios', 2)
        ->where('laboratorios.0', ['id' => $alfa, 'nome' => 'Alfa', 'papel' => 'rt'])
        ->where('laboratorios.1', ['id' => $beta, 'nome' => 'Beta', 'papel' => 'admin']));
});

it('entra no laboratório escolhido e permite trocar pelo outro', function (): void {
    $alfa = Laboratorios::criar('Alfa');
    $beta = Laboratorios::criar('Beta');
    $usuario = Laboratorios::usuarioEm($alfa);
    Laboratorios::usuarioEm($beta, 'bancada', $usuario);
    foreach ([$alfa, $beta] as $lab) {
        $this->actingAs($usuario)->post(route('laboratorio.entrar'), ['laboratorio_id' => $lab])
            ->assertRedirect(route('dashboard'))->assertSessionHas('laboratorio_id', $lab);
    }
});

it('recusa laboratório de outra conta', function (): void {
    $lab = Laboratorios::criar();
    $outro = Laboratorios::criar('Outro');
    $this->actingAs(Laboratorios::usuarioEm($lab))->withSession(['laboratorio_id' => $lab])
        ->post(route('laboratorio.entrar'), ['laboratorio_id' => $outro])
        ->assertSessionHasErrors('laboratorio_id')->assertSessionHas('laboratorio_id', $lab);
});

it('ignora acesso revogado e entra no único laboratório restante', function (): void {
    $antigo = Laboratorios::criar('Antigo');
    $atual = Laboratorios::criar('Atual');
    $usuario = Laboratorios::usuarioEm($antigo);
    Laboratorios::usuarioEm($atual, 'admin', $usuario);
    DB::table('laboratorio_usuario')->where('laboratorio_id', $antigo)->where('user_id', $usuario->id)->delete();
    $this->actingAs($usuario)->withSession(['laboratorio_id' => $antigo])->get('/dashboard')
        ->assertOk()->assertSessionHas('laboratorio_id', $atual);
});

it('nega o painel quando o último vínculo foi removido', function (): void {
    $lab = Laboratorios::criar();
    $usuario = Laboratorios::usuarioEm($lab);
    DB::table('laboratorio_usuario')->where('user_id', $usuario->id)->delete();
    $this->actingAs($usuario)->withSession(['laboratorio_id' => $lab])->get('/dashboard')->assertForbidden();
});

it('compartilha o laboratório atual e todos os acessos no painel', function (): void {
    $alfa = Laboratorios::criar('Alfa');
    $beta = Laboratorios::criar('Beta');
    $usuario = Laboratorios::usuarioEm($alfa, 'recepcao');
    Laboratorios::usuarioEm($beta, 'rt', $usuario);
    $this->actingAs($usuario)->withSession(['laboratorio_id' => $beta])->get('/dashboard')
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('dashboard')
        ->where('laboratorio', ['id' => $beta, 'nome' => 'Beta', 'papel' => 'rt', 'eRt' => false, 'areas' => Vidra\Acesso\Domain\Papel::Rt->areas()])
        ->has('laboratorios', 2)->where('laboratorios.0', ['id' => $alfa, 'nome' => 'Alfa', 'papel' => 'recepcao', 'eRt' => false, 'areas' => Vidra\Acesso\Domain\Papel::Recepcao->areas()])
        ->where('laboratorios.1', ['id' => $beta, 'nome' => 'Beta', 'papel' => 'rt', 'eRt' => false, 'areas' => Vidra\Acesso\Domain\Papel::Rt->areas()]));
});
