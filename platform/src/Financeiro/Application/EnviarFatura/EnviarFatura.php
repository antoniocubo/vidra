<?php

declare(strict_types=1);

namespace Vidra\Financeiro\Application\EnviarFatura;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Financeiro\Domain\ArquivosDeFatura;
use Vidra\Financeiro\Domain\EmissorDeFatura;
use Vidra\Financeiro\Domain\FaturaInvalida;
use Vidra\Financeiro\Domain\FaturaRepository;
use Vidra\Financeiro\Domain\GeradorDePdfDaFatura;

/** Executado pela fila. Na primeira vez marca a fatura como enviada; nas seguintes só manda de novo. */
final readonly class EnviarFatura
{
    public function __construct(private FaturaRepository $faturas, private GeradorDePdfDaFatura $gerador, private ArquivosDeFatura $arquivos, private EmissorDeFatura $emissor) {}

    public function __invoke(string $faturaId): void
    {
        $fatura = $this->faturas->porId($faturaId) ?? throw FaturaInvalida::com('Fatura não encontrada.');
        $primeiroEnvio = $fatura->enviadaEm() === null;
        if ($primeiroEnvio) {
            $fatura->enviar(new DateTimeImmutable('now', new DateTimeZone('UTC')));
        }

        $pdf = $this->gerador->gerar($fatura);
        $this->arquivos->guardar($fatura->id, $pdf);
        $this->emissor->enviar($fatura, $pdf);

        // Só grava depois do e-mail: se ele falhar, a fila tenta de novo e a fatura segue aberta.
        if ($primeiroEnvio) {
            $this->faturas->save($fatura);
        }
    }
}
