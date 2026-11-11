import { Alert, Button, FormField, TextInput } from '@/components/vidra';
import { useForm } from '@inertiajs/react';
import AuthFrame from './auth-frame';

export default function NovaSenha({ token, email }: { token: string; email: string }) {
    const f = useForm({ email, senha: '', senha_confirmation: '' });
    return (
        <AuthFrame title="Criar nova senha">
            <form
                className="flex flex-col gap-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    f.post(route('vet.senha.salvar', { token }), { onFinish: () => f.reset('senha', 'senha_confirmation') });
                }}
            >
                {f.errors.email && <Alert tone="crit" title={f.errors.email} />}
                <FormField id="email" label="E-mail">
                    <TextInput
                        className="h-11"
                        type="email"
                        required
                        autoComplete="username"
                        value={f.data.email}
                        onChange={(e) => f.setData('email', e.target.value)}
                    />
                </FormField>
                <FormField id="senha" label="Nova senha (pelo menos 10 caracteres)" error={f.errors.senha}>
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
                <FormField id="senha_confirmation" label="Repetir nova senha">
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
                    Salvar nova senha
                </Button>
            </form>
        </AuthFrame>
    );
}
