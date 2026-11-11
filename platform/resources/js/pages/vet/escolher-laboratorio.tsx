import { Alert, Button, LabMark, PageHeader, StatusBadge } from '@/components/vidra';
import VetLayout, { type IdentidadeVet } from '@/layouts/vet-layout';
import { Head, useForm } from '@inertiajs/react';

type Props = {
    identidade: IdentidadeVet;
    laboratorios: { id: string; nome: string }[];
    novidades: Record<string, { novos: number; criticos: number }>;
};
export default function EscolherLaboratorio({ identidade, laboratorios, novidades }: Props) {
    const { post, transform, processing, errors } = useForm({ laboratorio_id: '' });
    function entrar(id: string) {
        transform(() => ({ laboratorio_id: id }));
        post(route('vet.laboratorio.entrar'));
    }
    return (
        <VetLayout identidade={identidade}>
            <Head title="Escolher laboratório" />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
                <PageHeader title="Em qual laboratório você quer entrar?" description="Cada laboratório mostra só os pedidos feitos a ele." />
                {errors.laboratorio_id && <Alert tone="crit" title={errors.laboratorio_id} />}
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Laboratórios">
                    {laboratorios.map((lab) => (
                        <li key={lab.id} className="border-border bg-card flex flex-col gap-5 rounded-xl border p-5">
                            <div className="flex items-center gap-3">
                                <LabMark initials={lab.nome.slice(0, 2).toUpperCase()} />
                                <h2 className="font-semibold">{lab.nome}</h2>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {(novidades[lab.id]?.criticos ?? 0) > 0 && (
                                    <StatusBadge tone="crit">▲ {novidades[lab.id].criticos} valor crítico</StatusBadge>
                                )}
                                <StatusBadge tone={novidades[lab.id]?.novos ? 'info' : 'neutral'}>
                                    {novidades[lab.id]?.novos ? `${novidades[lab.id].novos} laudos novos` : 'Nada novo'}
                                </StatusBadge>
                            </div>
                            <Button className="mt-auto" loading={processing} onClick={() => entrar(lab.id)}>
                                Entrar no laboratório →
                            </Button>
                        </li>
                    ))}
                </ul>
                <Alert tone="info" title="Falta um laboratório?">
                    Peça a ele para cadastrar você com o e-mail {identidade.email}. Ele aparece aqui sem precisar criar outra senha.
                </Alert>
            </div>
        </VetLayout>
    );
}
