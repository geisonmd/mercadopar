<?php
$pageTitle = 'Lançamento Financeiro — MercadoPar';
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

// Lista colaboradores para vínculo opcional
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

    if (!$fields['descricao']) $errors[] = 'Descrição é obrigatória.';
    if (!$fields['data_vencimento']) $errors[] = 'Data de vencimento é obrigatória.';
    if ($fields['valor'] <= 0) $errors[] = 'Valor deve ser maior que zero.';

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

$categorias_receita = ['Salário','Serviços','Venda','Comissão','Outros'];
$categorias_despesa = ['Folha de Pagamento','Aluguel','Fornecedor','Impostos','Marketing','Outros'];
?>
<div class="page-header">
    <h1><?= $id ? 'Editar Lançamento' : 'Novo Lançamento' ?></h1>
    <a href="/financeiro/index.php" class="btn btn-outline">← Voltar</a>
</div>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Tipo *</label>
                <select name="tipo" id="tipo-select">
                    <option value="despesa" <?= ($l['tipo'] ?? 'despesa')==='despesa' ? 'selected' : '' ?>>Despesa</option>
                    <option value="receita" <?= ($l['tipo'] ?? '')==='receita' ? 'selected' : '' ?>>Receita</option>
                </select>
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <input type="text" name="categoria" list="lista-categorias"
                       value="<?= htmlspecialchars($l['categoria'] ?? '') ?>">
                <datalist id="lista-categorias">
                    <?php foreach (array_merge($categorias_receita, $categorias_despesa) as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>Descrição *</label>
                <input type="text" name="descricao" required value="<?= htmlspecialchars($l['descricao'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Valor (R$) *</label>
                <input type="number" name="valor" step="0.01" min="0.01" required
                       value="<?= htmlspecialchars($l['valor'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Data de Vencimento *</label>
                <input type="date" name="data_vencimento" required
                       value="<?= htmlspecialchars($l['data_vencimento'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label>Data de Pagamento</label>
                <input type="date" name="data_pagamento" value="<?= htmlspecialchars($l['data_pagamento'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="pendente"  <?= ($l['status']??'pendente')==='pendente'  ? 'selected' : '' ?>>Pendente</option>
                    <option value="pago"      <?= ($l['status']??'')==='pago'      ? 'selected' : '' ?>>Pago</option>
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
                <label>Observações</label>
                <textarea name="observacoes"><?= htmlspecialchars($l['observacoes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="/financeiro/index.php" class="btn btn-outline">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
