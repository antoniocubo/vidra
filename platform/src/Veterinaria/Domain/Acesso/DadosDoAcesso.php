<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Acesso;

interface DadosDoAcesso
{
    /** @return array{nome:string,crmv:string,email:string} */
    public function identidade(string $veterinario): array;

    /** @return array{novos:int,criticos:int} */
    public function novidades(LaboratorioDoVeterinario $acesso): array;

    public function registrarAcesso(LaboratorioDoVeterinario $acesso): void;
}
