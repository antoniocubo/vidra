<?php

declare(strict_types=1);

/*
| Regras da clean architecture. Se um teste daqui falhar, a dependência está
| apontando para o lado errado: mova o código, não afrouxe a regra.
*/

arch('o domínio não conhece framework nem infraestrutura')
    ->expect('Vidra\\*\\Domain')
    ->not->toUse(['Illuminate', 'Inertia', 'App', 'Vidra\\*\\Application', 'Vidra\\*\\Infrastructure']);

arch('o domínio compartilhado é puro')
    ->expect('Vidra\\Shared\\Domain')
    ->not->toUse(['Illuminate', 'Inertia', 'App']);

arch('a aplicação não conhece framework nem infraestrutura')
    ->expect('Vidra\\*\\Application')
    ->not->toUse(['Illuminate', 'Inertia', 'App', 'Vidra\\*\\Infrastructure']);

arch('o código de src usa tipos estritos')
    ->expect('Vidra')
    ->toUseStrictTypes();

arch('casos de uso são classes finais e imutáveis')
    ->expect('Vidra\\*\\Application')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('adaptadores de infraestrutura são classes finais')
    ->expect('Vidra\\*\\Infrastructure')
    ->classes()
    ->toBeFinal();

arch('repositórios do domínio são só contratos')
    ->expect('Vidra\\Cadastros\\Domain\\Veterinario\\VeterinarioRepository')
    ->toBeInterface();

arch('controllers não usam Eloquent direto')
    ->expect('App\\Http\\Controllers\\Lab')
    ->not->toUse(['Illuminate\\Database', 'Vidra\\*\\Infrastructure']);

arch('sem funções de depuração esquecidas')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();
