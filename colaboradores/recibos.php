<?php
$pageTitle = 'Recibos de Salario — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$colaborador_id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM colaboradores WHERE id = ?');
$stmt->execute([$colaborador_id]);
$col = $stmt->fetch();
if (!$col) { header('Location: /colaboradores/index.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mes         = (int)($_POST['mes'] ?? 0);
    $ano         = (int)($_POST['ano'] ?? 0);
    $moneda      = in_array($_POST['moneda'] ?? '', ['GS','USD']) ? $_POST['moneda'] : 'GS';
    $bruto       = (float)str_replace(['.', ','], ['', '.'], $_POST['salario_bruto'] ?? '0');
    $tipo_cambio = (float)str_replace(['.', ','], ['', '.'], $_POST['tipo_cambio'] ?? '0');
    $pagamento   = $_POST['data_pagamento'] ?: null;
    $obs         = trim($_POST['observacoes'] ?? '');

    // Calcular salario en Gs.
    $bruto_gs = ($moneda === 'USD') ? round($bruto * $tipo_cambio) : $bruto;

    // Items (anticipos + otros)
    $items = [];
    $itemDesc  = $_POST['item_desc']  ?? [];
    $itemFecha = $_POST['item_fecha'] ?? [];
    $itemTipo  = $_POST['item_tipo']  ?? [];
    $itemMonto = $_POST['item_monto'] ?? [];
    foreach ($itemDesc as $i => $desc) {
        if (trim($desc) === '') continue;
        $items[] = [
            'fecha'      => $itemFecha[$i] ?? '',
            'descripcion'=> trim($desc),
            'tipo'       => in_array($itemTipo[$i] ?? '', ['credito','debito']) ? $itemTipo[$i] : 'debito',
            'monto'      => (float)str_replace(['.', ','], ['', '.'], $itemMonto[$i] ?? '0'),
        ];
    }

    // Calcular líquido
    $total_creditos = $bruto_gs;
    $total_debitos  = 0;
    foreach ($items as $item) {
        if ($item['tipo'] === 'credito') $total_creditos += $item['monto'];
        else $total_debitos += $item['monto'];
    }
    $liquido = $total_creditos - $total_debitos;

    if (!$mes || !$ano) $errors[] = 'El mes y año son obligatorios.';
    if ($bruto <= 0)    $errors[] = 'El salario base debe ser mayor a cero.';
    if ($moneda === 'USD' && $tipo_cambio <= 0) $errors[] = 'Ingrese la tasa de cambio.';

    if (!$errors) {
        try {
            db()->prepare(
                'INSERT INTO recibos_salario
                 (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
                  salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
                 VALUES (?,?,?,?,0,0,0,0,?,?,?,?,?,?,?)'
            )->execute([
                $colaborador_id, $mes, $ano, $bruto,
                $liquido, $pagamento, $obs,
                $moneda, $tipo_cambio, $bruto_gs,
                json_encode($items, JSON_UNESCAPED_UNICODE)
            ]);
            header("Location: /colaboradores/recibos.php?id=$colaborador_id&saved=1");
            exit;
        } catch (\PDOException $e) {
            $errors[] = 'Ya existe un recibo para este mes/año.';
        }
    }
}

$recibos = db()->prepare(
    'SELECT * FROM recibos_salario WHERE colaborador_id = ? ORDER BY ano DESC, mes DESC'
);
$recibos->execute([$colaborador_id]);
$recibos = $recibos->fetchAll();

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Moneda y salario base del colaborador
$moneda_col = $col['moneda_salario'] ?? 'GS';
$salario_col = $col['salario'] ?? 0;
?>
<div class="page-header">
    <h1>Recibos — <?= htmlspecialchars($col['nome']) ?></h1>
    <a href="/colaboradores/index.php" class="btn btn-outline">← Volver</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Recibo registrado exitosamente.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:440px 1fr;gap:20px;align-items:start">
    <div class="form-card">
        <h3 style="margin-bottom:16px;font-size:15px">Nuevo Recibo</h3>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
        <form method="POST" id="form-recibo">
            <div class="form-grid" style="grid-template-columns:1fr 1fr">
                <div class="form-group">
                    <label>Mes *</label>
                    <select name="mes">
                        <?php for ($m=1;$m<=12;$m++): ?>
                            <option value="<?= $m ?>" <?= $m===(int)date('n') ? 'selected' : '' ?>><?= $meses[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Año *</label>
                    <input type="number" name="ano" value="<?= date('Y') ?>" min="2000" max="2100">
                </div>
            </div>

            <div class="form-group" style="margin-top:12px">
                <label>Moneda del Salario</label>
                <select name="moneda" id="moneda-sel" onchange="toggleCambio()">
                    <option value="GS"  <?= $moneda_col === 'GS'  ? 'selected' : '' ?>>Guaraní (Gs.)</option>
                    <option value="USD" <?= $moneda_col === 'USD' ? 'selected' : '' ?>>Dólar (USD)</option>
                </select>
            </div>

            <div class="form-group" style="margin-top:10px">
                <label>Salario Base <?= $moneda_col === 'USD' ? '(USD)' : '(Gs.)' ?></label>
                <input type="number" name="salario_bruto" id="salario-input" step="0.01" min="0"
                       value="<?= htmlspecialchars($salario_col) ?>">
            </div>

            <div id="cambio-box" style="margin-top:10px;<?= $moneda_col !== 'USD' ? 'display:none' : '' ?>">
                <div class="form-group">
                    <label>Tasa de Cambio (Gs. por 1 USD)</label>
                    <input type="number" name="tipo_cambio" id="tipo-cambio" step="1" min="0" value="0"
                           oninput="calcularGs()">
                </div>
                <div class="form-group" style="margin-top:8px">
                    <label>Equivalente en Guaraníes</label>
                    <input type="text" id="gs-preview" readonly
                           style="background:#f3f4f6;color:#374151;font-weight:600"
                           placeholder="Se calculará automáticamente">
                </div>
            </div>

            <div class="form-group" style="margin-top:10px">
                <label>Fecha de Pago</label>
                <input type="date" name="data_pagamento" value="<?= date('Y-m-d') ?>">
            </div>

            <!-- Items dinámicos: anticipos, débitos, créditos -->
            <div style="margin-top:16px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <label style="font-size:12px;font-weight:600;color:#4b5563">MOVIMIENTOS (anticipos, otros)</label>
                    <button type="button" onclick="addItem()" class="btn btn-outline btn-sm">+ Agregar</button>
                </div>
                <div id="items-container">
                    <!-- Se agregan dinámicamente -->
                </div>
            </div>

            <div class="form-group" style="margin-top:10px">
                <label>Observaciones</label>
                <textarea name="observacoes"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:16px;width:100%;justify-content:center">
                Generar Recibo
            </button>
        </form>
    </div>

    <div>
        <h3 style="margin-bottom:14px;font-size:15px">Historial de Recibos</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Período</th><th>Salario Base</th><th>Moneda</th><th>Líquido (Gs.)</th><th>Pago</th><th></th></tr>
                </thead>
                <tbody>
                <?php if ($recibos): foreach ($recibos as $r): ?>
                    <tr>
                        <td><?= $meses[$r['mes']] . ' ' . $r['ano'] ?></td>
                        <td>
                            <?php if (!empty($r['moneda']) && $r['moneda'] === 'USD'): ?>
                                USD <?= number_format($r['salario_bruto'], 2, '.', ',') ?>
                            <?php else: ?>
                                Gs. <?= number_format($r['salario_bruto'], 0, ',', '.') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($r['moneda'] ?? 'GS') ?></td>
                        <td><strong>Gs. <?= number_format($r['salario_liquido'], 0, ',', '.') ?></strong></td>
                        <td><?= $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '—' ?></td>
                        <td>
                            <a href="/colaboradores/recibo_imprimir.php?recibo_id=<?= $r['id'] ?>"
                               target="_blank" class="btn btn-outline btn-sm">Ver / Imprimir</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#6b7280;padding:16px">Ningún recibo generado.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleCambio() {
    var moneda = document.getElementById('moneda-sel').value;
    document.getElementById('cambio-box').style.display = moneda === 'USD' ? '' : 'none';
}
function calcularGs() {
    var sal = parseFloat(document.getElementById('salario-input').value) || 0;
    var tc  = parseFloat(document.getElementById('tipo-cambio').value) || 0;
    var gs  = Math.round(sal * tc);
    document.getElementById('gs-preview').value = gs > 0 ? 'Gs. ' + gs.toLocaleString('es-PY') : '';
}
document.getElementById('salario-input').addEventListener('input', calcularGs);

var itemCount = 0;
function addItem(fecha, desc, tipo, monto) {
    var c = document.getElementById('items-container');
    var div = document.createElement('div');
    div.style.cssText = 'display:grid;grid-template-columns:130px 1fr 90px 110px 30px;gap:6px;margin-bottom:8px;align-items:end';
    div.innerHTML = `
        <div>
            <label style="font-size:11px;font-weight:600;color:#6b7280">Fecha</label>
            <input type="date" name="item_fecha[]" value="${fecha||''}" style="width:100%;padding:7px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px">
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;color:#6b7280">Descripción</label>
            <input type="text" name="item_desc[]" value="${desc||''}" placeholder="Ej: Anticipo" style="width:100%;padding:7px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px">
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;color:#6b7280">Tipo</label>
            <select name="item_tipo[]" style="width:100%;padding:7px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px">
                <option value="debito" ${tipo==='debito'?'selected':''}>Débito</option>
                <option value="credito" ${tipo==='credito'?'selected':''}>Crédito</option>
            </select>
        </div>
        <div>
            <label style="font-size:11px;font-weight:600;color:#6b7280">Monto (Gs.)</label>
            <input type="number" name="item_monto[]" value="${monto||''}" min="0" step="1" style="width:100%;padding:7px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px">
        </div>
        <div style="padding-bottom:2px">
            <button type="button" onclick="this.parentNode.parentNode.remove()" style="background:#fee2e2;border:none;border-radius:6px;padding:7px 8px;cursor:pointer;font-size:14px;color:#e02424">✕</button>
        </div>
    `;
    c.appendChild(div);
    itemCount++;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
