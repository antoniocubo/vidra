import { Checkbox, FormField, FormSection, TextInput } from '@/components/vidra';
export type Contato = { nome: string; celular: string; email: string; cpf: string; cep: string; autoriza_email: boolean };
type Props = { data: Contato; onChange: (data: Contato) => void; errors: Record<string, string | undefined> };
export function ContatoFields({ data, onChange, errors }: Props) {
    return (
        <FormSection title="Tutor">
            {(['nome', 'celular', 'email', 'cpf', 'cep'] as const).map((campo) => (
                <FormField
                    key={campo}
                    id={campo}
                    label={{ nome: 'Nome completo', celular: 'Celular com DDD', email: 'E-mail', cpf: 'CPF', cep: 'CEP' }[campo]}
                    optional={!['nome', 'celular'].includes(campo)}
                    error={errors[campo]}
                    className={campo === 'nome' ? 'sm:col-span-2' : undefined}
                >
                    <TextInput
                        value={data[campo]}
                        type={campo === 'email' ? 'email' : 'text'}
                        inputMode={campo === 'celular' ? 'tel' : campo === 'cpf' || campo === 'cep' ? 'numeric' : undefined}
                        mono={['celular', 'cpf', 'cep'].includes(campo)}
                        required={['nome', 'celular'].includes(campo)}
                        onChange={(e) =>
                            onChange({ ...data, [campo]: e.target.value, autoriza_email: campo === 'email' ? false : data.autoriza_email })
                        }
                    />
                </FormField>
            ))}
            <div className="space-y-2 sm:col-span-2">
                <Checkbox
                    id="autoriza_email"
                    checked={data.autoriza_email}
                    disabled={!data.email.trim()}
                    onChange={(e) => onChange({ ...data, autoriza_email: e.target.checked })}
                    label={`${data.nome.trim() || 'O tutor'} autoriza receber os laudos por e-mail`}
                    description="A autorização fica registrada com data, hora e atendente. Pode ser retirada a qualquer momento."
                />
                {errors.autoriza_email && <p className="text-crit-600 text-sm">{errors.autoriza_email}</p>}
            </div>
        </FormSection>
    );
}
