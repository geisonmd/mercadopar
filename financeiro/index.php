<?php
$pageTitle = 'Financiero — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$tipo   = $_GET['tipo']   ?? '';
$status = $_GET['status'] ?? '';
$mes    = (int)($_GET['mes'] ?? date('n'));
$ano    = (int)($_GET['ano'] ?? date('Y'));

$sql = 'SELECT f.*, c.nome as colaborador_nome FROM financeiro f
        LEFT JOIN colaboradores c ON c.id = f.colaborador_id
        WHERE (MONTH(COALESCE(f.data_vencimento, f.data_pagamento, f.created_at))=?
           AND YEAR(COALESCE(f.data_vencimento, f.data_pagamento, f.created_at))=?)';
$params = [$mes, $ano];

if ($tipo)   { $sql .= ' AND f.tipo = ?';   $params[] = $tipo; }
if ($status) { $sql .= ' AND f.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY COALESCE(f.data_vencimento, f.data_pagamento, f.created_at) ASC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$lancamentos = $stmt->fetchAll();

$totais = db()->prepare(
    'SELECT
        SUM(CASE WHEN tipo="receita" AND status="pago" THEN valor ELSE 0 END) as receitas,
        SUM(CASE WHEN tipo="despesa" AND status="pago" THEN valor ELSE 0 END) as despesas,
        SUM(CASE WHEN status="pendente" THEN valor ELSE 0 END) as pendente
     FROM financeiro
     WHERE MONTH(COALESCE(data_vencimento, data_pagamento, created_at))=?
       AND YEAR(COALESCE(data_vencimento, data_pagamento, created_at))=?'
);
$totais->execute([$mes, $ano]);
$totais = $totais->fetch();

$meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
?>
<div class="page-header">
    <h1>Financiero</h1>
    <a href="/financeiro/form.php" class="btn btn-primary">+ Nuevo Movimiento</a>
</div>

<div class="cards-grid" style="margin-bottom:20px">
    <div class="card">
        <div class="card-stat" style="color:#057a55">Gs. <?= number_format($totais['receitas'] ?? 0, 0, ',', '.') ?></div>
        <div class="card-label">Ingresos pagados en el mes</div>
    </div>
    <div class="card">
        <div class="card-stat" style="color:#e02424">Gs. <?= number_format($totais['despesas'] ?? 0, 0, ',', '.') ?></div>
        <div class="card-label">Egresos pagados en el mes</div>
    </div>
    <div class="card">
        <div class="card-stat" style="color:#c27803">Gs. <?= number_format($totais['pendente'] ?? 0, 0, ',', '.') ?></div>
        <div class="card-label">A vencer en el mes</div>
    </div>
    <div class="card">
        <div class="card-stat" style="color:<?= (($totais['receitas']??0)-($totais['despesas']??0)) >= 0 ? '#057a55' : '#e02424' ?>">
            Gs. <?= number_format(($totais['receitas']??0)-($totais['despesas']??0), 0, ',', '.') ?>
        </div>
        <div class="card-label">Saldo del mes</div>
    </div>
</div>

<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <select name="mes" style="padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
        <?php for ($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>" <?= $m===$mes ? 'selected' : '' ?>><?= $meses[$m] ?></option>
        <?php endfor; ?>
    </select>
    <input type="number" name="ano" value="<?= $ano ?>" min="2000" max="2100"
           style="width:80px;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
    <select name="tipo" style="padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
        <option value="">Todos los tipos</option>
        <option value="receita" <?= $tipo==='receita' ? 'selected' : '' ?>>Ingresos</option>
        <option value="despesa" <?= $tipo==='despesa' ? 'selected' : '' ?>>Egresos</option>
    </select>
    <select name="status" style="padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px">
        <option value="">Todos los estados</option>
        <option value="pendente"  <?= $status==='pendente'  ? 'selected' : '' ?>>Pendiente</option>
        <option value="pago"      <?= $status==='pago'      ? 'selected' : '' ?>>Pagado</option>
        <option value="cancelado" <?= $status==='cancelado' ? 'selected' : '' ?>>Cancelado</option>
    </select>
    <button type="submit" class="btn btn-outline">Filtrar</button>
    <a href="/financeiro/export_csv.php?mes=<?= $mes ?>&ano=<?= $ano ?>&tipo=<?= urlencode($tipo) ?>&status=<?= urlencode($status) ?>"
       class="btn btn-outline">⬇ Exportar CSV</a>
</form>

<div class="table-wrapper">
    <table>
        <thead>
            <tr><th>Descripción</th><th>Tipo</th><th>Monto</th><th>Pago</th><th>Estado</th><th>Factura</th><th>Acciones</th></tr>
        </thead>
        <tbody>
        <?php if ($lancamentos): foreach ($lancamentos as $l): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($l['descricao']) ?>
                    <?php if ($l['colaborador_nome']): ?>
                        <br><small style="color:#6b7280"><?= htmlspecialchars($l['colaborador_nome']) ?></small>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?= $l['tipo']==='receita' ? 'badge-success' : 'badge-danger' ?>"><?= $l['tipo']==='receita' ? 'Ingreso' : 'Egreso' ?></span></td>
                <td>Gs. <?= number_format($l['valor'], 0, ',', '.') ?></td>
                <td><?= $l['data_pagamento'] ? date('d/m/Y', strtotime($l['data_pagamento'])) : '—' ?></td>
                <td>
                    <?php
                    $badgeStatus = ['pago'=>'badge-success', 'cancelado'=>'badge-danger'];
                    $labelStatus = ['pendente'=>'Pendiente', 'pago'=>'Pagado', 'cancelado'=>'Cancelado'];
                    $bs = $badgeStatus[$l['status']] ?? 'badge-warning';
                    ?>
                    <span class="badge <?= $bs ?>"><?= $labelStatus[$l['status']] ?? $l['status'] ?></span>
                </td>
                <td>
                    <?php if (!empty($l['factura_pdf'])): ?>
                        <a href="/financeiro/factura_download?id=<?= $l['id'] ?>" target="_blank" class="btn btn-outline btn-sm">📄 Ver</a>
                    <?php else: ?>
                        <span style="color:#d1d5db;font-size:12px">—</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap">
                    <a href="/financeiro/form.php?id=<?= $l['id'] ?>" class="btn btn-outline btn-sm">Editar</a>
                    <?php if ($l['status'] === 'pendente'): ?>
                        <a href="/financeiro/pagar.php?id=<?= $l['id'] ?>"
                           class="btn btn-primary btn-sm"
                           data-confirm="¿Marcar como pagado?">Pagar</a>
                    <?php endif; ?>
                    <a href="/financeiro/delete.php?id=<?= $l['id'] ?>"
                       class="btn btn-danger btn-sm"
                       data-confirm="¿Eliminar este movimiento?">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="7" style="text-align:center;color:#6b7280;padding:24px">Ningún movimiento encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
