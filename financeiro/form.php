<?php
$pageTitle = 'Movimiento Financiero — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$l = [];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM financeiro WHERE id = ?');
    $stmt->execute([$id]);
    $l = $stmt->fetch();
    if (!$l) { header('Location: /financeiro/index.php'); exit; }
}

$colaboradores = db()->query('SELECT id, nome FROM colaboradores WHERE status="ativo" ORDER BY nome')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'tipo'           => in_array($_POST['tipo'] ?? '', ['receita','despesa']) ? $_POST['tipo'] : 'despesa',
        'categoria'      => trim($_POST['categoria'] ?? ''),
        'descricao'      => trim($_POST['descricao'] ?? ''),
        'valor'          => (float)str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0'),
        'data_vencimento'=> $_POST['data_vencimento'] ?? '',
        'data_pagamento' => $_POST['data_pagamento'] ?: null,
        'status'         => in_array($_POST['status'] ?? '', ['pendente','pago','cancelado']) ? $_POST['status'] : 'pendente',
        'colaborador_id' => (int)($_POST['colaborador_id'] ?? 0) ?: null,
        'observacoes'    => trim($_POST['observacoes'] ?? ''),
    ];

    if (!$fields['descricao'])       $errors[] = 'La descripción es obligatoria.';
    if (!$fields['data_vencimento']) $errors[] = 'La fecha de vencimiento es obligatoria.';
    if ($fields['valor'] <= 0)       $errors[] = 'El monto debe ser mayor a cero.';

    if (!$errors) {
        if ($id) {
            $sql = 'UPDATE financeiro SET tipo=?,categoria=?,descricao=?,valor=?,data_vencimento=?,
                    data_pagamento=?,status=?,colaborador_id=?,observacoes=? WHERE id=?';
            $params = array_values($fields);
            $params[] = $id;
        } else {
            $sql = 'INSERT INTO financeiro (tipo,categoria,descricao,valor,data_vencimento,
                    data_pagamento,status,colaborador_id,observacoes) VALUES (?,?,?,?,?,?,?,?,?)';
            $params = array_values($fields);
        }
        db()->prepare($sql)->execute($params);
        header('Location: /financeiro/index.php?saved=1');
        exit;
    }
    $l = array_merge($l ?: [], $_POST);
}

$categorias_ingreso = ['Salario','Servicios','Venta','Comisión','Otros'];
$categorias_egreso  = ['Planilla','Alquiler','Proveedor','Impuestos','Marketing','Otros'];
?>
<div class="page-header">
    <h1><?= $id ? 'Editar Movimiento' : 'Nuevo Movimiento' ?></h1>
    <a href="/financeiro/index.php" class="btn btn-outline">← Volver</a>
</div>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Tipo *</label>
                <select name="tipo">
                    <option value="despesa" <?= ($l['tipo'] ?? 'despesa')==='despesa' ? 'selected' : '' ?>>Egreso</option>
                    <option value="receita" <?= ($l['tipo'] ?? '')==='receita' ? 'selected' : '' ?>>Ingreso</option>
                </select>
            </div>
            <div class="form-group">
                <label>Categoría</label>
                <input type="text" name="categoria" list="lista-categorias"
                       value="<?= htmlspecialchars($l['categoria'] ?? '') ?>">
                <datalist id="lista-categorias">
                    <?php foreach (array_merge($categorias_ingreso, $categorias_egreso) as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>Descripción *</label>
                <input type="text" name="descricao" required value="<?= htmlspecialchars($l['descricao'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Monto (Gs.) *</label>
                <input type="number" name="valor" step="1" min="1" required
                       value="<?= htmlspecialchars($l['valor'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Fecha de Vencimiento *</label>
                <input type="date" name="data_vencimento" required
                       value="<?= htmlspecialchars($l['data_vencimento'] ?? date('Y-m-d')) ?>">
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
                <label>Vincular a Colaborador (opcional)</label>
                <select name="colaborador_id">
                    <option value="">—</option>
                    <?php foreach ($colaboradores as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($l['colaborador_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>Observaciones</label>
                <textarea name="observacoes"><?= htmlspecialchars($l['observacoes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="/financeiro/index.php" class="btn btn-outline">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
