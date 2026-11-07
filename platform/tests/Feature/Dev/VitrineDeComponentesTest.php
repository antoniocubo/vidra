<?php

declare(strict_types=1);

it('mostra a vitrine de componentes fora de produção', function (): void {
    $this->get(route('dev.componentes'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dev/componentes'));
});
