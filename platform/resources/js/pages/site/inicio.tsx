import { AiBadge, Button, SegmentedControl, StatusBadge, VidraLogo } from '@/components/vidra';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, Barcode, Camera, Check, FileCheck, Mail, ReceiptText } from 'lucide-react';
import { useState } from 'react';

type Plano = {
    value: 'bancada' | 'laboratorio' | 'rede';
    nome: string;
    mensal: number;
    anual: number;
    pedidos: number;
    usuarios: number | null;
    unidades: number;
};
type Cobranca = 'mensal' | 'anual';
const area = 'mx-auto max-w-[1200px] px-6 py-20';
const titulo = 'font-display text-3xl font-bold tracking-tight sm:text-[40px] sm:leading-[46px]';
const rotulo = 'text-[13px] font-bold uppercase tracking-wider text-brand-500';
const foco = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500';
const subtitulo =
    'O Vidra organiza o laboratório inteiro na nuvem: recebe o pedido, etiqueta a amostra, confere os resultados com a referência da espécie e entrega o laudo assinado por e-mail, para o veterinário e para o tutor.';
const moeda = (centavos: number) =>
    new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: Math.round(centavos) % 100 === 0 ? 0 : 2,
        maximumFractionDigits: 2,
    }).format(Math.round(centavos) / 100);
const descricoes = {
    bancada: { para: 'Para começar ou para o laboratório pequeno', itens: ['Laudo por e-mail e link do tutor', 'Fatura das clínicas'] },
    laboratorio: { para: 'Para quem atende várias clínicas', itens: ['Pedido lido da foto pela IA', 'Tudo do Bancada'] },
    rede: { para: 'Para quem tem mais de uma unidade', itens: ['Tudo do Laboratório'] },
};
const menu = [
    ['como-funciona', 'Como funciona'],
    ['recursos', 'Recursos'],
    ['veterinarios', 'Para veterinários'],
    ['precos', 'Preços'],
    ['duvidas', 'Dúvidas'],
];

function ExemploLaudo() {
    return (
        <figure
            aria-label="Exemplo de tela: liberação de um hemograma do Thor e aviso de laudo enviado para Carla"
            className="relative min-w-0 sm:pb-10"
        >
            <div aria-hidden="true">
                <div className="border-border bg-card shadow-primary/10 overflow-hidden rounded-lg border shadow-xl">
                    <div className="border-border flex flex-wrap items-center justify-between gap-3 border-b p-4 sm:px-5">
                        <div className="flex min-w-0 flex-col gap-0.5">
                            <span className="font-bold">Thor · Hemograma completo</span>
                            <span className="text-muted-foreground text-[13px]">Cão, Golden Retriever, 7 anos · Tutora Carla Mendes</span>
                        </div>
                        <span className="text-muted-foreground font-mono text-[13px]">VD-004812</span>
                    </div>
                    <div className="px-3 pt-1 pb-2 text-xs sm:px-5 sm:text-sm">
                        <div className="border-border text-muted-foreground grid grid-cols-[1.4fr_1fr_1.1fr_.9fr] gap-1 border-b py-2.5 font-semibold sm:gap-2 sm:text-xs">
                            {['Exame', 'Resultado', 'Referência', 'Status'].map((texto) => (
                                <span key={texto}>{texto}</span>
                            ))}
                        </div>
                        {[
                            ['Hemácias', '7,2 ×10⁶/µL', '5,5–8,5', false],
                            ['Hematócrito', '48 %', '37–55', false],
                            ['Leucócitos', '19,8 ×10³/µL', '6,0–17,0', true],
                            ['Plaquetas', '310 ×10³/µL', '200–500', false],
                        ].map(([exame, resultado, referencia, alto]) => (
                            <div
                                key={String(exame)}
                                className="border-border grid grid-cols-[1.4fr_1fr_1.1fr_.9fr] items-center gap-1 border-b py-2.5 last:border-0 sm:gap-2"
                            >
                                <span>{exame}</span>
                                <span className={cn('font-mono', alto && 'font-bold')}>{resultado}</span>
                                <span className="text-muted-foreground font-mono sm:text-[13px]">{referencia}</span>
                                <StatusBadge
                                    symbol={false}
                                    tone={alto ? 'warn' : 'ok'}
                                    className="justify-self-start px-1 text-[11px] sm:px-2 sm:text-xs"
                                >
                                    {alto ? '↑ Alto' : 'Normal'}
                                </StatusBadge>
                            </div>
                        ))}
                    </div>
                    <div className="bg-secondary mx-4 mb-4 flex gap-2.5 rounded-md p-3 text-[13px] leading-[19px] sm:mx-5">
                        <AiBadge className="h-fit shrink-0">IA</AiBadge>
                        <span>Leucócitos subiram desde o exame de 12/09/2026 (14,2). Vale conferir o diferencial antes de liberar.</span>
                    </div>
                    <div className="border-border bg-background flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3.5">
                        <span className="text-muted-foreground text-[13px]">RT: Dra. Marina Alves · CRMV-SP 12345</span>
                        <span className="bg-primary text-primary-foreground inline-flex min-h-10 items-center rounded-md px-4 text-sm font-semibold">
                            Liberar laudo
                        </span>
                    </div>
                </div>
                <div className="border-border bg-card shadow-primary/10 flex items-center gap-3 rounded-lg border px-4 py-3 text-[13px] shadow-lg max-sm:mt-3 max-sm:w-fit sm:absolute sm:bottom-0 sm:-left-4">
                    <span className="bg-ok-100 text-ok-600 flex size-8 items-center justify-center rounded-full">
                        <Check className="size-[18px]" />
                    </span>
                    <span className="flex flex-col">
                        <span className="font-semibold">Laudo enviado para Carla</span>
                        <span className="text-muted-foreground">E-mail e link com código · 14:32</span>
                    </span>
                </div>
            </div>
        </figure>
    );
}

function Precos({ planos }: { planos: Plano[] }) {
    const [cobranca, setCobranca] = useState<Cobranca>('mensal');
    return (
        <section id="precos" aria-labelledby="precos-titulo" className={cn(area, 'flex scroll-mt-6 flex-col gap-8')}>
            <div className="flex flex-wrap items-end justify-between gap-6">
                <div className="flex max-w-[640px] flex-col gap-3">
                    <span className={rotulo}>Preços</span>
                    <h2 id="precos-titulo" className={titulo}>
                        Você paga por pedido, não por exame
                    </h2>
                    <p className="text-muted-foreground text-[17px] leading-[27px]">
                        Um pedido com hemograma, bioquímico e urinálise conta como um. Passou do limite do plano, cada pedido a mais custa R$ 0,25.
                    </p>
                </div>
                <SegmentedControl
                    label="Forma de cobrança"
                    value={cobranca}
                    onChange={setCobranca}
                    options={[
                        { value: 'mensal', label: 'Mensal' },
                        { value: 'anual', label: 'Anual: 2 meses grátis' },
                    ]}
                    className="max-w-full [&_button]:min-h-11"
                />
            </div>
            <div className="grid gap-5 md:grid-cols-3">
                {planos.map((plano) => {
                    const destaque = plano.value === 'laboratorio';
                    const itens = [
                        `${plano.pedidos.toLocaleString('pt-BR')} pedidos por mês`,
                        plano.usuarios === null ? 'Usuários ilimitados' : `${plano.usuarios} usuários`,
                        ...(plano.unidades > 1 ? [`Até ${plano.unidades} unidades`] : []),
                        ...descricoes[plano.value].itens,
                    ];
                    return (
                        <article
                            key={plano.value}
                            className={cn(
                                'bg-card flex min-w-0 flex-col gap-3 rounded-lg border p-7',
                                destaque ? 'border-primary border-2' : 'border-border',
                            )}
                        >
                            <div className="flex min-h-[26px] flex-wrap items-center justify-between gap-2">
                                <h3 className="font-display text-xl font-bold">{plano.nome}</h3>
                                {destaque && (
                                    <span className="bg-accent-400 text-ink-on-accent rounded-full px-2.5 py-0.5 text-xs font-bold">
                                        Mais escolhido
                                    </span>
                                )}
                            </div>
                            <p className="text-muted-foreground text-sm">{descricoes[plano.value].para}</p>
                            <div className="flex flex-wrap items-baseline gap-1.5">
                                <span className="font-display text-primary text-[44px] leading-[48px] font-bold">
                                    {moeda(cobranca === 'anual' ? plano.anual / 12 : plano.mensal)}
                                </span>
                                <span className="text-muted-foreground text-sm">por mês</span>
                            </div>
                            <p className="text-muted-foreground min-h-5 text-[13px]">
                                {cobranca === 'anual' ? `${moeda(plano.anual)} por ano, à vista` : `ou ${moeda(plano.anual)} no plano anual`}
                            </p>
                            <ul className="border-border mt-2 mb-3 flex flex-col gap-2.5 border-t pt-4 text-[15px]">
                                {itens.map((item) => (
                                    <li key={item} className="flex gap-2.5">
                                        <Check aria-hidden="true" className="text-ok-600 mt-0.5 size-[18px] shrink-0" />
                                        <span>{item}</span>
                                    </li>
                                ))}
                            </ul>
                            <Button
                                asChild
                                variant={destaque ? 'primary' : 'secondary'}
                                className="mt-auto h-auto min-h-12 px-3 py-2 whitespace-normal"
                            >
                                <Link href={route('cadastro', { plano: plano.value, cobranca })}>Começar teste no {plano.nome}</Link>
                            </Button>
                        </article>
                    );
                })}
            </div>
            <p className="text-muted-foreground text-sm">
                Todos os planos com 14 dias de teste, sem taxa de implantação e com todos os recursos. Clínicas, veterinários e tutores não pagam o
                Vidra.
            </p>
        </section>
    );
}

export default function Inicio({ planos }: { planos: Plano[] }) {
    const { auth } = usePage<SharedData>().props;
    return (
        <div className="bg-background text-foreground">
            <Head title="Sistema para laboratórios veterinários">
                <meta name="description" content={subtitulo} />
            </Head>
            <header className="border-border bg-card border-b">
                <div className="mx-auto flex max-w-[1200px] flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-3 sm:px-6">
                    <a href="#topo" aria-label="Vidra Pet, início" className={cn(foco, 'inline-flex min-h-11 items-center gap-2')}>
                        <VidraLogo line="pet" className="text-primary h-7" />
                        <span className="bg-pet-500 text-ink-on-accent rounded-sm px-2 py-0.5 text-xs font-bold">Pet</span>
                    </a>
                    <nav aria-label="Seções" className="hidden flex-wrap gap-x-6 gap-y-1 text-[15px] font-medium md:flex">
                        {menu.map(([id, texto]) => (
                            <a key={id} href={`#${id}`} className={cn(foco, 'hover:text-brand-500 inline-flex min-h-11 items-center')}>
                                {texto}
                            </a>
                        ))}
                    </nav>
                    <div className="flex items-center gap-1 sm:gap-2">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={route('dashboard')}>Ir para o painel</Link>
                            </Button>
                        ) : (
                            <>
                                <Button variant="ghost" asChild className="px-2 text-xs sm:px-4 sm:text-sm">
                                    <Link href={route('login')}>Entrar</Link>
                                </Button>
                                <Button asChild className="px-2 text-xs sm:px-4 sm:text-sm">
                                    <Link href={route('cadastro')}>Testar 14 dias grátis</Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            </header>
            <main id="topo">
                <section aria-labelledby="hero-titulo" className={cn(area, 'grid items-center gap-14 pt-18 lg:grid-cols-2')}>
                    <div className="flex min-w-0 flex-col gap-6">
                        <span className="bg-secondary text-primary inline-flex items-center gap-2 self-start rounded-full px-3 py-1 text-[13px] font-semibold">
                            <span aria-hidden="true" className="bg-pet-500 size-2 shrink-0 rounded-full" />
                            Sistema para laboratórios veterinários
                        </span>
                        <h1
                            id="hero-titulo"
                            className="font-display text-primary text-[44px] leading-[48px] font-bold tracking-tight sm:text-[60px] sm:leading-[64px]"
                        >
                            Do pedido ao laudo, no mesmo dia.
                        </h1>
                        <p className="text-muted-foreground max-w-[540px] text-[19px] leading-[30px]">{subtitulo}</p>
                        <div className="flex flex-wrap gap-3">
                            <Button asChild size="lg">
                                <Link href={route('cadastro')}>Testar 14 dias grátis</Link>
                            </Button>
                            <Button asChild size="lg" variant="secondary">
                                <a href="#como-funciona">Ver como funciona</a>
                            </Button>
                        </div>
                        <ul className="text-muted-foreground flex flex-wrap gap-x-6 gap-y-2 text-sm">
                            {['Sem taxa de implantação', 'Sem cartão para testar', 'Preço por pedido, não por exame'].map((texto) => (
                                <li key={texto} className="flex items-center gap-1.5">
                                    <Check aria-hidden="true" className="text-ok-600 size-4 shrink-0" />
                                    {texto}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <ExemploLaudo />
                </section>
                <section id="como-funciona" aria-labelledby="fluxo-titulo" className="border-border bg-card scroll-mt-6 border-y">
                    <div className={cn(area, 'flex flex-col gap-10')}>
                        <div className="flex max-w-[680px] flex-col gap-3">
                            <span className={rotulo}>Como funciona</span>
                            <h2 id="fluxo-titulo" className={titulo}>
                                Um caminho só, do balcão à fatura
                            </h2>
                            <p className="text-muted-foreground text-[17px] leading-[27px]">
                                Cada pedido anda pelas mesmas etapas, e todo mundo vê em qual ele está. Nada de planilha paralela, nada de laudo
                                perdido no WhatsApp.
                            </p>
                        </div>
                        <ol className="grid gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
                            {etapas.map(([nome, texto], indice) => (
                                <li key={nome} className="bg-background flex flex-col gap-2 rounded-lg p-5">
                                    <span className="text-brand-500 font-mono text-[13px]">{String(indice + 1).padStart(2, '0')}</span>
                                    <span className="text-[17px] font-bold">{nome}</span>
                                    <span className="text-muted-foreground text-sm leading-[21px]">{texto}</span>
                                </li>
                            ))}
                        </ol>
                    </div>
                </section>
                <section id="recursos" aria-labelledby="recursos-titulo" className={cn(area, 'flex scroll-mt-6 flex-col gap-10')}>
                    <div className="flex max-w-[680px] flex-col gap-3">
                        <span className={rotulo}>Recursos</span>
                        <h2 id="recursos-titulo" className={titulo}>
                            Feito para a rotina de um laboratório veterinário
                        </h2>
                    </div>
                    <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        {recursos.map(([nome, texto], indice) => {
                            const Icone = icones[indice];
                            return (
                                <article key={nome} className="border-border bg-card flex flex-col gap-3 rounded-lg border p-6">
                                    <span className="bg-secondary text-primary flex size-11 items-center justify-center rounded-[10px]">
                                        <Icone aria-hidden="true" className="size-[22px]" />
                                    </span>
                                    <h3 className="font-display text-lg leading-[26px] font-bold">{nome}</h3>
                                    <p className="text-muted-foreground text-[15px] leading-[23px]">{texto}</p>
                                </article>
                            );
                        })}
                    </div>
                </section>
                <section id="veterinarios" aria-labelledby="publicos-titulo" className="bg-primary text-primary-foreground scroll-mt-6">
                    <div className={cn(area, 'flex flex-col gap-10')}>
                        <div className="flex max-w-[680px] flex-col gap-3">
                            <span className="text-accent-400 dark:text-primary-foreground text-[13px] font-bold tracking-wider uppercase">
                                Quem usa
                            </span>
                            <h2 id="publicos-titulo" className={titulo}>
                                O laboratório assina. Veterinário e tutor recebem, sem pagar nada.
                            </h2>
                        </div>
                        <div className="grid gap-5 md:grid-cols-3">
                            {publicos.map(([dominio, nome, texto]) => (
                                <div
                                    key={nome}
                                    className="border-primary-foreground/20 bg-primary-foreground/10 flex flex-col gap-2.5 rounded-lg border p-6"
                                >
                                    <span className="text-primary-foreground/70 font-mono text-[13px]">{dominio}</span>
                                    <h3 className="font-display text-xl font-bold">{nome}</h3>
                                    <p className="text-primary-foreground/90 text-[15px] leading-[23px]">{texto}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
                <Precos planos={planos} />
                <section id="duvidas" aria-labelledby="duvidas-titulo" className="border-border bg-card scroll-mt-6 border-t">
                    <div className="mx-auto flex max-w-[800px] flex-col gap-6 px-6 py-20">
                        <h2 id="duvidas-titulo" className={titulo}>
                            Dúvidas comuns
                        </h2>
                        <div>
                            {duvidas.map(([pergunta, resposta], indice) => (
                                <details key={pergunta} open={indice === 0} className="border-border border-b">
                                    <summary
                                        className={cn(
                                            foco,
                                            'flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 py-4 text-[17px] font-semibold [&::-webkit-details-marker]:hidden',
                                        )}
                                    >
                                        {pergunta}
                                        <span aria-hidden="true" className="text-brand-500 text-[22px]">
                                            +
                                        </span>
                                    </summary>
                                    <p className="text-muted-foreground mb-5">{resposta}</p>
                                </details>
                            ))}
                        </div>
                    </div>
                </section>
                <section aria-labelledby="cta-titulo" className={area}>
                    <div className="bg-secondary flex flex-wrap items-center justify-between gap-6 rounded-2xl p-6 sm:p-12">
                        <div className="flex max-w-[620px] flex-col gap-2">
                            <h2 id="cta-titulo" className="font-display text-primary text-3xl font-bold tracking-tight sm:text-4xl sm:leading-[42px]">
                                Libere o primeiro laudo ainda nesta semana
                            </h2>
                            <p className="text-muted-foreground text-[17px] leading-[27px]">
                                Cadastre o laboratório, o responsável técnico e o catálogo de exames. Leva menos de uma tarde.
                            </p>
                        </div>
                        <Button asChild size="lg" className="h-auto min-h-13 py-3 whitespace-normal">
                            <Link href={route('cadastro')}>Cadastrar meu laboratório</Link>
                        </Button>
                    </div>
                </section>
            </main>
            <footer className="border-border bg-card border-t">
                <div className="text-muted-foreground mx-auto flex max-w-[1200px] flex-wrap items-center justify-between gap-4 px-6 py-8 text-sm">
                    <span className="flex flex-wrap gap-x-4 gap-y-1">
                        <span>Vidra Pet · sistema para laboratórios veterinários</span>
                        <span>Vidra Clínica, para laboratórios de análises clínicas: em breve</span>
                    </span>
                    <span className="flex flex-wrap gap-4">
                        {['Privacidade', 'Termos', 'Contato'].map((texto) => (
                            <a key={texto} href="#" className={cn(foco, 'text-brand-500 hover:text-primary inline-flex min-h-11 items-center')}>
                                {texto}
                            </a>
                        ))}
                    </span>
                </div>
            </footer>
        </div>
    );
}

const icones = [Camera, Barcode, Activity, FileCheck, Mail, ReceiptText];
const etapas = [
    ['Pedido', 'Digitado no balcão ou lido da foto da requisição pela IA.'],
    ['Amostra', 'Etiqueta com código de barras e triagem pelo leitor.'],
    ['Bancada', 'Resultados conferidos com a referência da espécie, na hora.'],
    ['Liberação', 'O RT revisa, assina com certificado ICP-Brasil e libera.'],
    ['Entrega', 'Laudo em PDF por e-mail, link para o tutor e acesso do veterinário.'],
    ['Fatura', 'No fim do mês, a fatura de cada clínica sai pronta.'],
];
const recursos = [
    ['Pedido lido da foto', 'Fotografe a requisição da clínica. A IA preenche animal, tutor, veterinário e exames, e o atendente só confere.'],
    ['Etiqueta e leitor de código', 'Imprime a etiqueta Code 128 do tubo e funciona com qualquer leitor USB. Bipou, a amostra está na triagem.'],
    [
        'Referência por espécie, raça e idade',
        'Valor fora da faixa, valor crítico e mudança brusca em relação ao exame anterior aparecem antes da liberação.',
    ],
    ['Laudo assinado pelo RT', 'Só o responsável técnico libera. O PDF sai com a marca do laboratório e assinatura digital ICP-Brasil.'],
    ['Entrega sem telefonema', 'E-mail automático, link com código de acesso para o tutor e um botão para compartilhar no WhatsApp.'],
    ['Fatura mensal das clínicas', 'Cada pedido já sabe quem paga: clínica, veterinário ou tutor. No dia do fechamento, a fatura vai por e-mail.'],
];
const publicos = [
    ['app.vidralab.com.br', 'Laboratório', 'Recepção, bancada, RT e financeiro na mesma tela, cada um com o seu papel.'],
    [
        'vet.vidralab.com.br',
        'Veterinário',
        'Um login só, mesmo pedindo exames a mais de um laboratório. Acompanha o pedido e baixa o laudo assim que sai.',
    ],
    ['laudo.vidralab.com.br', 'Tutor', 'Abre o laudo do Thor pelo link ou QR code, com um resumo em linguagem simples. Sem cadastro.'],
];
const duvidas = [
    [
        'Preciso instalar alguma coisa?',
        'Não. O Vidra roda no navegador. O leitor de código de barras USB funciona como teclado, sem driver. A integração direta com equipamentos chega na próxima fase.',
    ],
    ['O que conta como um pedido?', 'Um atendimento: um animal, numa data, com quantos exames forem pedidos juntos.'],
    [
        'A IA libera laudo sozinha?',
        'Nunca. A IA lê a foto do pedido e aponta o que merece atenção. Quem confere e libera é sempre o responsável técnico.',
    ],
    [
        'E os dados do tutor?',
        'O laudo só vai por e-mail com a autorização do tutor, registrada com data, hora e atendente, como pede a LGPD. Os arquivos ficam no Brasil.',
    ],
    ['O que acontece depois dos 14 dias?', 'Você escolhe o plano. Se não escolher, a conta fica só para consulta e nada se perde.'],
];
