<?php

declare(strict_types=1);

namespace Vidra\Laudos\Application\MontarLaudo;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final readonly class FormatoDoLaudo
{
    public static function data(?DateTimeInterface $data, string $formato = 'd/m/Y H:i'): ?string
    {
        return $data === null ? null : DateTimeImmutable::createFromInterface($data)->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format($formato);
    }

    public static function resultado(?string $valor, int $casas, ?string $texto = null): string
    {
        return $valor === null ? ($texto ?? '') : number_format((float) $valor, $casas, ',', '');
    }

    public static function faixa(string $referencia): string
    {
        return str_replace(['≥ ', '≤ ', '.'], ['a partir de ', 'até ', ','], $referencia);
    }

    public static function classificacao(string $valor): string
    {
        return match ($valor) {
            'alto' => '↑ Alto', 'baixo' => '↓ Baixo',
            'critico_alto', 'critico_baixo' => '▲ Crítico',
            'normal' => 'Normal', 'qualitativo' => 'Qualitativo',
            default => 'Sem referência',
        };
    }

    public static function whatsapp(string $responsavel, string $paciente, string $link, string $codigo): string
    {
        $nome = explode(' ', trim($responsavel))[0];

        return "Olá, {$nome}! O laudo do {$paciente} está pronto: {$link}. Código de acesso: {$codigo}.";
    }

    public static function celular(string $telefone): ?string
    {
        $numero = preg_replace('/\D/', '', $telefone) ?? '';
        if (strlen($numero) === 10 || strlen($numero) === 11) {
            return '55'.$numero;
        }

        return preg_match('/^55[0-9]{10,11}$/D', $numero) ? $numero : null;
    }
}
