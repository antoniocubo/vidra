import { Card } from '@/components/vidra';
import { AnimalCard } from '@/components/vidra/pet';
import { especies, type AnimalFormData } from './animal-form';

type Props = {
    animais: AnimalFormData[];
    nome: string;
    autoriza: boolean;
    hoje: string;
    perfis: { chave: string; nome: string; grupo: string }[];
    vinculos: { id: string; nome: string }[];
};
function idade(a: AnimalFormData, hoje: string) {
    if (a.modo_idade === 'aproximada') return Number(a.anos) * 12 + Number(a.meses);
    if (!a.nascimento || a.nascimento > hoje) return null;
    const [y, m, d] = hoje.split('-').map(Number);
    const [by, bm, bd] = a.nascimento.split('-').map(Number);
    return (y - by) * 12 + m - bm - (d < bd ? 1 : 0);
}
export function AnimalPreview({ animais, nome, autoriza, hoje, perfis, vinculos }: Props) {
    return (
        <aside aria-label="Resumo do cadastro" className="min-w-0 space-y-4 lg:sticky lg:top-6">
            <Card title="Como vai aparecer no pedido">
                {animais.map((a, i) => {
                    const meses = idade(a, hoje);
                    const anos = meses === null ? null : Math.floor(meses / 12);
                    const idadeTexto =
                        anos === null
                            ? null
                            : `${a.modo_idade === 'aproximada' ? '~' : ''}${anos > 0 ? `${anos} ${anos === 1 ? 'ano' : 'anos'}` : `${meses} ${meses === 1 ? 'mês' : 'meses'}`}`;
                    const resumo = [
                        especies.find((e) => e.value === a.especie)?.label,
                        a.raca,
                        a.sexo === 'femea' ? 'fêmea' : 'macho',
                        idadeTexto,
                        a.peso ? `${a.peso.replace('.', ',')} kg` : null,
                        a.castrado === 'sim' ? (a.sexo === 'femea' ? 'castrada' : 'castrado') : null,
                    ]
                        .filter(Boolean)
                        .join(' · ');
                    return (
                        <div key={i} className="space-y-2">
                            <AnimalCard name={a.nome || 'Nome do animal'} summary={resumo} />
                            {a.vinculo_veterinario_id && (
                                <p className="text-muted-foreground text-xs">
                                    Acompanha: {vinculos.find((v) => v.id === a.vinculo_veterinario_id)?.nome}
                                </p>
                            )}
                        </div>
                    );
                })}
                <p className="text-muted-foreground text-sm">
                    Tutor: {nome || 'Nome do tutor'} · {autoriza ? 'recebe laudos por e-mail' : 'sem envio por e-mail'}
                </p>
            </Card>
            <Card title="Referências que a bancada vai usar">
                {animais.map((a, i) => {
                    const meses = idade(a, hoje);
                    const anos = meses === null ? null : Math.floor(meses / 12);
                    const key =
                        a.especie === 'ave'
                            ? 'ave'
                            : anos === null
                              ? null
                              : a.especie === 'equino'
                                ? `equino.${anos < 2 ? 'potro' : anos < 15 ? 'adulto' : 'idoso'}`
                                : `${a.especie}.${anos < 1 ? 'filhote' : anos < (a.especie === 'cao' ? 7 : 10) ? 'adulto' : 'idoso'}`;
                    return (
                        <p key={i} className="text-sm">
                            <strong>{a.nome || `Animal ${i + 1}`}: </strong>
                            {a.especie === 'outra'
                                ? 'Sem tabela padrão. O laudo sai sem referência e com aviso para a clínica.'
                                : perfis.find((p) => p.chave === key)?.nome || 'Informe a idade para identificar o perfil.'}
                        </p>
                    );
                })}
                <p className="text-muted-foreground text-xs">
                    Sem data de nascimento, a idade aproximada basta para escolher a faixa: filhote, adulto ou idoso.
                </p>
            </Card>
        </aside>
    );
}
