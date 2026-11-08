<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Tests\Support\Laboratorios;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    app()->setLocale('pt_BR');
});

it('devolve a validação do formulário em português com o nome do campo', function (): void {
    actingAs(Laboratorios::usuario())->post(route('lab.veterinarios.store'), [])
        ->assertSessionHasErrors([
            'celular' => 'Preencha o campo celular.',
            'crmv_numero' => 'Preencha o campo CRMV.',
            'email' => 'Preencha o campo e-mail.',
        ]);
});

it('troca o nome do campo dentro de listas pelo que o laboratório fala', function (): void {
    $erros = Validator::make(['animais' => [['anos' => 'dois']]], ['animais.*.anos' => ['integer']])->errors();

    expect($erros->first('animais.0.anos'))->toBe('O campo idade em anos deve ser um número inteiro.');
});

it('tem em português as mensagens de e-mail, tamanho e opção inválida', function (): void {
    $erros = Validator::make(
        ['email' => 'x', 'nome' => str_repeat('a', 300), 'quem_paga' => 'outro'],
        ['email' => 'email', 'nome' => 'max:255', 'quem_paga' => 'in:clinica,veterinario,tutor'],
    )->errors();

    expect($erros->first('email'))->toBe('Use um e-mail válido.')
        ->and($erros->first('nome'))->toBe('O campo nome deve ter no máximo 255 caracteres.')
        ->and($erros->first('quem_paga'))->toBe('A opção escolhida em quem paga é inválida.');
});

it('traduz as mensagens de login, senha e paginação', function (): void {
    expect(__('auth.failed'))->toBe('E-mail ou senha incorretos.')
        ->and(__('passwords.sent'))->toBe('Enviamos por e-mail o link para redefinir a senha.')
        ->and(__('pagination.next'))->toBe('Próxima &raquo;');
});

it('tem em português todas as chaves do validation.php padrão do Laravel', function (): void {
    $padrao = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
    $nosso = require base_path('lang/pt_BR/validation.php');

    $faltando = array_diff_key($padrao, $nosso);
    expect($faltando)->toBe([]);
    foreach ($padrao as $chave => $valor) {
        if (is_array($valor) && ! in_array($chave, ['custom', 'attributes'], true)) {
            expect(array_diff_key($valor, $nosso[$chave]))->toBe([], "subchaves de {$chave}");
        }
    }
});
