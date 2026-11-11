import { Alert, Button, FormField, TextInput } from '@/components/vidra';
import { useForm } from '@inertiajs/react';
import AuthFrame from './auth-frame';

export default function Convite({
    token,
    convite,
    erro,
}: {
    token: string;
    convite: { nome: string; email: string; laboratorio: string } | null;
    erro: string | null;
}) {
    const f = useForm({ senha: '', senha_confirmation: '', form: '' });
    return (
        <AuthFrame title="Acesso do veterinário">
            {erro && <Alert tone="crit" title={erro} />}
            {convite && (
                <>
                    <p>
                        Olá, {convite.nome}. {convite.laboratorio} liberou seu acesso aos pedidos e laudos no Vidra.
                    </p>
                    <p className="text-muted-foreground">Seu e-mail: {convite.email}</p>
                    <form
                        className="flex flex-col gap-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            f.post(route('vet.convite.store', { token }), { onFinish: () => f.reset('senha', 'senha_confirmation') });
                        }}
                    >
                        {f.errors.form && <Alert tone="crit" title={f.errors.form} />}
                        <FormField id="senha" label="Senha (pelo menos 10 caracteres)" error={f.errors.senha}>
                            <TextInput
                                className="h-11"
                                type="password"
                                required
                                minLength={10}
                                autoComplete="new-password"
                                value={f.data.senha}
                                onChange={(e) => f.setData('senha', e.target.value)}
                            />
                        </FormField>
                        <FormField id="senha_confirmation" label="Repetir senha">
                            <TextInput
                                className="h-11"
                                type="password"
                                required
                                minLength={10}
                                autoComplete="new-password"
                                value={f.data.senha_confirmation}
                                onChange={(e) => f.setData('senha_confirmation', e.target.value)}
                            />
                        </FormField>
                        <Button type="submit" loading={f.processing}>
                            Criar senha e acessar
                        </Button>
                    </form>
                </>
            )}
        </AuthFrame>
    );
}
