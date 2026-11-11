<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Acesso;

use Closure;
use DateTimeImmutable;

interface ConvitesDoVeterinario
{
    /** @template T
     * @param Closure():T $acao
     * @return T */
    public function transacao(Closure $acao): mixed;

    public function nextIdentity(): string;

    public function token(): string;

    public function salvar(ConviteDoVeterinario $convite): void;

    public function peloHash(string $hash): ?ConviteDoVeterinario;

    public function aceitar(string $id, DateTimeImmutable $agora): void;

    /** Bloqueia a pessoa durante envio/aceite para serializar convites e contas.
     * @return array{nome:string,email:string} */
    public function pessoa(string $id): array;

    public function emailDaConta(string $veterinario): ?string;

    public function criarConta(string $veterinario, string $email, string $senha): int;

    public function nomeDoLaboratorio(string $id): string;

    public function temVinculo(string $laboratorio, string $veterinario): bool;
}
