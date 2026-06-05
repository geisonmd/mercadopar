<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Teste de ambiente</h3>";

// Testa .env
$envPath = __DIR__ . '/.env';
echo "Caminho do .env: $envPath<br>";
echo "Arquivo .env existe? " . (file_exists($envPath) ? '<b style="color:green">SIM</b>' : '<b style="color:red">NÃO</b>') . "<br><br>";

if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    echo "DB_HOST: " . ($env['DB_HOST'] ?? 'não definido') . "<br>";
    echo "DB_NAME: " . ($env['DB_NAME'] ?? 'não definido') . "<br>";
    echo "DB_USER: " . ($env['DB_USER'] ?? 'não definido') . "<br>";
    echo "DB_PASS: " . (isset($env['DB_PASS']) ? '(definida)' : 'não definido') . "<br><br>";

    // Testa conexão
    try {
        $pdo = new PDO(
            'mysql:host='.$env['DB_HOST'].';dbname='.$env['DB_NAME'].';charset=utf8mb4',
            $env['DB_USER'],
            $env['DB_PASS'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "<b style='color:green'>✅ Conexão com banco OK!</b><br>";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tabelas encontradas: " . implode(', ', $tables) . "<br>";
    } catch (Exception $e) {
        echo "<b style='color:red'>❌ Erro no banco: " . $e->getMessage() . "</b><br>";
    }
}

echo "<br><b>PHP Version:</b> " . PHP_VERSION;
echo "<br><b>App Version:</b> <span style='background:#1a56db;color:#fff;padding:2px 8px;border-radius:4px'>v2.0 — Paraguay</span>";
echo "<br><b>Deploy:</b> " . date('d/m/Y H:i:s') . " (hora do servidor)";

// Gerador de hash
echo "<hr><h3>Gerador de Hash</h3>";
$senha = $_GET['senha'] ?? '';
if ($senha) {
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    echo "<b>Senha:</b> $senha<br>";
    echo "<b>Hash:</b> $hash<br><br>";
    echo "<b>SQL para executar no phpMyAdmin:</b><br>";
    echo "<textarea style='width:100%;height:80px'>UPDATE users SET password_hash = '$hash' WHERE email = 'admin@mercadopar.com';</textarea>";
} else {
    echo '<form>Digite a senha desejada: <input name="senha" type="text"> <button>Gerar</button></form>';
}
