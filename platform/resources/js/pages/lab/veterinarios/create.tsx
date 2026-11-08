import {
    Alert,
    Button,
    Checkbox,
    FormActions,
    FormField,
    FormSection,
    PageHeader,
    RadioCardGroup,
    Select,
    TextInput,
    Toast,
    useFlashToast,
} from '@/components/vidra';
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
    quem_paga: 'clinica',
    recebe_laudo: 'cada_laudo',
    ve_pedidos_dos_colegas: false,
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

                <FormSection title="Quem paga os pedidos, por padrão">
                    <div className="sm:col-span-2">
                        <RadioCardGroup
                            name="quem_paga"
                            label="Pagador padrão"
                            value={data.quem_paga}
                            onChange={(value) => setData('quem_paga', value)}
                            options={[
                                {
                                    value: 'clinica',
                                    label: 'A clínica, na fatura mensal',
                                    description: 'O exame entra na fatura da clínica escolhida no pedido.',
                                },
                                {
                                    value: 'veterinario',
                                    label: 'O próprio veterinário, na fatura mensal',
                                    description: 'Para quem atende em casa ou em consultório sem CNPJ de clínica.',
                                },
                                {
                                    value: 'tutor',
                                    label: 'O tutor, no balcão',
                                    description: 'O tutor paga na entrega da amostra, por Pix ou cartão.',
                                },
                            ]}
                        />
                        {errors.quem_paga && <Alert tone="crit" title={errors.quem_paga} />}
                    </div>
                </FormSection>
                <FormSection title="Como recebe os laudos">
                    <div className="space-y-4 sm:col-span-2">
                        <RadioCardGroup
                            name="recebe_laudo"
                            label="Entrega dos laudos"
                            value={data.recebe_laudo}
                            onChange={(value) => setData('recebe_laudo', value)}
                            options={[
                                { value: 'cada_laudo', label: 'Um e-mail com o PDF a cada laudo liberado' },
                                { value: 'resumo_diario', label: 'Um resumo do dia, às 18h, com todos os laudos liberados' },
                            ]}
                        />
                        {errors.recebe_laudo && <Alert tone="crit" title={errors.recebe_laudo} />}
                        <Checkbox
                            id="ve_pedidos_dos_colegas"
                            label="Também ver os pedidos dos colegas da clínica"
                            description="Para quem responde pela clínica. Sem isso, cada veterinário vê só os próprios pedidos."
                            checked={data.ve_pedidos_dos_colegas}
                            onChange={(event) => setData('ve_pedidos_dos_colegas', event.target.checked)}
                        />
                        {errors.ve_pedidos_dos_colegas && <Alert tone="crit" title={errors.ve_pedidos_dos_colegas} />}
                    </div>
                </FormSection>
                {'form' in errors && <Alert tone="crit" title={String(errors.form)} />}
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
