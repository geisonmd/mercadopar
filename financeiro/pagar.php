<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    db()->prepare(
        'UPDATE financeiro SET status="pago", data_pagamento=CURDATE() WHERE id=?'
    )->execute([$id]);
}
header('Location: /financeiro');
exit;
