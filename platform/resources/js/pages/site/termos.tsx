// Texto provisório: precisa de revisão jurídica antes de publicar.
import SiteLegalLayout, { Secao } from '@/layouts/site-legal-layout';

export default function Termos() {
    return (
        <SiteLegalLayout titulo="Termos de uso" vigencia="10/11/2026">
            <p className="text-[15px] leading-6">
                Estes termos valem entre [RAZÃO SOCIAL E CNPJ DA VIDRA] e o laboratório que cria uma conta no Vidra.
            </p>

            <Secao titulo="1. O que é o Vidra">
                <p>
                    Sistema em nuvem para laboratórios: pedido, amostra, bancada, liberação e entrega do laudo, e fatura mensal das clínicas. O Vidra
                    é uma ferramenta; o laboratório continua responsável pela sua operação.
                </p>
            </Secao>

            <Secao titulo="2. Planos e cobrança">
                <p>A cobrança é por pedido (atendimento), não por exame.</p>
                <ul>
                    <li>Bancada: R$ 240 por mês, 600 pedidos incluídos.</li>
                    <li>Laboratório: R$ 540 por mês, 2.000 pedidos incluídos.</li>
                    <li>Rede: R$ 1.140 por mês, 6.000 pedidos incluídos.</li>
                    <li>Pedidos acima do plano: R$ 0,25 por pedido.</li>
                    <li>Teste de 14 dias, sem taxa de implantação.</li>
                    <li>No plano anual, 2 meses grátis.</li>
                </ul>
                <p>A assinatura é cobrada pelo Asaas. Clínicas, veterinários e tutores não pagam o Vidra.</p>
            </Secao>

            <Secao titulo="3. Responsabilidades">
                <ul>
                    <li>O responsável técnico (RT) do laboratório é o único que libera e assina laudos, e responde por eles.</li>
                    <li>
                        Os alertas do sistema (valor fora da referência, valor crítico, mudança em relação ao exame anterior) e qualquer recurso de IA
                        apontam, mas não decidem: a revisão e a liberação são do RT.
                    </li>
                    <li>O laboratório responde pelos dados que cadastra e por ter a autorização do tutor quando ela for necessária.</li>
                    <li>Cada pessoa é responsável por guardar a sua senha. Não compartilhe contas.</li>
                </ul>
                <p>Os papéis na proteção de dados estão descritos na política de privacidade.</p>
            </Secao>

            <Secao titulo="4. Disponibilidade">
                <p>
                    Trabalhamos para manter o Vidra no ar, com cópias de segurança. Pode haver paradas para manutenção, que avisaremos quando forem
                    programadas. [META DE DISPONIBILIDADE, SE HOUVER]
                </p>
            </Secao>

            <Secao titulo="5. Cancelamento">
                <p>
                    O laboratório pode cancelar a qualquer momento e não paga novas mensalidades depois do cancelamento. Os dados ficam disponíveis
                    para exportação por [PRAZO] e depois são excluídos, conforme a política de privacidade. Podemos suspender contas que violem estes
                    termos ou fiquem sem pagamento, com aviso prévio.
                </p>
            </Secao>

            <Secao titulo="6. Mudanças e foro">
                <p>
                    Podemos atualizar estes termos, avisando por e-mail com antecedência. Fica eleito o foro da comarca de [CIDADE/UF] para resolver
                    dúvidas sobre eles.
                </p>
            </Secao>
        </SiteLegalLayout>
    );
}
