<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

use Vidra\Catalogo\Domain\Exame\CodigoDoExame;
use Vidra\Catalogo\Domain\Exame\ExameRepository;
use Vidra\Catalogo\Domain\Exame\Exceptions\CodigoDeExameJaUsado;
use Vidra\Catalogo\Domain\Exame\Setor;
use Vidra\Shared\Domain\Transacao;

final readonly class AlterarExame
{
    public function __construct(private ExameRepository $exames, private ObterExame $obter, private Transacao $transacao, private MontarAnalitos $montar) {}

    public function __invoke(string $id, DadosExameInput $input): void
    {
        $this->transacao->executar(function () use ($id, $input): void {
            $exame = ($this->obter)($id);
            $codigo = new CodigoDoExame($input->codigo);
            $existente = $this->exames->existeCodigo($codigo, $exame->id);
            if ($existente !== null) {
                throw CodigoDeExameJaUsado::para($codigo->value, $existente->nome);
            }
            $analitos = ($this->montar)($input, $exame);
            $exame->alterarDados($codigo, $input->nome, Setor::from($input->setor), $input->material, $input->metodo, $input->prazoHoras, $input->precoCentavos);
            $exame->definirAnalitos($analitos);
            $this->exames->save($exame);
        });
    }
}
