<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

use Vidra\Catalogo\Domain\Exame\ExameRepository;
use Vidra\Shared\Domain\Transacao;

final readonly class DesativarExame
{
    public function __construct(private ExameRepository $exames, private ObterExame $obter, private Transacao $transacao) {}

    public function __invoke(string $id): void
    {
        $this->transacao->executar(function () use ($id): void {
            $exame = ($this->obter)($id);
            $exame->desativar();
            $this->exames->save($exame);
        });
    }
}
