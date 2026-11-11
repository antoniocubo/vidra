<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\Assinatura;

use Vidra\Acesso\Domain\Assinatura\AssinaturaRepository;
use Vidra\Acesso\Domain\Relogio;

/**
 * A leitura barata que o middleware e a faixa de aviso usam em toda requisição.
 * A requisição guarda o resultado (atributo `assinatura`) para o middleware e a faixa não repetirem a conta.
 */
final readonly class SituacaoDaAssinaturaAtual
{
    public function __construct(private AssinaturaRepository $assinaturas, private Relogio $relogio) {}

    /** @return array{situacao: string, abrePedido: bool, diasRestantes: int, deveAvisar: bool} */
    public function __invoke(string $laboratorioId): array
    {
        $assinatura = $this->assinaturas->doLaboratorio($laboratorioId);
        $agora = $this->relogio->agora();
        $situacao = $assinatura->situacao($agora);

        return [
            'situacao' => $situacao->value,
            'abrePedido' => $situacao->abrePedido(),
            'diasRestantes' => $assinatura->diasRestantes($agora),
            'deveAvisar' => $assinatura->deveAvisar($agora),
        ];
    }
}
