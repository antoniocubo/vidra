<?php

declare(strict_types=1);

namespace Vidra\Pedidos\Domain\Amostra;

/** Texto pronto para o laboratório copiar e mandar à clínica quando recusa uma amostra. */
final class MensagemDeRecoleta
{
    /** @param list<string> $exames nomes dos exames que usam a amostra */
    public static function montar(string $codigo, string $paciente, MotivoDeRejeicao $motivo, ?string $detalhe, string $material, array $exames): string
    {
        $problema = $motivo->frase();
        $detalhe = $detalhe === null ? '' : trim($detalhe);
        if ($motivo === MotivoDeRejeicao::Outro && $detalhe !== '') {
            $problema .= ': '.rtrim($detalhe, '.');
        }

        $para = $exames === [] ? '' : ' para '.self::lista($exames);

        return sprintf('Amostra %s do %s %s. Precisamos de nova coleta de %s%s.', $codigo, $paciente, $problema, mb_lcfirst($material), $para);
    }

    /** @param list<string> $nomes */
    private static function lista(array $nomes): string
    {
        $ultimo = array_pop($nomes);

        return $nomes === [] ? (string) $ultimo : implode(', ', $nomes).' e '.$ultimo;
    }
}
