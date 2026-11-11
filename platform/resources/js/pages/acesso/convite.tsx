import { Alert, Button, FormField, TextInput } from '@/components/vidra';
import AuthLayout from '@/layouts/auth-layout';
import { type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

type Convite = { nome: string; email: string; papel: string; laboratorio: string; temConta: boolean };

export default function Convite({ token, convite, erro }: { token: string; convite: Convite | null; erro: string | null }) {
    const { auth, errors } = usePage<SharedData>().props;
    const usuario = auth.user;
    const form = useForm({ nome: convite?.nome ?? '', password: '', password_confirmation: '' });
    const mesmaConta = usuario && usuario.email.trim().toLowerCase() === convite?.email;
    return (
        <AuthLayout
            title={erro ? 'Convite indisponível' : `Convite para o ${convite?.laboratorio}`}
            description={
                erro ? 'Peça um novo link ao administrador do laboratório.' : 'Sua equipe usa o Vidra para cuidar de cada etapa do laboratório.'
            }
        >
            <Head title="Aceitar convite" />
            {erro ? (
                <Alert tone="warn" title={erro} />
            ) : (
                convite && (
                    <div className="space-y-6">
                        <p className="text-muted-foreground text-sm break-words">
                            Convite para <strong className="text-foreground">{convite.email}</strong>, com o papel de {convite.papel}.
                        </p>
                        {errors.form && <Alert tone="crit" title={errors.form} />}
                        {usuario && !mesmaConta ? (
                            <div className="space-y-4">
                                <Alert
                                    tone="warn"
                                    title="Você entrou com outro e-mail."
                                >{`Saia da conta ${usuario.email} e entre com ${convite.email} para aceitar este convite.`}</Alert>
                                <Button variant="secondary" asChild>
                                    <Link href={route('logout')} method="post" as="button">
                                        Sair desta conta
                                    </Link>
                                </Button>
                            </div>
                        ) : !usuario && convite.temConta ? (
                            <div className="space-y-4">
                                <p className="text-sm">Você já tem uma conta. Entre com o e-mail que recebeu o convite para continuar.</p>
                                <Button asChild>
                                    <Link href={route('login')}>Entrar na minha conta</Link>
                                </Button>
                            </div>
                        ) : (
                            <form
                                className="space-y-4"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    form.post(route('convite.aceitar', { token }), {
                                        onFinish: () => form.reset('password', 'password_confirmation'),
                                    });
                                }}
                            >
                                {!usuario && (
                                    <>
                                        <FormField id="nome" label="Seu nome" error={form.errors.nome}>
                                            <TextInput
                                                autoComplete="name"
                                                required
                                                maxLength={255}
                                                value={form.data.nome}
                                                onChange={(e) => form.setData('nome', e.target.value)}
                                            />
                                        </FormField>
                                        <FormField
                                            id="password"
                                            label="Crie sua senha"
                                            hint="Use pelo menos 10 caracteres."
                                            error={form.errors.password}
                                        >
                                            <TextInput
                                                type="password"
                                                autoComplete="new-password"
                                                required
                                                minLength={10}
                                                maxLength={255}
                                                value={form.data.password}
                                                onChange={(e) => form.setData('password', e.target.value)}
                                            />
                                        </FormField>
                                        <FormField id="password_confirmation" label="Confirme sua senha" error={form.errors.password_confirmation}>
                                            <TextInput
                                                type="password"
                                                autoComplete="new-password"
                                                required
                                                value={form.data.password_confirmation}
                                                onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                            />
                                        </FormField>
                                    </>
                                )}
                                <Button type="submit" loading={form.processing} className="w-full">
                                    {usuario ? `Entrar no ${convite.laboratorio}` : 'Criar conta e aceitar convite'}
                                </Button>
                            </form>
                        )}
                    </div>
                )
            )}
        </AuthLayout>
    );
}
