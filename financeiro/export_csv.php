<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

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

$labelTipo   = ['receita' => 'Ingreso', 'despesa' => 'Egreso'];
$labelStatus = ['pendente' => 'Pendiente', 'pago' => 'Pagado', 'cancelado' => 'Cancelado'];

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="movimientos_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Descripción', 'Colaborador', 'Tipo', 'Monto', 'Vencimiento', 'Pago', 'Estado'], ';');

foreach ($lancamentos as $l) {
    fputcsv($out, [
        $l['descricao'],
        $l['colaborador_nome'] ?? '',
        $labelTipo[$l['tipo']] ?? $l['tipo'],
        number_format((float)$l['valor'], 0, ',', '.'),
        $l['data_vencimento'] ? date('d/m/Y', strtotime($l['data_vencimento'])) : '',
        $l['data_pagamento'] ? date('d/m/Y', strtotime($l['data_pagamento'])) : '',
        $labelStatus[$l['status']] ?? $l['status'],
    ], ';');
}

fclose($out);
