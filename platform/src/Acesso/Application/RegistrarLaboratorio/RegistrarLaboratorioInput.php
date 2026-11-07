<?php

declare(strict_types=1);

namespace Vidra\Acesso\Application\RegistrarLaboratorio;

final readonly class RegistrarLaboratorioInput
{
    public function __construct(
        public string $laboratorioNome,
        public string $cnpj,
        public string $telefone,
        public string $cep,
        public string $cidade,
        public string $uf,
        public string $endereco,
        public string $linha,
        public string $plano,
        public string $cobranca,
        public string $rtNome,
        public string $rtConselho,
        public string $rtNumero,
        public string $rtUf,
        public ?string $rtTitulo,
        public ?string $rtEmail,
        public bool $souRt,
        public string $nome,
        public string $email,
        public string $celular,
        public string $senha
    ) {}
}
