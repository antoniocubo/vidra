<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>Fatura {{ $fatura->numeroFormatado() }}</title></head>
<body style="font-family:Arial,sans-serif;line-height:1.6;margin:0;padding:24px;">
<table role="presentation" style="max-width:560px;width:100%;margin:auto;border:1px solid;border-radius:12px;"><tr><td style="padding:24px;">
    <h2 style="margin:0 0 20px;">{{ $laboratorio }}</h2>
    <p>Olá, {{ $fatura->pagador->nome }}.</p>
    <p>Segue a fatura <strong>{{ $fatura->numeroFormatado() }}</strong> da competência {{ $fatura->competencia->rotulo() }}, com {{ count($fatura->linhas) }} {{ count($fatura->linhas) === 1 ? 'pedido' : 'pedidos' }}.</p>
    <p>Total: <strong>R$ {{ number_format($fatura->total() / 100, 2, ',', '.') }}</strong><br>
    Vencimento: <strong>{{ $fatura->vencimento->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y') }}</strong></p>
    <p>O PDF com a lista de pedidos segue em anexo. Em caso de dúvida, responda a este e-mail ou fale com o laboratório.</p>
    <p style="font-size:12px;">Fatura emitida pelo Vidra</p>
</td></tr></table>
</body>
</html>
