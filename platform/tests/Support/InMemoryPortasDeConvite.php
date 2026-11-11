<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use DateTimeImmutable;
use Vidra\Acesso\Domain\Convite;
use Vidra\Acesso\Domain\EntregaDeConvites;
use Vidra\Acesso\Domain\Relogio;
use Vidra\Acesso\Domain\TokensDeConvite;
use Vidra\Acesso\Domain\TransacaoDaEquipe;

final class InMemoryPortasDeConvite implements EntregaDeConvites, Relogio, TokensDeConvite, TransacaoDaEquipe
{
    /** @var list<array{convite:Convite,token:string}> */
    public array $envios = [];

    private int $sequencia = 0;

    public DateTimeImmutable $data;

    public function __construct()
    {
        $this->data = new DateTimeImmutable('2026-11-10T12:00:00+00:00');
    }

    public function enviar(Convite $convite, string $token): void
    {
        $this->envios[] = ['convite' => clone $convite, 'token' => $token];
    }

    public function gerar(): string
    {
        return str_pad((string) ++$this->sequencia, 48, 'a');
    }

    public function executar(string $laboratorioId, Closure $acao): mixed
    {
        return $acao();
    }

    public function agora(): DateTimeImmutable
    {
        return $this->data;
    }
}
