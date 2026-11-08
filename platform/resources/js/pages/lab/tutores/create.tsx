import { Alert, Button, FormActions, PageHeader } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { AnimalForm, animalVazio } from './animal-form';
import { AnimalPreview } from './animal-preview';
import { ContatoFields, type Contato } from './contato-fields';
type Props = {
    celular: string;
    voltar: string;
    vinculos: { id: string; nome: string }[];
    perfis: { chave: string; nome: string; grupo: string }[];
    hoje: string;
};
export default function CadastrarTutor({ celular, voltar, vinculos, perfis, hoje }: Props) {
    const voltaAoPedido = voltar === 'pedido';
    const form = useForm<Contato & { animais: ReturnType<typeof animalVazio>[]; voltar: string }>({
        nome: '',
        celular,
        email: '',
        cpf: '',
        cep: '',
        autoriza_email: false,
        animais: [animalVazio()],
        voltar,
    });
    const errors: Record<string, string | undefined> = form.errors;
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Tutores', href: route('lab.tutores.index') },
                { title: 'Novo tutor', href: route('lab.tutores.create') },
            ]}
        >
            <Head title="Novo tutor" />
            <div className="space-y-6 p-4 md:p-8">
                <PageHeader
                    eyebrow="Cadastros"
                    title="Novo tutor e animal"
                    description="Cadastre o contato do tutor e os animais que ele acompanha."
                />
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(route('lab.tutores.store'));
                    }}
                    className="grid items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]"
                >
                    <div className="min-w-0 space-y-4">
                        <ContatoFields data={form.data} onChange={(data) => form.setData({ ...form.data, ...data })} errors={errors} />
                        {form.data.animais.map((animal, i) => (
                            <AnimalForm
                                key={i}
                                title={`Animal ${i + 1}`}
                                animal={animal}
                                onChange={(a) =>
                                    form.setData(
                                        'animais',
                                        form.data.animais.map((old, j) => (j === i ? a : old)),
                                    )
                                }
                                vinculos={vinculos}
                                errors={errors}
                                prefix={`animais.${i}.`}
                                hoje={hoje}
                            />
                        ))}
                        <Button
                            variant="secondary"
                            type="button"
                            onClick={() =>
                                form.setData(
                                    'animais',
                                    form.data.animais.length === 1 ? [...form.data.animais, animalVazio()] : form.data.animais.slice(0, 1),
                                )
                            }
                        >
                            {form.data.animais.length === 1 ? '+ Adicionar outro animal' : 'Remover segundo animal'}
                        </Button>
                        {(errors.form || errors.animais) && <Alert tone="crit" title={errors.form || errors.animais || ''} />}
                        <FormActions>
                            <Button variant="secondary" asChild>
                                <Link href={voltaAoPedido ? route('lab.pedidos.create') : route('lab.tutores.index')}>Cancelar cadastro</Link>
                            </Button>
                            <Button type="submit" loading={form.processing}>
                                {voltaAoPedido ? 'Salvar e voltar ao pedido' : 'Salvar tutor e animais'}
                            </Button>
                        </FormActions>
                    </div>
                    <AnimalPreview
                        animais={form.data.animais}
                        nome={form.data.nome}
                        autoriza={form.data.autoriza_email}
                        vinculos={vinculos}
                        perfis={perfis}
                        hoje={hoje}
                    />
                </form>
            </div>
        </AppLayout>
    );
}
