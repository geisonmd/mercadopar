<?php
$pageTitle = 'Recibos de Salário — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$colaborador_id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM colaboradores WHERE id = ?');
$stmt->execute([$colaborador_id]);
$col = $stmt->fetch();
if (!$col) { header('Location: /colaboradores/index.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mes     = (int)($_POST['mes'] ?? 0);
    $ano     = (int)($_POST['ano'] ?? 0);
    $bruto   = (float)str_replace(['.', ','], ['', '.'], $_POST['salario_bruto'] ?? '0');
    $inss    = (float)str_replace(['.', ','], ['', '.'], $_POST['inss'] ?? '0');
    $irrf    = (float)str_replace(['.', ','], ['', '.'], $_POST['irrf'] ?? '0');
    $descontos  = (float)str_replace(['.', ','], ['', '.'], $_POST['outros_descontos'] ?? '0');
    $acrescimos = (float)str_replace(['.', ','], ['', '.'], $_POST['outros_acrescimos'] ?? '0');
    $liquido = $bruto - $inss - $irrf - $descontos + $acrescimos;
    $pagamento  = $_POST['data_pagamento'] ?: null;
    $obs        = trim($_POST['observacoes'] ?? '');

    if (!$mes || !$ano) $errors[] = 'Mês e ano são obrigatórios.';

    if (!$errors) {
        try {
            db()->prepare(
                'INSERT INTO recibos_salario
                 (colaborador_id,mes,ano,salario_bruto,inss,irrf,outros_descontos,outros_acrescimos,salario_liquido,data_pagamento,observacoes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([$colaborador_id,$mes,$ano,$bruto,$inss,$irrf,$descontos,$acrescimos,$liquido,$pagamento,$obs]);
            header("Location: /colaboradores/recibos.php?id=$colaborador_id&saved=1");
            exit;
        } catch (\PDOException $e) {
            $errors[] = 'Já existe um recibo para este mês/ano.';
        }
    }
}

$recibos = db()->prepare(
    'SELECT * FROM recibos_salario WHERE colaborador_id = ? ORDER BY ano DESC, mes DESC'
);
$recibos->execute([$colaborador_id]);
$recibos = $recibos->fetchAll();

$meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
?>
<div class="page-header">
    <h1>Recibos — <?= htmlspecialchars($col['nome']) ?></h1>
    <a href="/colaboradores/index.php" class="btn btn-outline">← Voltar</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Recibo registrado com sucesso.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:380px 1fr;gap:20px;align-items:start">
    <div class="form-card">
        <h3 style="margin-bottom:16px;font-size:15px">Novo Recibo</h3>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
        <form method="POST">
            <div class="form-grid" style="grid-template-columns:1fr 1fr">
                <div class="form-group">
                    <label>Mês *</label>
                    <select name="mes">
                        <?php for ($m=1;$m<=12;$m++): ?>
                            <option value="<?= $m ?>" <?= $m===(int)date('n') ? 'selected' : '' ?>><?= $meses[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ano *</label>
                    <input type="number" name="ano" value="<?= date('Y') ?>" min="2000" max="2100">
                </div>
            </div>
            <div class="form-group" style="margin-top:12px">
                <label>Salário Bruto (R$)</label>
                <input type="number" name="salario_bruto" step="0.01" min="0"
                       value="<?= htmlspecialchars($col['salario']) ?>">
            </div>
            <div class="form-group" style="margin-top:10px">
                <label>INSS (R$)</label>
                <input type="number" name="inss" step="0.01" min="0" value="0">
            </div>
            <div class="form-group" style="margin-top:10px">
                <label>IRRF (R$)</label>
                <input type="number" name="irrf" step="0.01" min="0" value="0">
            </div>
            <div class="form-group" style="margin-top:10px">
                <label>Outros Descontos (R$)</label>
                <input type="number" name="outros_descontos" step="0.01" min="0" value="0">
            </div>
            <div class="form-group" style="margin-top:10px">
                <label>Outros Acréscimos (R$)</label>
                <input type="number" name="outros_acrescimos" step="0.01" min="0" value="0">
            </div>
            <div class="form-group" style="margin-top:10px">
                <label>Data de Pagamento</label>
                <input type="date" name="data_pagamento">
            </div>
            <div class="form-group" style="margin-top:10px">
                <label>Observações</label>
                <textarea name="observacoes"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:16px">Gerar Recibo</button>
        </form>
    </div>

    <div>
        <h3 style="margin-bottom:14px;font-size:15px">Histórico de Recibos</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Período</th><th>Bruto</th><th>Descontos</th><th>Líquido</th><th>Pagamento</th><th></th></tr>
                </thead>
                <tbody>
                <?php if ($recibos): foreach ($recibos as $r): ?>
                    <tr>
                        <td><?= $meses[$r['mes']] . '/' . $r['ano'] ?></td>
                        <td>R$ <?= number_format($r['salario_bruto'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($r['inss']+$r['irrf']+$r['outros_descontos'], 2, ',', '.') ?></td>
                        <td><strong>R$ <?= number_format($r['salario_liquido'], 2, ',', '.') ?></strong></td>
                        <td><?= $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '—' ?></td>
                        <td>
                            <a href="/colaboradores/recibo_imprimir.php?recibo_id=<?= $r['id'] ?>"
                               target="_blank" class="btn btn-outline btn-sm">Imprimir</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#6b7280;padding:16px">Nenhum recibo gerado.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
