<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>Laudo disponível</title></head>
<body style="font-family:Arial,sans-serif;line-height:1.6;margin:0;padding:24px;">
<table role="presentation" style="max-width:560px;width:100%;margin:auto;border:1px solid;border-radius:12px;"><tr><td style="padding:24px;">
    <h2 style="margin:0 0 20px;">{{ $laudo->laboratorio['nome'] }}</h2>
    <p>Olá{{ $papel === 'tutor' ? ', '.$laudo->paciente['responsavel'] : '' }}.</p>
    <p>O laudo de <strong>{{ $laudo->paciente['nome'] }}</strong> está pronto. Liberado em {{ $laudo->datas['liberacao'] }}.</p>
    <p style="margin:24px 0;"><a href="{{ $laudo->link }}" style="display:inline-block;padding:12px 20px;border:1px solid;border-radius:8px;font-weight:bold;">Ver laudo de {{ $laudo->paciente['nome'] }}</a></p>
    <p>Código de acesso: <strong style="font-family:monospace;font-size:20px;letter-spacing:2px;">{{ $laudo->codigo }}</strong></p>
    <p>O PDF completo segue em anexo. Converse com o profissional solicitante sobre os resultados.</p>
    @if ($papel === 'tutor')<p style="font-size:12px;">Você recebe este e-mail porque autorizou o envio de laudos no atendimento.</p>@endif
    <p style="font-size:12px;">{{ $laudo->laboratorio['endereco'] }} · {{ $laudo->laboratorio['telefone'] }}<br>Laudo emitido pelo Vidra</p>
</td></tr></table>
</body>
</html>
