import { Alert, Checkbox, ChipToggleGroup, FormField, FormSection, RadioCardGroup, TextInput } from '@/components/vidra';
import { Link } from '@inertiajs/react';

/** Campos do vínculo do veterinário com o laboratório: o que é nosso, não da pessoa. */
export type VinculoData = {
    telefone_critico: string;
    quem_paga: string;
    recebe_laudo: string;
    ve_pedidos_dos_colegas: boolean;
    clinica_ids: string[];
};

export type ClinicaOpcao = { id: string; nome: string; ativa: boolean };

type Errors = Record<string, string | undefined>;

export function TelefoneCriticoField({ value, onChange, error }: { value: string; onChange: (value: string) => void; error?: string }) {
    return (
        <FormField
            id="telefone_critico"
            label="Telefone para valor crítico"
            hint="A bancada liga neste número antes de liberar um valor crítico. Pode ser o da clínica."
            error={error}
        >
            <TextInput mono inputMode="tel" value={value} onChange={(e) => onChange(e.target.value)} required />
        </FormField>
    );
}

export function OndeAtendeSection({
    clinicas,
    value,
    onChange,
    error,
}: {
    clinicas: ClinicaOpcao[];
    value: string[];
    onChange: (value: string[]) => void;
    error?: string;
}) {
    return (
        <FormSection title="Onde atende" description="Marque as clínicas. No pedido, o laboratório escolhe por onde aquele exame vai ser cobrado.">
            <div className="space-y-3 sm:col-span-2">
                {clinicas.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        Nenhuma clínica cadastrada ainda.{' '}
                        <Link href={route('lab.clinicas.create')} className="text-primary font-semibold underline underline-offset-4">
                            Cadastrar clínica
                        </Link>
                    </p>
                ) : (
                    <ChipToggleGroup
                        mode="multiple"
                        label="Clínicas onde atende"
                        value={value}
                        onChange={onChange}
                        options={clinicas.map((c) => ({ value: c.id, label: c.ativa ? c.nome : `${c.nome} (inativa)` }))}
                    />
                )}
                {error && <Alert tone="crit" title={error} />}
            </div>
        </FormSection>
    );
}

export function PagamentoELaudosSections({
    data,
    setData,
    errors,
}: {
    data: VinculoData;
    setData: <K extends keyof VinculoData>(key: K, value: VinculoData[K]) => void;
    errors: Errors;
}) {
    return (
        <>
            <FormSection title="Quem paga os pedidos, por padrão">
                <div className="sm:col-span-2">
                    <RadioCardGroup
                        name="quem_paga"
                        label="Pagador padrão"
                        value={data.quem_paga}
                        onChange={(value) => setData('quem_paga', value)}
                        options={[
                            {
                                value: 'clinica',
                                label: 'A clínica, na fatura mensal',
                                description: 'O exame entra na fatura da clínica escolhida no pedido.',
                            },
                            {
                                value: 'veterinario',
                                label: 'O próprio veterinário, na fatura mensal',
                                description: 'Para quem atende em casa ou em consultório sem CNPJ de clínica.',
                            },
                            {
                                value: 'tutor',
                                label: 'O tutor, no balcão',
                                description: 'O tutor paga na entrega da amostra, por Pix ou cartão.',
                            },
                        ]}
                    />
                    {errors.quem_paga && <Alert tone="crit" title={errors.quem_paga} />}
                </div>
            </FormSection>
            <FormSection title="Como recebe os laudos">
                <div className="space-y-4 sm:col-span-2">
                    <RadioCardGroup
                        name="recebe_laudo"
                        label="Entrega dos laudos"
                        value={data.recebe_laudo}
                        onChange={(value) => setData('recebe_laudo', value)}
                        options={[
                            // O resumo do dia volta quando existir: até lá, cada laudo sai na hora.
                            { value: 'cada_laudo', label: 'Um e-mail com o PDF a cada laudo liberado' },
                        ]}
                    />
                    {errors.recebe_laudo && <Alert tone="crit" title={errors.recebe_laudo} />}
                    <Checkbox
                        id="ve_pedidos_dos_colegas"
                        label="Também ver os pedidos dos colegas da clínica"
                        description="Para quem responde pela clínica. Sem isso, cada veterinário vê só os próprios pedidos."
                        checked={data.ve_pedidos_dos_colegas}
                        onChange={(event) => setData('ve_pedidos_dos_colegas', event.target.checked)}
                    />
                    {errors.ve_pedidos_dos_colegas && <Alert tone="crit" title={errors.ve_pedidos_dos_colegas} />}
                </div>
            </FormSection>
        </>
    );
}
