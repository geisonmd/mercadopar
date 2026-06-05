<?php
$pageTitle = 'Colaboradores — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$status = $_GET['status'] ?? 'ativo';
$search = trim($_GET['q'] ?? '');

$sql = 'SELECT * FROM colaboradores WHERE status = ?';
$params = [$status];
if ($search) {
    $sql .= ' AND (nome LIKE ? OR cpf LIKE ? OR cargo LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= ' ORDER BY nome ASC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$colaboradores = $stmt->fetchAll();
?>
<div class="page-header">
    <h1>Colaboradores</h1>
    <a href="/colaboradores/form.php" class="btn btn-primary">+ Novo Colaborador</a>
</div>

<form method="GET" style="display:flex;gap:10px;margin-bottom:20px">
    <input type="text" name="q" placeholder="Buscar por nome, CPF ou cargo..."
           value="<?= htmlspecialchars($search) ?>"
           style="flex:1;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
    <select name="status" style="padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
        <option value="ativo"   <?= $status === 'ativo'   ? 'selected' : '' ?>>Ativos</option>
        <option value="inativo" <?= $status === 'inativo' ? 'selected' : '' ?>>Inativos</option>
    </select>
    <button type="submit" class="btn btn-outline">Buscar</button>
</form>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Nome</th><th>CPF</th><th>Cargo</th><th>Departamento</th>
                <th>Admissão</th><th>Salário</th><th>Status</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($colaboradores): foreach ($colaboradores as $c): ?>
            <tr>
                <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                <td><?= htmlspecialchars($c['cpf']) ?></td>
                <td><?= htmlspecialchars($c['cargo'] ?? '—') ?></td>
                <td><?= htmlspecialchars($c['departamento'] ?? '—') ?></td>
                <td><?= date('d/m/Y', strtotime($c['data_admissao'])) ?></td>
                <td>R$ <?= number_format($c['salario'], 2, ',', '.') ?></td>
                <td><span class="badge <?= $c['status'] === 'ativo' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($c['status']) ?></span></td>
                <td style="white-space:nowrap">
                    <a href="/colaboradores/form.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">Editar</a>
                    <a href="/colaboradores/contrato.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">Contrato</a>
                    <a href="/colaboradores/recibos.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">Recibos</a>
                    <a href="/colaboradores/delete.php?id=<?= $c['id'] ?>"
                       class="btn btn-danger btn-sm"
                       data-confirm="Deseja realmente excluir <?= htmlspecialchars($c['nome']) ?>?">Excluir</a>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="8" style="text-align:center;color:#6b7280;padding:24px">Nenhum colaborador encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
