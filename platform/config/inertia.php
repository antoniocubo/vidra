<?php

declare(strict_types=1);

/*
| As páginas ficam em resources/js/pages (minúsculo). O padrão do Inertia é
| js/Pages: no macOS passa, no Linux do CI cada página "não existe".
| As outras chaves vêm do pacote (mergeConfigFrom).
*/
return [
    'page_paths' => [resource_path('js/pages')],

    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [resource_path('js/pages')],
        'page_extensions' => ['tsx', 'ts'],
    ],
];
