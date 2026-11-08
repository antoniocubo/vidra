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

arch('o núcleo não conhece nenhuma linha de produto')
    // Ao criar um módulo do núcleo (Pedidos, Amostras, Bancada, Laudos...), inclua-o aqui.
    ->expect(['Vidra\\Shared', 'Vidra\\Acesso', 'Vidra\\Catalogo'])
    ->not->toUse(['Vidra\\Veterinaria', 'Vidra\\Humana']);

arch('uma linha não usa a outra')
    ->expect('Vidra\\Veterinaria')
    ->not->toUse('Vidra\\Humana');

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
    ->expect(['Vidra\\Veterinaria\\Domain\\Veterinario\\VeterinarioRepository', 'Vidra\\Acesso\\Domain\\LaboratorioRepository', 'Vidra\\Veterinaria\\Domain\\Vinculo\\VinculoRepository'])
    ->toBeInterface();

arch('controllers não usam Eloquent direto')
    ->expect(['App\\Http\\Controllers\\Lab', 'App\\Http\\Controllers\\Acesso'])
    ->not->toUse(['Illuminate\\Database', 'Vidra\\*\\Infrastructure']);

arch('sem funções de depuração esquecidas')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();
