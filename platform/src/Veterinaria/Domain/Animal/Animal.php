<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Animal;

use DateTimeImmutable;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Veterinaria\Domain\Referencia\FaixaEtaria;
use Vidra\Veterinaria\Domain\Tutor\TutorId;
use Vidra\Veterinaria\Domain\Vinculo\VinculoId;

final readonly class Animal
{
    private function __construct(
        public AnimalId $id, public string $laboratorioId, public TutorId $tutorId,
        public string $nome, public Especie $especie, public ?string $raca, public Sexo $sexo,
        public DateTimeImmutable $nascimento, public bool $nascimentoEstimado,
        public ?int $pesoGramas, public Castrado $castrado, public ?string $microchip,
        public ?VinculoId $vinculoVeterinarioId,
    ) {}

    public static function registrar(AnimalId $id, string $laboratorioId, TutorId $tutorId, string $nome, Especie $especie, ?string $raca, Sexo $sexo, DateTimeImmutable $nascimento, bool $nascimentoEstimado, ?int $pesoGramas, Castrado $castrado, ?string $microchip, ?VinculoId $vinculoVeterinarioId): self
    {
        if (trim($nome) === '') {
            throw InvalidValue::for('nome', 'informe o nome do animal');
        }
        if ($pesoGramas !== null && ($pesoGramas <= 0 || $pesoGramas >= 2000000)) {
            throw InvalidValue::for('peso', 'informe peso maior que zero e menor que 2000 kg');
        }
        if ($microchip !== null && ! preg_match('/^\d{15}$/', $microchip)) {
            throw InvalidValue::for('microchip', 'use 15 dígitos');
        }

        return new self($id, $laboratorioId, $tutorId, trim($nome), $especie, $raca, $sexo, $nascimento, $nascimentoEstimado, $pesoGramas, $castrado, $microchip, $vinculoVeterinarioId);
    }

    public static function restaurar(AnimalId $id, string $laboratorioId, TutorId $tutorId, string $nome, Especie $especie, ?string $raca, Sexo $sexo, DateTimeImmutable $nascimento, bool $nascimentoEstimado, ?int $pesoGramas, Castrado $castrado, ?string $microchip, ?VinculoId $vinculoVeterinarioId): self
    {
        return new self($id, $laboratorioId, $tutorId, $nome, $especie, $raca, $sexo, $nascimento, $nascimentoEstimado, $pesoGramas, $castrado, $microchip, $vinculoVeterinarioId);
    }

    public function perfilDeReferencia(DateTimeImmutable $hoje): ?string
    {
        return FaixaEtaria::perfil($this->especie, $this->nascimento, $hoje);
    }

    public function descricao(DateTimeImmutable $hoje): string
    {
        $idade = $this->nascimento->diff($hoje);
        $tempo = $idade->y > 0 ? $idade->y.($idade->y === 1 ? ' ano' : ' anos') : $idade->m.($idade->m === 1 ? ' mês' : ' meses');
        $partes = [match ($this->especie) {
            Especie::Cao => 'Cão', Especie::Gato => 'Gato', Especie::Ave => 'Ave', Especie::Equino => 'Equino', Especie::Outra => 'Outra espécie'
        }, $this->raca, $this->sexo === Sexo::Femea ? 'fêmea' : 'macho', ($this->nascimentoEstimado ? '~' : '').$tempo];
        if ($this->pesoGramas !== null) {
            $partes[] = rtrim(rtrim(number_format($this->pesoGramas / 1000, 3, ',', '.'), '0'), ',').' kg';
        }
        $partes[] = match ($this->castrado) {
            Castrado::Sim => $this->sexo === Sexo::Femea ? 'castrada' : 'castrado', Castrado::Nao => $this->sexo === Sexo::Femea ? 'não castrada' : 'não castrado', Castrado::NaoInformado => null
        };

        return implode(' · ', array_filter($partes));
    }
}
