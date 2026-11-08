<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Domain\Clinica;

use Vidra\Shared\Domain\ValueObjects\Cnpj;
use Vidra\Shared\Domain\ValueObjects\Email;
use Vidra\Shared\Domain\ValueObjects\InvalidValue;
use Vidra\Shared\Domain\ValueObjects\Telefone;

/** Tudo o que o laboratório edita no cadastro da clínica. Normaliza texto e vazios no construtor. */
final readonly class DadosDaClinica
{
    public string $nomeFantasia;

    public ?string $razaoSocial;

    public string $cidade;

    public ?string $bairro;

    public ?string $responsavel;

    public ?string $observacaoDeColeta;

    public function __construct(
        string $nomeFantasia,
        ?string $razaoSocial,
        public ?Cnpj $cnpj,
        string $cidade,
        ?string $bairro,
        public ?Email $emailDeLaudos,
        public ?Telefone $telefoneCritico,
        ?string $responsavel,
        ?string $observacaoDeColeta,
        public Faturamento $faturamento,
    ) {
        $this->nomeFantasia = self::obrigatorio('nome', $nomeFantasia, 2);
        $this->cidade = self::obrigatorio('cidade', $cidade, 2);
        $this->razaoSocial = self::opcional($razaoSocial);
        $this->bairro = self::opcional($bairro);
        $this->responsavel = self::opcional($responsavel);
        $this->observacaoDeColeta = self::opcional($observacaoDeColeta);
    }

    private static function obrigatorio(string $campo, string $valor, int $minimo): string
    {
        $valor = preg_replace('/\s+/', ' ', trim($valor)) ?? '';

        if (mb_strlen($valor) < $minimo) {
            throw InvalidValue::for($campo, 'preencha este campo');
        }

        return $valor;
    }

    private static function opcional(?string $valor): ?string
    {
        $valor = $valor === null ? null : trim($valor);

        return $valor === '' ? null : $valor;
    }
}
