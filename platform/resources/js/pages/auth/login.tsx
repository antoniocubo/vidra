import { Alert, Button, Checkbox, FormField, IconButton, TextInput, VidraLogo } from '@/components/vidra';
import { useTemaClaro } from '@/hooks/use-appearance';
import { Head, Link, useForm } from '@inertiajs/react';
import { Check, Eye, EyeOff } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

type LoginProps = {
    status?: string;
    canResetPassword: boolean;
    /** Endereço do acesso do veterinário, vindo de config/vidra.php. */
    vetUrl: string;
};

const beneficios = [
    'Amostra com etiqueta, bancada e liberação pelo RT no mesmo lugar.',
    'Laudo por e-mail, link para o tutor e acesso do veterinário.',
    'Fatura mensal das clínicas fechada sem planilha.',
];

const linkClasses =
    'text-brand-500 hover:text-primary focus-visible:outline-brand-500 rounded-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2';

export default function Login({ status, canResetPassword, vetUrl }: LoginProps) {
    useTemaClaro();
    const [mostrarSenha, setMostrarSenha] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const erroGeral = errors.email ?? errors.password;

    return (
        <div className="bg-background text-foreground flex min-h-screen flex-wrap">
            <Head title="Entrar no laboratório" />

            <aside
                aria-label="Sobre o Vidra"
                className="bg-sidebar text-sidebar-foreground flex min-w-0 flex-[1_1_440px] flex-col justify-between gap-12 p-8 md:p-12"
            >
                <VidraLogo line="marca" className="h-8" />
                <div className="flex max-w-[480px] flex-col gap-6">
                    <h2 className="font-display text-4xl leading-tight font-bold tracking-tight md:text-[44px] md:leading-[50px]">
                        Do pedido ao laudo, no mesmo dia.
                    </h2>
                    <ul className="flex flex-col gap-3 text-[15px] leading-[22px]">
                        {beneficios.map((texto) => (
                            <li key={texto} className="flex gap-2.5">
                                <Check aria-hidden="true" className="text-accent-400 mt-0.5 size-4 shrink-0" strokeWidth={3} />
                                <span>{texto}</span>
                            </li>
                        ))}
                    </ul>
                </div>
                <span className="text-xs opacity-75">Vidra Pet · laboratórios veterinários</span>
            </aside>

            <main className="flex min-w-0 flex-[1_1_480px] flex-col items-center justify-center gap-8 px-4 py-12 sm:px-8">
                <form onSubmit={submit} className="flex w-full max-w-[400px] flex-col gap-5">
                    <div className="flex flex-col gap-1">
                        <h1 className="font-display text-[32px] leading-[38px] font-bold">Entrar no laboratório</h1>
                        <span className="text-muted-foreground text-sm">Use o e-mail e a senha da sua conta no Vidra.</span>
                    </div>

                    {status && <Alert tone="ok" title={status} />}

                    {erroGeral && (
                        <Alert tone="crit" title={erroGeral}>
                            Confira e tente de novo. Depois de 5 tentativas, o acesso fica bloqueado por alguns minutos.
                        </Alert>
                    )}

                    <FormField id="email" label="E-mail">
                        <TextInput
                            type="email"
                            required
                            autoFocus
                            autoComplete="username"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            aria-invalid={erroGeral ? true : undefined}
                        />
                    </FormField>

                    <div className="flex flex-col gap-1.5">
                        <div className="flex items-baseline justify-between gap-2">
                            <label htmlFor="password" className="text-muted-foreground text-xs font-semibold">
                                Senha
                            </label>
                            {canResetPassword && (
                                <Link href={route('password.request')} className={`${linkClasses} text-xs`}>
                                    Esqueci minha senha
                                </Link>
                            )}
                        </div>
                        <div className="relative">
                            <TextInput
                                id="password"
                                type={mostrarSenha ? 'text' : 'password'}
                                required
                                autoComplete="current-password"
                                className="pr-12"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                aria-invalid={erroGeral ? true : undefined}
                            />
                            <IconButton
                                variant="ghost"
                                size="sm"
                                icon={mostrarSenha ? EyeOff : Eye}
                                label={mostrarSenha ? 'Esconder senha' : 'Mostrar senha'}
                                aria-pressed={mostrarSenha}
                                onClick={() => setMostrarSenha((atual) => !atual)}
                                className="absolute top-1 right-1"
                            />
                        </div>
                    </div>

                    <Checkbox
                        label="Manter conectado neste computador"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                    />

                    <Button type="submit" size="lg" loading={processing} className="w-full">
                        Entrar
                    </Button>

                    <div className="bg-card border-border flex flex-col gap-1 rounded-lg border p-4">
                        <span className="font-semibold">Seu laboratório ainda não usa o Vidra?</span>
                        <span className="text-muted-foreground">Teste por 14 dias, sem taxa de implantação.</span>
                        <Link href={route('cadastro')} className={`${linkClasses} mt-1.5`}>
                            Cadastrar meu laboratório →
                        </Link>
                    </div>

                    <span className="text-muted-foreground text-[13px]">
                        É veterinário e pede exames?{' '}
                        <a href={vetUrl} className={linkClasses}>
                            Entre pelo acesso do veterinário
                        </a>
                        .
                    </span>
                </form>

                <footer className="text-muted-foreground flex w-full max-w-[400px] flex-wrap justify-between gap-2 text-xs">
                    <span className="font-mono">app.vidralab.com.br</span>
                    <span className="flex gap-4">
                        <a href="/privacidade" className={linkClasses}>
                            Privacidade
                        </a>
                        <a href="/ajuda" className={linkClasses}>
                            Ajuda
                        </a>
                    </span>
                </footer>
            </main>
        </div>
    );
}
