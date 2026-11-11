{{-- O PDF não usa Tailwind/Vite. Só neste Blade os tokens --vidra-* têm valores hex locais. --}}
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Laudo {{ $laudo->numero }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap">
    <style>
        :root { --vidra-brand-900:#0E4F5C; --vidra-glass-50:#F4F7F6; --vidra-ink-900:#1D2626; --vidra-ink-500:#5A6766; --vidra-line-400:#D8E0DE; --vidra-crit-100:#FBE4E4; --vidra-crit-600:#B83232; }
        * { box-sizing:border-box; }
        body { margin:0; color:var(--vidra-ink-900); font:12px/1.6 'Plus Jakarta Sans',sans-serif; }
        h1,h2 { color:var(--vidra-brand-900); font-family:'Bricolage Grotesque',sans-serif; margin:0; }
        h1 { font-size:25px; } h2 { font-size:15px; margin-top:22px; }
        header { display:flex; justify-content:space-between; gap:24px; padding-bottom:18px; border-bottom:2px solid var(--vidra-brand-900); }
        .muted { color:var(--vidra-ink-500); font-size:11px; } .mono { font-family:'JetBrains Mono',monospace; }
        .identificacao { background:var(--vidra-glass-50); border-radius:8px; padding:16px; margin:18px 0; display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:10px 24px; }
        .identificacao strong { display:block; } .datas { margin:14px 0; }
        table { width:100%; border-collapse:collapse; margin-top:10px; table-layout:fixed; }
        th { text-align:left; font-size:10px; color:var(--vidra-ink-500); }
        th,td { border-bottom:1px solid var(--vidra-line-400); padding:8px 5px; vertical-align:top; overflow-wrap:anywhere; }
        th:first-child,td:first-child { padding-left:0; width:24%; } th:nth-child(2) { width:24%; }
        thead { display:table-header-group; } tr { break-inside:avoid; } h2 { break-after:avoid; }
        .observacoes { margin-top:18px; border-left:3px solid var(--vidra-brand-900); padding:8px 14px; background:var(--vidra-glass-50); }
        .critico { color:var(--vidra-crit-600); font-weight:700; }
        footer { display:flex; align-items:center; justify-content:space-between; gap:18px; border-top:1px solid var(--vidra-line-400); padding-top:18px; margin-top:26px; break-inside:avoid; }
        footer img { width:100px; height:100px; flex-shrink:0; } footer > div { min-width:0; overflow-wrap:anywhere; } footer > div:nth-child(2) { max-width:210px; } .identificacao > div { min-width:0; overflow-wrap:anywhere; } .codigo { font-size:15px; letter-spacing:1px; } p { margin:5px 0; }
    </style>
</head>
<body>
<header>
    <div>
        <h1>{{ $laudo->laboratorio['nome'] }}</h1>
        <div class="muted">{{ $laudo->laboratorio['endereco'] }}</div>
        <div class="muted">CNPJ {{ $laudo->laboratorio['cnpj'] }} · {{ $laudo->laboratorio['telefone'] }}</div>
        <div class="muted">RT {{ $laudo->rtNome }} · {{ $laudo->rtRegistro }}</div>
    </div>
    <div><div class="muted">Protocolo</div><strong class="mono">{{ $laudo->protocolo }}</strong><div class="mono">{{ $laudo->numero }}</div></div>
</header>
@if ($laudo->versao > 1)
<section class="observacoes">Laudo retificado em {{ $laudo->retificacao?->em->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('d/m/Y \à\s H:i') }} · {{ $laudo->retificacao?->motivo }}. Substitui a versão anterior.</section>
@endif
<section class="identificacao" aria-label="Identificação">
    <div><span class="muted">{{ $laudo->textos['paciente'] }}</span><strong>{{ $laudo->paciente['nome'] }}</strong>{{ $laudo->paciente['descricao'] }}</div>
    <div><span class="muted">{{ $laudo->textos['responsavel'] }}</span><strong>{{ $laudo->paciente['responsavel'] }}</strong></div>
    <div><span class="muted">{{ $laudo->textos['solicitante'] }}</span><strong>{{ $laudo->solicitante['nome'] }}</strong>{{ $laudo->solicitante['registro'] }}</div>
    <div><span class="muted">Datas (horário de Brasília)</span><div>Pedido: {{ $laudo->datas['pedido'] }}</div><div>Amostra recebida: {{ $laudo->datas['recebimento'] ?? 'Não registrada' }}</div><div>Liberação: {{ $laudo->datas['liberacao'] }}</div></div>
</section>
@foreach ($laudo->exames as $exame)
<section>
    <h2>{{ $exame['nome'] }} · {{ $exame['material'] }}</h2>
    @if ($exame['metodo'])<div class="muted">Método: {{ $exame['metodo'] }}</div>@endif
    <table>
        <thead><tr><th>Analito</th><th>Resultado / classificação</th><th>Unidade</th><th>Referência</th><th>Anterior</th></tr></thead>
        <tbody>
        @foreach ($exame['resultados'] as $r)
            <tr><td>{{ $r['nome'] }}</td><td class="mono {{ $r['classificacao'] === '▲ Crítico' ? 'critico' : '' }}"><strong>{{ $r['valor'] }}</strong><br>{{ $r['classificacao'] }}</td><td>{{ $r['unidade'] }}</td><td class="mono">{{ $r['referencia'] }}</td><td class="mono">{{ $r['anterior'] ?? '—' }}@if ($r['anteriorEm'])<div class="muted">{{ $r['anteriorEm'] }}</div>@endif</td></tr>
        @endforeach
        </tbody>
    </table>
</section>
@endforeach
@if ($laudo->ressalvas || $laudo->observacao || $laudo->avisos)
<section class="observacoes"><strong>Observações</strong>
    @foreach ($laudo->ressalvas as $texto)<p>{{ $texto }}</p>@endforeach
    @if ($laudo->observacao)<p>{{ $laudo->observacao }}</p>@endif
    @foreach ($laudo->avisos as $texto)<p>{{ $texto }}</p>@endforeach
</section>
@endif
<footer>
    <div><strong>Liberado por {{ $laudo->rtNome }}</strong><div>{{ $laudo->rtRegistro }} · {{ str_replace(' ', ' às ', $laudo->datas['liberacao']) }}</div><p class="muted">↑ Alto · ↓ Baixo · ▲ Crítico</p><p class="muted">Laudo emitido pelo Vidra</p></div>
    <div><div class="muted">Código de acesso</div><strong class="mono codigo">{{ $laudo->codigo }}</strong><div class="muted">{{ $laudo->link }}</div></div>
    <img src="{{ $qr }}" alt="QR code para consultar o laudo">
</footer>
</body>
</html>
