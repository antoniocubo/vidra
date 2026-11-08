<?php

declare(strict_types=1);

namespace Vidra\Veterinaria\Application\Busca;

/** Compara texto sem diferenciar maiúsculas nem acentos: "Cambuí" casa com "cambui". */
final readonly class TextoDeBusca
{
    private const array SEM_ACENTO = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
    ];

    public static function normalizar(string $texto): string
    {
        return strtr(mb_strtolower(trim($texto)), self::SEM_ACENTO);
    }

    /** Só os dígitos, para casar CNPJ e CRMV digitados com pontuação. */
    public static function digitos(string $texto): string
    {
        return preg_replace('/\D/', '', $texto) ?? '';
    }

    /** @param  list<string|null>  $campos */
    public static function casa(string $termo, array $campos): bool
    {
        $termo = self::normalizar($termo);
        if ($termo === '') {
            return true;
        }
        $digitos = self::digitos($termo);

        foreach ($campos as $campo) {
            if ($campo === null) {
                continue;
            }
            if (str_contains(self::normalizar($campo), $termo)) {
                return true;
            }
            if ($digitos !== '' && str_contains(self::digitos($campo), $digitos)) {
                return true;
            }
        }

        return false;
    }
}
