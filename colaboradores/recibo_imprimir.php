<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$recibo_id = (int)($_GET['recibo_id'] ?? 0);
$stmt = db()->prepare(
    'SELECT r.*, c.nome, c.cpf, c.cargo, c.departamento
     FROM recibos_salario r JOIN colaboradores c ON c.id = r.colaborador_id
     WHERE r.id = ?'
);
$stmt->execute([$recibo_id]);
$r = $stmt->fetch();
if (!$r) { echo 'Recibo no encontrado.'; exit; }

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Extraer moneda e items — compatível com e sem migration
$moneda  = $r['moneda'] ?? 'GS';
$items   = [];
$obs_raw = $r['observacoes'] ?? '';

// Se migration rodou, usar items_json; senão, extrair de observacoes
if (!empty($r['items_json'])) {
    $items = json_decode($r['items_json'], true) ?: [];
} elseif (preg_match('/Items: (\[.*\])/', $obs_raw, $m2)) {
    $items = json_decode($m2[1], true) ?: [];
}

// Extrair tasa de cambio da observação se não tem coluna
$tipo_cambio_str = '';
if (!empty($r['tipo_cambio'])) {
    $tipo_cambio_str = number_format((float)$r['tipo_cambio'], 0, ',', '.');
} elseif (preg_match('/Tasa de cambio: ([^\|]+)/', $obs_raw, $m3)) {
    $tipo_cambio_str = trim($m3[1]);
}

// Limpar observações para exibição (remover partes técnicas)
$obs_display = preg_replace('/Tasa de cambio:[^\|]*\|?\s*/', '', $obs_raw);
$obs_display = preg_replace('/Moneda:[^\|]*\|?\s*/', '', $obs_display);
$obs_display = preg_replace('/Items:\s*\[.*?\]\s*/', '', $obs_display);
$obs_display = trim($obs_display, " \t\n\r|");

$bruto_gs = (float)($r['salario_gs'] ?? $r['salario_bruto']);
$liquido  = (float)$r['salario_liquido'];
$periodo  = $meses[$r['mes']] . ' ' . $r['ano'];

// Número por extenso
function guaraniesALetras(float $n): string {
    $n = (int)round($n);
    if ($n === 0) return 'CERO GUARANÍES';
    $u = ['','UNO','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE',
          'DIEZ','ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];
    $d = ['','DIEZ','VEINTE','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
    $c = ['','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS','SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];
    function g(int $n, $u, $d, $c): string {
        $s = '';
        if ($n >= 100) { $s .= ($n===100?'CIEN':$c[(int)($n/100)]).' '; $n%=100; }
        if ($n >= 20)  { $s .= $d[(int)($n/10)]; if($n%10) $s.=' Y '.$u[$n%10]; $n=0; }
        if ($n > 0)    { $s .= $u[$n]; }
        return trim($s);
    }
    $p = [];
    if ($n>=1000000){ $m=(int)($n/1000000); $p[]=($m===1?'UN MILLÓN':g($m,$u,$d,$c).' MILLONES'); $n%=1000000; }
    if ($n>=1000)   { $m=(int)($n/1000);    $p[]=($m===1?'MIL':g($m,$u,$d,$c).' MIL');             $n%=1000; }
    if ($n>0)       { $p[]=g($n,$u,$d,$c); }
    return implode(' ',$p).' GUARANÍES';
}
$liquido_letras = guaraniesALetras($liquido);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo <?= $periodo ?> — <?= htmlspecialchars($r['nome']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 13px;
            max-width: 780px;
            margin: 30px auto;
            padding: 0 24px;
            color: #1f2937;
        }
        .no-print { margin-bottom: 24px; display: flex; gap: 10px; }
        .btn-print {
            padding: 9px 20px; background: #1a56db; color: #fff;
            border: none; border-radius: 7px; cursor: pointer;
            font-family: inherit; font-size: 13px;
        }
        .btn-back {
            padding: 9px 20px; background: #fff; color: #374151;
            border: 1px solid #d1d5db; border-radius: 7px; cursor: pointer;
            font-family: inherit; font-size: 13px; text-decoration: none;
            display: inline-flex; align-items: center;
        }
        /* Logo */
        .recibo-logo { margin-bottom: 20px; }
        .recibo-logo img { width: 180px; height: auto; display: block; }

        /* Título */
        h1 { font-size: 20px; font-weight: 700; margin-bottom: 14px; }

        /* Datos */
        .datos-grid {
            display: grid;
            grid-template-columns: 110px 1fr;
            gap: 3px 0;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .datos-grid .lbl { color: #6b7280; font-weight: 500; }
        .datos-grid .val { color: #111827; font-weight: 400; }

        /* Sección */
        .section-title { font-size: 15px; font-weight: 600; margin-bottom: 10px; }

        /* Tabla */
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th {
            background: #c9daf8 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            padding: 9px 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: #1e3a5f;
            border: 1px solid #b0c8f0;
        }
        td {
            padding: 8px 12px;
            border: 1px solid #c9daf8;
            font-size: 12px;
            vertical-align: middle;
            color: #1f2937;
        }
        tr:nth-child(even) td { background: #f8faff; }

        /* Totales */
        .totales { margin-top: 4px; }
        .totales p { font-size: 12px; color: #374151; margin-bottom: 5px; }
        .totales .total-principal { font-size: 14px; font-weight: 700; color: #111827; }

        /* Firmas */
        .firma-area {
            display: flex;
            justify-content: space-between;
            margin-top: 90px;
        }
        .firma-box { text-align: center; width: 42%; }
        .firma-line {
            border-top: 1px solid #374151;
            padding-top: 8px;
            font-size: 12px;
            color: #374151;
        }

        @media print {
            .no-print { display: none !important; }
            body { margin: 15px; padding: 0 10px; }
            th {
                background: #c9daf8 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">Imprimir / Guardar PDF</button>
    <a href="/colaboradores/recibos?id=<?= $r['colaborador_id'] ?>" class="btn-back">← Volver</a>
</div>

<div class="recibo-logo">
    <img src="/assets/img/logo-negro.png" alt="MercadoPar">
</div>

<h1>Recibo de pago de salario</h1>

<div class="datos-grid">
    <span class="lbl">Colaborador:</span>
    <span class="val"><?= htmlspecialchars($r['nome']) ?></span>
    <span class="lbl">C.I.:</span>
    <span class="val"><?= htmlspecialchars($r['cpf']) ?></span>
    <span class="lbl">Cargo:</span>
    <span class="val"><?= htmlspecialchars($r['cargo'] ?? '—') ?></span>
    <span class="lbl">Período:</span>
    <span class="val"><?= $periodo ?></span>
    <span class="lbl">Fecha de pago:</span>
    <span class="val"><?= $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '___/___/' . $r['ano'] ?></span>
</div>

<p class="section-title">Detalles del pago</p>

<table>
    <thead>
        <tr>
            <th style="width:110px">Fecha</th>
            <th>Descripción</th>
            <th style="width:140px;text-align:right">Crédito</th>
            <th style="width:140px;text-align:right">Débito</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): if ($item['tipo'] !== 'debito') continue; ?>
        <tr>
            <td><?= $item['fecha'] ? date('d/m/Y', strtotime($item['fecha'])) : '' ?></td>
            <td><?= htmlspecialchars($item['descripcion']) ?></td>
            <td style="text-align:right">—</td>
            <td style="text-align:right"><?= number_format($item['monto'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php foreach ($items as $item): if ($item['tipo'] !== 'credito') continue; ?>
        <tr>
            <td><?= $item['fecha'] ? date('d/m/Y', strtotime($item['fecha'])) : '' ?></td>
            <td><?= htmlspecialchars($item['descripcion']) ?></td>
            <td style="text-align:right"><?= number_format($item['monto'], 0, ',', '.') ?></td>
            <td style="text-align:right">—</td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td><?= $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '' ?></td>
            <td>
                Sueldo base <?= $periodo ?>
                <?php if ($moneda === 'USD'): ?>
                    — <?= number_format((float)$r['salario_bruto'], 2, '.', ',') ?> USD
                <?php endif; ?>
            </td>
            <td style="text-align:right"><?= number_format($bruto_gs, 0, ',', '.') ?> Gs</td>
            <td style="text-align:right">—</td>
        </tr>
    </tbody>
</table>

<div class="totales">
    <p class="total-principal">Total acreditado en el mes: <?= number_format($liquido, 0, ',', '.') ?> Gs.</p>
    <p>La suma de: <?= $liquido_letras ?>.</p>
    <?php if ($tipo_cambio_str): ?>
    <p>Tasa de cambio: <?= htmlspecialchars($tipo_cambio_str) ?> Gs. por USD.</p>
    <?php endif; ?>
    <?php if ($obs_display): ?>
    <p style="margin-top:6px;color:#6b7280"><em><?= htmlspecialchars($obs_display) ?></em></p>
    <?php endif; ?>
</div>

<div class="firma-area">
    <div class="firma-box">
        <div class="firma-line">
            <strong>MercadoPar</strong><br>Empleador
        </div>
    </div>
    <div class="firma-box">
        <div class="firma-line">
            <strong><?= htmlspecialchars($r['nome']) ?></strong><br>
            Colaborador — C.I.: <?= htmlspecialchars($r['cpf']) ?>
        </div>
    </div>
</div>

</body>
</html>
