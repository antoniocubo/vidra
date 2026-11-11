<?php

declare(strict_types=1);

namespace App\Console\Commands;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use Vidra\Financeiro\Application\FecharFaturas\FecharFaturasDoDia;
use Vidra\Shared\Infrastructure\Tenancy\ContextoLaboratorio;

final class FecharFaturas extends Command
{
    protected $signature = 'vidra:fechar-faturas {--dia= : Dia do fechamento (AAAA-MM-DD, horário de São Paulo). Padrão: hoje}';

    protected $description = 'Fecha as faturas mensais de quem tem o dia de fechamento hoje, laboratório por laboratório';

    public function handle(ContextoLaboratorio $contexto, FecharFaturasDoDia $fechar): int
    {
        $dia = $this->option('dia') === null
            ? new DateTimeImmutable('now', new DateTimeZone('UTC'))
            : DateTimeImmutable::createFromFormat('!Y-m-d H:i', $this->option('dia').' 12:00', new DateTimeZone('America/Sao_Paulo'));
        if ($dia === false) {
            $this->error('Use o dia no formato AAAA-MM-DD.');

            return self::INVALID;
        }

        $falhou = false;
        foreach (DB::connection(config()->string('vidra.conexao_sistema'))->table('laboratorios')->pluck('id') as $id) {
            try {
                $total = $contexto->executarComo((string) $id, fn (): int => $fechar($dia));
                $this->line("Laboratório {$id}: {$total} fatura(s) fechada(s).");
            } catch (Throwable $e) {
                $falhou = true;
                report($e);
                $this->error("Laboratório {$id}: {$e->getMessage()}");
            }
        }

        return $falhou ? self::FAILURE : self::SUCCESS;
    }
}
