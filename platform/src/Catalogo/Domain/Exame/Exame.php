<?php

declare(strict_types=1);

namespace Vidra\Catalogo\Domain\Exame;

use Vidra\Catalogo\Domain\Exame\Exceptions\AnalitoRepetido;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;

final class Exame
{
    /** @param list<Analito> $analitos */
    private function __construct(public readonly ExameId $id, public readonly string $laboratorioId, public private(set) CodigoDoExame $codigo, public private(set) string $nome, public private(set) Setor $setor, public private(set) string $material, public private(set) ?string $metodo, public private(set) int $prazoHoras, public private(set) int $precoCentavos, public private(set) bool $ativo, public private(set) array $analitos) {}

    /** @param list<Analito> $analitos */
    public static function cadastrar(ExameId $id, string $laboratorioId, CodigoDoExame $codigo, string $nome, Setor $setor, string $material, ?string $metodo, int $prazoHoras, int $precoCentavos, array $analitos): self
    {
        $exame = new self($id, $laboratorioId, $codigo, $nome, $setor, $material, $metodo, $prazoHoras, $precoCentavos, true, []);
        $exame->alterarDados($codigo, $nome, $setor, $material, $metodo, $prazoHoras, $precoCentavos);
        $exame->definirAnalitos($analitos);

        return $exame;
    }

    /** @param list<Analito> $analitos */
    public static function restaurar(ExameId $id, string $laboratorioId, CodigoDoExame $codigo, string $nome, Setor $setor, string $material, ?string $metodo, int $prazoHoras, int $precoCentavos, bool $ativo, array $analitos): self
    {
        return new self($id, $laboratorioId, $codigo, $nome, $setor, $material, $metodo, $prazoHoras, $precoCentavos, $ativo, $analitos);
    }

    public function alterarDados(CodigoDoExame $codigo, string $nome, Setor $setor, string $material, ?string $metodo, int $prazoHoras, int $precoCentavos): void
    {
        if (trim($nome) === '' || trim($material) === '' || mb_strlen($nome) > 255 || mb_strlen($material) > 255 || $prazoHoras < 1 || $prazoHoras > 720 || $precoCentavos < 0) {
            throw InvalidValue::for('form', 'Confira nome, material, prazo (1–720 horas) e preço não negativo.');
        }
        $this->codigo = $codigo;
        $this->nome = trim($nome);
        $this->setor = $setor;
        $this->material = trim($material);
        $this->metodo = $metodo;
        $this->prazoHoras = $prazoHoras;
        $this->precoCentavos = $precoCentavos;
    }

    /** @param list<Analito> $analitos */
    public function definirAnalitos(array $analitos): void
    {
        if ($analitos === []) {
            throw InvalidValue::for('analitos', 'O exame precisa de pelo menos um analito.');
        }
        $nomes = [];
        $ids = [];
        foreach ($analitos as $a) {
            $nome = mb_strtolower(trim($a->nome));
            if (isset($nomes[$nome]) || isset($ids[$a->id->value])) {
                throw AnalitoRepetido::para($a->nome);
            }
            $nomes[$nome] = true;
            $ids[$a->id->value] = true;
        }
        $this->analitos = $analitos;
    }

    public function analito(AnalitoId $id): Analito
    {
        foreach ($this->analitos as $a) {
            if ($a->id->equals($id)) {
                return $a;
            }
        }
        throw InvalidValue::for('analitoId', 'Analito não pertence a este exame.');
    }

    /** @param list<FaixaDeReferencia> $faixas */
    public function substituirFaixas(AnalitoId $id, array $faixas): void
    {
        $novo = $this->analito($id)->comFaixas($faixas);
        $this->analitos = array_map(fn (Analito $a): Analito => $a->id->equals($id) ? $novo : $a, $this->analitos);
    }

    public function definirFaixa(AnalitoId $analitoId, string $perfil, ?Decimal $minimo, ?Decimal $maximo, ?Decimal $criticoBaixo = null, ?Decimal $criticoAlto = null): void
    {
        $faixas = array_values(array_filter($this->analito($analitoId)->faixas, fn (FaixaDeReferencia $f): bool => $f->perfil !== $perfil));
        $faixas[] = new FaixaDeReferencia($perfil, $minimo, $maximo, $criticoBaixo, $criticoAlto);
        $this->substituirFaixas($analitoId, $faixas);
    }

    public function removerFaixa(AnalitoId $analitoId, string $perfil): void
    {
        $this->substituirFaixas($analitoId, array_values(array_filter($this->analito($analitoId)->faixas, fn (FaixaDeReferencia $f): bool => $f->perfil !== $perfil)));
    }

    public function desativar(): void
    {
        $this->ativo = false;
    }

    public function reativar(): void
    {
        $this->ativo = true;
    }
}
