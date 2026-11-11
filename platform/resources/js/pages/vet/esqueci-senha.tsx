import { Alert, Button, FormField, TextInput } from '@/components/vidra';
import { useForm } from '@inertiajs/react';
import AuthFrame from './auth-frame';

export default function EsqueciSenha({ status }: { status?: string }) {
    const f = useForm({ email: '' });
    return (
        <AuthFrame title="Esqueci minha senha">
            <p className="text-muted-foreground">Informe o e-mail do seu acesso de veterinário.</p>
            {status && <Alert tone="ok" title={status} />}
            <form
                className="flex flex-col gap-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    f.post(route('vet.senha.enviar'));
                }}
            >
                <FormField id="email" label="E-mail" error={f.errors.email}>
                    <TextInput
                        className="h-11"
                        type="email"
                        autoComplete="email"
                        required
                        value={f.data.email}
                        onChange={(e) => f.setData('email', e.target.value)}
                    />
                </FormField>
                <Button type="submit" loading={f.processing}>
                    Receber link para nova senha
                </Button>
            </form>
        </AuthFrame>
    );
}
