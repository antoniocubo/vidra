import { Alert, Button, FormActions, PageHeader } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import { CLINICA_VAZIA, ClinicaFields } from './clinica-fields';

type Props = { formasDePagamento: string[] };

export default function NovaClinica({ formasDePagamento }: Props) {
    const { data, setData, post, errors, processing } = useForm(CLINICA_VAZIA);

    const salvar: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('lab.clinicas.store'));
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Clínicas', href: route('lab.clinicas.index') },
                { title: 'Nova clínica', href: '#' },
            ]}
        >
            <Head title="Nova clínica" />
            <form onSubmit={salvar} className="flex max-w-3xl flex-col gap-4 p-4 md:p-8">
                <PageHeader
                    eyebrow="Cadastros"
                    title="Nova clínica"
                    description="Clínica parceira que pede exames e recebe a fatura mensal por e-mail."
                />
                <ClinicaFields data={data} setData={setData} errors={errors} formasDePagamento={formasDePagamento} />
                {'form' in errors && <Alert tone="crit" title={String(errors.form)} />}
                <FormActions
                    secondary={
                        <Button variant="secondary" asChild>
                            <Link href={route('lab.clinicas.index')}>Cancelar</Link>
                        </Button>
                    }
                >
                    <Button type="submit" loading={processing}>
                        Salvar clínica
                    </Button>
                </FormActions>
            </form>
        </AppLayout>
    );
}
