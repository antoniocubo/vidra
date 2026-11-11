<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application\ListarLaudos;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Laudos\Domain\ConsultaDeLaudos;
use Vidra\Shared\Domain\Tenancy\LaboratorioAtual;

final readonly class ListarLaudos
{
    public function __construct(private ConsultaDeLaudos $consulta, private LaboratorioAtual $laboratorio) {}

    public function __invoke(ListarLaudosInput $input): ListaDeLaudosDto
    {
        $pagina = max(1, $input->pagina);
        $linhas = $this->consulta->pagina($this->laboratorio->id(), trim($input->busca), $this->limite($input->de), $this->limite($input->ate, true), $input->entrega, $pagina);

        return new ListaDeLaudosDto(array_map(fn (array $linha): LinhaDeLaudoDto => new LinhaDeLaudoDto(...$linha), array_slice($linhas, 0, 25)), $pagina, count($linhas) > 25);
    }

    private function limite(?string $data, bool $fim = false): ?DateTimeImmutable
    {
        if ($data === null) {
            return null;
        }
        $dia = new DateTimeImmutable($data.' 12:00:00', new DateTimeZone('America/Sao_Paulo'));
        if ($fim) {
            $dia = $dia->modify('+1 day');
        }

        return $dia->setTime(0, 0)->setTimezone(new DateTimeZone('UTC'));
    }
}
