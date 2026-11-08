import { FormField, FormSection, Select, TextInput } from '@/components/vidra';
import { controlClasses, useFieldControl } from '@/components/vidra/form-field';
import { cn } from '@/lib/utils';
import { type TextareaHTMLAttributes } from 'react';

export type ClinicaData = {
    nome_fantasia: string;
    razao_social: string;
    cnpj: string;
    cidade: string;
    bairro: string;
    email_laudos: string;
    email_faturamento: string;
    telefone_critico: string;
    responsavel: string;
    observacao_coleta: string;
    desconto: string;
    dia_fechamento: string;
    dia_vencimento: string;
    forma_pagamento: string;
};

export const CLINICA_VAZIA: ClinicaData = {
    nome_fantasia: '',
    razao_social: '',
    cnpj: '',
    cidade: '',
    bairro: '',
    email_laudos: '',
    email_faturamento: '',
    telefone_critico: '',
    responsavel: '',
    observacao_coleta: '',
    desconto: '0',
    dia_fechamento: '31',
    dia_vencimento: '10',
    forma_pagamento: 'pix',
};

export const ROTULO_FORMA: Record<string, string> = { pix: 'Pix', boleto: 'Boleto', transferencia: 'Transferência' };

const DIAS = Array.from({ length: 28 }, (_, i) => String(i + 1));
const DIAS_DE_FECHAMENTO = [...DIAS.map((d) => ({ value: d, label: `Dia ${d}` })), { value: '31', label: 'Último dia do mês' }];
const DIAS_DE_VENCIMENTO = DIAS.map((d) => ({ value: d, label: `Dia ${d}` }));

function Textarea({ className, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    const field = useFieldControl();
    return (
        <textarea
            id={field.id}
            aria-describedby={field.describedBy}
            aria-invalid={field.invalid || undefined}
            rows={3}
            className={cn(controlClasses, 'h-auto min-h-24 py-2.5', className)}
            {...props}
        />
    );
}

type Props = {
    data: ClinicaData;
    setData: <K extends keyof ClinicaData>(key: K, value: ClinicaData[K]) => void;
    errors: Partial<Record<keyof ClinicaData, string>>;
    formasDePagamento: string[];
};

/** As quatro seções do cadastro de clínica, iguais para criar e editar. */
export function ClinicaFields({ data, setData, errors, formasDePagamento }: Props) {
    return (
        <>
            <FormSection title="Identificação">
                <FormField id="nome_fantasia" label="Nome fantasia" error={errors.nome_fantasia} className="sm:col-span-2">
                    <TextInput value={data.nome_fantasia} onChange={(e) => setData('nome_fantasia', e.target.value)} required />
                </FormField>
                <FormField id="razao_social" label="Razão social" optional error={errors.razao_social}>
                    <TextInput value={data.razao_social} onChange={(e) => setData('razao_social', e.target.value)} />
                </FormField>
                <FormField id="cnpj" label="CNPJ" optional hint="Não pode repetir no seu laboratório." error={errors.cnpj}>
                    <TextInput mono inputMode="numeric" value={data.cnpj} onChange={(e) => setData('cnpj', e.target.value)} />
                </FormField>
                <FormField id="cidade" label="Cidade" error={errors.cidade}>
                    <TextInput value={data.cidade} onChange={(e) => setData('cidade', e.target.value)} required />
                </FormField>
                <FormField id="bairro" label="Bairro" optional error={errors.bairro}>
                    <TextInput value={data.bairro} onChange={(e) => setData('bairro', e.target.value)} />
                </FormField>
            </FormSection>

            <FormSection title="Contato">
                <FormField id="responsavel" label="Responsável" optional error={errors.responsavel}>
                    <TextInput value={data.responsavel} onChange={(e) => setData('responsavel', e.target.value)} />
                </FormField>
                <FormField
                    id="telefone_critico"
                    label="Telefone para valor crítico"
                    optional
                    hint="A bancada liga neste número quando um veterinário da clínica não atende."
                    error={errors.telefone_critico}
                >
                    <TextInput mono inputMode="tel" value={data.telefone_critico} onChange={(e) => setData('telefone_critico', e.target.value)} />
                </FormField>
                <FormField id="email_laudos" label="E-mail de laudos" optional error={errors.email_laudos} className="sm:col-span-2">
                    <TextInput type="email" value={data.email_laudos} onChange={(e) => setData('email_laudos', e.target.value)} />
                </FormField>
            </FormSection>

            <FormSection title="Faturamento" description="A clínica não tem acesso ao Vidra. Ela recebe a fatura mensal por e-mail.">
                <FormField
                    id="email_faturamento"
                    label="E-mail de faturamento"
                    hint="A fatura vai para este endereço."
                    error={errors.email_faturamento}
                    className="sm:col-span-2"
                >
                    <TextInput type="email" value={data.email_faturamento} onChange={(e) => setData('email_faturamento', e.target.value)} required />
                </FormField>
                <FormField
                    id="desconto"
                    label="Desconto sobre a tabela base"
                    hint="0% usa a tabela base. 10% é a tabela parceira."
                    error={errors.desconto}
                >
                    <TextInput
                        mono
                        type="number"
                        min={0}
                        max={100}
                        step={1}
                        suffix="%"
                        value={data.desconto}
                        onChange={(e) => setData('desconto', e.target.value)}
                        required
                    />
                </FormField>
                <FormField id="forma_pagamento" label="Forma de pagamento" error={errors.forma_pagamento}>
                    <Select
                        value={data.forma_pagamento}
                        onChange={(value) => setData('forma_pagamento', value)}
                        options={formasDePagamento.map((f) => ({ value: f, label: ROTULO_FORMA[f] ?? f }))}
                    />
                </FormField>
                <FormField id="dia_fechamento" label="Fechamento da fatura" error={errors.dia_fechamento}>
                    <Select value={data.dia_fechamento} onChange={(value) => setData('dia_fechamento', value)} options={DIAS_DE_FECHAMENTO} />
                </FormField>
                <FormField id="dia_vencimento" label="Vencimento" error={errors.dia_vencimento}>
                    <Select value={data.dia_vencimento} onChange={(value) => setData('dia_vencimento', value)} options={DIAS_DE_VENCIMENTO} />
                </FormField>
            </FormSection>

            <FormSection title="Coleta">
                <FormField
                    id="observacao_coleta"
                    label="Como e quando coletar"
                    optional
                    hint="Ex.: Motoboy, rota Norte, 10h e 15h."
                    error={errors.observacao_coleta}
                    className="sm:col-span-2"
                >
                    <Textarea value={data.observacao_coleta} onChange={(e) => setData('observacao_coleta', e.target.value)} maxLength={500} />
                </FormField>
            </FormSection>
        </>
    );
}
