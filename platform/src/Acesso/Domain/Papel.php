<?php

declare(strict_types=1);

namespace Vidra\Acesso\Domain;

/**
 * Papel do usuário num laboratório (laboratorio_usuario.papel). Decide as áreas
 * que ele abre. Liberar e assinar laudo depende também de ser o RT (e_rt).
 */
enum Papel: string
{
    case Admin = 'admin';
    case Rt = 'rt';
    case Bancada = 'bancada';
    case Recepcao = 'recepcao';

    /** Áreas do portal do laboratório; cada grupo de rotas exige uma. */
    public const array AREAS = ['painel', 'laudos', 'pedidos', 'cadastros', 'amostras', 'bancada', 'liberacao', 'exames', 'equipe', 'financeiro', 'assinatura'];

    /** @return list<string> */
    public function areas(): array
    {
        return match ($this) {
            self::Admin => self::AREAS,
            self::Rt => ['painel', 'laudos', 'pedidos', 'cadastros', 'amostras', 'bancada', 'liberacao', 'exames'],
            self::Bancada => ['painel', 'laudos', 'amostras', 'bancada'],
            self::Recepcao => ['painel', 'laudos', 'pedidos', 'cadastros', 'amostras'],
        };
    }

    public function pode(string $area): bool
    {
        return in_array($area, $this->areas(), true);
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Rt => 'Responsável técnico',
            self::Bancada => 'Bancada',
            self::Recepcao => 'Recepção',
        };
    }
}
