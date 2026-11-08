import { Button, Card, DetailList, EmptyState, FormField, PageHeader, TextInput, Toast, useFlashToast } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Stethoscope } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { ROTULO_LAUDOS, ROTULO_QUEM_PAGA, descreverPagador } from './rotulos';

type Veterinario = {
    vinculoId: string;
    nome: string;
    nomeCompleto: string;
    especialidade: string | null;
    crmv: string;
    email: string;
    celular: string;
    telefoneCritico: string;
    quemPaga: string;
    recebeLaudo: string;
    vePedidosDosColegas: boolean;
    clinicas: { id: string; nome: string; ativa: boolean }[];
};
type Props = { veterinarios: Veterinario[]; selecionado: Veterinario | null; busca: string };

export default function Veterinarios({ veterinarios, selecionado, busca }: Props) {
    const [termo, setTermo] = useState(busca);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const flash = useFlashToast();
    useEffect(
        () => () => {
            if (timer.current) clearTimeout(timer.current);
        },
        [],
    );
    const buscar = (texto: string) => {
        setTermo(texto);
        if (timer.current) clearTimeout(timer.current);
        timer.current = setTimeout(
            () => router.get(route('lab.veterinarios.index'), { busca: texto }, { preserveState: true, preserveScroll: true, replace: true }),
            350,
        );
    };
    const onde = (v: Veterinario) => (v.clinicas.length > 0 ? v.clinicas.map((c) => c.nome).join(', ') : 'Sem clínica');

    return (
        <AppLayout breadcrumbs={[{ title: 'Veterinários', href: route('lab.veterinarios.index') }]}>
            <Head title="Veterinários" />
            <div className="min-w-0 space-y-6 p-4 md:p-8">
                <PageHeader
                    eyebrow="Cadastros"
                    title="Veterinários"
                    actions={
                        <Button asChild>
                            <Link href={route('lab.veterinarios.create')}>Novo veterinário</Link>
                        </Button>
                    }
                />
                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(320px,2fr)]">
                    <Card title="Veterinários" className="min-w-0">
                        <FormField id="busca" label="Buscar veterinário">
                            <TextInput value={termo} onChange={(e) => buscar(e.target.value)} placeholder="Nome, CRMV ou clínica" />
                        </FormField>
                        {veterinarios.length === 0 ? (
                            <EmptyState
                                icon={Stethoscope}
                                title={busca ? 'Nenhum veterinário encontrado' : 'Nenhum veterinário ainda'}
                                description={busca ? 'Tente outro nome, CRMV ou clínica.' : 'Cadastre quem pede exames ao seu laboratório.'}
                                action={
                                    <Button asChild>
                                        <Link href={route('lab.veterinarios.create')}>Novo veterinário</Link>
                                    </Button>
                                }
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="text-muted-foreground border-border border-b text-xs">
                                        <tr>
                                            <th className="p-3">Veterinário</th>
                                            <th className="p-3">CRMV</th>
                                            <th className="p-3">Onde atende</th>
                                            <th className="p-3">Quem paga</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {veterinarios.map((v) => (
                                            <tr
                                                key={v.vinculoId}
                                                className={`border-border border-b ${selecionado?.vinculoId === v.vinculoId ? 'bg-secondary' : ''}`}
                                            >
                                                <td className="p-2">
                                                    <Link
                                                        href={route('lab.veterinarios.index', { busca, veterinario: v.vinculoId })}
                                                        preserveState
                                                        preserveScroll
                                                        className="focus-visible:outline-primary flex min-h-11 flex-col justify-center rounded-md p-1 focus-visible:outline-2"
                                                        aria-current={selecionado?.vinculoId === v.vinculoId ? 'true' : undefined}
                                                    >
                                                        <span className="font-semibold">{v.nome}</span>
                                                        {v.especialidade && <span className="text-muted-foreground text-xs">{v.especialidade}</span>}
                                                    </Link>
                                                </td>
                                                <td className="p-3 font-mono whitespace-nowrap">{v.crmv}</td>
                                                <td className="p-3">{onde(v)}</td>
                                                <td className="p-3">{ROTULO_QUEM_PAGA[v.quemPaga] ?? v.quemPaga}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Card>
                    {selecionado && (
                        <aside aria-label={`Detalhes de ${selecionado.nome}`} className="min-w-0">
                            <Card
                                title={selecionado.nome}
                                action={
                                    <Button variant="secondary" asChild>
                                        <Link href={route('lab.veterinarios.edit', { vinculo: selecionado.vinculoId })}>Editar cadastro</Link>
                                    </Button>
                                }
                            >
                                <p className="text-muted-foreground text-sm">
                                    <span className="font-mono">{selecionado.crmv}</span>
                                    {selecionado.especialidade && ` · ${selecionado.especialidade}`}
                                </p>
                                <DetailList
                                    className="[&_dd]:break-all"
                                    items={[
                                        { term: 'Onde atende', value: onde(selecionado) },
                                        { term: 'E-mail', value: selecionado.email },
                                        { term: 'Celular', value: selecionado.celular, mono: true },
                                        {
                                            term: 'Valor crítico',
                                            value: (
                                                <>
                                                    Ligar em <span className="font-mono">{selecionado.telefoneCritico}</span>
                                                </>
                                            ),
                                        },
                                        { term: 'Quem paga', value: descreverPagador(selecionado.quemPaga, selecionado.clinicas) },
                                        { term: 'Laudos', value: ROTULO_LAUDOS[selecionado.recebeLaudo] ?? selecionado.recebeLaudo },
                                        {
                                            term: 'Pedidos dos colegas',
                                            value: selecionado.vePedidosDosColegas ? 'Vê os da clínica' : 'Vê só os próprios',
                                        },
                                    ]}
                                />
                            </Card>
                        </aside>
                    )}
                </div>
            </div>
            {flash.message && <Toast message={flash.message} onDismiss={flash.dismiss} className="fixed right-4 bottom-4 z-50" />}
        </AppLayout>
    );
}
