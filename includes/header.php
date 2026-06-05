<?php require_once __DIR__ . '/../config/auth.php'; require_login(); $user = current_user(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'MercadoPar') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">
        <span class="logo">MercadoPar</span>
        <span class="subtitle">Internal Tools</span>
    </div>
    <ul class="navbar-menu">
        <li><a href="/index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="/colaboradores/index.php" class="<?= str_contains($_SERVER['PHP_SELF'], '/colaboradores/') ? 'active' : '' ?>">Colaboradores</a></li>
        <li><a href="/financeiro/index.php" class="<?= str_contains($_SERVER['PHP_SELF'], '/financeiro/') ? 'active' : '' ?>">Financeiro</a></li>
    </ul>
    <div class="navbar-user">
        <span><?= htmlspecialchars($user['name']) ?></span>
        <a href="/logout.php" class="btn-logout">Sair</a>
    </div>
</nav>
<main class="container">
