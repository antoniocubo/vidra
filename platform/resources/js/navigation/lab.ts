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
        | null;
}

export const labNavigation: LabNavItem[] = [
    { title: 'Painel', icon: LayoutGrid, routeName: 'dashboard' },
    { title: 'Pedidos', icon: ClipboardList, routeName: 'lab.pedidos.index' },
    { title: 'Amostras', icon: TestTube, routeName: 'lab.amostras.index' },
    { title: 'Bancada', icon: FlaskConical, routeName: 'lab.bancada.index' },
    { title: 'Liberação', icon: FileCheck, routeName: 'lab.liberacao.index' },
    { title: 'Laudos', icon: FileText, routeName: null },
    { title: 'Tutores e animais', icon: PawPrint, routeName: 'lab.tutores.index' },
    { title: 'Veterinários', icon: Stethoscope, routeName: 'lab.veterinarios.index' },
    { title: 'Clínicas', icon: Building2, routeName: 'lab.clinicas.index' },
    { title: 'Exames', icon: ListChecks, routeName: 'lab.exames.index' },
    { title: 'Financeiro', icon: Wallet, routeName: null },
];
