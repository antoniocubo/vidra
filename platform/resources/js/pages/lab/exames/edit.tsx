import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { FormularioExame } from './create';
type Props = {
    exame: {
        id: string;
        codigo: string;
        nome: string;
        setor: string;
        material: string;
        metodo: string | null;
        prazo_horas: number;
        preco_centavos: number;
        analitos: { id: string; nome: string; unidade: string | null; casas_decimais: number }[];
    };
};
export default function EditarExame({ exame }: Props) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Exames', href: '/exames' },
                { title: 'Editar exame', href: `/exames/${exame.id}/editar` },
            ]}
        >
            <Head title={`Editar ${exame.nome}`} />
            <FormularioExame exame={exame} />
        </AppLayout>
    );
}
