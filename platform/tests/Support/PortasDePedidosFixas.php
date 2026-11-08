<?php

declare(strict_types=1);

namespace Tests\Support;

use Vidra\Pedidos\Domain\Portas\CatalogoDeExames;
use Vidra\Pedidos\Domain\Portas\ClinicaPagadora;
use Vidra\Pedidos\Domain\Portas\ExameDoPedido;
use Vidra\Pedidos\Domain\Portas\PacienteDoPedido;
use Vidra\Pedidos\Domain\Portas\Pacientes;
use Vidra\Pedidos\Domain\Portas\Pagadores;
use Vidra\Pedidos\Domain\Portas\SolicitanteDoPedido;
use Vidra\Pedidos\Domain\Portas\Solicitantes;

/** Dados prontos para os testes do núcleo, sem nenhuma linha de produto. */
final class PortasDePedidosFixas
{
    public const string THOR = '019a5d70-0000-7000-8000-0000000000a1';

    public const string RAFAEL = '019a5d70-0000-7000-8000-0000000000b1';

    public const string PATAS = '019a5d70-0000-7000-8000-0000000000c1';

    public const string BIOQUIMICO = '019a5d70-0000-7000-8000-0000000000d1';

    public const string HEMOGRAMA = '019a5d70-0000-7000-8000-0000000000d2';

    public const string RENAL = '019a5d70-0000-7000-8000-0000000000d3';

    public static function thor(): PacienteDoPedido
    {
        return new PacienteDoPedido(self::THOR, 'Thor', 'Cão · Golden Retriever · macho · 7 anos', 'cão', 'cao.idoso', 'Carla Mendes', 'carla@exemplo.com.br', true, '(19) 99812-4410');
    }

    public static function pacientes(): Pacientes
    {
        return new class implements Pacientes
        {
            public function resumo(string $pacienteId): ?PacienteDoPedido
            {
                return $pacienteId === PortasDePedidosFixas::THOR ? PortasDePedidosFixas::thor() : null;
            }

            public function resumos(array $pacienteIds): array
            {
                return in_array(PortasDePedidosFixas::THOR, $pacienteIds, true) ? [PortasDePedidosFixas::THOR => PortasDePedidosFixas::thor()] : [];
            }

            public function buscar(string $termo, int $limite): array
            {
                return [PortasDePedidosFixas::thor()];
            }
        };
    }

    public static function solicitantes(): Solicitantes
    {
        $rafael = new SolicitanteDoPedido(self::RAFAEL, 'Dr. Rafael Lima', 'CRMV-SP 41208', 'rafael@exemplo.com.br', '(19) 3241-0099', 'clinica');

        return new class($rafael) implements Solicitantes
        {
            public function __construct(private SolicitanteDoPedido $rafael) {}

            public function resumo(string $solicitanteId): ?SolicitanteDoPedido
            {
                return $solicitanteId === $this->rafael->id ? $this->rafael : null;
            }

            public function resumos(array $solicitanteIds): array
            {
                return in_array($this->rafael->id, $solicitanteIds, true) ? [$this->rafael->id => $this->rafael] : [];
            }

            public function todos(): array
            {
                return [$this->rafael];
            }
        };
    }

    public static function pagadores(int $desconto = 10, bool $ativa = true): Pagadores
    {
        $patas = new ClinicaPagadora(self::PATAS, 'Clínica Patas', $desconto, $ativa);

        return new class($patas) implements Pagadores
        {
            public function __construct(private ClinicaPagadora $patas) {}

            public function clinicasDoSolicitante(string $solicitanteId): array
            {
                return [$this->patas];
            }

            public function clinica(string $id): ?ClinicaPagadora
            {
                return $id === $this->patas->id ? $this->patas : null;
            }

            public function clinicas(array $ids): array
            {
                return in_array($this->patas->id, $ids, true) ? [$this->patas->id => $this->patas] : [];
            }

            public function todasAtivas(): array
            {
                return [$this->patas];
            }
        };
    }

    public static function catalogo(): CatalogoDeExames
    {
        $exames = [
            self::BIOQUIMICO => new ExameDoPedido(self::BIOQUIMICO, 'BIOQ', 'Bioquímico renal e hepático', 'bioquimica', 'Soro', 10667),
            self::HEMOGRAMA => new ExameDoPedido(self::HEMOGRAMA, 'HEMO', 'Hemograma completo', 'hematologia', 'Sangue EDTA', 4667),
            self::RENAL => new ExameDoPedido(self::RENAL, 'UREIA', 'Ureia e creatinina', 'bioquimica', 'soro ', 3500),
        ];

        return new class($exames) implements CatalogoDeExames
        {
            /** @param array<string, ExameDoPedido> $exames */
            public function __construct(private array $exames) {}

            public function ativo(string $exameId): ?ExameDoPedido
            {
                return $this->exames[$exameId] ?? null;
            }

            public function buscar(string $termo, int $limite): array
            {
                return array_slice(array_values($this->exames), 0, $limite);
            }
        };
    }
}
