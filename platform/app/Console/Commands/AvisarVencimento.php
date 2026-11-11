<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use Vidra\Acesso\Application\Assinatura\AvisarVencimento as Avisar;

final class AvisarVencimento extends Command
{
    protected $signature = 'vidra:avisar-vencimento';

    protected $description = 'Avisa por e-mail os administradores de laboratórios com teste ou pagamento perto de vencer';

    public function handle(Avisar $avisar): int
    {
        $falhou = false;
        foreach (DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorios')->pluck('id') as $id) {
            try {
                if ($avisar((string) $id)) {
                    $this->line("Laboratório {$id}: aviso enviado.");
                }
            } catch (Throwable $e) {
                $falhou = true;
                report($e);
                $this->error("Laboratório {$id}: {$e->getMessage()}");
            }
        }

        return $falhou ? self::FAILURE : self::SUCCESS;
    }
}
