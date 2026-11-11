<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><title>Convite para o Vidra</title></head>
<body style="font-family:Arial,sans-serif;line-height:1.6;margin:0;padding:24px;">
<table role="presentation" style="max-width:560px;width:100%;margin:auto;border:1px solid;border-radius:12px;"><tr><td style="padding:24px;">
<h2>{{ $laboratorio }}</h2>
<p>Olá, {{ $convite->nome }}.</p>
<p>{{ $quemConvidou }} convidou você para o {{ $laboratorio }} no Vidra.</p>
<p>Seu papel será: <strong>{{ $convite->papel->rotulo() }}</strong>.</p>
<p style="margin:24px 0;"><a href="{{ $link }}" style="display:inline-block;padding:12px 20px;border:1px solid;border-radius:8px;font-weight:bold;">Aceitar convite</a></p>
<p>Este convite é válido até {{ $convite->expiraEm->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i') }} (horário de São Paulo) e pode ser usado uma vez.</p>
<p>Se você não esperava este convite, pode ignorar este e-mail.</p>
<p style="font-size:12px;">Convite enviado pelo Vidra</p>
</td></tr></table>
</body></html>
