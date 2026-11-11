<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>Plano e cobrança do Vidra</title></head>
<body style="font-family:Arial,sans-serif;line-height:1.6;margin:0;padding:24px;">
<table role="presentation" style="max-width:560px;width:100%;margin:auto;border:1px solid;border-radius:12px;"><tr><td style="padding:24px;">
<h2>{{ $laboratorio }}</h2>
@if ($situacao === \Vidra\Acesso\Domain\Assinatura\SituacaoDaAssinatura::EmTeste)
<p>Faltam {{ $diasRestantes }} {{ $diasRestantes === 1 ? 'dia' : 'dias' }} do seu teste do Vidra. Escolha um plano para continuar abrindo pedidos.</p>
@elseif ($situacao === \Vidra\Acesso\Domain\Assinatura\SituacaoDaAssinatura::Ativa)
<p>Sua assinatura do Vidra vence em {{ $diasRestantes }} {{ $diasRestantes === 1 ? 'dia' : 'dias' }}. Pague a cobrança para não interromper a abertura de pedidos.</p>
@elseif ($situacao === \Vidra\Acesso\Domain\Assinatura\SituacaoDaAssinatura::Atrasada)
<p>O pagamento do Vidra está atrasado. Regularize para não passar o laboratório para só consulta.</p>
@else
<p>O laboratório está só em consulta: os laudos continuam disponíveis, mas não dá para abrir pedidos novos. Escolha um plano ou pague a cobrança em aberto.</p>
@endif
<p style="margin:24px 0;"><a href="{{ $link }}" style="display:inline-block;padding:12px 20px;border:1px solid;border-radius:8px;font-weight:bold;">Ver plano e cobrança</a></p>
<p style="font-size:12px;">Aviso enviado pelo Vidra aos administradores do laboratório</p>
</td></tr></table>
</body></html>
