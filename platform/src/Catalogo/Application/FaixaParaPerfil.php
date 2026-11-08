<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

final readonly class FaixaParaPerfil
{
    public function __construct(private ObterExame $obter) {}

    /** @return array{perfil:string,minimo:?string,maximo:?string,criticoBaixo:?string,criticoAlto:?string}|null */
    public function __invoke(string $exameId, string $analitoId, ?string $perfil): ?array
    {
        $dto = new ExameDto(($this->obter)($exameId));
        foreach ($dto->analitos as $a) {
            if ($a['id'] === $analitoId) {
                foreach ($a['faixas'] as $f) {
                    if ($f['perfil'] === $perfil) {
                        return $f;
                    }
                }
            }
        }

        return null;
    }
}
