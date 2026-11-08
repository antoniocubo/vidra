<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Infrastructure\Pedidos;

use Vidra\Pedidos\Domain\Portas\PacienteDoPedido;
use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Veterinaria\Domain\Animal\Animal;
use Vidra\Veterinaria\Domain\Animal\AnimalId;
use Vidra\Veterinaria\Domain\Animal\AnimalRepository;
use Vidra\Veterinaria\Domain\Animal\Especie;
use Vidra\Veterinaria\Domain\Animal\Sexo;
use Vidra\Veterinaria\Domain\Relogio;
use Vidra\Veterinaria\Domain\Tutor\Tutor;
use Vidra\Veterinaria\Domain\Tutor\TutorRepository;

/** Na linha Pet, o paciente é o animal e o responsável é o tutor. */
final readonly class PacientesVeterinaria implements Pacientes
{
    public function __construct(private AnimalRepository $animais, private TutorRepository $tutores, private Relogio $relogio) {}

    public function resumo(string $pacienteId): ?PacienteDoPedido
    {
        try {
            $animal = $this->animais->porId(new AnimalId($pacienteId));
        } catch (InvalidValue) {
            return null;
        }
        $tutor = $animal === null ? null : $this->tutores->porId($animal->tutorId);

        return $animal === null || $tutor === null ? null : $this->converter($animal, $tutor);
    }

    public function resumos(array $pacienteIds): array
    {
        $resumos = [];
        foreach ($pacienteIds as $id) {
            $resumo = $this->resumo($id);
            if ($resumo !== null) {
                $resumos[$id] = $resumo;
            }
        }

        return $resumos;
    }

    public function buscar(string $termo, int $limite): array
    {
        $achados = [];
        foreach ($this->tutores->buscar($termo, $limite) as $tutor) {
            foreach ($this->animais->doTutor($tutor->id) as $animal) {
                $achados[] = $this->converter($animal, $tutor);
            }
        }

        return array_slice($achados, 0, $limite);
    }

    private function converter(Animal $animal, Tutor $tutor): PacienteDoPedido
    {
        $hoje = $this->relogio->agora();

        return new PacienteDoPedido(
            $animal->id->value,
            $animal->nome,
            $animal->descricao($hoje),
            self::especieCurta($animal),
            $animal->perfilDeReferencia($hoje),
            $tutor->nome(),
            $tutor->email()?->value,
            $tutor->email() !== null && $tutor->autorizacao() !== null,
            $tutor->celular()->formatted(),
        );
    }

    /** Nas listas e etiquetas a espécie vai no feminino quando o animal é fêmea: "gata", "cadela". */
    private static function especieCurta(Animal $animal): string
    {
        $femea = $animal->sexo === Sexo::Femea;

        return match ($animal->especie) {
            Especie::Cao => $femea ? 'cadela' : 'cão',
            Especie::Gato => $femea ? 'gata' : 'gato',
            Especie::Ave => 'ave',
            Especie::Equino => $femea ? 'égua' : 'equino',
            Especie::Outra => 'animal',
        };
    }
}
