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
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">
        <img src="/assets/img/logo.png" alt="MercadoPar">
        <div class="navbar-brand-text">
            <span class="logo">MercadoPar</span>
            <span class="subtitle">Internal Tools</span>
        </div>
    </div>
    <ul class="navbar-menu">
        <li><a href="/index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="/colaboradores/index.php" class="<?= strpos($_SERVER['PHP_SELF'], '/colaboradores/') !== false ? 'active' : '' ?>">Colaboradores</a></li>
        <li><a href="/financeiro/index.php" class="<?= strpos($_SERVER['PHP_SELF'], '/financeiro/') !== false ? 'active' : '' ?>">Financiero</a></li>
    </ul>
    <div class="navbar-user">
        <span><?= htmlspecialchars($user['name'] ?? '') ?></span>
        <a href="/logout.php" class="btn-logout">Salir</a>
    </div>
</nav>
<main class="container">
