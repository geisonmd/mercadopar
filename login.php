<?php
require_once __DIR__ . '/config/auth.php';

if (is_logged_in()) {
    header('Location: /');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login($email, $password)) {
        header('Location: /');
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
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div style="text-align:center;margin-bottom:24px">
            <img src="/assets/img/favicon.png" alt="MercadoPar" style="height:56px;width:56px;object-fit:contain">
        </div>
        <h1 style="text-align:center;font-size:22px;margin-bottom:4px">MercadoPar</h1>
        <p class="subtitle" style="text-align:center;margin-bottom:28px">Herramientas Internas &mdash; Ingrese a su cuenta</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <div class="form-group" style="margin-bottom:14px">
                <label>Correo electrónico</label>
                <input type="email" name="email" required autofocus
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:22px">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:10px">
                Ingresar
            </button>
        </form>
    </div>
</div>
</body>
</html>
