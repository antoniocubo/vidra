<?php

declare(strict_types=1);

namespace Vidra\Bancada\Infrastructure;

use Vidra\Bancada\Domain\Analito;
use Vidra\Bancada\Domain\Decimal;
use Vidra\Bancada\Domain\Faixa;
use Vidra\Bancada\Domain\Referencias;
use Vidra\Catalogo\Application\DetalharExame;
use Vidra\Catalogo\Application\FaixaParaPerfil;

final readonly class ReferenciasDoCatalogo implements Referencias
{
    public function __construct(private DetalharExame $detalhar, private FaixaParaPerfil $consulta) {}

    public function descricaoDoPerfil(string $exameId, ?string $perfil): ?string
    {
        foreach (($this->detalhar)($exameId)['perfis'] as $p) {
            if ($p->chave === $perfil) {
                return $p->nome;
            }
        }

        return null;
    }

    public function doExame(string $exameId, ?string $perfil): array
    {
        $analitos = [];
        foreach (($this->detalhar)($exameId)['exame']->analitos as $a) {
            $f = ($this->consulta)($exameId, $a['id'], $perfil);
            $d = static fn (?string $v): ?Decimal => $v === null ? null : new Decimal($v);
            $analitos[] = new Analito($a['id'], $a['nome'], $a['unidade'], $a['casas_decimais'], $f === null ? null : new Faixa($f['perfil'], $d($f['minimo']), $d($f['maximo']), $d($f['criticoBaixo']), $d($f['criticoAlto'])));
        }

        return $analitos;
    }
}
