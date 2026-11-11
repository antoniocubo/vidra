<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

it('abre a política de privacidade sem login', function (): void {
    $this->get(route('site.privacidade'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('site/privacidade'));
});

it('abre os termos de uso sem login', function (): void {
    $this->get(route('site.termos'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('site/termos'));
});
