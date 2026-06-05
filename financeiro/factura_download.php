<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT factura_pdf FROM financeiro WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || empty($row['factura_pdf'])) {
    http_response_code(404); echo 'Factura no encontrada.'; exit;
}

$filepath = __DIR__ . '/../uploads/facturas/' . basename($row['factura_pdf']);
if (!file_exists($filepath)) {
    http_response_code(404); echo 'Archivo no encontrado en el servidor.'; exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($row['factura_pdf']) . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
