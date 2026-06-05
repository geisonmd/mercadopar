<?php
$pageTitle = 'Contrato — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$colaborador_id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM colaboradores WHERE id = ?');
$stmt->execute([$colaborador_id]);
$col = $stmt->fetch();
if (!$col) { header('Location: /colaboradores/index.php'); exit; }

$errors = [];

// Contrato atual
$contratos = db()->prepare('SELECT * FROM contratos WHERE colaborador_id = ? ORDER BY data_inicio DESC');
$contratos->execute([$colaborador_id]);
$contratos = $contratos->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo       = $_POST['tipo'] ?? 'CLT';
    $data_inicio= $_POST['data_inicio'] ?? '';
    $data_fim   = $_POST['data_fim'] ?: null;
    $salario    = (float)str_replace(['.', ','], ['', '.'], $_POST['salario'] ?? '0');
    $cargo      = trim($_POST['cargo'] ?? '');
    $observacoes= trim($_POST['observacoes'] ?? '');

    if (!$data_inicio) $errors[] = 'Data de início é obrigatória.';

    if (!$errors) {
        db()->prepare(
            'INSERT INTO contratos (colaborador_id,tipo,data_inicio,data_fim,salario,cargo,observacoes)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([$colaborador_id, $tipo, $data_inicio, $data_fim, $salario, $cargo, $observacoes]);
        header("Location: /colaboradores/contrato.php?id=$colaborador_id&saved=1");
        exit;
    }
}
?>
<div class="page-header">
    <h1>Contratos — <?= htmlspecialchars($col['nome']) ?></h1>
    <a href="/colaboradores/index.php" class="btn btn-outline">← Voltar</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Contrato registrado com sucesso.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">
    <div class="form-card">
        <h3 style="margin-bottom:16px;font-size:15px">Novo Contrato</h3>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
        <form method="POST">
            <div class="form-group" style="margin-bottom:12px">
                <label>Tipo de Contrato</label>
                <select name="tipo">
                    <option value="CLT">CLT</option>
                    <option value="PJ">PJ (Pessoa Jurídica)</option>
                    <option value="Estágio">Estágio</option>
                    <option value="Temporário">Temporário</option>
                    <option value="Autônomo">Autônomo</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label>Cargo no Contrato</label>
                <input type="text" name="cargo" value="<?= htmlspecialchars($col['cargo'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label>Salário no Contrato (R$)</label>
                <input type="number" name="salario" step="0.01" min="0"
                       value="<?= htmlspecialchars($col['salario'] ?? '0') ?>">
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label>Data de Início *</label>
                <input type="date" name="data_inicio" required value="<?= $col['data_admissao'] ?>">
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label>Data de Fim (se houver)</label>
                <input type="date" name="data_fim">
            </div>
            <div class="form-group" style="margin-bottom:16px">
                <label>Observações</label>
                <textarea name="observacoes"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Registrar Contrato</button>
        </form>
    </div>

    <div>
        <h3 style="margin-bottom:14px;font-size:15px">Histórico de Contratos</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Tipo</th><th>Cargo</th><th>Salário</th><th>Início</th><th>Fim</th><th></th></tr>
                </thead>
                <tbody>
                <?php if ($contratos): foreach ($contratos as $ct): ?>
                    <tr>
                        <td><?= htmlspecialchars($ct['tipo']) ?></td>
                        <td><?= htmlspecialchars($ct['cargo'] ?? '—') ?></td>
                        <td>R$ <?= number_format($ct['salario'], 2, ',', '.') ?></td>
                        <td><?= date('d/m/Y', strtotime($ct['data_inicio'])) ?></td>
                        <td><?= $ct['data_fim'] ? date('d/m/Y', strtotime($ct['data_fim'])) : '—' ?></td>
                        <td>
                            <a href="/colaboradores/contrato_imprimir.php?contrato_id=<?= $ct['id'] ?>"
                               target="_blank" class="btn btn-outline btn-sm">Imprimir</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#6b7280;padding:16px">Nenhum contrato registrado.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
