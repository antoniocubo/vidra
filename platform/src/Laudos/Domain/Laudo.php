<?php

declare(strict_types=1);

namespace Vidra\Laudos\Domain;

use DateTimeImmutable;

/**
 * O laudo de um pedido. Número e código de acesso nunca mudam; cada retificação
 * gera uma versão nova (v2, v3...) e as anteriores ficam guardadas como estavam.
 * `retificacao` é o motivo da versão atual; `reaberto`, a retificação em andamento.
 */
final readonly class Laudo
{
    /**
     * @param list<AvisoDeValorCritico> $avisos */
    private function __construct(public string $id, public string $laboratorioId, public string $pedidoId, public int $numero, public int $liberadoPor, public string $usuarioNome, public string $rtNome, public string $rtRegistro, public DateTimeImmutable $liberadoEm, public ?string $observacao, public CodigoDeAcesso $codigoAcesso, public array $avisos, public int $versao = 1, public ?Retificacao $retificacao = null, public ?Retificacao $reaberto = null) {}

    public static function liberar(string $id, PedidoParaConferencia $pedido, int $numero, int $por, string $usuarioNome, string $rtNome, string $rtRegistro, DateTimeImmutable $quando, ?string $observacao, CodigoDeAcesso $codigo, bool $ehRt): self
    {
        if (! $ehRt) {
            throw ErroDeLiberacao::com('Só o responsável técnico libera laudos.');
        }
        if (! $pedido->pronto() || $pedido->resultados === []) {
            throw ErroDeLiberacao::com('Todos os itens precisam de resultados digitados antes de liberar.');
        }
        $avisos = [];
        foreach ($pedido->resultados as $r) {
            if ($r->critico() && ! $r->avisado()) {
                throw ErroDeLiberacao::com("Registre o aviso do valor crítico de {$r->nome} antes de liberar.");
            }
            if ($r->critico() && $r->aviso !== null) {
                $avisos[] = $r->aviso;
            }
        }

        return new self($id, $pedido->laboratorioId, $pedido->id, $numero, $por, $usuarioNome, $rtNome, $rtRegistro, $quando, $observacao, $codigo, $avisos);
    }

    /**
     * @param list<AvisoDeValorCritico> $avisos */
    public static function restaurar(string $id, string $laboratorioId, string $pedidoId, int $numero, int $por, string $usuarioNome, string $rtNome, string $rtRegistro, DateTimeImmutable $quando, ?string $observacao, CodigoDeAcesso $codigo, array $avisos, int $versao = 1, ?Retificacao $retificacao = null, ?Retificacao $reaberto = null): self
    {
        return new self($id, $laboratorioId, $pedidoId, $numero, $por, $usuarioNome, $rtNome, $rtRegistro, $quando, $observacao, $codigo, $avisos, $versao, $retificacao, $reaberto);
    }

    /** O RT reabre o laudo liberado. A versão atual continua valendo até a nova ser liberada. */
    public function reabrir(Retificacao $retificacao, bool $ehRt): self
    {
        if (! $ehRt) {
            throw ErroDeLiberacao::com('Só o responsável técnico retifica laudos.');
        }
        if ($this->emRetificacao()) {
            throw ErroDeLiberacao::com('Este laudo já está aberto para retificação.');
        }

        return new self($this->id, $this->laboratorioId, $this->pedidoId, $this->numero, $this->liberadoPor, $this->usuarioNome, $this->rtNome, $this->rtRegistro, $this->liberadoEm, $this->observacao, $this->codigoAcesso, $this->avisos, $this->versao, $this->retificacao, $retificacao);
    }

    /** Libera a versão seguinte com as mesmas conferências da primeira liberação. */
    public function liberarRetificacao(PedidoParaConferencia $pedido, int $por, string $usuarioNome, string $rtNome, string $rtRegistro, DateTimeImmutable $quando, ?string $observacao, bool $ehRt): self
    {
        if ($this->reaberto === null) {
            throw ErroDeLiberacao::com('Reabra o laudo para retificação antes de liberar uma versão nova.');
        }
        $nova = self::liberar($this->id, $pedido, $this->numero, $por, $usuarioNome, $rtNome, $rtRegistro, $quando, $observacao, $this->codigoAcesso, $ehRt);

        return new self($nova->id, $nova->laboratorioId, $nova->pedidoId, $nova->numero, $nova->liberadoPor, $nova->usuarioNome, $nova->rtNome, $nova->rtRegistro, $nova->liberadoEm, $nova->observacao, $nova->codigoAcesso, $nova->avisos, $this->versao + 1, $this->reaberto, null);
    }

    public function emRetificacao(): bool
    {
        return $this->reaberto !== null;
    }

    /** L-00001, ou L-00001 v2 depois de retificado. */
    public function versaoFormatada(): string
    {
        return $this->versao === 1 ? $this->numeroFormatado() : "{$this->numeroFormatado()} v{$this->versao}";
    }

    public function numeroFormatado(): string
    {
        return 'L-'.str_pad((string) $this->numero, 5, '0', STR_PAD_LEFT);
    }
}
