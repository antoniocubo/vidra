<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Application;

use Vidra\Catalogo\Domain\Exame\AnalitoId;
use Vidra\Catalogo\Domain\Exame\Decimal;
use Vidra\Catalogo\Domain\Exame\ExameRepository;
use Vidra\Catalogo\Domain\Exame\Exceptions\ErroNaFaixa;
use Vidra\Catalogo\Domain\Exame\Exceptions\FaixaInvalida;
use Vidra\Catalogo\Domain\Exame\Exceptions\PerfilDesconhecido;
use Vidra\Catalogo\Domain\Exame\FaixaDeReferencia;
use Vidra\Catalogo\Domain\Referencia\PerfisDeReferencia;
use Vidra\Shared\Domain\Transacao;

final readonly class DefinirFaixasDeReferencia
{
    public function __construct(private ExameRepository $exames, private ObterExame $obter, private Transacao $transacao, private PerfisDeReferencia $perfis) {}

    public function __invoke(string $id, DefinirFaixasInput $input): void
    {
        $this->transacao->executar(function () use ($id, $input): void {
            $exame = ($this->obter)($id);
            $faixas = [];
            $vistos = [];
            foreach ($input->faixas as $i => $f) {
                if (! $this->perfis->existe($f['perfil'])) {
                    throw PerfilDesconhecido::para($f['perfil'], "faixas.$i.perfil");
                }
                if (isset($vistos[$f['perfil']])) {
                    throw new ErroNaFaixa("faixas.$i.perfil", FaixaInvalida::para('perfil', 'O perfil está repetido neste analito.'));
                }
                $vistos[$f['perfil']] = true;
                if ($f['minimo'] === null && $f['maximo'] === null && $f['criticoBaixo'] === null && $f['criticoAlto'] === null) {
                    continue;
                }
                try {
                    $faixas[] = new FaixaDeReferencia($f['perfil'], self::decimal($f['minimo']), self::decimal($f['maximo']), self::decimal($f['criticoBaixo']), self::decimal($f['criticoAlto']));
                } catch (FaixaInvalida $e) {
                    throw new ErroNaFaixa("faixas.$i.$e->campo", $e);
                }
            }
            $exame->substituirFaixas(new AnalitoId($input->analitoId), $faixas);
            $this->exames->save($exame);
        });
    }

    private static function decimal(?string $valor): ?Decimal
    {
        return $valor === null ? null : new Decimal($valor);
    }
}
