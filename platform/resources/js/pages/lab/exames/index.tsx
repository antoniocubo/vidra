import { Button, Checkbox, ChipToggleGroup, EmptyState, FormField, PageHeader, StatusBadge, TextInput, useFlashToast } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { FlaskConical } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { setores } from './create';
type Faixa = { perfil: string; minimo: string | null; maximo: string | null; criticoBaixo: string | null; criticoAlto: string | null };
type Exame = {
    id: string;
    codigo: string;
    nome: string;
    setor: string;
    material: string;
    metodo: string | null;
    prazo_horas: number;
    preco_centavos: number;
    ativo: boolean;
    contagemAnalitos: number;
    perfisCobertos: number;
    analitos: { id: string; nome: string; unidade: string | null; casas_decimais: number; faixas: Faixa[] }[];
};
type Props = {
    exames: Exame[];
    detalhe: { exame: Exame; perfis: { chave: string; nome: string; grupo: string }[] } | null;
    filtros: { busca: string; setor: string; inativos: boolean };
};
const prazo = (h: number) => (h >= 48 && h % 24 === 0 ? `${h / 24} dias` : `${h} h`);
const dinheiro = (c: number) => (c / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const decimal = (v: string | null) => v?.replace('.', ',') ?? '—';
const plural = (n: number, um: string, varios: string) => `${n} ${n === 1 ? um : varios}`;

export default function Catalogo({ exames, detalhe, filtros }: Props) {
    const [busca, setBusca] = useState(filtros.busca);
    useFlashToast();
    function filtrar(patch: Partial<Props['filtros']>) {
        const f = { ...filtros, busca, ...patch };
        router.get(route('lab.exames.index'), { ...f, inativos: f.inativos ? 1 : 0 }, { preserveState: true, preserveScroll: true });
    }
    function buscar(e: FormEvent) {
        e.preventDefault();
        filtrar({ busca });
    }
    const selecionado = detalhe?.exame;
    return (
        <AppLayout breadcrumbs={[{ title: 'Exames', href: '/exames' }]}>
            <Head title="Catálogo de exames" />
            <div className="flex min-w-0 flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <PageHeader
                        eyebrow="Cadastros"
                        title="Catálogo de exames"
                        description={plural(exames.length, 'exame', 'exames') + ' nesta seleção'}
                    />
                    <Button asChild>
                        <Link href={route('lab.exames.create')}>Cadastrar exame</Link>
                    </Button>
                </div>
                <form onSubmit={buscar} className="flex flex-wrap items-end gap-3">
                    <FormField id="busca" label="Buscar por código ou nome" className="min-w-0 flex-1">
                        <TextInput value={busca} onChange={(e) => setBusca(e.target.value)} placeholder="Buscar exame" />
                    </FormField>
                    <Button type="submit" variant="secondary">
                        Buscar exames
                    </Button>
                </form>
                <ChipToggleGroup
                    label="Filtrar por setor"
                    mode="single"
                    value={filtros.setor}
                    options={[{ value: '', label: 'Todos os setores' }, ...setores]}
                    onChange={(setor) => filtrar({ setor })}
                />
                <Checkbox
                    id="inativos"
                    label="Mostrar inativos"
                    checked={filtros.inativos}
                    onChange={(e) => filtrar({ inativos: e.target.checked })}
                />
                <div className={`grid min-w-0 items-start gap-6 ${selecionado ? '2xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]' : ''}`}>
                    {exames.length === 0 ? (
                        <EmptyState
                            icon={FlaskConical}
                            title={filtros.busca || filtros.setor ? 'Nenhum exame encontrado' : 'Nenhum exame no catálogo'}
                            description={
                                filtros.busca || filtros.setor
                                    ? 'Ajuste a busca ou os filtros para encontrar um exame.'
                                    : 'Cadastre o primeiro exame para definir analitos e referências.'
                            }
                            action={
                                <Button asChild>
                                    <Link href={route('lab.exames.create')}>Cadastrar exame</Link>
                                </Button>
                            }
                        />
                    ) : (
                        <section aria-label="Lista de exames" className="bg-card min-w-0 overflow-x-auto rounded-xl border">
                            <table className="w-full min-w-[900px] text-left text-sm">
                                <thead className="bg-muted text-muted-foreground text-xs">
                                    <tr>
                                        {['Código', 'Nome', 'Setor', 'Material', 'Prazo', 'Preço', 'Situação'].map((t) => (
                                            <th key={t} className="p-3">
                                                {t}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {exames.map((e) => (
                                        <tr key={e.id} className={`border-t ${selecionado?.id === e.id ? 'bg-secondary' : ''}`}>
                                            <td className="p-3 font-mono">{e.codigo}</td>
                                            <td className="p-3">
                                                <Link
                                                    className="text-primary font-semibold underline underline-offset-4"
                                                    href={route('lab.exames.index', { ...filtros, inativos: filtros.inativos ? 1 : 0, exame: e.id })}
                                                >
                                                    {e.nome}
                                                </Link>
                                                <p className="text-muted-foreground mt-1 text-xs">
                                                    {plural(e.contagemAnalitos, 'analito', 'analitos')} ·{' '}
                                                    {plural(e.perfisCobertos, 'perfil', 'perfis')}
                                                </p>
                                            </td>
                                            <td className="p-3">{setores.find((s) => s.value === e.setor)?.label}</td>
                                            <td className="p-3">{e.material}</td>
                                            <td className="p-3 whitespace-nowrap">{prazo(e.prazo_horas)}</td>
                                            <td className="p-3 font-mono whitespace-nowrap">{dinheiro(e.preco_centavos)}</td>
                                            <td className="p-3">
                                                <StatusBadge tone={e.ativo ? 'ok' : 'neutral'}>{e.ativo ? 'Ativo' : 'Inativo'}</StatusBadge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </section>
                    )}
                    {selecionado && detalhe && (
                        <section aria-label={`Detalhes de ${selecionado.nome}`} className="min-w-0 space-y-4">
                            <div className="bg-card space-y-4 rounded-xl border p-5">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h2 className="font-display text-2xl font-semibold">{selecionado.nome}</h2>
                                        <p className="text-muted-foreground font-mono">{selecionado.codigo}</p>
                                    </div>
                                    <StatusBadge tone={selecionado.ativo ? 'ok' : 'neutral'}>{selecionado.ativo ? 'Ativo' : 'Inativo'}</StatusBadge>
                                </div>
                                <dl className="grid grid-cols-2 gap-4">
                                    {[
                                        ['Setor', setores.find((s) => s.value === selecionado.setor)?.label],
                                        ['Material', selecionado.material],
                                        ['Método', selecionado.metodo ?? 'Não informado'],
                                        ['Prazo', prazo(selecionado.prazo_horas)],
                                        ['Preço', dinheiro(selecionado.preco_centavos)],
                                    ].map(([label, value]) => (
                                        <div key={label}>
                                            <dt className="text-muted-foreground text-xs">{label}</dt>
                                            <dd>{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                <div className="flex flex-wrap gap-2">
                                    <Button asChild variant="secondary">
                                        <Link href={route('lab.exames.edit', selecionado.id)}>Editar exame</Link>
                                    </Button>
                                    <Button asChild variant="secondary">
                                        <Link href={route('lab.exames.referencias', selecionado.id)}>Editar referências</Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        onClick={() =>
                                            router.post(
                                                route(selecionado.ativo ? 'lab.exames.desativar' : 'lab.exames.reativar', selecionado.id),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        {selecionado.ativo ? 'Desativar exame' : 'Reativar exame'}
                                    </Button>
                                </div>
                            </div>
                            <h3 className="font-display text-xl font-semibold">Valores de referência e limites críticos</h3>
                            {selecionado.analitos.map((a) => (
                                <div key={a.id} className="bg-card overflow-x-auto rounded-xl border">
                                    <h4 className="p-4 font-semibold">
                                        {a.nome}{' '}
                                        <span className="text-muted-foreground font-normal">
                                            {a.unidade ?? 'Sem unidade'} · {a.casas_decimais} casas decimais
                                        </span>
                                    </h4>
                                    <table className="w-full min-w-[500px] text-left text-sm">
                                        <thead className="text-muted-foreground border-y text-xs">
                                            <tr>
                                                {['Perfil', 'Referência', 'Crítico baixo', 'Crítico alto'].map((t) => (
                                                    <th key={t} className="p-3">
                                                        {t}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        {Array.from(new Set(detalhe.perfis.map((p) => p.grupo))).map((grupo) => (
                                            <tbody key={grupo}>
                                                <tr className="bg-muted">
                                                    <th colSpan={4} className="p-3">
                                                        {grupo}
                                                    </th>
                                                </tr>
                                                {detalhe.perfis
                                                    .filter((p) => p.grupo === grupo)
                                                    .map((p) => {
                                                        const f = a.faixas.find((f) => f.perfil === p.chave);
                                                        return (
                                                            <tr key={p.chave} className="border-t">
                                                                <th scope="row" className="p-3 font-medium">
                                                                    {p.nome}
                                                                </th>
                                                                {f ? (
                                                                    <>
                                                                        <td className="p-3 font-mono">
                                                                            {f.minimo === null
                                                                                ? `Até ${decimal(f.maximo)}`
                                                                                : f.maximo === null
                                                                                  ? `A partir de ${decimal(f.minimo)}`
                                                                                  : `${decimal(f.minimo)} a ${decimal(f.maximo)}`}
                                                                        </td>
                                                                        <td className="p-3 font-mono">{decimal(f.criticoBaixo)}</td>
                                                                        <td className="p-3 font-mono">{decimal(f.criticoAlto)}</td>
                                                                    </>
                                                                ) : (
                                                                    <td colSpan={3} className="text-muted-foreground p-3">
                                                                        sem referência
                                                                    </td>
                                                                )}
                                                            </tr>
                                                        );
                                                    })}
                                            </tbody>
                                        ))}
                                    </table>
                                    {detalhe.perfis.length === 0 && <p className="text-muted-foreground p-4">Nenhum perfil disponível.</p>}
                                </div>
                            ))}
                        </section>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
