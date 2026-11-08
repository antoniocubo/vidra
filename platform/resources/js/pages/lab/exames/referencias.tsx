import { Alert, Button, EmptyState, FormField, PageHeader, Select, TextInput, Toast, useFlashToast } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ListChecks } from 'lucide-react';
import { useState, type FormEvent } from 'react';
type Faixa = { perfil: string; minimo: string | null; maximo: string | null; criticoBaixo: string | null; criticoAlto: string | null };
type Analito = { id: string; nome: string; unidade: string | null; faixas: Faixa[] };
type Perfil = { chave: string; nome: string; grupo: string };
type Props = { exame: { id: string; nome: string; analitos: Analito[] }; perfis: Perfil[] };
const campos = ['minimo', 'maximo', 'criticoBaixo', 'criticoAlto'] as const;
const rotulos = { minimo: 'Mínimo', maximo: 'Máximo', criticoBaixo: 'Crítico baixo', criticoAlto: 'Crítico alto' };
function Tabela({ exameId, analito, perfis }: { exameId: string; analito: Analito; perfis: Perfil[] }) {
    const form = useForm({
        analitoId: analito.id,
        faixas: perfis.map((p) => {
            const f = analito.faixas.find((f) => f.perfil === p.chave);
            return {
                perfil: p.chave,
                minimo: f?.minimo?.replace('.', ',') ?? '',
                maximo: f?.maximo?.replace('.', ',') ?? '',
                criticoBaixo: f?.criticoBaixo?.replace('.', ',') ?? '',
                criticoAlto: f?.criticoAlto?.replace('.', ',') ?? '',
            };
        }),
    });
    const errors = form.errors as Record<string, string>;
    function salvar(e: FormEvent) {
        e.preventDefault();
        form.put(route('lab.exames.referencias.update', exameId), { preserveScroll: true });
    }
    return (
        <form onSubmit={salvar} className="space-y-4">
            <p className="text-muted-foreground text-sm">
                Deixe os quatro campos vazios para remover a referência desse perfil. Salve antes de trocar de analito.
            </p>
            <div className="bg-card overflow-x-auto rounded-lg border">
                <table className="w-full min-w-[900px] text-sm">
                    <caption className="sr-only">Referências de {analito.nome}</caption>
                    <thead>
                        <tr className="border-b text-left">
                            <th className="p-4">Perfil</th>
                            {campos.map((c) => (
                                <th key={c} className="p-4">
                                    {rotulos[c]}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    {Array.from(new Set(perfis.map((p) => p.grupo))).map((grupo) => (
                        <tbody key={grupo}>
                            <tr className="bg-muted">
                                <th colSpan={5} className="p-3 text-left">
                                    {grupo}
                                </th>
                            </tr>
                            {perfis.map((p, i) =>
                                p.grupo !== grupo ? null : (
                                    <tr key={p.chave} className="border-b">
                                        <th scope="row" className="p-4 text-left">
                                            {p.nome}
                                            {errors[`faixas.${i}.perfil`] && <p className="text-crit-600">{errors[`faixas.${i}.perfil`]}</p>}
                                        </th>
                                        {campos.map((campo) => (
                                            <td key={campo} className="p-3">
                                                <FormField
                                                    id={`${analito.id}-${i}-${campo}`}
                                                    label={`${rotulos[campo]} · ${p.nome}`}
                                                    hideLabel
                                                    error={errors[`faixas.${i}.${campo}`]}
                                                >
                                                    <TextInput
                                                        mono
                                                        inputMode="decimal"
                                                        suffix={analito.unidade ?? undefined}
                                                        value={form.data.faixas[i][campo]}
                                                        onChange={(e) =>
                                                            form.setData(
                                                                'faixas',
                                                                form.data.faixas.map((f, index) =>
                                                                    index === i ? { ...f, [campo]: e.target.value } : f,
                                                                ),
                                                            )
                                                        }
                                                    />
                                                </FormField>
                                            </td>
                                        ))}
                                    </tr>
                                ),
                            )}
                        </tbody>
                    ))}
                </table>
            </div>
            {errors.form && <Alert tone="crit" title={errors.form} />}
            {errors.analitoId && <Alert tone="crit" title={errors.analitoId} />}
            <Button type="submit" loading={form.processing}>
                Salvar referências de {analito.nome}
            </Button>
        </form>
    );
}
export default function Referencias({ exame, perfis }: Props) {
    const [id, setId] = useState(exame.analitos[0]?.id ?? '');
    const analito = exame.analitos.find((a) => a.id === id);
    const flash = useFlashToast();
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Exames', href: '/exames' },
                { title: 'Referências', href: `/exames/${exame.id}/referencias` },
            ]}
        >
            <Head title={`Referências · ${exame.nome}`} />
            <div className="space-y-6 p-4 md:p-8">
                <PageHeader eyebrow={exame.nome} title="Valores de referência" />
                <FormField id="analito" label="Analito" className="max-w-md">
                    <Select value={id} options={exame.analitos.map((a) => ({ value: a.id, label: a.nome }))} onChange={setId} />
                </FormField>
                {perfis.length === 0 ? (
                    <EmptyState
                        icon={ListChecks}
                        title="Nenhum perfil disponível"
                        description="Os perfis de referência ainda não foram definidos para esta linha de produto."
                    />
                ) : (
                    analito && <Tabela key={analito.id} exameId={exame.id} analito={analito} perfis={perfis} />
                )}
                <Button asChild variant="ghost">
                    <Link href={route('lab.exames.index', { exame: exame.id })}>Voltar ao exame</Link>
                </Button>
            </div>
            {flash.message && <Toast message={flash.message} onDismiss={flash.dismiss} className="fixed right-4 bottom-4" />}
        </AppLayout>
    );
}
