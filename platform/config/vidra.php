<?php

declare(strict_types=1);

return [

    /*
    | Endereços das superfícies do Vidra (cada uma num subdomínio).
    */
    /*
    | Host de cada superfície em produção (app., vet., laudo. e o site). Vazio
    | (local e testes): tudo responde no mesmo host, com /vet e /laudo de prefixo.
    */
    'dominios' => [
        'site' => env('VIDRA_DOMINIO_SITE'),
        'app' => env('VIDRA_DOMINIO_APP'),
        'vet' => env('VIDRA_DOMINIO_VET'),
        'laudo' => env('VIDRA_DOMINIO_LAUDO'),
    ],

    'urls' => [
        'laudo' => env('VIDRA_URL_LAUDO', 'https://laudo.vidralab.com.br'),
        'vet' => env('VIDRA_URL_VET', 'https://vet.vidralab.com.br'),
    ],

    /*
    | Conexão que enxerga todos os laboratórios (ignora o RLS). Só para o que é
    | de fato entre laboratórios: o mapa de acessos, o link do tutor, a fatura
    | mensal. No Postgres é a pgsql_sistema; no SQLite, a conexão padrão.
    */
    'conexao_sistema' => env('DB_SISTEMA_CONNECTION', env('DB_CONNECTION', 'sqlite')),

];
