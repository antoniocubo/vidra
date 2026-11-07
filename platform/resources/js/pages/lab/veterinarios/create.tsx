import { Button, FormActions, FormField, FormSection, PageHeader, Select, TextInput, Toast, useFlashToast } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

type Props = {
    tratamentos: string[];
    ufs: string[];
};

const ROTULO_TRATAMENTO: Record<string, string> = { dra: 'Dra.', dr: 'Dr.', nenhum: 'Nenhum' };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Veterinários', href: '#' },
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
};

export default function NovoVeterinario({ tratamentos, ufs }: Props) {
    const { data, setData, post, errors, processing } = useForm(VAZIO);
    const flash = useFlashToast();

    const salvar: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('lab.veterinarios.store'), { onSuccess: () => setData(VAZIO) });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Novo veterinário" />

            <form onSubmit={salvar} className="flex max-w-3xl flex-col gap-4 p-4 md:p-8">
                <PageHeader
                    eyebrow="Cadastros"
                    title="Novo veterinário"
                    description="Quem pede exames ao laboratório. Com o acesso, acompanha os próprios pedidos e baixa os laudos."
                />

                <FormSection title="Identificação">
                    <div className="flex gap-4 sm:col-span-2">
                        <FormField id="tratamento" label="Tratamento" error={errors.tratamento} className="w-32 shrink-0">
                            <Select
                                value={data.tratamento}
                                onChange={(e) => setData('tratamento', e.target.value)}
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
                                onChange={(e) => setData('crmv_uf', e.target.value)}
                                options={ufs.map((uf) => ({ value: uf, label: uf }))}
                            />
                        </FormField>
                    </div>
                    <FormField id="especialidade" label="Especialidade" optional error={errors.especialidade}>
                        <TextInput value={data.especialidade} onChange={(e) => setData('especialidade', e.target.value)} />
                    </FormField>
                </FormSection>

                <FormSection title="Contato">
                    <FormField id="email" label="E-mail" hint="Recebe os laudos e é o login." error={errors.email} className="sm:col-span-2">
                        <TextInput type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required autoComplete="email" />
                    </FormField>
                    <FormField id="celular" label="Celular" error={errors.celular}>
                        <TextInput mono inputMode="tel" value={data.celular} onChange={(e) => setData('celular', e.target.value)} required />
                    </FormField>
                    <FormField
                        id="telefone_critico"
                        label="Telefone para valor crítico"
                        hint="A bancada liga neste número antes de liberar um valor crítico."
                        error={errors.telefone_critico}
                    >
                        <TextInput
                            mono
                            inputMode="tel"
                            value={data.telefone_critico}
                            onChange={(e) => setData('telefone_critico', e.target.value)}
                            required
                        />
                    </FormField>
                </FormSection>

                <FormActions>
                    <Button type="submit" loading={processing}>
                        Salvar veterinário
                    </Button>
                </FormActions>
            </form>

            {flash.message && <Toast message={flash.message} onDismiss={flash.dismiss} className="fixed right-4 bottom-4 z-50" />}
        </AppLayout>
    );
}
