<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Support\Laboratorios;
use Tests\Support\Veterinarios;

it('reseta pelo broker do vet sem mudar a senha da equipe com o mesmo email', function (): void {
    Notification::fake();
    $vet = Veterinarios::comContaEm([Laboratorios::criar()]);
    $equipe = User::factory()->create(['email' => $vet->email]);
    $antes = $equipe->getAuthPassword();
    $this->post(route('vet.senha.enviar'), ['email' => $vet->email])->assertSessionHasNoErrors();
    $token = '';
    Notification::assertSentTo($vet, ResetPassword::class, function (ResetPassword $n) use (&$token, $vet): bool {
        $token = $n->token;
        expect($n->toMail($vet)->actionUrl)->toBe(route('vet.senha.nova', ['token' => $token, 'email' => $vet->email]));

        return true;
    });
    $this->assertDatabaseCount('password_reset_tokens', 0);
    $this->get(route('vet.senha.nova', ['token' => $token, 'email' => $vet->email]))->assertOk();
    $this->post(route('vet.senha.salvar', $token), ['email' => $vet->email, 'senha' => 'nova-senha-do-vet', 'senha_confirmation' => 'nova-senha-do-vet'])->assertRedirect(route('vet.entrar'));
    expect(Hash::check('nova-senha-do-vet', $vet->fresh()->getAuthPassword()))->toBeTrue()->and($equipe->fresh()->getAuthPassword())->toBe($antes);
    $this->assertDatabaseCount('password_reset_tokens_vet', 0);
    $this->post(route('vet.senha.salvar', $token), ['email' => $vet->email, 'senha' => 'outra-senha-do-vet', 'senha_confirmation' => 'outra-senha-do-vet'])->assertSessionHasErrors('email');
});

it('não revela email desconhecido e não aceita token do broker da equipe', function (): void {
    Notification::fake();
    $vet = Veterinarios::comContaEm([Laboratorios::criar()]);
    $equipe = User::factory()->create(['email' => $vet->email]);
    $token = Password::broker('users')->createToken($equipe);
    $this->post(route('vet.senha.enviar'), ['email' => 'nao-existe@exemplo.com'])->assertSessionHas('status');
    Notification::assertNothingSent();
    $this->post(route('vet.senha.salvar', $token), ['email' => $vet->email, 'senha' => 'nova-senha-do-vet', 'senha_confirmation' => 'nova-senha-do-vet'])->assertSessionHasErrors('email');
    expect(Hash::check(Veterinarios::SENHA, $vet->fresh()->getAuthPassword()))->toBeTrue();
});
