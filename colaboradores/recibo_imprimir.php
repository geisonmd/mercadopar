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

$moneda     = $r['moneda'] ?? 'GS';
$tipo_cambio = (float)($r['tipo_cambio'] ?? 0);
$bruto_gs   = (float)($r['salario_gs'] ?? ($moneda === 'GS' ? $r['salario_bruto'] : 0));
$liquido    = (float)$r['salario_liquido'];
$items      = json_decode($r['items_json'] ?? '[]', true) ?: [];
$mes_nome   = $meses[$r['mes']];
$periodo    = $mes_nome . ' ' . $r['ano'];

// Calcular total débitos
$total_debitos = 0;
foreach ($items as $item) {
    if ($item['tipo'] === 'debito') $total_debitos += $item['monto'];
}

// Número por extenso en español
function guaraniesALetras(float $n): string {
    $n = (int)round($n);
    if ($n === 0) return 'CERO GUARANÍES';
    $unidades = ['','UNO','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE',
                 'DIEZ','ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];
    $decenas  = ['','DIEZ','VEINTE','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
    $centenas = ['','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS','SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];
    function grupo(int $n, array $u, array $d, array $c): string {
        $s = '';
        if ($n >= 100) { $s .= ($n === 100 ? 'CIEN' : $c[(int)($n/100)]) . ' '; $n %= 100; }
        if ($n >= 20)  { $s .= $d[(int)($n/10)]; if ($n%10) $s .= ' Y ' . $u[$n%10]; $n = 0; }
        if ($n > 0)    { $s .= $u[$n]; }
        return trim($s);
    }
    $partes = [];
    if ($n >= 1000000) {
        $m = (int)($n/1000000);
        $partes[] = ($m === 1 ? 'UN MILLÓN' : grupo($m, $unidades, $decenas, $centenas) . ' MILLONES');
        $n %= 1000000;
    }
    if ($n >= 1000) {
        $m = (int)($n/1000);
        $partes[] = ($m === 1 ? 'MIL' : grupo($m, $unidades, $decenas, $centenas) . ' MIL');
        $n %= 1000;
    }
    if ($n > 0) {
        $partes[] = grupo($n, $unidades, $decenas, $centenas);
    }
    return implode(' ', $partes) . ' GUARANÍES';
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
            padding: 0 20px;
            color: #1f2937;
        }
        .no-print { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn-print {
            padding: 9px 20px; background: #1a56db; color: #fff;
            border: none; border-radius: 7px; cursor: pointer; font-family: inherit; font-size: 13px;
        }
        .btn-back {
            padding: 9px 20px; background: #fff; color: #374151;
            border: 1px solid #d1d5db; border-radius: 7px; cursor: pointer;
            font-family: inherit; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center;
        }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .info-line { margin-bottom: 3px; font-size: 13px; color: #374151; }
        .info-line span { color: #6b7280; }
        .section-title { font-size: 17px; font-weight: 600; margin: 20px 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th {
            background: #c9daf8;
            padding: 9px 10px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            border-bottom: 2px solid #c9daf8;
        }
        td { padding: 8px 10px; border: 1px solid #c9daf8; font-size: 12px; vertical-align: top; }
        .total-line { font-size: 12px; color: #374151; margin-bottom: 4px; }
        .total-line strong { font-size: 13px; }
        .firma-area {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }
        .firma-box { text-align: center; width: 44%; }
        .firma-line { border-top: 1px solid #1f2937; padding-top: 5px; font-size: 12px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 20px; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    <a href="javascript:history.back()" class="btn-back">← Volver</a>
</div>

<h1>Recibo de pago de salario</h1>
<br>
<p class="info-line"><span>Datos del colaborador:</span> <?= htmlspecialchars($r['nome']) ?></p>
<p class="info-line"><span>C.I.:</span> <?= htmlspecialchars($r['cpf']) ?></p>
<p class="info-line"><span>Cargo:</span> <?= htmlspecialchars($r['cargo'] ?? '—') ?></p>
<p class="info-line"><span>Fecha:</span> <?= $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '___/___/' . $r['ano'] ?></p>

<p class="section-title">Detalles del pago</p>

<table>
    <thead>
        <tr>
            <th style="width:120px">Fecha</th>
            <th>Descripción</th>
            <th style="width:150px;text-align:right">Crédito</th>
            <th style="width:150px;text-align:right">Débito</th>
        </tr>
    </thead>
    <tbody>
        <!-- Primero los débitos (anticipos) -->
        <?php foreach ($items as $item): if ($item['tipo'] !== 'debito') continue; ?>
        <tr>
            <td><?= $item['fecha'] ? date('d/m/Y', strtotime($item['fecha'])) : '' ?></td>
            <td><?= htmlspecialchars($item['descripcion']) ?></td>
            <td style="text-align:right"></td>
            <td style="text-align:right"><?= number_format($item['monto'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <!-- Créditos adicionales -->
        <?php foreach ($items as $item): if ($item['tipo'] !== 'credito') continue; ?>
        <tr>
            <td><?= $item['fecha'] ? date('d/m/Y', strtotime($item['fecha'])) : '' ?></td>
            <td><?= htmlspecialchars($item['descripcion']) ?></td>
            <td style="text-align:right"><?= number_format($item['monto'], 0, ',', '.') ?></td>
            <td style="text-align:right"></td>
        </tr>
        <?php endforeach; ?>
        <!-- Sueldo base -->
        <tr>
            <td><?= $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '' ?></td>
            <td>
                Sueldo base <?= $periodo ?>
                <?php if ($moneda === 'USD'): ?>
                    — <?= number_format($r['salario_bruto'], 0, '.', ',') ?> USD
                <?php endif; ?>
            </td>
            <td style="text-align:right"><?= number_format($bruto_gs, 0, ',', '.') ?> Gs</td>
            <td style="text-align:right">–</td>
        </tr>
    </tbody>
</table>

<p class="total-line">
    <strong>Total acreditado en el mes <?= number_format($liquido, 0, ',', '.') ?> Gs.</strong>
</p>
<p class="total-line">La suma de: <?= $liquido_letras ?>.</p>
<?php if ($moneda === 'USD' && $tipo_cambio > 0): ?>
<p class="total-line">Tasa de cambio: <?= number_format($tipo_cambio, 0, ',', '.') ?> Gs.</p>
<?php endif; ?>
<?php if ($r['observacoes']): ?>
<p class="total-line" style="margin-top:8px"><em><?= htmlspecialchars($r['observacoes']) ?></em></p>
<?php endif; ?>

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
