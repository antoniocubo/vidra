<?php

declare(strict_types=1);

namespace Vidra\Bancada\Application;

use Vidra\Bancada\Domain\ErroDeBancada;
use Vidra\Bancada\Domain\Referencias;
use Vidra\Bancada\Domain\ResultadoRepository;
use Vidra\Bancada\Domain\Trabalhos;
use Vidra\Shared\Domain\Transacao;

final readonly class EnviarParaConferencia
{
    public function __construct(private ObterTrabalho $obter, private Trabalhos $trabalhos, private Referencias $referencias, private ResultadoRepository $resultados, private Transacao $transacao) {}

    public function __invoke(string $id): void
    {
        $this->transacao->executar(function () use ($id): void {
            $i = ($this->obter)($id);
            $this->trabalhos->bloquear($i->pedidoId);
            $i = ($this->obter)($id);
            if ($i->situacao !== 'em_analise' || $i->chegada === null) {
                throw ErroDeBancada::com('Só itens em análise podem ser enviados.');
            }
            $analitos = $this->referencias->doExame($i->exameId, $i->perfil);
            $ids = array_map(fn ($r): string => $r->analitoId, $this->resultados->doItem($id));
            if ($analitos === []) {
                throw ErroDeBancada::com('Cadastre os parâmetros do exame antes de enviar.');
            }
            foreach ($analitos as $a) {
                if (! in_array($a->id, $ids, true)) {
                    throw ErroDeBancada::com("Preencha {$a->nome} antes de enviar para conferência.");
                }
            }
            $this->trabalhos->transicionar($i->pedidoId, $id, 'resultado_digitado');
        });
    }
}
