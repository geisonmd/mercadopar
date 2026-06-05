<?php require_once __DIR__ . '/../config/auth.php'; require_login(); $user = current_user(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'MercadoPar') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">
        <img src="/assets/img/logo.png" alt="MercadoPar">
        <span class="subtitle" style="font-size:13px;opacity:.9;font-weight:500">Internal Tools</span>
    </div>
    <ul class="navbar-menu">
        <li><a href="/" class="<?= $_SERVER['PHP_SELF'] === '/index.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="/colaboradores" class="<?= strpos($_SERVER['PHP_SELF'], '/colaboradores/') !== false ? 'active' : '' ?>">Colaboradores</a></li>
        <li><a href="/financeiro" class="<?= strpos($_SERVER['PHP_SELF'], '/financeiro/') !== false ? 'active' : '' ?>">Financiero</a></li>
    </ul>
    <div class="navbar-user">
        <span><?= htmlspecialchars($user['name'] ?? '') ?></span>
        <a href="/logout.php" class="btn-logout">Salir</a>
    </div>
</nav>
<main class="container">
