{{--
    Etiquetas de amostra (50 × 25 mm, Code 128). No MVP a impressão é esta página:
    o navegador manda para a impressora térmica. O QZ Tray ou o agente em Go
    (fase 2) passam a imprimir direto, sem diálogo.
--}}
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiquetas {{ $etiquetas[0]->protocolo ?? '' }}</title>
    <style>
        @page { size: 50mm 25mm; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Plus Jakarta Sans', Arial, sans-serif; color: #000; background: #fff; }
        .etiqueta { width: 50mm; height: 25mm; padding: 1.5mm 2mm; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; page-break-after: always; break-after: page; }
        .etiqueta:last-of-type { page-break-after: auto; break-after: auto; }
        .paciente { font-size: 9pt; font-weight: 700; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .paciente span { font-weight: 500; }
        .barras { height: 10mm; }
        .barras svg { display: block; width: 100%; height: 100%; }
        .codigo { font-family: 'JetBrains Mono', 'Courier New', monospace; font-size: 8pt; font-weight: 700; text-align: center; line-height: 1; }
        .rodape { display: flex; justify-content: space-between; font-size: 6.5pt; line-height: 1; gap: 2mm; }
        .acoes { padding: 12px; display: flex; gap: 12px; align-items: center; font-size: 14px; }
        .acoes button { min-height: 44px; padding: 0 20px; border: 0; border-radius: 6px; background: #0E4F5C; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; }
        @media print { .acoes { display: none; } }
        @media screen { .etiqueta { border: 1px dashed #999; margin: 12px; } }
    </style>
</head>
<body>
    <div class="acoes">
        <button type="button" onclick="window.print()">Imprimir etiquetas</button>
        <span>{{ count($etiquetas) }} {{ count($etiquetas) === 1 ? 'etiqueta' : 'etiquetas' }} de 50 × 25 mm</span>
    </div>
    @foreach ($etiquetas as $etiqueta)
        <div class="etiqueta">
            <div class="paciente">{{ $etiqueta->paciente }} <span>· {{ $etiqueta->especieCurta }}</span></div>
            <div class="barras" role="img" aria-label="Código de barras {{ $etiqueta->codigo }}">{!! str_replace('<svg ', '<svg preserveAspectRatio="none" ', $etiqueta->codigoDeBarrasSvg) !!}</div>
            <div class="codigo">{{ $etiqueta->codigo }}</div>
            <div class="rodape"><span>{{ $etiqueta->material }}</span><span>{{ $etiqueta->protocolo }}</span></div>
        </div>
    @endforeach
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
