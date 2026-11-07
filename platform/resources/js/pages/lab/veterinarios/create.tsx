import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

    const salvar: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('lab.veterinarios.store'), { onSuccess: () => setData(VAZIO) });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Novo veterinário" />

            <form onSubmit={salvar} className="flex max-w-3xl flex-col gap-4 p-4 md:p-8">
                <header className="flex flex-col gap-1">
                    <h1 className="font-display text-3xl font-bold">Novo veterinário</h1>
                    <p className="text-muted-foreground">
                        Quem pede exames ao laboratório. Com o acesso, acompanha os próprios pedidos e baixa os laudos.
                    </p>
                </header>

                <section aria-labelledby="sec-identificacao" className="bg-card grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
                    <h2 id="sec-identificacao" className="text-base font-semibold sm:col-span-2">
                        Identificação
                    </h2>
                    <div className="flex gap-4 sm:col-span-2">
                        <FormField id="tratamento" label="Tratamento" error={errors.tratamento} className="w-32 shrink-0">
                            <NativeSelect
                                id="tratamento"
                                value={data.tratamento}
                                onChange={(e) => setData('tratamento', e.target.value)}
                                options={tratamentos.map((t) => ({ value: t, label: ROTULO_TRATAMENTO[t] ?? t }))}
                            />
                        </FormField>
                        <FormField id="nome" label="Nome completo" error={errors.nome} className="flex-1">
                            <Input id="nome" className="h-11" value={data.nome} onChange={(e) => setData('nome', e.target.value)} required />
                        </FormField>
                    </div>
                    <div className="flex gap-4">
                        <FormField id="crmv_numero" label="CRMV" error={errors.crmv_numero} className="flex-1">
                            <Input
                                id="crmv_numero"
                                inputMode="numeric"
                                className="h-11 font-mono"
                                value={data.crmv_numero}
                                onChange={(e) => setData('crmv_numero', e.target.value)}
                                required
                            />
                        </FormField>
                        <FormField id="crmv_uf" label="UF" error={errors.crmv_uf} className="w-24 shrink-0">
                            <NativeSelect
                                id="crmv_uf"
                                value={data.crmv_uf}
                                onChange={(e) => setData('crmv_uf', e.target.value)}
                                options={ufs.map((uf) => ({ value: uf, label: uf }))}
                            />
                        </FormField>
                    </div>
                    <FormField id="especialidade" label="Especialidade" hint="opcional" error={errors.especialidade}>
                        <Input
                            id="especialidade"
                            className="h-11"
                            value={data.especialidade}
                            onChange={(e) => setData('especialidade', e.target.value)}
                        />
                    </FormField>
                </section>

                <section aria-labelledby="sec-contato" className="bg-card grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
                    <h2 id="sec-contato" className="text-base font-semibold sm:col-span-2">
                        Contato
                    </h2>
                    <FormField id="email" label="E-mail" hint="recebe os laudos e é o login" error={errors.email} className="sm:col-span-2">
                        <Input
                            id="email"
                            type="email"
                            className="h-11"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                    </FormField>
                    <FormField id="celular" label="Celular" error={errors.celular}>
                        <Input
                            id="celular"
                            inputMode="tel"
                            className="h-11 font-mono"
                            value={data.celular}
                            onChange={(e) => setData('celular', e.target.value)}
                            required
                        />
                    </FormField>
                    <FormField id="telefone_critico" label="Telefone para valor crítico" error={errors.telefone_critico}>
                        <Input
                            id="telefone_critico"
                            inputMode="tel"
                            className="h-11 font-mono"
                            value={data.telefone_critico}
                            onChange={(e) => setData('telefone_critico', e.target.value)}
                            required
                        />
                    </FormField>
                    <p className="text-muted-foreground text-xs sm:col-span-2">
                        A bancada liga neste número antes de liberar um valor crítico. Pode ser o da clínica.
                    </p>
                </section>

                <div className="flex justify-end gap-3">
                    <Button type="submit" size="lg" disabled={processing}>
                        Salvar veterinário
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
