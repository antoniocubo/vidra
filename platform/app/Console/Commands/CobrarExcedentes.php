<?php

declare(strict_types=1);

namespace App\Console\Commands;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use Vidra\Acesso\Application\Assinatura\CobrarExcedentes as Cobrar;

final class CobrarExcedentes extends Command
{
    protected $signature = 'vidra:cobrar-excedentes {--competencia= : Mês a cobrar (AAAA-MM). Padrão: o mês anterior}';

    protected $description = 'Cobra os pedidos que passaram do limite do plano no mês anterior, laboratório por laboratório';

    public function handle(Cobrar $cobrar): int
    {
        $mes = $this->option('competencia') === null
            ? (new DateTimeImmutable('first day of last month', new DateTimeZone('America/Sao_Paulo')))->format('Y-m')
            : (string) $this->option('competencia');
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $mes, $partes) !== 1) {
            $this->error('Use a competência no formato AAAA-MM.');

            return self::INVALID;
        }

        $falhou = false;
        foreach (DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorios')->pluck('id') as $id) {
            try {
                $this->line("Laboratório {$id}: ".($cobrar((string) $id, (int) $partes[1], (int) $partes[2]) ? 'excedente cobrado.' : 'nada a cobrar.'));
            } catch (Throwable $e) {
                $falhou = true;
                report($e);
                $this->error("Laboratório {$id}: {$e->getMessage()}");
            }
        }

        return $falhou ? self::FAILURE : self::SUCCESS;
    }
}
