<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\Laboratorios;
use Vidra\Acesso\Infrastructure\Mail\ConviteMail;

beforeEach(function (): void {
    Mail::fake();
    $this->lab = Laboratorios::criar();
    $this->admin = Laboratorios::usuarioEm($this->lab);
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);
});

it('convida uma pessoa com token seguro e envia o e-mail em português', function (): void {
    $this->post('/equipe/convites', ['nome' => 'Ana Souza', 'email' => 'ANA@example.com', 'papel' => 'rt'])->assertRedirect('/equipe');
    $convite = DB::table('convites')->first();
    expect($convite->email)->toBe('ana@example.com')->and($convite->papel)->toBe('rt')
        ->and($convite->created_at)->not->toBeNull()->and($convite->aceito_em)->toBeNull();
    Mail::assertSent(ConviteMail::class, function (ConviteMail $mail) use ($convite): bool {
        $token = basename($mail->link);
        expect(strlen($token))->toBe(48)->and(hash('sha256', $token))->toBe($convite->token_hash)
            ->and($mail->envelope()->subject)->toBe($this->admin->name.' convidou você para o Laboratório Alfa no Vidra');
        $mail->assertSeeInHtml('Responsável técnico');
        $mail->assertSeeInHtml('Aceitar convite');

        return $mail->hasTo('ana@example.com');
    });
});

it('não convida quem já pertence ao laboratório', function (): void {
    $this->post('/equipe/convites', ['nome' => 'Outra pessoa', 'email' => strtoupper($this->admin->email), 'papel' => 'bancada'])->assertSessionHasErrors('form');
    $this->assertDatabaseCount('convites', 0);
    Mail::assertNothingSent();
});

it('substitui o convite pendente do mesmo e-mail e invalida o link anterior', function (): void {
    $dados = ['nome' => 'Ana Souza', 'email' => 'ana@example.com', 'papel' => 'rt'];
    $this->post('/equipe/convites', $dados)->assertRedirect();
    $anterior = DB::table('convites')->first();
    $this->post('/equipe/convites', [...$dados, 'papel' => 'bancada'])->assertRedirect();
    $this->assertDatabaseCount('convites', 2);
    expect(DB::table('convites')->where('id', $anterior->id)->value('cancelado_em'))->not->toBeNull();
    expect(DB::table('convites')->whereNull('cancelado_em')->value('papel'))->toBe('bancada');
    Mail::assertSentCount(2);
});

it('lista só membros e convites do laboratório escolhido', function (): void {
    $beta = Laboratorios::criar('Laboratório Beta');
    $outro = Laboratorios::usuarioEm($beta);
    $this->post('/equipe/convites', ['nome' => 'Ana Alfa', 'email' => 'alfa@example.com', 'papel' => 'rt']);
    $this->actingAs($outro)->withSession(['laboratorio_id' => $beta])
        ->post('/equipe/convites', ['nome' => 'Ana Beta', 'email' => 'beta@example.com', 'papel' => 'rt']);
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab])->get('/equipe')->assertInertia(fn (Assert $page) => $page
        ->component('lab/equipe/index')->has('membros', 1)->where('membros.0.id', $this->admin->id)
        ->has('convites', 1)->where('convites.0.email', 'alfa@example.com')->missing('convites.0.tokenHash')->missing('convites.0.token_hash'));
});

it('nega a gestão da equipe a recepção bancada e responsável técnico', function (string $papel): void {
    $usuario = Laboratorios::usuarioEm($this->lab, $papel);
    $this->actingAs($usuario)->get('/equipe')->assertForbidden();
    $this->post('/equipe/convites', ['nome' => 'Ana Souza', 'email' => 'ana@example.com', 'papel' => 'admin'])->assertForbidden();
    $this->patch('/equipe/'.$this->admin->id.'/papel', ['papel' => 'recepcao'])->assertForbidden();
    $this->delete('/equipe/'.$this->admin->id)->assertForbidden();
})->with(['recepcao', 'bancada', 'rt']);

it('não remove o último administrador', function (): void {
    $this->delete('/equipe/'.$this->admin->id)->assertSessionHasErrors(['form' => 'O laboratório precisa ter pelo menos um administrador.']);
    $this->assertDatabaseHas('laboratorio_usuario', ['laboratorio_id' => $this->lab, 'user_id' => $this->admin->id, 'papel' => 'admin']);
});

it('não troca o próprio papel mesmo havendo outro administrador', function (): void {
    Laboratorios::usuarioEm($this->lab);
    $this->patch('/equipe/'.$this->admin->id.'/papel', ['papel' => 'rt'])->assertSessionHasErrors(['form' => 'Você não pode trocar seu próprio papel nem remover a si mesmo.']);
});

it('não remove a si mesmo mesmo havendo outro administrador', function (): void {
    Laboratorios::usuarioEm($this->lab);
    $this->delete('/equipe/'.$this->admin->id)->assertSessionHasErrors('form');
});

it('troca papéis e mantém o RT ao promover para administrador', function (): void {
    $membro = Laboratorios::usuarioEm($this->lab, 'recepcao');
    foreach ([['rt', true], ['admin', true], ['bancada', false], ['admin', false]] as [$papel, $eRt]) {
        $this->patch('/equipe/'.$membro->id.'/papel', ['papel' => $papel])->assertRedirect('/equipe');
        $this->assertDatabaseHas('laboratorio_usuario', ['laboratorio_id' => $this->lab, 'user_id' => $membro->id, 'papel' => $papel, 'e_rt' => $eRt]);
    }
});

it('remove apenas o vínculo com o laboratório atual', function (): void {
    $membro = Laboratorios::usuarioEm($this->lab, 'bancada');
    $beta = Laboratorios::criar('Beta');
    Laboratorios::usuarioEm($beta, 'bancada', $membro);
    $this->delete('/equipe/'.$membro->id)->assertRedirect('/equipe');
    $this->assertDatabaseMissing('laboratorio_usuario', ['laboratorio_id' => $this->lab, 'user_id' => $membro->id]);
    $this->assertDatabaseHas('laboratorio_usuario', ['laboratorio_id' => $beta, 'user_id' => $membro->id]);
    $this->assertDatabaseHas('users', ['id' => $membro->id]);
});

it('não altera membro nem convite de outro laboratório', function (): void {
    $beta = Laboratorios::criar('Beta');
    $outro = Laboratorios::usuarioEm($beta);
    $this->actingAs($outro)->withSession(['laboratorio_id' => $beta])->post('/equipe/convites', ['nome' => 'Ana Beta', 'email' => 'beta@example.com', 'papel' => 'rt']);
    $id = DB::table('convites')->value('id');
    $this->actingAs($this->admin)->withSession(['laboratorio_id' => $this->lab]);
    $this->post('/equipe/convites/'.$id.'/reenviar')->assertSessionHasErrors('form');
    $this->delete('/equipe/convites/'.$id)->assertSessionHasErrors('form');
    $this->patch('/equipe/'.$outro->id.'/papel', ['papel' => 'bancada'])->assertSessionHasErrors('form');
    $this->delete('/equipe/'.$outro->id)->assertSessionHasErrors('form');
    $this->assertDatabaseHas('laboratorio_usuario', ['laboratorio_id' => $beta, 'user_id' => $outro->id, 'papel' => 'admin']);
    expect(DB::table('convites')->where('id', $id)->value('cancelado_em'))->toBeNull();
});

it('reenvia convite expirado com token novo e sete dias de validade', function (): void {
    $this->post('/equipe/convites', ['nome' => 'Ana Souza', 'email' => 'ana@example.com', 'papel' => 'rt']);
    $anterior = DB::table('convites')->first();
    $this->travel(8)->days();
    $this->post('/equipe/convites/'.$anterior->id.'/reenviar')->assertRedirect('/equipe');
    $novo = DB::table('convites')->whereNull('cancelado_em')->first();
    expect($novo->token_hash)->not->toBe($anterior->token_hash)
        ->and(Carbon\CarbonImmutable::parse($novo->expira_em)->diffInSeconds(now()->addDays(7), true))->toBeLessThan(2);
    Mail::assertSentCount(2);
});

it('cancela o convite sem enviar outro e-mail', function (): void {
    $this->post('/equipe/convites', ['nome' => 'Ana Souza', 'email' => 'ana@example.com', 'papel' => 'rt']);
    $id = DB::table('convites')->value('id');
    $this->delete('/equipe/convites/'.$id)->assertRedirect('/equipe');
    expect(DB::table('convites')->value('cancelado_em'))->not->toBeNull();
    $this->get('/equipe')->assertInertia(fn (Assert $page) => $page->has('convites', 0));
    Mail::assertSentCount(1);
});

it('valida nome e-mail e papel antes de convidar', function (): void {
    $this->post('/equipe/convites', ['nome' => 'A', 'email' => 'invalido', 'papel' => 'dono'])->assertSessionHasErrors(['nome', 'email', 'papel']);
    $this->assertDatabaseCount('convites', 0);
});
