import { Button, StatusBadge } from '@/components/vidra';
import { useTemaClaro } from '@/hooks/use-appearance';
import { Head, router } from '@inertiajs/react';
import { Download, Share2 } from 'lucide-react';

type Resultado = {
    nome: string;
    valor: string;
    unidade: string | null;
    referencia: string;
    classificacao: string;
    anterior: string | null;
    anteriorEm: string | null;
};
type Props = {
    laudo: {
        numero: string;
        codigo: string;
        laboratorio: { nome: string; endereco: string; telefone: string; cnpj: string };
        paciente: { nome: string; descricao: string; responsavel: string };
        datas: { pedido: string; recebimento: string | null; liberacao: string };
        exames: { nome: string; material: string; metodo: string; resultados: Resultado[] }[];
        textos: { paciente: string; responsavel: string; solicitante: string };
        rtNome: string;
        rtRegistro: string;
        observacao: string | null;
        ressalvas: string[];
        avisos: string[];
        pdf: string | null;
        whatsapp: string;
    };
};
function tom(classificacao: string) {
    if (classificacao.includes('Crítico')) return 'crit';
    if (classificacao.includes('Alto') || classificacao.includes('Baixo')) return 'warn';
    return classificacao === 'Normal' ? 'ok' : 'neutral';
}
export default function Laudo({ laudo }: Props) {
    useTemaClaro();
    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title={`Laudo de ${laudo.paciente.nome}`}>
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <header className="mx-auto max-w-2xl px-5 pt-8 pb-5">
                <p className="text-brand-900 font-semibold break-words">{laudo.laboratorio.nome}</p>
                <p className="text-muted-foreground text-xs">Laudo {laudo.numero}</p>
            </header>
            <main className="mx-auto max-w-2xl space-y-5 px-5 pb-8">
                <section className="bg-card border-border rounded-xl border p-5">
                    <p className="text-muted-foreground text-xs">{laudo.textos.paciente}</p>
                    <h1 className="font-display mt-1 text-3xl font-bold break-words">{laudo.paciente.nome}</h1>
                    <p className="text-muted-foreground mt-2 text-sm">{laudo.paciente.descricao}</p>
                    <p className="mt-3 text-sm">
                        {laudo.textos.responsavel}: {laudo.paciente.responsavel}
                    </p>
                    <p className="text-muted-foreground mt-1 text-xs">Liberado em {laudo.datas.liberacao}</p>
                </section>
                {laudo.exames.map((exame, index) => (
                    <section key={index} className="bg-card border-border overflow-hidden rounded-xl border">
                        <div className="border-border border-b p-4">
                            <h2 className="font-display text-lg font-bold">{exame.nome}</h2>
                            <p className="text-muted-foreground text-xs">
                                {exame.material}
                                {exame.metodo ? ` · ${exame.metodo}` : ''}
                            </p>
                        </div>
                        <ul className="divide-border divide-y">
                            {exame.resultados.map((r, i) => (
                                <li key={i} className="space-y-2 p-4">
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <h3 className="font-semibold break-words">{r.nome}</h3>
                                        {/* A classificação já vem do back com a seta ou o símbolo ("▲ Crítico"). */}
                                        <StatusBadge symbol={false} tone={tom(r.classificacao)}>
                                            {r.classificacao}
                                        </StatusBadge>
                                    </div>
                                    <p className="font-mono text-lg break-words">
                                        {r.valor} <span className="text-muted-foreground text-sm">{r.unidade}</span>
                                    </p>
                                    <p className="text-muted-foreground text-xs">Referência: {r.referencia}</p>
                                    {r.anterior && (
                                        <p className="text-muted-foreground text-xs">
                                            Anterior: {r.anterior} {r.unidade} · {r.anteriorEm}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                ))}
                {(laudo.observacao || laudo.ressalvas.length > 0 || laudo.avisos.length > 0) && (
                    <section className="bg-secondary space-y-2 rounded-xl p-4 text-sm">
                        <h2 className="font-semibold">Observações</h2>
                        {laudo.ressalvas.map((r, i) => (
                            <p key={`r${i}`}>{r}</p>
                        ))}
                        {laudo.observacao && <p>{laudo.observacao}</p>}
                        {laudo.avisos.map((a, i) => (
                            <p key={`a${i}`}>{a}</p>
                        ))}
                    </section>
                )}
                <p className="text-muted-foreground text-sm">
                    Liberado por {laudo.rtNome} · {laudo.rtRegistro}. Converse com o profissional solicitante sobre os resultados.
                </p>
                <div className="flex flex-col gap-3">
                    {laudo.pdf ? (
                        <Button asChild icon={Download} className="w-full">
                            <a href={laudo.pdf}>Baixar o laudo em PDF</a>
                        </Button>
                    ) : (
                        <div className="space-y-2">
                            <p role="status" className="text-muted-foreground text-sm">
                                Gerando o PDF…
                            </p>
                            <Button variant="secondary" className="w-full" onClick={() => router.reload()}>
                                Atualizar andamento
                            </Button>
                        </div>
                    )}
                    <Button variant="secondary" asChild icon={Share2} className="w-full">
                        <a href={laudo.whatsapp} target="_blank" rel="noopener noreferrer">
                            Compartilhar no WhatsApp
                        </a>
                    </Button>
                </div>
                <footer className="text-muted-foreground space-y-1 text-xs">
                    <p>
                        {laudo.laboratorio.endereco} · {laudo.laboratorio.telefone}
                    </p>
                    <p>Laudo emitido pelo Vidra</p>
                </footer>
            </main>
        </div>
    );
}
