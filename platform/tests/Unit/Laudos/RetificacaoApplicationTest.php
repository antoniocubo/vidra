<?php

declare(strict_types=1);

use Tests\Support\InMemoryLaudoRepository;
use Vidra\Laudos\Application\LiberarLaudo;
use Vidra\Laudos\Application\LiberarLaudoInput;
use Vidra\Laudos\Application\ObterConferencia;
use Vidra\Laudos\Application\ReabrirLaudo;
use Vidra\Laudos\Application\ReabrirLaudoInput;
use Vidra\Laudos\Domain\Conferencia;
use Vidra\Laudos\Domain\ErroDeLiberacao;
use Vidra\Laudos\Domain\LaudoLiberado;
use Vidra\Laudos\Domain\PedidoParaConferencia;
use Vidra\Laudos\Domain\ResponsavelTecnico;
use Vidra\Laudos\Domain\ResultadoParaConferencia;
use Vidra\Shared\Domain\Eventos;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;

beforeEach(function (): void {
    $this->laudos = new InMemoryLaudoRepository;
    $this->conferencia = new class implements Conferencia
    {
        public string $situacao = 'resultado_digitado';

        public function bloquear(string $pedidoId): void {}

        public function liberarItens(string $pedidoId): void
        {
            $this->situacao = 'liberado';
        }

        public function reabrirItens(string $pedidoId): void
        {
            $this->situacao = 'resultado_digitado';
        }

        public function pedidos(): array
        {
            return [new PedidoParaConferencia('pedido', 'lab', 'P-00001', 'Thor', 'Solicitante', '', ['item' => $this->situacao], [new ResultadoParaConferencia('r', 'item', 'Exame', 'Potássio', '4.5', null, 'normal', '3 a 5', 'Marina', new DateTimeImmutable, null, null, false, null, null)])];
        }
    };
    $this->rt = new class implements ResponsavelTecnico
    {
        public function ehRt(int $usuarioId): bool
        {
            return $usuarioId === 1;
        }

        public function dados(): array
        {
            return ['nome' => 'Marina', 'registro' => 'CRMV-SP 12345'];
        }
    };
    $this->laboratorio = new class implements LaboratorioAtual
    {
        public function id(): string
        {
            return 'lab';
        }

        public function definido(): bool
        {
            return true;
        }
    };
    $this->transacao = new class implements Transacao
    {
        public function executar(Closure $operacao): mixed
        {
            return $operacao();
        }
    };
    $this->eventos = new class implements Eventos
    {
        public array $publicados = [];

        public function publicar(object $evento): void
        {
            $this->publicados[] = $evento;
        }
    };
    $this->liberar = new LiberarLaudo(new ObterConferencia($this->conferencia), $this->conferencia, $this->laudos, $this->rt, $this->eventos, $this->laboratorio, $this->transacao);
    $this->reabrir = new ReabrirLaudo($this->laudos, $this->conferencia, $this->rt, $this->transacao, $this->laboratorio);
    ($this->liberar)(new LiberarLaudoInput('pedido', 1, 'Marina', null));
});

it('reabre no caso de uso e libera a versão seguinte preservando identidade e evento', function (): void {
    $primeiro = $this->laudos->doPedido('pedido');
    ($this->reabrir)(new ReabrirLaudoInput('pedido', 'Correção', 1));
    expect($this->conferencia->situacao)->toBe('resultado_digitado')->and($this->laudos->doPedido('pedido')->emRetificacao())->toBeTrue();
    ($this->liberar)(new LiberarLaudoInput('pedido', 1, 'Marina', 'Revisado'));
    $atual = $this->laudos->doPedido('pedido');
    expect($atual->versao)->toBe(2)->and($atual->id)->toBe($primeiro->id)->and($atual->codigoAcesso)->toEqual($primeiro->codigoAcesso)->and($this->laudos->versoes($atual->id))->toHaveCount(2);
    expect($this->eventos->publicados[1])->toBeInstanceOf(LaudoLiberado::class)->and($this->eventos->publicados[1]->versao)->toBe(2);
});

it('não reabre por usuário sem RT e não muda os itens', function (): void {
    expect(fn () => ($this->reabrir)(new ReabrirLaudoInput('pedido', 'Correção', 2)))->toThrow(ErroDeLiberacao::class, 'Só o responsável técnico retifica laudos.');
    expect($this->conferencia->situacao)->toBe('liberado');
});

it('recusa motivo vazio e reabertura repetida nos casos de uso', function (): void {
    expect(fn () => ($this->reabrir)(new ReabrirLaudoInput('pedido', ' ', 1)))->toThrow(ErroDeLiberacao::class);
    ($this->reabrir)(new ReabrirLaudoInput('pedido', 'Correção', 1));
    expect(fn () => ($this->reabrir)(new ReabrirLaudoInput('pedido', 'Outra correção', 1)))->toThrow(ErroDeLiberacao::class, 'já está aberto');
});

it('não libera de novo sem retificação nem publica outro evento', function (): void {
    expect(fn () => ($this->liberar)(new LiberarLaudoInput('pedido', 1, 'Marina', null)))->toThrow(ErroDeLiberacao::class, 'Este pedido já foi liberado.');
    expect($this->eventos->publicados)->toHaveCount(1);
});

it('exige RT e resultados conferidos também na liberação da retificação', function (): void {
    ($this->reabrir)(new ReabrirLaudoInput('pedido', 'Correção', 1));
    expect(fn () => ($this->liberar)(new LiberarLaudoInput('pedido', 2, 'Outra pessoa', null)))->toThrow(ErroDeLiberacao::class);
    $this->conferencia->situacao = 'em_analise';
    expect(fn () => ($this->liberar)(new LiberarLaudoInput('pedido', 1, 'Marina', null)))->toThrow(ErroDeLiberacao::class);
    expect($this->laudos->doPedido('pedido')->versao)->toBe(1)->and($this->eventos->publicados)->toHaveCount(1);
});
