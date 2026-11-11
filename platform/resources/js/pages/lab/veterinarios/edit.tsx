import { Alert, Button, DetailList, FormActions, FormSection, PageHeader } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import { OndeAtendeSection, PagamentoELaudosSections, TelefoneCriticoField, type ClinicaOpcao } from './vinculo-fields';

type Props = {
    veterinario: {
        vinculoId: string;
        nome: string;
        crmv: string;
        email: string;
        celular: string;
        telefoneCritico: string;
        quemPaga: string;
        recebeLaudo: string;
        vePedidosDosColegas: boolean;
        clinicas: { id: string }[];
    };
    clinicas: ClinicaOpcao[];
    emailDeAcesso: string | null;
};

export default function EditarVeterinario({ veterinario, clinicas, emailDeAcesso }: Props) {
    const acesso = useForm({ acesso: '' });
    const { data, setData, put, errors, processing } = useForm({
        telefone_critico: veterinario.telefoneCritico,
        quem_paga: veterinario.quemPaga,
        recebe_laudo: veterinario.recebeLaudo,
        ve_pedidos_dos_colegas: veterinario.vePedidosDosColegas,
        clinica_ids: veterinario.clinicas.map((c) => c.id),
    });
    const voltar = route('lab.veterinarios.index', { veterinario: veterinario.vinculoId });

    const salvar: FormEventHandler = (event) => {
        event.preventDefault();
        put(route('lab.veterinarios.update', { vinculo: veterinario.vinculoId }));
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Veterinários', href: route('lab.veterinarios.index') },
                { title: 'Editar cadastro', href: '#' },
            ]}
        >
            <Head title={`Editar ${veterinario.nome}`} />
            <form onSubmit={salvar} className="flex max-w-3xl flex-col gap-4 p-4 md:p-8">
                <PageHeader
                    eyebrow="Cadastros"
                    title={`Editar ${veterinario.nome}`}
                    description="Ajuste como este veterinário trabalha com o seu laboratório."
                />

                <FormSection title="Dados do veterinário">
                    <div className="space-y-3 sm:col-span-2">
                        <DetailList
                            items={[
                                { term: 'Nome', value: veterinario.nome },
                                { term: 'CRMV', value: veterinario.crmv, mono: true },
                                { term: 'E-mail', value: veterinario.email },
                                { term: 'Celular', value: veterinario.celular, mono: true },
                            ]}
                        />
                        <Alert tone="info" title="Nome, CRMV e e-mail são do cadastro único do veterinário no Vidra." />
                    </div>
                    <TelefoneCriticoField
                        value={data.telefone_critico}
                        onChange={(value) => setData('telefone_critico', value)}
                        error={errors.telefone_critico}
                    />
                </FormSection>

                <FormSection title="Acesso do veterinário">
                    <div className="space-y-3 sm:col-span-2">
                        {emailDeAcesso ? (
                            <p>Já acessa o Vidra com {emailDeAcesso}</p>
                        ) : (
                            <>
                                <p className="text-muted-foreground">
                                    Libere os pedidos e laudos deste laboratório para {veterinario.email}. O convite vale por 7 dias.
                                </p>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    loading={acesso.processing}
                                    onClick={() =>
                                        acesso.post(route('lab.veterinarios.acesso', { vinculo: veterinario.vinculoId }), { preserveScroll: true })
                                    }
                                >
                                    Enviar acesso por e-mail
                                </Button>
                            </>
                        )}
                        {acesso.errors.acesso && <Alert tone="crit" title={acesso.errors.acesso} />}
                    </div>
                </FormSection>
                <OndeAtendeSection
                    clinicas={clinicas}
                    value={data.clinica_ids}
                    onChange={(value) => setData('clinica_ids', value)}
                    error={errors.clinica_ids}
                />
                <PagamentoELaudosSections data={data} setData={setData} errors={errors} />
                {'form' in errors && <Alert tone="crit" title={String(errors.form)} />}
                <FormActions
                    secondary={
                        <Button variant="secondary" asChild>
                            <Link href={voltar}>Voltar ao veterinário</Link>
                        </Button>
                    }
                >
                    <Button type="submit" loading={processing}>
                        Salvar alterações
                    </Button>
                </FormActions>
            </form>
        </AppLayout>
    );
}
