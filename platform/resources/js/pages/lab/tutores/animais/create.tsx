import { Alert, Button, FormActions, PageHeader } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { AnimalForm, animalVazio } from '../animal-form';
import { AnimalPreview } from '../animal-preview';
type Props = {
    tutor: { id: string; nome: string; autorizadoEm: string | null };
    vinculos: { id: string; nome: string }[];
    perfis: { chave: string; nome: string; grupo: string }[];
    hoje: string;
};
export default function AdicionarAnimal({ tutor, vinculos, perfis, hoje }: Props) {
    const form = useForm(animalVazio());
    const errors: Record<string, string | undefined> = form.errors;
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Tutores', href: route('lab.tutores.index') },
                { title: 'Novo animal', href: '#' },
            ]}
        >
            <Head title="Adicionar animal" />
            <div className="space-y-6 p-4 md:p-8">
                <PageHeader eyebrow={tutor.nome} title="Adicionar animal" />
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(route('lab.tutores.animais.store', { tutor: tutor.id }));
                    }}
                    className="grid items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]"
                >
                    <div className="min-w-0 space-y-4">
                        <AnimalForm animal={form.data} onChange={(a) => form.setData(a)} errors={errors} vinculos={vinculos} hoje={hoje} />
                        {errors.form && <Alert tone="crit" title={errors.form} />}
                        <FormActions>
                            <Button variant="secondary" asChild>
                                <Link href={route('lab.tutores.index', { tutor: tutor.id })}>Voltar ao tutor</Link>
                            </Button>
                            <Button type="submit" loading={form.processing}>
                                Salvar animal
                            </Button>
                        </FormActions>
                    </div>
                    <AnimalPreview
                        animais={[form.data]}
                        nome={tutor.nome}
                        autoriza={!!tutor.autorizadoEm}
                        vinculos={vinculos}
                        perfis={perfis}
                        hoje={hoje}
                    />
                </form>
            </div>
        </AppLayout>
    );
}
