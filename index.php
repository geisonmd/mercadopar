<?php
$pageTitle = 'Dashboard — MercadoPar';
require_once __DIR__ . '/includes/header.php';

// Totais
$totalColaboradores = db()->query('SELECT COUNT(*) FROM colaboradores WHERE status = "ativo"')->fetchColumn();
$totalInativos      = db()->query('SELECT COUNT(*) FROM colaboradores WHERE status = "inativo"')->fetchColumn();
$totalReceitas      = db()->query('SELECT COALESCE(SUM(valor),0) FROM financeiro WHERE tipo="receita" AND status="pago" AND MONTH(data_pagamento)=MONTH(NOW()) AND YEAR(data_pagamento)=YEAR(NOW())')->fetchColumn();
$totalDespesas      = db()->query('SELECT COALESCE(SUM(valor),0) FROM financeiro WHERE tipo="despesa" AND status="pago" AND MONTH(data_pagamento)=MONTH(NOW()) AND YEAR(data_pagamento)=YEAR(NOW())')->fetchColumn();
$pendentes          = db()->query('SELECT COUNT(*) FROM financeiro WHERE status="pendente"')->fetchColumn();

// Próximos vencimentos
$vencimentos = db()->query(
    'SELECT descricao, tipo, valor, data_vencimento FROM financeiro
     WHERE status = "pendente" AND data_vencimento >= CURDATE()
     ORDER BY data_vencimento ASC LIMIT 5'
)->fetchAll();

// Últimos colaboradores
$ultimosColaboradores = db()->query(
    'SELECT nome, cargo, data_admissao, status FROM colaboradores ORDER BY created_at DESC LIMIT 5'
)->fetchAll();
?>
<div class="page-header">
    <h1>Dashboard</h1>
    <span style="color:#6b7280;font-size:13px"><?= date('d/m/Y') ?></span>
</div>

<div class="cards-grid">
    <div class="card">
        <div class="card-stat"><?= $totalColaboradores ?></div>
        <div class="card-label">Colaboradores ativos</div>
    </div>
    <div class="card">
        <div class="card-stat" style="color:#057a55">R$ <?= number_format($totalReceitas, 2, ',', '.') ?></div>
        <div class="card-label">Receitas no mês</div>
    </div>
    <div class="card">
        <div class="card-stat" style="color:#e02424">R$ <?= number_format($totalDespesas, 2, ',', '.') ?></div>
        <div class="card-label">Despesas no mês</div>
    </div>
    <div class="card">
        <div class="card-stat" style="color:#c27803"><?= $pendentes ?></div>
        <div class="card-label">Lançamentos pendentes</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div>
        <h2 style="margin-bottom:14px;font-size:16px">Próximos Vencimentos</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Descrição</th><th>Tipo</th><th>Valor</th><th>Vencimento</th></tr>
                </thead>
                <tbody>
                <?php if ($vencimentos): foreach ($vencimentos as $v): ?>
                    <tr>
                        <td><?= htmlspecialchars($v['descricao']) ?></td>
                        <td><span class="badge <?= $v['tipo'] === 'receita' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($v['tipo']) ?></span></td>
                        <td>R$ <?= number_format($v['valor'], 2, ',', '.') ?></td>
                        <td><?= date('d/m/Y', strtotime($v['data_vencimento'])) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center;color:#6b7280">Nenhum vencimento próximo</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <h2 style="margin-bottom:14px;font-size:16px">Colaboradores Recentes</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Nome</th><th>Cargo</th><th>Admissão</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if ($ultimosColaboradores): foreach ($ultimosColaboradores as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nome']) ?></td>
                        <td><?= htmlspecialchars($c['cargo'] ?? '—') ?></td>
                        <td><?= date('d/m/Y', strtotime($c['data_admissao'])) ?></td>
                        <td><span class="badge <?= $c['status'] === 'ativo' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($c['status']) ?></span></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center;color:#6b7280">Nenhum colaborador cadastrado</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
