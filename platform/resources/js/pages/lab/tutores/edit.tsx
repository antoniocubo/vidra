import { Alert, Button, ConfirmDialog, FormActions, PageHeader } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ContatoFields } from './contato-fields';
type Props = {
    tutor: { id: string; nome: string; celular: string; email: string | null; cpf: string | null; cep: string | null; autorizadoEm: string | null };
};
export default function EditarTutor({ tutor }: Props) {
    const form = useForm({
        nome: tutor.nome,
        celular: tutor.celular,
        email: tutor.email ?? '',
        cpf: tutor.cpf ?? '',
        cep: tutor.cep ?? '',
        autoriza_email: !!tutor.autorizadoEm,
    });
    const [confirmar, setConfirmar] = useState(false);
    const errors: Record<string, string | undefined> = form.errors;
    const salvar = () => form.put(route('lab.tutores.update', { tutor: tutor.id }));
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Tutores', href: route('lab.tutores.index') },
                { title: 'Editar tutor', href: '#' },
            ]}
        >
            <Head title={`Editar ${tutor.nome}`} />
            <form
                className="max-w-3xl space-y-4 p-4 md:p-8"
                onSubmit={(e) => {
                    e.preventDefault();
                    if (tutor.autorizadoEm && !form.data.autoriza_email) setConfirmar(true);
                    else salvar();
                }}
            >
                <PageHeader title="Editar tutor" description="Atualize o contato e a autorização de envio de laudos." />
                <ContatoFields data={form.data} onChange={(d) => form.setData(d)} errors={errors} />
                {errors.form && <Alert tone="crit" title={errors.form} />}
                <FormActions>
                    <Button variant="secondary" asChild>
                        <Link href={route('lab.tutores.index', { tutor: tutor.id })}>Voltar ao tutor</Link>
                    </Button>
                    <Button type="submit" loading={form.processing}>
                        Salvar alterações
                    </Button>
                </FormActions>
            </form>
            <ConfirmDialog
                open={confirmar}
                onOpenChange={setConfirmar}
                title={`Retirar a autorização de ${tutor.nome}?`}
                description="Os laudos deixam de ser enviados por e-mail ao tutor. As alterações de contato também serão salvas."
                confirmLabel="Retirar e salvar alterações"
                loading={form.processing}
                onConfirm={() => {
                    setConfirmar(false);
                    salvar();
                }}
            />
        </AppLayout>
    );
}
