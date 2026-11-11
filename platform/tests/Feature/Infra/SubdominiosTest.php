<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;

/** Recarrega as rotas como em produção, com um host por superfície. */
function comSubdominios(): void
{
    config(['vidra.dominios' => ['site' => 'vidra.test', 'app' => 'app.vidra.test', 'vet' => 'vet.vidra.test', 'laudo' => 'laudo.vidra.test']]);
    Route::setRoutes(new RouteCollection);
    Route::middleware('web')->group(base_path('routes/web.php'));
    Route::getRoutes()->refreshNameLookups();
}

it('cada superfície responde só no seu host', function (): void {
    comSubdominios();

    $this->get('http://vet.vidra.test/entrar')->assertOk();
    $this->get('http://app.vidra.test/entrar')->assertNotFound();
    $this->get('http://app.vidra.test/login')->assertOk();
    $this->get('http://vet.vidra.test/login')->assertNotFound();
    $this->get('http://laudo.vidra.test/')->assertOk();
    $this->get('http://vidra.test/')->assertOk();
    $this->get('http://app.vidra.test/')->assertRedirect('/dashboard');
});

it('gera links absolutos com o host certo e sem o prefixo local', function (): void {
    comSubdominios();

    expect(route('vet.entrar'))->toBe('http://vet.vidra.test/entrar')
        ->and(route('laudo.show', 'ABCD2345'))->toBe('http://laudo.vidra.test/ABCD2345')
        ->and(route('dashboard'))->toBe('http://app.vidra.test/dashboard')
        ->and(route('site.privacidade'))->toBe('http://vidra.test/privacidade');
});

it('manda o veterinário sem sessão para o login do host vet', function (): void {
    comSubdominios();

    $this->get('http://vet.vidra.test/')->assertRedirect('http://vet.vidra.test/entrar');
    $this->get('http://app.vidra.test/dashboard')->assertRedirect('http://app.vidra.test/login');
});
