import {
    Button,
    Card,
    Checkbox,
    ConfirmDialog,
    DetailList,
    EmptyState,
    FormField,
    PageHeader,
    StatusBadge,
    TextInput,
    useFlashToast,
} from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ROTULO_FORMA } from './clinica-fields';

type Clinica = {
    id: string;
    nomeFantasia: string;
    razaoSocial: string | null;
    cnpj: string | null;
    cidade: string;
    bairro: string | null;
    emailDeLaudos: string | null;
    emailDeFaturamento: string;
    telefoneCritico: string | null;
    responsavel: string | null;
    observacaoDeColeta: string | null;
    desconto: number;
    diaDeFechamento: number;
    diaDeVencimento: number;
    formaDePagamento: string;
    ativa: boolean;
    veterinarios: { vinculoId: string; nome: string }[];
};
type Props = { clinicas: Clinica[]; selecionado: Clinica | null; filtros: { busca: string; inativas: boolean } };

const tabela = (desconto: number) => (desconto === 0 ? 'Base' : `Parceira −${desconto}%`);
const local = (c: Clinica) => (c.bairro ? `${c.cidade} · ${c.bairro}` : c.cidade);
const fechamento = (c: Clinica) => (c.diaDeFechamento === 31 ? 'Fecha no último dia' : `Fecha dia ${c.diaDeFechamento}`);

export default function Clinicas({ clinicas, selecionado, filtros }: Props) {
    const [termo, setTermo] = useState(filtros.busca);
    const [confirmar, setConfirmar] = useState(false);
    const [salvando, setSalvando] = useState(false);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    useFlashToast();
    useEffect(
        () => () => {
            if (timer.current) clearTimeout(timer.current);
        },
        [],
    );
    const consulta = (extra: Partial<{ busca: string; inativas: boolean; clinica: string }>) => {
        const dados = { busca: filtros.busca, inativas: filtros.inativas, ...extra };
        return { busca: dados.busca, inativas: dados.inativas ? 1 : 0, ...(extra.clinica ? { clinica: extra.clinica } : {}) };
    };
    const buscar = (texto: string) => {
        setTermo(texto);
        if (timer.current) clearTimeout(timer.current);
        timer.current = setTimeout(
            () => router.get(route('lab.clinicas.index'), consulta({ busca: texto }), { preserveState: true, preserveScroll: true, replace: true }),
            350,
        );
    };
    const desativar = () => {
        if (!selecionado) return;
        router.post(
            route('lab.clinicas.desativar', { clinica: selecionado.id }),
            {},
            {
                preserveScroll: true,
                onStart: () => setSalvando(true),
                onFinish: () => {
                    setSalvando(false);
                    setConfirmar(false);
                },
            },
        );
    };
    const reativar = () => {
        if (!selecionado) return;
        router.post(
            route('lab.clinicas.reativar', { clinica: selecionado.id }),
            {},
            { preserveScroll: true, onStart: () => setSalvando(true), onFinish: () => setSalvando(false) },
        );
    };
    const filtrando = filtros.busca !== '';

    return (
        <AppLayout breadcrumbs={[{ title: 'Clínicas', href: route('lab.clinicas.index') }]}>
            <Head title="Clínicas parceiras" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader
                    eyebrow="Cadastros"
                    title="Clínicas parceiras"
                    actions={
                        <Button asChild>
                            <Link href={route('lab.clinicas.create')}>Nova clínica</Link>
                        </Button>
                    }
                />
                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(320px,2fr)]">
                    <Card title="Clínicas" className="min-w-0">
                        <FormField id="busca" label="Buscar clínica">
                            <TextInput value={termo} onChange={(e) => buscar(e.target.value)} placeholder="Nome, CNPJ ou cidade" />
                        </FormField>
                        <Checkbox
                            id="inativas"
                            label="Mostrar inativas"
                            checked={filtros.inativas}
                            onChange={(e) =>
                                router.get(route('lab.clinicas.index'), consulta({ busca: termo, inativas: e.target.checked }), {
                                    preserveState: true,
                                    preserveScroll: true,
                                })
                            }
                        />
                        {clinicas.length === 0 ? (
                            <EmptyState
                                icon={Building2}
                                title={filtrando ? 'Nenhuma clínica encontrada' : 'Nenhuma clínica ainda'}
                                description={
                                    filtrando
                                        ? 'Tente outro nome, CNPJ ou cidade.'
                                        : 'Cadastre a primeira clínica parceira para cobrar pela fatura mensal.'
                                }
                                action={
                                    <Button asChild>
                                        <Link href={route('lab.clinicas.create')}>Nova clínica</Link>
                                    </Button>
                                }
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="text-muted-foreground border-border border-b text-xs">
                                        <tr>
                                            <th className="p-3">Clínica</th>
                                            <th className="p-3">Tabela</th>
                                            <th className="p-3">Situação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {clinicas.map((c) => (
                                            <tr key={c.id} className={`border-border border-b ${selecionado?.id === c.id ? 'bg-secondary' : ''}`}>
                                                <td className="p-2">
                                                    <Link
                                                        href={route('lab.clinicas.index', consulta({ clinica: c.id }))}
                                                        preserveState
                                                        preserveScroll
                                                        className="focus-visible:outline-primary flex min-h-11 flex-col justify-center rounded-md p-1 focus-visible:outline-2"
                                                        aria-current={selecionado?.id === c.id ? 'true' : undefined}
                                                    >
                                                        <span className="font-semibold">{c.nomeFantasia}</span>
                                                        <span className="text-muted-foreground text-xs">{local(c)}</span>
                                                    </Link>
                                                </td>
                                                <td className="p-3 whitespace-nowrap">{tabela(c.desconto)}</td>
                                                <td className="p-3">
                                                    <StatusBadge tone={c.ativa ? 'ok' : 'neutral'}>{c.ativa ? 'Ativa' : 'Inativa'}</StatusBadge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Card>
                    {selecionado && (
                        <aside aria-label={`Detalhes de ${selecionado.nomeFantasia}`} className="min-w-0 space-y-4">
                            <Card
                                title={selecionado.nomeFantasia}
                                action={
                                    <Button variant="secondary" asChild>
                                        <Link href={route('lab.clinicas.edit', { clinica: selecionado.id })}>Editar clínica</Link>
                                    </Button>
                                }
                            >
                                <p className="text-muted-foreground text-sm">
                                    {selecionado.razaoSocial}
                                    {selecionado.razaoSocial && selecionado.cnpj && ' · '}
                                    {selecionado.cnpj && <span className="font-mono">{selecionado.cnpj}</span>}
                                </p>
                                <StatusBadge tone={selecionado.ativa ? 'ok' : 'neutral'} className="w-fit">
                                    {selecionado.ativa ? 'Ativa' : 'Inativa'}
                                </StatusBadge>
                                <DetailList
                                    className="[&_dd]:break-words"
                                    items={[
                                        { term: 'Local', value: local(selecionado) },
                                        { term: 'Responsável', value: selecionado.responsavel || 'Não informado' },
                                        { term: 'E-mail de laudos', value: selecionado.emailDeLaudos || 'Não informado' },
                                        { term: 'E-mail de faturamento', value: selecionado.emailDeFaturamento },
                                        {
                                            term: 'Telefone p/ crítico',
                                            value: selecionado.telefoneCritico || 'Não informado',
                                            mono: !!selecionado.telefoneCritico,
                                        },
                                        { term: 'Coleta', value: selecionado.observacaoDeColeta || 'Não informada' },
                                        {
                                            term: 'Tabela de preço',
                                            value: selecionado.desconto === 0 ? 'Base' : `Parceira: ${selecionado.desconto}% abaixo da base`,
                                        },
                                        {
                                            term: 'Faturamento',
                                            value: `${fechamento(selecionado)} · vence dia ${selecionado.diaDeVencimento} · ${ROTULO_FORMA[selecionado.formaDePagamento] ?? selecionado.formaDePagamento}`,
                                        },
                                    ]}
                                />
                                <p className="text-muted-foreground text-xs">A clínica não tem acesso próprio. Ela recebe a fatura por e-mail.</p>
                            </Card>
                            <Card title="Veterinários que pedem por esta clínica">
                                {selecionado.veterinarios.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">Nenhum veterinário marcado para esta clínica.</p>
                                ) : (
                                    <ul className="space-y-1">
                                        {selecionado.veterinarios.map((v) => (
                                            <li key={v.vinculoId}>
                                                <Link
                                                    href={route('lab.veterinarios.index', { veterinario: v.vinculoId })}
                                                    className="text-primary focus-visible:outline-primary flex min-h-11 items-center rounded-md font-semibold underline underline-offset-4 focus-visible:outline-2"
                                                >
                                                    {v.nome}
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <Button variant="secondary" asChild className="w-fit">
                                    <Link href={route('lab.veterinarios.create')}>Cadastrar veterinário</Link>
                                </Button>
                            </Card>
                            {selecionado.ativa ? (
                                <Button variant="ghost" onClick={() => setConfirmar(true)}>
                                    Desativar clínica
                                </Button>
                            ) : (
                                <Button variant="secondary" loading={salvando} onClick={reativar}>
                                    Reativar clínica
                                </Button>
                            )}
                        </aside>
                    )}
                </div>
            </div>
            <ConfirmDialog
                open={confirmar}
                onOpenChange={setConfirmar}
                title={`Desativar ${selecionado?.nomeFantasia}?`}
                description="A clínica sai da lista de ativas e das opções de “Onde atende” no cadastro do veterinário. O cadastro e o histórico ficam guardados, e dá para reativar quando quiser."
                confirmLabel="Desativar clínica"
                tone="danger"
                loading={salvando}
                onConfirm={desativar}
            />
        </AppLayout>
    );
}
