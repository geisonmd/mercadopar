<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM contratos WHERE id = ?');
$stmt->execute([$id]);
$ct = $stmt->fetch();

if (!$ct || empty($ct['arquivo_pdf'])) {
    http_response_code(404);
    echo 'Archivo no encontrado.';
    exit;
}

$filepath = __DIR__ . '/../uploads/contratos/' . basename($ct['arquivo_pdf']);
if (!file_exists($filepath)) {
    http_response_code(404);
    echo 'Archivo no encontrado en el servidor.';
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($ct['arquivo_pdf']) . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
