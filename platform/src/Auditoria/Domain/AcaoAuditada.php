<?php

declare(strict_types=1);

namespace Vidra\Auditoria\Domain;

enum AcaoAuditada: string
{
    case pedido_aberto = 'pedido_aberto';
    case exame_incluido = 'exame_incluido';
    case exame_cancelado = 'exame_cancelado';
    case pedido_cancelado = 'pedido_cancelado';
    case resultado_digitado = 'resultado_digitado';
    case laudo_liberado = 'laudo_liberado';
    case laudo_reaberto = 'laudo_reaberto';
    case laudo_retificado = 'laudo_retificado';
    case laudo_baixado = 'laudo_baixado';
    case laudo_reenviado = 'laudo_reenviado';
    case laudo_aberto_pelo_tutor = 'laudo_aberto_pelo_tutor';
    case pessoa_convidada = 'pessoa_convidada';
    case convite_aceito = 'convite_aceito';
    case papel_trocado = 'papel_trocado';
    case pessoa_removida = 'pessoa_removida';
    case fatura_enviada = 'fatura_enviada';
    case pagamento_registrado = 'pagamento_registrado';
    case credito_concedido = 'credito_concedido';
}
