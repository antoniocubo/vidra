<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use Vidra\Laudos\Domain\CodigoDeAcesso;
use Vidra\Laudos\Domain\Conferencia;
use Vidra\Laudos\Domain\ErroDeLiberacao;
use Vidra\Laudos\Domain\Laudo;
use Vidra\Laudos\Domain\LaudoLiberado;
use Vidra\Laudos\Domain\LaudoRepository;
use Vidra\Laudos\Domain\ResponsavelTecnico;
use Vidra\Shared\Domain\Eventos;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;

final readonly class LiberarLaudo
{
    public function __construct(private ObterConferencia $obter, private Conferencia $conferencia, private LaudoRepository $laudos, private ResponsavelTecnico $rt, private Eventos $eventos, private LaboratorioAtual $laboratorio, private Transacao $transacao) {}

    public function __invoke(LiberarLaudoInput $input): string
    {
        $laudo = $this->transacao->executar(function () use ($input): Laudo {
            $this->conferencia->bloquear($input->pedidoId);
            $p = ($this->obter)($input->pedidoId);
            if ($p->laboratorioId !== $this->laboratorio->id()) {
                throw new LogicException('Laboratório divergente.');
            }
            $atual = $this->laudos->doPedido($p->id);
            if ($atual !== null && ! $atual->emRetificacao()) {
                throw ErroDeLiberacao::com('Este pedido já foi liberado.');
            }
            $dados = $this->rt->dados();
            $quando = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $ehRt = $this->rt->ehRt($input->por);
            if ($atual !== null) {
                $l = $atual->liberarRetificacao($p, $input->por, $input->nome, $dados['nome'], $dados['registro'], $quando, $input->observacao, $ehRt);
            } else {
                $numero = $this->laudos->proximoNumero();
                do {
                    $codigo = CodigoDeAcesso::gerar();
                } while ($this->laudos->codigoExiste($codigo));
                $l = Laudo::liberar($this->laudos->nextIdentity(), $p, $numero, $input->por, $input->nome, $dados['nome'], $dados['registro'], $quando, $input->observacao, $codigo, $ehRt);
            }
            $this->laudos->save($l);
            $this->conferencia->liberarItens($p->id);

            return $l;
        });
        $this->eventos->publicar(new LaudoLiberado($laudo->id, $laudo->laboratorioId, $laudo->pedidoId, $laudo->liberadoEm, $laudo->versao));

        return $laudo->versaoFormatada();
    }
}
