<?php
$pageTitle = 'Recibos de Salario — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$colaborador_id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM colaboradores WHERE id = ?');
$stmt->execute([$colaborador_id]);
$col = $stmt->fetch();
if (!$col) { header('Location: /colaboradores'); exit; }

$errors = [];
$moneda_col  = $col['moneda_salario'] ?? 'GS';
$salario_col = (float)($col['salario'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mes         = (int)($_POST['mes'] ?? 0);
    $ano         = (int)($_POST['ano'] ?? 0);
    $moneda      = in_array($_POST['moneda_recibo'] ?? '', ['GS','USD']) ? $_POST['moneda_recibo'] : 'GS';
    $tipo_cambio = trim($_POST['tipo_cambio'] ?? '');
    $tc_val      = (float)str_replace(',', '.', $tipo_cambio ?: '0');
    $pagamento   = $_POST['data_pagamento'] ?: null;
    $obs         = trim($_POST['observacoes'] ?? '');

    // Parsing do salário base conforme moeda do colaborador
    $salarioRaw = preg_replace('/[^0-9,.]/', '', $_POST['salario_bruto'] ?? '0');
    if ($moneda_col === 'USD') {
        $bruto = (float)str_replace(',', '', $salarioRaw); // "1,100.00" → 1100.00
    } else {
        $bruto = (float)str_replace(['.', ','], ['', '.'], $salarioRaw); // "6.710.000" → 6710000
    }

    // Converter para Gs se recibo é em GS mas salário base é USD
    $bruto_gs = ($moneda_col === 'USD' && $moneda === 'GS' && $tc_val > 0)
        ? round($bruto * $tc_val)
        : $bruto;

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

    // Líquido = bruto_gs - débitos + créditos (itens sempre em Gs)
    $total_debitos  = 0;
    $total_creditos = 0;
    foreach ($items as $item) {
        if ($item['tipo'] === 'debito') $total_debitos  += $item['monto'];
        else                            $total_creditos += $item['monto'];
    }
    $liquido = $bruto_gs + $total_creditos - $total_debitos;

    if (!$mes || !$ano) $errors[] = 'El mes y año son obligatorios.';
    if ($bruto <= 0)    $errors[] = 'El salario base debe ser mayor a cero.';

    if (!$errors) {
        // Verificar duplicado
        $dup = db()->prepare('SELECT id FROM recibos_salario WHERE colaborador_id=? AND mes=? AND ano=?');
        $dup->execute([$colaborador_id, $mes, $ano]);
        if ($dup->fetch()) {
            $errors[] = 'Ya existe un recibo para ' . $meses[$mes] . ' ' . $ano . '. Elimínelo primero.';
        } else {
            // Guardar tasa de cambio en observaciones si fue informada
            $obs_full = $tipo_cambio ? 'Tasa de cambio: ' . $tipo_cambio . ($obs ? ' | ' . $obs : '') : $obs;

            try {
                // Intentar con columnas nuevas (si migration fue ejecutada)
                db()->prepare(
                    'INSERT INTO recibos_salario
                     (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
                      salario_liquido,data_pagamento,observacoes,moneda,tipo_cambio,salario_gs,items_json)
                     VALUES (?,?,?,?,0,0,?,?,?,?,?,?,?,?,?)'
                )->execute([
                    $colaborador_id, $mes, $ano, $bruto,
                    $total_debitos, $total_creditos,
                    $liquido, $pagamento, $obs_full,
                    $moneda, $tc_val,
                    $bruto_gs,
                    json_encode($items, JSON_UNESCAPED_UNICODE)
                ]);
            } catch (\PDOException $e) {
                // Fallback: columnas nuevas no existen aún (migration pendiente)
                db()->prepare(
                    'INSERT INTO recibos_salario
                     (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,
                      salario_liquido,data_pagamento,observacoes)
                     VALUES (?,?,?,?,0,0,?,?,?,?,?)'
                )->execute([
                    $colaborador_id, $mes, $ano, $bruto,
                    $total_debitos, $total_creditos,
                    $liquido, $pagamento,
                    $obs_full . ' | Moneda: ' . $moneda . ' | Items: ' . json_encode($items, JSON_UNESCAPED_UNICODE)
                ]);
            }

            header("Location: /colaboradores/recibos?id=$colaborador_id&saved=1");
            exit;
        }
    }
}

$recibos = db()->prepare('SELECT * FROM recibos_salario WHERE colaborador_id=? ORDER BY ano DESC, mes DESC');
$recibos->execute([$colaborador_id]);
$recibos = $recibos->fetchAll();

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

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

<div style="display:grid;grid-template-columns:440px 1fr;gap:20px;align-items:start">
<div class="form-card">
    <h3 style="margin-bottom:4px;font-size:15px">Nuevo Recibo</h3>
    <p style="font-size:12px;color:#6b7280;margin-bottom:16px">
        Salario base: <strong><?= $moneda_col === 'USD' ? 'USD ' . $salario_fmt : 'Gs. ' . $salario_fmt ?></strong>
    </p>

    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST">
        <div class="form-grid" style="grid-template-columns:1fr 1fr;margin-bottom:12px">
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

        <div class="form-grid" style="grid-template-columns:1fr 1fr;margin-bottom:12px">
            <div class="form-group">
                <label>Moneda del Recibo</label>
                <select name="moneda_recibo">
                    <option value="GS"  <?= $moneda_col === 'GS'  ? 'selected' : '' ?>>Guaraní (Gs.)</option>
                    <option value="USD" <?= $moneda_col === 'USD' ? 'selected' : '' ?>>Dólar (USD)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tasa de Cambio <span style="color:#9ca3af;font-weight:400">(referencia)</span></label>
                <input type="text" name="tipo_cambio" placeholder="Ej: 7.800" autocomplete="off">
            </div>
        </div>

        <div class="form-group" style="margin-bottom:12px">
            <label>Salario Base *</label>
            <input type="text" name="salario_bruto" id="salario-input" required
                   value="<?= htmlspecialchars($salario_fmt) ?>"
                   placeholder="0" autocomplete="off" oninput="maskNum(this)">
        </div>

        <div class="form-group" style="margin-bottom:12px">
            <label>Fecha de Pago</label>
            <input type="date" name="data_pagamento" value="<?= date('Y-m-d') ?>">
        </div>

        <!-- Items -->
        <div style="margin-top:6px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
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
                <tr><th>Período</th><th>Salario</th><th>Líquido</th><th>Pago</th><th></th></tr>
            </thead>
            <tbody>
            <?php if ($recibos): foreach ($recibos as $r): ?>
                <tr>
                    <td><?= $meses[$r['mes']] . ' ' . $r['ano'] ?></td>
                    <td>
                        <?php
                        $m = $r['moneda'] ?? 'GS';
                        echo $m === 'USD'
                            ? 'USD ' . number_format($r['salario_bruto'], 2, '.', ',')
                            : 'Gs. ' . number_format((float)($r['salario_gs'] ?? $r['salario_bruto']), 0, ',', '.');
                        ?>
                    </td>
                    <td><strong>Gs. <?= number_format($r['salario_liquido'], 0, ',', '.') ?></strong></td>
                    <td><?= $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '—' ?></td>
                    <td>
                        <a href="/colaboradores/recibo_imprimir?recibo_id=<?= $r['id'] ?>"
                           target="_blank" class="btn btn-outline btn-sm">Ver / Imprimir</a>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center;color:#6b7280;padding:16px">Ningún recibo generado.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<script>
function maskNum(el) {
    let raw = el.value.replace(/\D/g, '');
    if (raw) el.value = parseInt(raw, 10).toLocaleString('es-PY');
}

function addItem(fecha, desc, tipo, monto) {
    const c = document.getElementById('items-container');
    const div = document.createElement('div');
    div.style.cssText = 'background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:10px;position:relative';
    div.innerHTML = `
        <button type="button" onclick="this.parentNode.remove()"
            style="position:absolute;top:8px;right:8px;background:#fee2e2;border:none;border-radius:5px;
                   padding:2px 8px;cursor:pointer;font-size:12px;color:#e02424">✕</button>
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
                    <option value="debito"  ${!tipo||tipo==='debito'?'selected':''}>Débito (descuento)</option>
                    <option value="credito" ${tipo==='credito'?'selected':''}>Crédito (adicional)</option>
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:3px">Monto (Gs.)</label>
                <input type="text" name="item_monto[]" value="${monto||''}" placeholder="0"
                    style="width:100%;padding:7px 8px;border:1px solid #e5e7eb;border-radius:6px;font-size:12px"
                    oninput="maskNum(this)">
            </div>
        </div>`;
    c.appendChild(div);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
