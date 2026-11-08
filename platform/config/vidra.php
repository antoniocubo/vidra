<?php

declare(strict_types=1);

return [

    /*
    | Endereços das superfícies do Vidra (cada uma num subdomínio).
    */
    'urls' => [
        'vet' => env('VIDRA_URL_VET', 'https://vet.vidralab.com.br'),
    ],

    /*
    | Conexão que enxerga todos os laboratórios (ignora o RLS). Só para o que é
    | de fato entre laboratórios: o mapa de acessos, o link do tutor, a fatura
    | mensal. No Postgres é a pgsql_sistema; no SQLite, a conexão padrão.
    */
    'conexao_sistema' => env('DB_SISTEMA_CONNECTION', env('DB_CONNECTION', 'sqlite')),

];
