<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Portas;

/** Palavras que mudam de linha para linha: "tutor e animal" na Pet, "paciente" na Clínica. */
final readonly class TextosDaLinha
{
    public function __construct(
        public string $paciente,
        public string $responsavel,
        public string $solicitante,
        public string $secaoDoPaciente,
        public string $buscaDoPaciente,
        public string $pagadorSolicitante,
        public string $pagadorResponsavel,
        public string $novoSolicitante,
        public string $novoPaciente,
    ) {}
}
