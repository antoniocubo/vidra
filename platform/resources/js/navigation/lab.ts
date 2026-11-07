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
    routeName: 'dashboard' | 'lab.veterinarios.create' | null;
}

export const labNavigation: LabNavItem[] = [
    { title: 'Painel', icon: LayoutGrid, routeName: 'dashboard' },
    { title: 'Pedidos', icon: ClipboardList, routeName: null },
    { title: 'Amostras', icon: TestTube, routeName: null },
    { title: 'Bancada', icon: FlaskConical, routeName: null },
    { title: 'Liberação', icon: FileCheck, routeName: null },
    { title: 'Laudos', icon: FileText, routeName: null },
    { title: 'Tutores e animais', icon: PawPrint, routeName: null },
    { title: 'Veterinários', icon: Stethoscope, routeName: 'lab.veterinarios.create' },
    { title: 'Clínicas', icon: Building2, routeName: null },
    { title: 'Exames', icon: ListChecks, routeName: null },
    { title: 'Financeiro', icon: Wallet, routeName: null },
];
