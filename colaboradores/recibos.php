<?php
$pageTitle = 'Recibos de Salario — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$colaborador_id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM colaboradores WHERE id = ?');
$stmt->execute([$colaborador_id]);
$col = $stmt->fetch();
if (!$col) { header('Location: /colaboradores'); exit; }

$errors = [];
$moneda_col = $col['moneda_salario'] ?? 'GS';
$salario_col = (float)($col['salario'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mes          = (int)($_POST['mes'] ?? 0);
    $ano          = (int)($_POST['ano'] ?? 0);
    $moneda_recibo= in_array($_POST['moneda_recibo'] ?? '', ['GS','USD']) ? $_POST['moneda_recibo'] : $moneda_col;
    $bruto_raw    = (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9,.]/', '', $_POST['salario_bruto'] ?? '0'));
    $tipo_cambio  = (float)str_replace(['.', ','], ['', '.'], $_POST['tipo_cambio'] ?? '0');
    $pagamento    = $_POST['data_pagamento'] ?: null;
    $obs          = trim($_POST['observacoes'] ?? '');

    // Calcular valores
    // bruto = valor tal como ingresado (en la moneda del recibo)
    // bruto_gs = equivalente en Gs (para el recibo)
    $bruto_gs = 0;
    $bruto_usd = 0;
    $precisa_cambio = ($moneda_col !== $moneda_recibo);

    if ($moneda_recibo === 'GS') {
        if ($moneda_col === 'GS') {
            // Mismo: GS → GS
            $bruto_gs = $bruto_raw;
        } else {
            // USD base → recibo GS: GS = USD * tipo_cambio
            $bruto_usd = $bruto_raw;
            $bruto_gs  = round($bruto_raw * $tipo_cambio);
        }
    } else {
        // Recibo en USD
        if ($moneda_col === 'USD') {
            // Mismo: USD → USD
            $bruto_usd = $bruto_raw;
            $bruto_gs  = $tipo_cambio > 0 ? round($bruto_raw * $tipo_cambio) : 0;
        } else {
            // GS base → recibo USD: USD = GS / tipo_cambio
            $bruto_gs  = $bruto_raw;
            $bruto_usd = $tipo_cambio > 0 ? round($bruto_raw / $tipo_cambio, 2) : 0;
        }
    }

    // Items
    $items = [];
    foreach ($_POST['item_desc'] ?? [] as $i => $desc) {
        if (trim($desc) === '') continue;
        $items[] = [
            'fecha'       => $_POST['item_fecha'][$i] ?? '',
            'descripcion' => trim($desc),
            'tipo'        => in_array($_POST['item_tipo'][$i] ?? '', ['credito','debito']) ? $_POST['item_tipo'][$i] : 'debito',
            'monto'       => (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9,.]/', '', $_POST['item_monto'][$i] ?? '0')),
        ];
    }

    // Líquido en Gs.
    $extra_creditos = 0;
    $total_debitos  = 0;
    foreach ($items as $item) {
        if ($item['tipo'] === 'credito') $extra_creditos += $item['monto'];
        else $total_debitos += $item['monto'];
    }
    $liquido = $bruto_gs + $extra_creditos - $total_debitos;

    if (!$mes || !$ano)       $errors[] = 'El mes y año son obligatorios.';
    if ($bruto_raw <= 0)      $errors[] = 'El salario base debe ser mayor a cero.';
    if ($precisa_cambio && $tipo_cambio <= 0) $errors[] = 'Ingrese la tasa de cambio.';

    if (!$errors) {
        // Verificar duplicado manualmente para mostrar erro correcto
        $dup = db()->prepare('SELECT id FROM recibos_salario WHERE colaborador_id=? AND mes=? AND ano=?');
        $dup->execute([$colaborador_id, $mes, $ano]);
        if ($dup->fetch()) {
            $errors[] = 'Ya existe un recibo para ' . $mes . '/' . $ano . '. Elimínelo primero para generar uno nuevo.';
        } else {
            try {
                db()->prepare(
                    'INSERT INTO recibos_salario
                     (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
                      salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
                     VALUES (?,?,?,?,0,0,0,0,?,?,?,?,?,?,?)'
                )->execute([
                    $colaborador_id, $mes, $ano, $bruto_raw,
                    $liquido, $pagamento, $obs,
                    $moneda_recibo, $tipo_cambio, $bruto_gs,
                    json_encode($items, JSON_UNESCAPED_UNICODE)
                ]);
                header("Location: /colaboradores/recibos?id=$colaborador_id&saved=1");
                exit;
            } catch (\PDOException $e) {
                $errors[] = 'Error al guardar: ' . $e->getMessage();
            }
        }
    }
}

$recibos = db()->prepare('SELECT * FROM recibos_salario WHERE colaborador_id=? ORDER BY ano DESC, mes DESC');
$recibos->execute([$colaborador_id]);
$recibos = $recibos->fetchAll();

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Formato salario del colaborador para mostrar en el form
$salario_fmt = $moneda_col === 'USD'
    ? number_format($salario_col, 2, '.', ',')
    : number_format($salario_col, 0, ',', '.');
?>
<div class="page-header">
    <h1>Recibos — <?= htmlspecialchars($col['nome']) ?></h1>
    <a href="/colaboradores" class="btn btn-outline">← Volver</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Recibo registrado exitosamente.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:460px 1fr;gap:20px;align-items:start">
<div class="form-card">
    <h3 style="margin-bottom:16px;font-size:15px">Nuevo Recibo</h3>
    <p style="font-size:12px;color:#6b7280;margin-bottom:16px">
        Salario base registrado:
        <strong><?= $moneda_col === 'USD' ? 'USD ' . $salario_fmt : 'Gs. ' . $salario_fmt ?></strong>
    </p>

    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST">
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
            <label>Moneda del Recibo</label>
            <select name="moneda_recibo" id="moneda-recibo" onchange="onMonedaChange()">
                <option value="GS"  <?= $moneda_col === 'GS'  ? 'selected' : '' ?>>Guaraní (Gs.)</option>
                <option value="USD" <?= $moneda_col === 'USD' ? 'selected' : '' ?>>Dólar (USD)</option>
            </select>
        </div>

        <div class="form-group" style="margin-top:10px">
            <label id="label-salario">Salario Base</label>
            <input type="text" name="salario_bruto" id="salario-input"
                   value="<?= htmlspecialchars($salario_fmt) ?>"
                   autocomplete="off" oninput="calcularEquivalente()">
        </div>

        <div id="cambio-box" style="margin-top:10px;display:none">
            <div class="form-group">
                <label id="label-cambio">Tasa de Cambio (Gs. por 1 USD)</label>
                <input type="number" name="tipo_cambio" id="tipo-cambio" step="1" min="0" value="0"
                       oninput="calcularEquivalente()">
            </div>
            <div class="form-group" style="margin-top:8px">
                <label>Equivalente</label>
                <input type="text" id="equiv-preview" readonly
                       style="background:#f3f4f6;color:#374151;font-weight:600"
                       placeholder="Se calculará automáticamente">
            </div>
        </div>

        <div class="form-group" style="margin-top:10px">
            <label>Fecha de Pago</label>
            <input type="date" name="data_pagamento" value="<?= date('Y-m-d') ?>">
        </div>

        <!-- Items -->
        <div style="margin-top:18px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <label style="font-size:12px;font-weight:600;color:#4b5563">ANTICIPOS Y OTROS MOVIMIENTOS</label>
                <button type="button" onclick="addItem()" class="btn btn-outline btn-sm">+ Agregar</button>
            </div>
            <div id="items-container"></div>
        </div>

        <div class="form-group" style="margin-top:12px">
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
                <tr><th>Período</th><th>Salario</th><th>Moneda</th><th>Líquido (Gs.)</th><th>Pago</th><th></th></tr>
            </thead>
            <tbody>
            <?php if ($recibos): foreach ($recibos as $r): ?>
                <tr>
                    <td><?= $meses[$r['mes']] . ' ' . $r['ano'] ?></td>
                    <td>
                        <?php if (($r['moneda'] ?? 'GS') === 'USD'): ?>
                            USD <?= number_format($r['salario_bruto'], 2, '.', ',') ?>
                        <?php else: ?>
                            Gs. <?= number_format($r['salario_bruto'], 0, ',', '.') ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['moneda'] ?? 'GS') ?></td>
                    <td><strong>Gs. <?= number_format($r['salario_liquido'], 0, ',', '.') ?></strong></td>
                    <td><?= $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '—' ?></td>
                    <td>
                        <a href="/colaboradores/recibo_imprimir?recibo_id=<?= $r['id'] ?>"
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
const monedaCol  = '<?= $moneda_col ?>';
const salarioCol = <?= $salario_col ?>;

function onMonedaChange() {
    const monedaRecibo = document.getElementById('moneda-recibo').value;
    const precisa = monedaRecibo !== monedaCol;
    document.getElementById('cambio-box').style.display = precisa ? '' : 'none';

    const lbl = document.getElementById('label-salario');
    if (monedaRecibo === 'GS') {
        lbl.textContent = 'Salario Base (Gs.)';
        document.getElementById('label-cambio').textContent = 'Tasa de Cambio (Gs. por 1 USD)';
    } else {
        lbl.textContent = 'Salario Base (USD)';
        document.getElementById('label-cambio').textContent = 'Tasa de Cambio (Gs. por 1 USD)';
    }
    calcularEquivalente();
}

function calcularEquivalente() {
    const monedaRecibo = document.getElementById('moneda-recibo').value;
    const raw   = document.getElementById('salario-input').value.replace(/[^0-9.]/g, '');
    const tc    = parseFloat(document.getElementById('tipo-cambio')?.value) || 0;
    const val   = parseFloat(raw) || 0;
    const prev  = document.getElementById('equiv-preview');
    if (!prev) return;

    let equiv = '';
    if (monedaRecibo === 'GS' && monedaCol === 'USD') {
        // USD base → recibo GS
        equiv = tc > 0 ? 'Gs. ' + Math.round(val * tc).toLocaleString('es-PY') : '';
    } else if (monedaRecibo === 'USD' && monedaCol === 'GS') {
        // GS base → recibo USD
        equiv = tc > 0 ? 'USD ' + (val / tc).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '';
    } else if (monedaRecibo === 'USD' && monedaCol === 'USD') {
        // Mismo USD, mostrar GS referencial
        equiv = tc > 0 ? 'Gs. ' + Math.round(val * tc).toLocaleString('es-PY') : '';
    }
    prev.value = equiv;
}

// Inicializar estado
onMonedaChange();

// Items
function addItem(fecha, desc, tipo, monto) {
    const c = document.getElementById('items-container');
    const div = document.createElement('div');
    div.style.cssText = 'background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:10px;position:relative';
    div.innerHTML = `
        <button type="button" onclick="this.parentNode.remove()"
            style="position:absolute;top:8px;right:8px;background:#fee2e2;border:none;border-radius:5px;
                   padding:2px 7px;cursor:pointer;font-size:13px;color:#e02424;line-height:1.4">✕</button>
        <div style="display:grid;grid-template-columns:130px 1fr;gap:8px;margin-bottom:8px">
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:3px">Fecha</label>
                <input type="date" name="item_fecha[]" value="${fecha||''}"
                    style="width:100%;padding:7px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px">
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:3px">Descripción</label>
                <input type="text" name="item_desc[]" value="${desc||''}" placeholder="Ej: Anticipo"
                    style="width:100%;padding:7px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:3px">Tipo</label>
                <select name="item_tipo[]"
                    style="width:100%;padding:7px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px">
                    <option value="debito"  ${tipo==='debito' ||!tipo?'selected':''}>Débito (descuento)</option>
                    <option value="credito" ${tipo==='credito'?'selected':''}>Crédito (adicional)</option>
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:3px">Monto (Gs.)</label>
                <input type="text" name="item_monto[]" value="${monto||''}" placeholder="0"
                    style="width:100%;padding:7px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px"
                    oninput="maskGs(this)">
            </div>
        </div>
    `;
    c.appendChild(div);
}

function maskGs(el) {
    let raw = el.value.replace(/\D/g, '');
    if (raw) el.value = parseInt(raw, 10).toLocaleString('es-PY');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
