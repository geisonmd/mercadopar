<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$l = [];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM financeiro WHERE id = ?');
    $stmt->execute([$id]);
    $l = $stmt->fetch();
    if (!$l) { header('Location: /financeiro'); exit; }
}

$colaboradores = db()->query('SELECT id, nome FROM colaboradores WHERE status="ativo" ORDER BY nome')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'tipo'           => in_array($_POST['tipo'] ?? '', ['receita','despesa']) ? $_POST['tipo'] : 'despesa',
        'categoria'      => '',
        'descricao'      => trim($_POST['descricao'] ?? ''),
        'valor'          => (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9,.]/', '', $_POST['valor'] ?? '0')),
        'data_vencimento'=> $_POST['data_vencimento'] ?: null,
        'data_pagamento' => $_POST['data_pagamento'] ?: null,
        'status'         => in_array($_POST['status'] ?? '', ['pendente','pago','cancelado']) ? $_POST['status'] : 'pendente',
        'colaborador_id' => (int)($_POST['colaborador_id'] ?? 0) ?: null,
        'observacoes'    => trim($_POST['observacoes'] ?? ''),
    ];

    if (!$fields['descricao']) $errors[] = 'La descripción es obligatoria.';
    if ($fields['valor'] <= 0) $errors[] = 'El monto debe ser mayor a cero.';

    // Upload factura PDF
    $factura_pdf = $l['factura_pdf'] ?? null;
    if (!empty($_FILES['factura_pdf']['name'])) {
        $ext = strtolower(pathinfo($_FILES['factura_pdf']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $errors[] = 'La factura debe ser un archivo PDF.';
        } elseif ($_FILES['factura_pdf']['size'] > 10 * 1024 * 1024) {
            $errors[] = 'El archivo no puede superar 10 MB.';
        } else {
            $uploadDir = __DIR__ . '/../uploads/facturas/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'factura_' . time() . '_' . uniqid() . '.pdf';
            if (move_uploaded_file($_FILES['factura_pdf']['tmp_name'], $uploadDir . $filename)) {
                // Elimina el anterior si existe
                if (!empty($l['factura_pdf'])) {
                    @unlink($uploadDir . basename($l['factura_pdf']));
                }
                $factura_pdf = $filename;
            } else {
                $errors[] = 'Error al subir la factura.';
            }
        }
    }

    if (!$errors) {
        if ($id) {
            $sql = 'UPDATE financeiro SET tipo=?,categoria=?,descricao=?,valor=?,data_vencimento=?,
                    data_pagamento=?,status=?,colaborador_id=?,observacoes=?,factura_pdf=? WHERE id=?';
            db()->prepare($sql)->execute([
                $fields['tipo'], $fields['categoria'], $fields['descricao'], $fields['valor'],
                $fields['data_vencimento'], $fields['data_pagamento'], $fields['status'],
                $fields['colaborador_id'], $fields['observacoes'], $factura_pdf, $id
            ]);
        } else {
            $sql = 'INSERT INTO financeiro (tipo,categoria,descricao,valor,data_vencimento,
                    data_pagamento,status,colaborador_id,observacoes,factura_pdf) VALUES (?,?,?,?,?,?,?,?,?,?)';
            db()->prepare($sql)->execute([
                $fields['tipo'], $fields['categoria'], $fields['descricao'], $fields['valor'],
                $fields['data_vencimento'], $fields['data_pagamento'], $fields['status'],
                $fields['colaborador_id'], $fields['observacoes'], $factura_pdf
            ]);
        }
        header('Location: /financeiro');
        exit;
    }
    $l = array_merge($l ?: [], $_POST);
    $l['factura_pdf'] = $factura_pdf;
}

$pageTitle = 'Movimiento Financiero — MercadoPar';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1><?= $id ? 'Editar Movimiento' : 'Nuevo Movimiento' ?></h1>
    <a href="/financeiro" class="btn btn-outline">← Volver</a>
</div>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label>Tipo *</label>
                <select name="tipo">
                    <option value="despesa" <?= ($l['tipo'] ?? 'despesa')==='despesa' ? 'selected' : '' ?>>Egreso</option>
                    <option value="receita" <?= ($l['tipo'] ?? '')==='receita' ? 'selected' : '' ?>>Ingreso</option>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>Descripción *</label>
                <input type="text" name="descricao" required value="<?= htmlspecialchars($l['descricao'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Monto (Gs.) *</label>
                <input type="text" name="valor" id="monto-input" required
                       value="<?= $l['valor'] ? number_format((float)$l['valor'], 0, ',', '.') : '' ?>"
                       placeholder="0" autocomplete="off">
            </div>
            <div class="form-group">
                <label>Fecha de Pago</label>
                <input type="date" name="data_pagamento" value="<?= htmlspecialchars($l['data_pagamento'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="status">
                    <option value="pendente"  <?= ($l['status']??'pendente')==='pendente'  ? 'selected' : '' ?>>Pendiente</option>
                    <option value="pago"      <?= ($l['status']??'')==='pago'      ? 'selected' : '' ?>>Pagado</option>
                    <option value="cancelado" <?= ($l['status']??'')==='cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de Vencimiento <span style="color:#9ca3af;font-weight:400">(opcional)</span></label>
                <input type="date" name="data_vencimento" value="<?= htmlspecialchars($l['data_vencimento'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Vincular a Colaborador <span style="color:#9ca3af;font-weight:400">(opcional)</span></label>
                <select name="colaborador_id">
                    <option value="">—</option>
                    <?php foreach ($colaboradores as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($l['colaborador_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Factura PDF <span style="color:#9ca3af;font-weight:400">(opcional)</span></label>
                <input type="file" name="factura_pdf" accept=".pdf"
                       style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;background:#fff">
                <?php if (!empty($l['factura_pdf'])): ?>
                    <small style="color:#057a55">✓ Ya tiene factura adjunta —
                        <a href="/financeiro/factura_download?id=<?= $id ?>" target="_blank">ver</a> |
                        subir nueva para reemplazar
                    </small>
                <?php else: ?>
                    <small style="color:#6b7280;font-size:11px">Máximo 10 MB.</small>
                <?php endif; ?>
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>Observaciones</label>
                <textarea name="observacoes"><?= htmlspecialchars($l['observacoes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="/financeiro" class="btn btn-outline">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Máscara de miles para Guaraníes
const montoInput = document.getElementById('monto-input');
montoInput.addEventListener('input', function() {
    let raw = this.value.replace(/\D/g, '');
    if (raw === '') { this.value = ''; return; }
    this.value = parseInt(raw, 10).toLocaleString('es-PY');
});
montoInput.addEventListener('blur', function() {
    let raw = this.value.replace(/\D/g, '');
    if (raw) this.value = parseInt(raw, 10).toLocaleString('es-PY');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
