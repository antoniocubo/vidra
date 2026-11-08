import { Alert, Button, FormActions, FormField, FormSection, PageHeader, Select, TextInput } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import { OndeAtendeSection, PagamentoELaudosSections, TelefoneCriticoField, type ClinicaOpcao } from './vinculo-fields';

type Props = {
    tratamentos: string[];
    ufs: string[];
    clinicas: ClinicaOpcao[];
};

const ROTULO_TRATAMENTO: Record<string, string> = { dra: 'Dra.', dr: 'Dr.', nenhum: 'Nenhum' };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Veterinários', href: '/veterinarios' },
    { title: 'Novo veterinário', href: '/veterinarios/novo' },
];

const VAZIO = {
    tratamento: 'dra',
    nome: '',
    crmv_numero: '',
    crmv_uf: 'SP',
    email: '',
    celular: '',
    telefone_critico: '',
    especialidade: '',
    quem_paga: 'clinica',
    recebe_laudo: 'cada_laudo',
    ve_pedidos_dos_colegas: false,
    clinica_ids: [] as string[],
};

export default function NovoVeterinario({ tratamentos, ufs, clinicas }: Props) {
    const { data, setData, post, errors, processing } = useForm(VAZIO);

    const salvar: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('lab.veterinarios.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Novo veterinário" />

            <form onSubmit={salvar} className="flex max-w-3xl flex-col gap-4 p-4 md:p-8">
                <PageHeader
                    eyebrow="Cadastros"
                    title="Novo veterinário"
                    description="Quem pede exames ao laboratório. O CRMV e o contato aparecem no laudo."
                />

                <FormSection title="Identificação">
                    <div className="flex gap-4 sm:col-span-2">
                        <FormField id="tratamento" label="Tratamento" error={errors.tratamento} className="w-32 shrink-0">
                            <Select
                                value={data.tratamento}
                                onChange={(value) => setData('tratamento', value)}
                                options={tratamentos.map((t) => ({ value: t, label: ROTULO_TRATAMENTO[t] ?? t }))}
                            />
                        </FormField>
                        <FormField id="nome" label="Nome completo" error={errors.nome} className="flex-1">
                            <TextInput value={data.nome} onChange={(e) => setData('nome', e.target.value)} required autoComplete="name" />
                        </FormField>
                    </div>
                    <div className="flex gap-4">
                        <FormField id="crmv_numero" label="CRMV" error={errors.crmv_numero} className="flex-1">
                            <TextInput
                                mono
                                inputMode="numeric"
                                value={data.crmv_numero}
                                onChange={(e) => setData('crmv_numero', e.target.value)}
                                required
                            />
                        </FormField>
                        <FormField id="crmv_uf" label="UF" error={errors.crmv_uf} className="w-24 shrink-0">
                            <Select
                                value={data.crmv_uf}
                                onChange={(value) => setData('crmv_uf', value)}
                                options={ufs.map((uf) => ({ value: uf, label: uf }))}
                            />
                        </FormField>
                    </div>
                    <FormField id="especialidade" label="Especialidade" optional error={errors.especialidade}>
                        <TextInput value={data.especialidade} onChange={(e) => setData('especialidade', e.target.value)} />
                    </FormField>
                </FormSection>

                <p className="text-muted-foreground text-xs">
                    O CRMV aparece no laudo como solicitante. Se outro laboratório já cadastrou este veterinário, o Vidra aproveita o cadastro.
                </p>
                <FormSection title="Contato">
                    <FormField id="email" label="E-mail" hint="Recebe os laudos." error={errors.email} className="sm:col-span-2">
                        <TextInput type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required autoComplete="email" />
                    </FormField>
                    <FormField id="celular" label="Celular" error={errors.celular}>
                        <TextInput mono inputMode="tel" value={data.celular} onChange={(e) => setData('celular', e.target.value)} required />
                    </FormField>
                    <TelefoneCriticoField
                        value={data.telefone_critico}
                        onChange={(value) => setData('telefone_critico', value)}
                        error={errors.telefone_critico}
                    />
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
                            <Link href={route('lab.veterinarios.index')}>Cancelar</Link>
                        </Button>
                    }
                >
                    <Button type="submit" loading={processing}>
                        Salvar veterinário
                    </Button>
                </FormActions>
            </form>
        </AppLayout>
    );
}
