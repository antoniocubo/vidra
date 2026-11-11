import {
    Building2,
    ClipboardList,
    FileCheck,
    FileText,
    FlaskConical,
    LayoutGrid,
    ListChecks,
    PawPrint,
    Stethoscope,
    TestTube,
    Users,
    Wallet,
    type LucideIcon,
} from 'lucide-react';

export interface LabNavItem {
    title: string;
    icon: LucideIcon;
    routeName:
        | 'dashboard'
        | 'lab.bancada.index'
        | 'lab.liberacao.index'
        | 'lab.pedidos.index'
        | 'lab.amostras.index'
        | 'lab.veterinarios.index'
        | 'lab.tutores.index'
        | 'lab.clinicas.index'
        | 'lab.exames.index'
        | 'lab.equipe.index'
        | null;
    /** Área do papel que abre o item (Vidra\Acesso\Domain\Papel). */
    area: string;
}

export const labNavigation: LabNavItem[] = [
    { title: 'Painel', icon: LayoutGrid, routeName: 'dashboard', area: 'painel' },
    { title: 'Pedidos', icon: ClipboardList, routeName: 'lab.pedidos.index', area: 'pedidos' },
    { title: 'Amostras', icon: TestTube, routeName: 'lab.amostras.index', area: 'amostras' },
    { title: 'Bancada', icon: FlaskConical, routeName: 'lab.bancada.index', area: 'bancada' },
    { title: 'Liberação', icon: FileCheck, routeName: 'lab.liberacao.index', area: 'liberacao' },
    { title: 'Laudos', icon: FileText, routeName: null, area: 'laudos' },
    { title: 'Tutores e animais', icon: PawPrint, routeName: 'lab.tutores.index', area: 'cadastros' },
    { title: 'Veterinários', icon: Stethoscope, routeName: 'lab.veterinarios.index', area: 'cadastros' },
    { title: 'Clínicas', icon: Building2, routeName: 'lab.clinicas.index', area: 'cadastros' },
    { title: 'Exames', icon: ListChecks, routeName: 'lab.exames.index', area: 'exames' },
    { title: 'Equipe', icon: Users, routeName: 'lab.equipe.index', area: 'equipe' },
    { title: 'Financeiro', icon: Wallet, routeName: null, area: 'financeiro' },
];

export function navegacaoDoPapel(areas: string[]): LabNavItem[] {
    return labNavigation.filter((item) => areas.includes(item.area));
}
