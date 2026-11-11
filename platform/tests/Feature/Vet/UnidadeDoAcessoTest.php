<?php

declare(strict_types=1);

use Tests\Support\Veterinarios;
use Vidra\Veterinaria\Application\AcessoDoVeterinario\AceitarConviteDoVeterinario;
use Vidra\Veterinaria\Application\AcessoDoVeterinario\ConsultarConviteDoVeterinario;
use Vidra\Veterinaria\Domain\Acesso\AcessoVeterinarioInvalido;
use Vidra\Veterinaria\Domain\Acesso\ConviteDoVeterinario;
use Vidra\Veterinaria\Domain\Relogio;

it('o domínio vence o convite exatamente ao completar sete dias', function (): void {
    $agora = new DateTimeImmutable('2026-11-10 12:00:00+00:00');
    $convite = ConviteDoVeterinario::criar('id', 'lab', 'vet', hash('sha256', 'token'), 1, $agora);
    $convite->conferir($agora->modify('+7 days -1 second'));
    expect(fn () => $convite->conferir($agora->modify('+7 days')))->toThrow(AcessoVeterinarioInvalido::class);
});

it('o caso de uso exige senha longa e consome o convite uma única vez em memória', function (): void {
    $agora = new DateTimeImmutable('2026-11-10 12:00:00+00:00');
    $relogio = new class($agora) implements Relogio
    {
        public function __construct(private DateTimeImmutable $agora) {}

        public function agora(): DateTimeImmutable
        {
            return $this->agora;
        }
    };
    $repositorio = Veterinarios::convitesEmMemoria();
    $repositorio->salvar(ConviteDoVeterinario::criar('id', 'lab', 'vet', hash('sha256', 'token'), 1, $agora));
    $aceitar = new AceitarConviteDoVeterinario($repositorio, new ConsultarConviteDoVeterinario($repositorio, $relogio), $relogio);
    expect(fn () => $aceitar(new Vidra\Veterinaria\Application\AcessoDoVeterinario\AceitarConviteDoVeterinarioInput('token', 'curta')))->toThrow(AcessoVeterinarioInvalido::class);
    expect($repositorio->emailDaConta('vet'))->toBeNull();
    expect($aceitar(new Vidra\Veterinaria\Application\AcessoDoVeterinario\AceitarConviteDoVeterinarioInput('token', 'senha-do-vet-2026')))->toBe(['contaId' => 1, 'laboratorioId' => 'lab']);
    expect(fn () => $aceitar(new Vidra\Veterinaria\Application\AcessoDoVeterinario\AceitarConviteDoVeterinarioInput('token', 'outra-senha-do-vet')))->toThrow(AcessoVeterinarioInvalido::class);
});
