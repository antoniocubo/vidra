<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

use Vidra\Catalogo\Domain\Exame\CodigoDoExame;
use Vidra\Catalogo\Domain\Exame\Exame;
use Vidra\Catalogo\Domain\Exame\ExameId;
use Vidra\Catalogo\Domain\Exame\ExameRepository;
use Vidra\Catalogo\Domain\Exame\Exceptions\CodigoDeExameJaUsado;
use Vidra\Catalogo\Domain\Exame\Setor;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;
use Vidra\Shared\Domain\Transacao;

final readonly class CadastrarExame
{
    public function __construct(private ExameRepository $exames, private LaboratorioAtual $laboratorio, private Transacao $transacao, private MontarAnalitos $montar) {}

    public function __invoke(DadosExameInput $input): ExameId
    {
        return $this->transacao->executar(function () use ($input): ExameId {
            $codigo = new CodigoDoExame($input->codigo);
            $existente = $this->exames->existeCodigo($codigo);
            if ($existente !== null) {
                throw CodigoDeExameJaUsado::para($codigo->value, $existente->nome);
            }
            $exame = Exame::cadastrar($this->exames->nextIdentity(), $this->laboratorio->id(), $codigo, $input->nome, Setor::from($input->setor), $input->material, $input->metodo, $input->prazoHoras, $input->precoCentavos, ($this->montar)($input));
            $this->exames->save($exame);

            return $exame->id;
        });
    }
}
