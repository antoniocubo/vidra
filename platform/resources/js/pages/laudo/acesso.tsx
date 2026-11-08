import { Button, FormField, TextInput } from '@/components/vidra';
import { useTemaClaro } from '@/hooks/use-appearance';
import { Head } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

// Superfície calma: laboratório e paciente guiam a leitura, resultados em listas e ações ao final.
// O formulário tem foco visível; as ações usam as transições discretas do design system.
export default function Acesso({ erro }: { erro?: string }) {
    useTemaClaro();
    const [codigo, setCodigo] = useState('');
    function acessar(event: FormEvent) {
        event.preventDefault();
        window.location.assign(route('laudo.show', { codigo: codigo.trim().replaceAll('-', '').toUpperCase() }));
    }
    return (
        <main className="bg-background text-foreground flex min-h-svh items-center justify-center p-5">
            <Head title="Acessar laudo">
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <section className="border-border bg-card w-full max-w-md space-y-6 rounded-xl border p-6 sm:p-8">
                <div>
                    <p className="text-brand-900 text-sm font-semibold">Laudos · Vidra</p>
                    <h1 className="font-display mt-3 text-3xl font-bold">Acesse seu laudo</h1>
                </div>
                <p className="text-muted-foreground text-sm">
                    O código de acesso está no e-mail e no comprovante do pedido. Digite com ou sem hífen.
                </p>
                <form onSubmit={acessar} className="space-y-5">
                    <FormField id="codigo" label="Código de acesso" error={erro}>
                        <TextInput
                            mono
                            name="codigo"
                            value={codigo}
                            onChange={(e) => setCodigo(e.target.value)}
                            required
                            maxLength={9}
                            autoCapitalize="characters"
                            autoComplete="off"
                            spellCheck={false}
                            placeholder="K7Q4-M2PX"
                        />
                    </FormField>
                    <Button type="submit" className="w-full" disabled={!codigo.trim()}>
                        Ver laudo
                    </Button>
                </form>
            </section>
        </main>
    );
}
