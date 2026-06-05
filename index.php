<?php
$pageTitle = 'Dashboard — MercadoPar';
require_once __DIR__ . '/includes/header.php';

// Totales
$totalColaboradores = db()->query('SELECT COUNT(*) FROM colaboradores WHERE status = "ativo"')->fetchColumn();
$totalInativos      = db()->query('SELECT COUNT(*) FROM colaboradores WHERE status = "inativo"')->fetchColumn();
$totalReceitas      = db()->query('SELECT COALESCE(SUM(valor),0) FROM financeiro WHERE tipo="receita" AND status="pago" AND MONTH(data_pagamento)=MONTH(NOW()) AND YEAR(data_pagamento)=YEAR(NOW())')->fetchColumn();
$totalDespesas      = db()->query('SELECT COALESCE(SUM(valor),0) FROM financeiro WHERE tipo="despesa" AND status="pago" AND MONTH(data_pagamento)=MONTH(NOW()) AND YEAR(data_pagamento)=YEAR(NOW())')->fetchColumn();
$pendentes          = db()->query('SELECT COUNT(*) FROM financeiro WHERE status="pendente"')->fetchColumn();

// Próximos vencimientos
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
        <div class="card-label">Colaboradores activos</div>
    </div>
    <div class="card">
        <div class="card-stat" style="color:#057a55">Gs. <?= number_format($totalReceitas, 0, ',', '.') ?></div>
        <div class="card-label">Ingresos del mes</div>
    </div>
    <div class="card">
        <div class="card-stat" style="color:#e02424">Gs. <?= number_format($totalDespesas, 0, ',', '.') ?></div>
        <div class="card-label">Egresos del mes</div>
    </div>
    <div class="card">
        <div class="card-stat" style="color:#c27803"><?= $pendentes ?></div>
        <div class="card-label">Movimientos pendientes</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div>
        <h2 style="margin-bottom:14px;font-size:16px">Próximos Vencimientos</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Descripción</th><th>Tipo</th><th>Monto</th><th>Vencimiento</th></tr>
                </thead>
                <tbody>
                <?php if ($vencimentos): foreach ($vencimentos as $v): ?>
                    <tr>
                        <td><?= htmlspecialchars($v['descricao']) ?></td>
                        <td><span class="badge <?= $v['tipo'] === 'receita' ? 'badge-success' : 'badge-danger' ?>"><?= $v['tipo'] === 'receita' ? 'Ingreso' : 'Egreso' ?></span></td>
                        <td>Gs. <?= number_format($v['valor'], 0, ',', '.') ?></td>
                        <td><?= date('d/m/Y', strtotime($v['data_vencimento'])) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center;color:#6b7280">Sin vencimientos próximos</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <h2 style="margin-bottom:14px;font-size:16px">Colaboradores Recientes</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Nombre</th><th>Cargo</th><th>Ingreso</th><th>Estado</th></tr>
                </thead>
                <tbody>
                <?php if ($ultimosColaboradores): foreach ($ultimosColaboradores as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nome']) ?></td>
                        <td><?= htmlspecialchars($c['cargo'] ?? '—') ?></td>
                        <td><?= date('d/m/Y', strtotime($c['data_admissao'])) ?></td>
                        <td><span class="badge <?= $c['status'] === 'ativo' ? 'badge-success' : 'badge-danger' ?>"><?= $c['status'] === 'ativo' ? 'Activo' : 'Inactivo' ?></span></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center;color:#6b7280">Sin colaboradores registrados</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
