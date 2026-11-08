<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

use DateTimeImmutable;
use DateTimeZone;
use Vidra\Shared\Domain\ValueObjects\Cnpj;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;

final readonly class Laboratorio
{
    private function __construct(
        public LaboratorioId $id, public string $nome, public Cnpj $cnpj, public Telefone $telefone,
        public Endereco $endereco, public LinhaDeProduto $linha, public Plano $plano, public Cobranca $cobranca,
        public ResponsavelTecnico $rt, public DateTimeImmutable $testeTerminaEm,
    ) {}

    public static function registrar(LaboratorioId $id, string $nome, Cnpj $cnpj, Telefone $telefone, Endereco $endereco, LinhaDeProduto $linha, Plano $plano, Cobranca $cobranca, ResponsavelTecnico $rt, DateTimeImmutable $agora): self
    {
        if ($linha !== LinhaDeProduto::Pet) {
            throw InvalidValue::for('linha', 'esta linha ainda não está disponível');
        }
        if (mb_strlen(trim($nome)) < 3) {
            throw InvalidValue::for('laboratorio_nome', 'informe o nome do laboratório');
        }

        return new self($id, trim($nome), $cnpj, $telefone, $endereco, $linha, $plano, $cobranca, $rt, $agora->setTimezone(new DateTimeZone('UTC'))->modify('+14 days'));
    }

    public static function restaurar(LaboratorioId $id, string $nome, Cnpj $cnpj, Telefone $telefone, Endereco $endereco, LinhaDeProduto $linha, Plano $plano, Cobranca $cobranca, ResponsavelTecnico $rt, DateTimeImmutable $testeTerminaEm): self
    {
        return new self($id, $nome, $cnpj, $telefone, $endereco, $linha, $plano, $cobranca, $rt, $testeTerminaEm);
    }
}
