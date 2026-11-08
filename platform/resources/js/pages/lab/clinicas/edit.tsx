import { Alert, Button, FormActions, PageHeader } from '@/components/vidra';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import { ClinicaFields } from './clinica-fields';

type Props = {
    clinica: {
        id: string;
        nomeFantasia: string;
        razaoSocial: string | null;
        cnpj: string | null;
        cidade: string;
        bairro: string | null;
        emailDeLaudos: string | null;
        emailDeFaturamento: string;
        telefoneCritico: string | null;
        responsavel: string | null;
        observacaoDeColeta: string | null;
        desconto: number;
        diaDeFechamento: number;
        diaDeVencimento: number;
        formaDePagamento: string;
    };
    formasDePagamento: string[];
};

export default function EditarClinica({ clinica, formasDePagamento }: Props) {
    const { data, setData, put, errors, processing } = useForm({
        nome_fantasia: clinica.nomeFantasia,
        razao_social: clinica.razaoSocial ?? '',
        cnpj: clinica.cnpj ?? '',
        cidade: clinica.cidade,
        bairro: clinica.bairro ?? '',
        email_laudos: clinica.emailDeLaudos ?? '',
        email_faturamento: clinica.emailDeFaturamento,
        telefone_critico: clinica.telefoneCritico ?? '',
        responsavel: clinica.responsavel ?? '',
        observacao_coleta: clinica.observacaoDeColeta ?? '',
        desconto: String(clinica.desconto),
        dia_fechamento: String(clinica.diaDeFechamento),
        dia_vencimento: String(clinica.diaDeVencimento),
        forma_pagamento: clinica.formaDePagamento,
    });

    const salvar: FormEventHandler = (event) => {
        event.preventDefault();
        put(route('lab.clinicas.update', { clinica: clinica.id }));
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Clínicas', href: route('lab.clinicas.index') },
                { title: 'Editar clínica', href: '#' },
            ]}
        >
            <Head title={`Editar ${clinica.nomeFantasia}`} />
            <form onSubmit={salvar} className="flex max-w-3xl flex-col gap-4 p-4 md:p-8">
                <PageHeader eyebrow="Cadastros" title={`Editar ${clinica.nomeFantasia}`} />
                <ClinicaFields data={data} setData={setData} errors={errors} formasDePagamento={formasDePagamento} />
                {'form' in errors && <Alert tone="crit" title={String(errors.form)} />}
                <FormActions
                    secondary={
                        <Button variant="secondary" asChild>
                            <Link href={route('lab.clinicas.index', { clinica: clinica.id })}>Voltar à clínica</Link>
                        </Button>
                    }
                >
                    <Button type="submit" loading={processing}>
                        Salvar alterações
                    </Button>
                </FormActions>
            </form>
        </AppLayout>
    );
}
