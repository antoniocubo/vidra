import { Alert, ChipToggleGroup, FormField, FormSection, Select, TextInput } from '@/components/vidra';

export const animalVazio = () => ({
    nome: '',
    especie: 'cao',
    raca: '',
    sexo: 'femea',
    modo_idade: 'data',
    nascimento: '',
    anos: '',
    meses: '0',
    peso: '',
    castrado: 'nao_informado',
    microchip: '',
    vinculo_veterinario_id: '',
});
export type AnimalFormData = ReturnType<typeof animalVazio>;
type Props = {
    animal: AnimalFormData;
    onChange: (animal: AnimalFormData) => void;
    vinculos: { id: string; nome: string }[];
    errors: Record<string, string | undefined>;
    prefix?: string;
    hoje: string;
    title?: string;
};
export const especies = [
    { value: 'cao', label: 'Cão' },
    { value: 'gato', label: 'Gato' },
    { value: 'ave', label: 'Ave' },
    { value: 'equino', label: 'Equino' },
    { value: 'outra', label: 'Outra' },
];
export function AnimalForm({ animal, onChange, vinculos, errors, prefix = '', hoje, title = 'Animal' }: Props) {
    const set = (campo: keyof AnimalFormData, value: string) => onChange({ ...animal, [campo]: value });
    const field = (campo: keyof AnimalFormData, label: string, optional = false, type = 'text') => (
        <FormField id={`${prefix}${campo}`} label={label} optional={optional} error={errors[`${prefix}${campo}`]}>
            <TextInput className="h-11" type={type} value={animal[campo]} onChange={(e) => set(campo, e.target.value)} required={!optional} />
        </FormField>
    );
    return (
        <FormSection title={title}>
            {field('nome', 'Nome do animal')}
            <div className="space-y-2 sm:col-span-2">
                <ChipToggleGroup mode="single" label="Espécie" value={animal.especie} onChange={(v) => set('especie', v)} options={especies} />
                {errors[`${prefix}especie`] && <Alert tone="crit" title={errors[`${prefix}especie`]!} />}
            </div>
            {field('raca', 'Raça', true)}
            <FormField id={`${prefix}sexo`} label="Sexo" error={errors[`${prefix}sexo`]}>
                <Select
                    value={animal.sexo}
                    onChange={(v) => set('sexo', v)}
                    options={[
                        { value: 'femea', label: 'Fêmea' },
                        { value: 'macho', label: 'Macho' },
                    ]}
                />
            </FormField>
            <div className="sm:col-span-2">
                <ChipToggleGroup
                    mode="single"
                    label="Como informar a idade"
                    value={animal.modo_idade}
                    onChange={(v) => set('modo_idade', v)}
                    options={[
                        { value: 'data', label: 'Data de nascimento' },
                        { value: 'aproximada', label: 'Idade aproximada' },
                    ]}
                />
            </div>
            {animal.modo_idade === 'data' ? (
                <FormField id={`${prefix}nascimento`} label="Data de nascimento" error={errors[`${prefix}nascimento`]}>
                    <TextInput type="date" max={hoje} value={animal.nascimento} onChange={(e) => set('nascimento', e.target.value)} required />
                </FormField>
            ) : (
                <>
                    <FormField id={`${prefix}anos`} label="Anos" error={errors[`${prefix}anos`]}>
                        <TextInput type="number" min="0" max="40" value={animal.anos} onChange={(e) => set('anos', e.target.value)} required />
                    </FormField>
                    <FormField id={`${prefix}meses`} label="Meses" error={errors[`${prefix}meses`]}>
                        <TextInput
                            type="number"
                            min="0"
                            max={animal.anos === '40' ? '0' : '11'}
                            value={animal.meses}
                            onChange={(e) => set('meses', e.target.value)}
                            required
                        />
                    </FormField>
                </>
            )}
            <FormField id={`${prefix}peso`} label="Peso" optional error={errors[`${prefix}peso`]}>
                <TextInput inputMode="decimal" suffix="kg" value={animal.peso} onChange={(e) => set('peso', e.target.value)} />
            </FormField>
            <FormField id={`${prefix}castrado`} label="Castrado" error={errors[`${prefix}castrado`]}>
                <Select
                    value={animal.castrado}
                    onChange={(v) => set('castrado', v)}
                    options={[
                        { value: 'sim', label: 'Sim' },
                        { value: 'nao', label: 'Não' },
                        { value: 'nao_informado', label: 'Não informado' },
                    ]}
                />
            </FormField>
            <FormField
                id={`${prefix}vinculo_veterinario_id`}
                label="Veterinário que acompanha"
                optional
                error={errors[`${prefix}vinculo_veterinario_id`]}
            >
                <Select
                    value={animal.vinculo_veterinario_id}
                    onChange={(v) => set('vinculo_veterinario_id', v)}
                    options={[{ value: '', label: 'Não informado' }, ...vinculos.map((v) => ({ value: v.id, label: v.nome }))]}
                />
            </FormField>
            {field('microchip', 'Microchip (15 dígitos)', true)}
        </FormSection>
    );
}
