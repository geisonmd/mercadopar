<?php
require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login($email, $password)) {
        header('Location: /index.php');
        exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión &mdash; MercadoPar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div style="text-align:center;margin-bottom:20px">
            <img src="/assets/img/logo.png" alt="MercadoPar" style="height:52px">
        </div>
        <h1 style="text-align:center">MercadoPar</h1>
        <p class="subtitle" style="text-align:center">Herramientas Internas &mdash; Ingrese a su cuenta</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login.php">
            <div class="form-group" style="margin-bottom:14px">
                <label>Correo electrónico</label>
                <input type="email" name="email" required autofocus
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:22px">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                Ingresar
            </button>
        </form>
    </div>
</div>
</body>
</html>
