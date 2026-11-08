import { Alert, Button, FormActions, FormField, FormSection, PageHeader, Select, TextInput } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

type Analito = { id?: string; nome: string; unidade: string | null; casas_decimais: number };
type Dados = {
    id?: string;
    codigo: string;
    nome: string;
    setor: string;
    material: string;
    metodo: string | null;
    prazo_horas: number;
    preco_centavos: number;
    analitos: Analito[];
};
type Props = { exame?: Dados };
export const setores = [
    { value: 'hematologia', label: 'Hematologia' },
    { value: 'bioquimica', label: 'Bioquímica' },
    { value: 'urinalise', label: 'Urinálise' },
    { value: 'parasitologia', label: 'Parasitologia' },
    { value: 'hormonios', label: 'Hormônios' },
    { value: 'microbiologia', label: 'Microbiologia' },
    { value: 'outro', label: 'Outro' },
];
const novoAnalito = (): Analito => ({ nome: '', unidade: '', casas_decimais: 2 });
export function FormularioExame({ exame }: Props) {
    const form = useForm<Dados>(
        exame ?? {
            codigo: '',
            nome: '',
            setor: 'hematologia',
            material: '',
            metodo: '',
            prazo_horas: 24,
            preco_centavos: 0,
            analitos: [novoAnalito()],
        },
    );
    const [preco, setPreco] = useState(((exame?.preco_centavos ?? 0) / 100).toFixed(2).replace('.', ','));
    const errors = form.errors as Record<string, string>;
    function salvar(event: FormEvent) {
        event.preventDefault();
        if (!/^\d+(?:,\d{1,2})?$/.test(preco)) {
            form.setError('preco_centavos', 'Informe o preço com até duas casas decimais.');
            return;
        }
        const [inteiro, fracao = ''] = preco.split(',');
        const centavos = Number(inteiro) * 100 + Number(fracao.padEnd(2, '0'));
        form.transform((data) => ({ ...data, preco_centavos: centavos }));
        if (exame?.id) form.put(route('lab.exames.update', exame.id));
        else form.post(route('lab.exames.store'));
    }
    function mover(index: number, direcao: number) {
        const analitos = [...form.data.analitos];
        [analitos[index], analitos[index + direcao]] = [analitos[index + direcao], analitos[index]];
        form.setData('analitos', analitos);
    }
    function alterar(index: number, patch: Partial<Analito>) {
        form.setData(
            'analitos',
            form.data.analitos.map((a, i) => (i === index ? { ...a, ...patch } : a)),
        );
    }
    return (
        <form onSubmit={salvar} className="flex max-w-5xl flex-col gap-6 p-4 md:p-8">
            <PageHeader
                eyebrow="Catálogo"
                title={exame ? 'Editar exame' : 'Cadastrar exame'}
                description="Defina os dados do exame e a ordem dos analitos no resultado."
            />
            <FormSection title="Dados do exame">
                {(['codigo', 'nome', 'material', 'metodo'] as const).map((campo) => (
                    <FormField
                        key={campo}
                        id={campo}
                        label={{ codigo: 'Código', nome: 'Nome do exame', material: 'Material', metodo: 'Método' }[campo]}
                        error={errors[campo]}
                        optional={campo === 'metodo'}
                    >
                        <TextInput
                            value={form.data[campo] ?? ''}
                            required={campo !== 'metodo'}
                            maxLength={campo === 'codigo' ? 12 : 255}
                            mono={campo === 'codigo'}
                            onChange={(e) => form.setData(campo, e.target.value)}
                        />
                    </FormField>
                ))}
                <FormField id="setor" label="Setor" error={errors.setor}>
                    <Select value={form.data.setor} options={setores} onChange={(v) => form.setData('setor', v)} />
                </FormField>
                <FormField id="prazo" label="Prazo em horas" error={errors.prazo_horas}>
                    <TextInput
                        type="number"
                        min={1}
                        max={720}
                        required
                        value={form.data.prazo_horas}
                        suffix="h"
                        onChange={(e) => form.setData('prazo_horas', Number(e.target.value))}
                    />
                </FormField>
                <FormField id="preco" label="Preço (R$)" error={errors.preco_centavos}>
                    <TextInput mono inputMode="decimal" required value={preco} onChange={(e) => setPreco(e.target.value)} />
                </FormField>
            </FormSection>
            <section className="space-y-4" aria-labelledby="analitos-titulo">
                <h2 id="analitos-titulo" className="font-display text-xl font-semibold">
                    Analitos
                </h2>
                {errors.analitos && <Alert tone="crit" title={errors.analitos} />}
                {form.data.analitos.map((a, i) => (
                    <fieldset key={a.id ?? i} className="bg-card grid gap-4 rounded-lg border p-4 sm:grid-cols-3">
                        <legend className="px-2 font-semibold">Analito {i + 1}</legend>
                        <FormField id={`nome-${i}`} label="Nome do analito" error={errors[`analitos.${i}.nome`]}>
                            <TextInput required value={a.nome} onChange={(e) => alterar(i, { nome: e.target.value })} />
                        </FormField>
                        <FormField id={`unidade-${i}`} label="Unidade" optional error={errors[`analitos.${i}.unidade`]}>
                            <TextInput value={a.unidade ?? ''} onChange={(e) => alterar(i, { unidade: e.target.value })} />
                        </FormField>
                        <FormField id={`casas-${i}`} label="Casas decimais" error={errors[`analitos.${i}.casas_decimais`]}>
                            <TextInput
                                type="number"
                                min={0}
                                max={4}
                                required
                                value={a.casas_decimais}
                                onChange={(e) => alterar(i, { casas_decimais: Number(e.target.value) })}
                            />
                        </FormField>
                        <div className="flex flex-wrap gap-2 sm:col-span-3">
                            <Button
                                type="button"
                                variant="secondary"
                                disabled={i === 0}
                                aria-label={`Mover analito ${i + 1} para cima`}
                                onClick={() => mover(i, -1)}
                            >
                                ↑
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                disabled={i === form.data.analitos.length - 1}
                                aria-label={`Mover analito ${i + 1} para baixo`}
                                onClick={() => mover(i, 1)}
                            >
                                ↓
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                disabled={form.data.analitos.length === 1}
                                onClick={() =>
                                    form.setData(
                                        'analitos',
                                        form.data.analitos.filter((_, index) => index !== i),
                                    )
                                }
                            >
                                Remover analito {i + 1}
                            </Button>
                        </div>
                    </fieldset>
                ))}
                <Button type="button" variant="secondary" onClick={() => form.setData('analitos', [...form.data.analitos, novoAnalito()])}>
                    Adicionar analito
                </Button>
            </section>
            {errors.form && <Alert tone="crit" title={errors.form} />}
            <FormActions>
                <Button type="submit" loading={form.processing}>
                    Salvar exame
                </Button>
                <Button asChild variant="ghost">
                    <Link href={route('lab.exames.index')}>Voltar ao catálogo</Link>
                </Button>
            </FormActions>
        </form>
    );
}
export default function NovoExame() {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Exames', href: '/exames' },
                { title: 'Cadastrar exame', href: '/exames/novo' },
            ]}
        >
            <Head title="Cadastrar exame" />
            <FormularioExame />
        </AppLayout>
    );
}
