<?php

declare(strict_types=1);

use Tests\Support\AvisosDeAssinaturaEmMemoria;
use Tests\Support\GatewayDeCobrancaFalso;
use Tests\Support\InMemoryAssinaturaRepository;
use Tests\Support\InMemoryCobrancasDaAssinatura;
use Tests\Support\LaboratorioAtualFixo;
use Tests\Support\PedidosDaAssinaturaFixos;
use Tests\Support\RelogioDaAssinaturaFixo;
use Vidra\Acesso\Application\Assinatura\AssinarPlano;
use Vidra\Acesso\Application\Assinatura\AssinarPlanoInput;
use Vidra\Acesso\Application\Assinatura\AvisarVencimento;
use Vidra\Acesso\Application\Assinatura\CobrarExcedentes;
use Vidra\Acesso\Application\Assinatura\EventoDoAsaas;
use Vidra\Acesso\Application\Assinatura\ProcessarEventoDoAsaas;
use Vidra\Acesso\Domain\Assinatura\Assinatura;
use Vidra\Acesso\Domain\Assinatura\SituacaoDaCobranca;
use Vidra\Acesso\Domain\Cobranca;
use Vidra\Acesso\Domain\Plano;

beforeEach(function (): void {
    $this->relogio = new RelogioDaAssinaturaFixo(new DateTimeImmutable('2026-12-02T15:00:00Z'));
    $this->assinaturas = new InMemoryAssinaturaRepository;
    $this->cobrancas = new InMemoryCobrancasDaAssinatura;
    $this->pedidos = new PedidosDaAssinaturaFixos;
    $this->gateway = new GatewayDeCobrancaFalso;
    $this->avisos = new AvisosDeAssinaturaEmMemoria;
    // Teste que termina em 10/12; pago_ate nulo.
    $this->assinaturas->save(Assinatura::restaurar('alfa', Plano::Bancada, Cobranca::Mensal, new DateTimeImmutable('2026-12-10T03:00:00Z'), null));
    $this->assinaturas->emails['alfa'] = ['admin@alfa.test'];
});

it('assinar durante o teste cobra só no fim do teste e guarda os ids do gateway', function (): void {
    (new AssinarPlano(new LaboratorioAtualFixo('alfa'), $this->assinaturas, $this->cobrancas, $this->gateway, $this->relogio))(new AssinarPlanoInput('laboratorio', 'anual'));

    expect($this->gateway->chamadas)->toBe(['cliente', 'assinatura:laboratorio:anual:2026-12-10'])
        ->and($this->assinaturas->vinculos['alfa']['cliente'])->toBe('cus_1')
        ->and($this->assinaturas->doLaboratorio('alfa')->plano())->toBe(Plano::Laboratorio)
        ->and($this->assinaturas->doLaboratorio('alfa')->cobranca())->toBe(Cobranca::Anual)
        ->and(array_values($this->cobrancas->cobrancas)[0]->valorCentavos)->toBe(590000);
});

it('trocar de plano cancela a assinatura antiga antes de criar a nova', function (): void {
    $assinar = new AssinarPlano(new LaboratorioAtualFixo('alfa'), $this->assinaturas, $this->cobrancas, $this->gateway, $this->relogio);
    $assinar(new AssinarPlanoInput('bancada', 'mensal'));
    $assinar(new AssinarPlanoInput('rede', 'mensal'));

    expect($this->gateway->chamadas)->toContain('cancelar:sub_2')
        ->and($this->assinaturas->vinculos['alfa']['assinatura'])->toBe('sub_5');
});

it('pagamento confirmado estende o período uma vez só, mesmo reenviado', function (): void {
    $this->assinaturas->vincularCliente('alfa', 'cus_1');
    $processar = new ProcessarEventoDoAsaas($this->assinaturas, $this->cobrancas, $this->relogio);
    $evento = new EventoDoAsaas('PAYMENT_RECEIVED', 'pay_9', 'cus_1', 29000, '2026-12-10', 'https://asaas.test/i/9');

    $processar($evento);
    $processar($evento);
    $processar(new EventoDoAsaas('PAYMENT_CONFIRMED', 'pay_9', 'cus_1', 29000, '2026-12-10', null));

    // Estende a partir do fim do teste (10/12), um mês.
    expect($this->assinaturas->doLaboratorio('alfa')->pagoAte()?->format('Y-m-d'))->toBe('2027-01-10')
        ->and($this->cobrancas->cobrancas['pay_9']->situacao)->toBe(SituacaoDaCobranca::Paga);
});

it('pagamento atrasado marca a cobrança como vencida e ignora evento de cliente desconhecido', function (): void {
    $this->assinaturas->vincularCliente('alfa', 'cus_1');
    $processar = new ProcessarEventoDoAsaas($this->assinaturas, $this->cobrancas, $this->relogio);

    $processar(new EventoDoAsaas('PAYMENT_OVERDUE', 'pay_9', 'cus_1', 29000, '2026-12-10', null));
    $processar(new EventoDoAsaas('PAYMENT_RECEIVED', 'pay_x', 'cus_outro', 100, '2026-12-10', null));

    expect($this->cobrancas->cobrancas['pay_9']->situacao)->toBe(SituacaoDaCobranca::Vencida)
        ->and($this->cobrancas->cobrancas)->not->toHaveKey('pay_x');
});

it('cobra o excedente uma vez por competência e nada dentro do limite', function (): void {
    $this->assinaturas->save(Assinatura::restaurar('alfa', Plano::Bancada, Cobranca::Mensal, new DateTimeImmutable('2026-11-01T03:00:00Z'), new DateTimeImmutable('2027-01-01T03:00:00Z')));
    $cobrar = new CobrarExcedentes($this->assinaturas, $this->cobrancas, $this->pedidos, $this->gateway, $this->relogio);

    $this->pedidos->total = 600;
    expect($cobrar('alfa', 2026, 11))->toBeFalse();

    $this->pedidos->total = 640;
    expect($cobrar('alfa', 2026, 11))->toBeTrue()
        ->and($cobrar('alfa', 2026, 11))->toBeFalse()
        ->and($this->gateway->avulsas)->toBe([[1000, 'Excedente de 40 pedidos em 11/2026']]);
});

it('não cobra excedente de quem ainda está no teste', function (): void {
    $this->pedidos->total = 900;
    $cobrar = new CobrarExcedentes($this->assinaturas, $this->cobrancas, $this->pedidos, $this->gateway, $this->relogio);

    expect($cobrar('alfa', 2026, 11))->toBeFalse()->and($this->gateway->avulsas)->toBe([]);
});

it('avisa o vencimento só nos últimos cinco dias e no máximo uma vez por dia', function (): void {
    $avisar = new AvisarVencimento($this->assinaturas, $this->cobrancas, $this->avisos, $this->relogio);

    expect($avisar('alfa'))->toBeFalse();

    $this->relogio->agora = new DateTimeImmutable('2026-12-05T15:00:00Z');
    expect($avisar('alfa'))->toBeTrue()->and($avisar('alfa'))->toBeFalse()
        ->and($this->avisos->enviados)->toHaveCount(1)->and($this->avisos->enviados[0]['dias'])->toBe(5);

    $this->relogio->agora = new DateTimeImmutable('2026-12-06T15:00:00Z');
    expect($avisar('alfa'))->toBeTrue()->and($this->avisos->enviados)->toHaveCount(2);
});
