<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

final class ProtecaoDaEquipe
{
    /** @param list<MembroDaEquipe> $membros */
    public static function conferir(array $membros, int $autorId, int $alvoId, ?Papel $novo): MembroDaEquipe
    {
        $alvo = null;
        $admins = 0;
        foreach ($membros as $membro) {
            if ($membro->id === $alvoId) {
                $alvo = $membro;
            }
            if ($membro->papel === Papel::Admin) {
                $admins++;
            }
        }
        if ($alvo === null) {
            throw \Vidra\Shared\Domain\ValueObjects\InvalidValue::for('form', 'Esta pessoa não faz parte deste laboratório.');
        }
        if ($alvo->papel === Papel::Admin && $novo !== Papel::Admin && $admins <= 1) {
            throw LaboratorioSemAdministrador::para();
        }
        if ($autorId === $alvoId) {
            throw NaoPodeAlterarASiMesmo::para();
        }

        return $alvo;
    }
}
