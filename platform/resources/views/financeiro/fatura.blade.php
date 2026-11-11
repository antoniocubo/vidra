{{-- O PDF não usa Tailwind/Vite. Só neste Blade os tokens --vidra-* têm valores hex locais. --}}
@php
    $fuso = new DateTimeZone('America/Sao_Paulo');
    $reais = fn (int $centavos): string => 'R$ '.number_format($centavos / 100, 2, ',', '.');
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Fatura {{ $fatura->numeroFormatado() }}</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700&amp;family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap">
    <style>
        :root { --vidra-brand-900:#0E4F5C; --vidra-glass-50:#F4F7F6; --vidra-ink-900:#1D2626; --vidra-ink-500:#5A6766; --vidra-line-400:#D8E0DE; }
        * { box-sizing:border-box; }
        body { margin:0; color:var(--vidra-ink-900); font:12px/1.6 'Plus Jakarta Sans',sans-serif; }
        h1,h2 { color:var(--vidra-brand-900); font-family:'Bricolage Grotesque',sans-serif; margin:0; }
        h1 { font-size:25px; } h2 { font-size:15px; margin-top:22px; }
        header { display:flex; justify-content:space-between; gap:24px; padding-bottom:18px; border-bottom:2px solid var(--vidra-brand-900); }
        .muted { color:var(--vidra-ink-500); font-size:11px; } .mono { font-family:'JetBrains Mono',monospace; } .direita { text-align:right; }
        .pagador { background:var(--vidra-glass-50); border-radius:8px; padding:16px; margin:18px 0; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th { text-align:left; font-size:10px; color:var(--vidra-ink-500); }
        th,td { border-bottom:1px solid var(--vidra-line-400); padding:8px 5px; vertical-align:top; overflow-wrap:anywhere; }
        th.direita { text-align:right; } thead { display:table-header-group; } tr { break-inside:avoid; }
        .totais { margin-top:18px; margin-left:auto; width:260px; } .totais td { border:0; padding:3px 0; } .total td { font-size:15px; font-weight:700; border-top:2px solid var(--vidra-brand-900); padding-top:8px; }
        p { margin:5px 0; }
    </style>
</head>
<body>
<header>
    <div>
        <h1>{{ $laboratorio?->nome }}</h1>
        <div class="muted">{{ $laboratorio?->endereco }} · {{ $laboratorio?->cidade }}/{{ $laboratorio?->uf }}</div>
        <div class="muted">CNPJ {{ $laboratorio?->cnpj }} · {{ $laboratorio?->telefone }}</div>
    </div>
    <div class="direita"><div class="muted">Fatura</div><strong class="mono">{{ $fatura->numeroFormatado() }}</strong><div class="muted">Competência {{ $fatura->competencia->rotulo() }}</div></div>
</header>
<section class="pagador" aria-label="Pagador">
    <span class="muted">{{ $fatura->pagador->tipo->value === 'clinica' ? 'Clínica' : 'Veterinário' }}</span>
    <strong style="display:block;">{{ $fatura->pagador->nome }}</strong>
    @if ($fatura->pagador->email)<span>{{ $fatura->pagador->email }}</span>@endif
    <p>Vencimento: <strong>{{ $fatura->vencimento->setTimezone($fuso)->format('d/m/Y') }}</strong></p>
</section>
<h2>Pedidos liberados em {{ $fatura->competencia->rotulo() }}</h2>
<table>
    <thead><tr><th>Liberado em</th><th>Protocolo</th><th>Paciente</th><th>Exames</th><th class="direita">Valor</th></tr></thead>
    <tbody>
    @foreach ($fatura->linhas as $linha)
        <tr>
            <td class="mono">{{ $linha->liberadoEm->setTimezone($fuso)->format('d/m/Y') }}</td>
            <td class="mono">{{ $linha->protocolo }}</td>
            <td>{{ $linha->paciente }}</td>
            <td>{{ $linha->exames }}</td>
            <td class="mono direita">{{ $reais($linha->valorCentavos) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<table class="totais">
    <tr><td>Subtotal</td><td class="mono direita">{{ $reais($fatura->subtotal()) }}</td></tr>
    @foreach ($fatura->creditos() as $credito)
        <tr><td>Crédito: {{ $credito->motivo }}</td><td class="mono direita">− {{ $reais($credito->valorCentavos) }}</td></tr>
    @endforeach
    <tr class="total"><td>Total</td><td class="mono direita">{{ $reais($fatura->total()) }}</td></tr>
</table>
<p class="muted" style="margin-top:26px;">Fatura emitida pelo Vidra</p>
</body>
</html>
