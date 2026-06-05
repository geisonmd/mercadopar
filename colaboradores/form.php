<?php
$pageTitle = 'Colaborador — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$col = [];
$errors = [];

if ($id) {
    $col = db()->prepare('SELECT * FROM colaboradores WHERE id = ?');
    $col->execute([$id]);
    $col = $col->fetch();
    if (!$col) { header('Location: /colaboradores/index.php'); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'nome'           => trim($_POST['nome'] ?? ''),
        'cpf'            => trim($_POST['cedula'] ?? ''),   // columna cpf = C.I.
        'rg'             => '',                              // campo eliminado
        'data_nascimento'=> $_POST['data_nascimento'] ?: null,
        'email'          => trim($_POST['email'] ?? ''),
        'telefone'       => trim($_POST['telefone'] ?? ''),
        'endereco'       => trim($_POST['endereco'] ?? ''),
        'cargo'          => trim($_POST['cargo'] ?? ''),
        'departamento'   => trim($_POST['departamento'] ?? ''),
        'data_admissao'  => $_POST['data_admissao'] ?? '',
        'salario'        => (float)str_replace(['.', ','], ['', '.'], $_POST['salario'] ?? '0'),
        'moneda_salario' => in_array($_POST['moneda_salario'] ?? '', ['GS','USD']) ? $_POST['moneda_salario'] : 'GS',
        'status'         => in_array($_POST['status'] ?? '', ['ativo','inativo']) ? $_POST['status'] : 'ativo',
    ];

    if (!$fields['nome'])         $errors[] = 'El nombre es obligatorio.';
    if (!$fields['cpf'])          $errors[] = 'La Cédula de Identidad es obligatoria.';
    if (!$fields['data_admissao']) $errors[] = 'La fecha de ingreso es obligatoria.';

    if (!$errors) {
        if ($id) {
            $sql = 'UPDATE colaboradores SET nome=?,cpf=?,rg=?,data_nascimento=?,email=?,telefone=?,
                    endereco=?,cargo=?,departamento=?,data_admissao=?,salario=?,status=? WHERE id=?';
            $params = [
                $fields['nome'], $fields['cpf'], $fields['rg'], $fields['data_nascimento'],
                $fields['email'], $fields['telefone'], $fields['endereco'], $fields['cargo'],
                $fields['departamento'], $fields['data_admissao'], $fields['salario'], $fields['status'],
                $id
            ];
        } else {
            $sql = 'INSERT INTO colaboradores (nome,cpf,rg,data_nascimento,email,telefone,
                    endereco,cargo,departamento,data_admissao,salario,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
            $params = [
                $fields['nome'], $fields['cpf'], $fields['rg'], $fields['data_nascimento'],
                $fields['email'], $fields['telefone'], $fields['endereco'], $fields['cargo'],
                $fields['departamento'], $fields['data_admissao'], $fields['salario'], $fields['status'],
            ];
        }
        db()->prepare($sql)->execute($params);
        header('Location: /colaboradores/index.php?saved=1');
        exit;
    }
    $col = array_merge($col ?: [], $_POST);
}
?>
<div class="page-header">
    <h1><?= $id ? 'Editar Colaborador' : 'Nuevo Colaborador' ?></h1>
    <a href="/colaboradores/index.php" class="btn btn-outline">← Volver</a>
</div>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
    <form method="POST">
        <h3 style="margin-bottom:16px;font-size:15px;color:#374151">Datos Personales</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="nome" required value="<?= htmlspecialchars($col['nome'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Cédula de Identidad *</label>
                <input type="text" name="cedula" required
                       value="<?= htmlspecialchars($col['cpf'] ?? '') ?>"
                       placeholder="Ej: 7.968.714">
            </div>
            <div class="form-group">
                <label>Fecha de Nacimiento</label>
                <input type="date" name="data_nascimento" value="<?= htmlspecialchars($col['data_nascimento'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Correo electrónico</label>
                <input type="email" name="email" value="<?= htmlspecialchars($col['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefone" id="telefone"
                       value="<?= htmlspecialchars($col['telefone'] ?? '') ?>"
                       placeholder="(0981 - 234-567)" maxlength="16">
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>Dirección</label>
                <input type="text" name="endereco" value="<?= htmlspecialchars($col['endereco'] ?? '') ?>">
            </div>
        </div>

        <h3 style="margin:24px 0 16px;font-size:15px;color:#374151">Datos Profesionales</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Cargo</label>
                <input type="text" name="cargo" value="<?= htmlspecialchars($col['cargo'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Departamento</label>
                <input type="text" name="departamento" value="<?= htmlspecialchars($col['departamento'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Fecha de Ingreso *</label>
                <input type="date" name="data_admissao" required value="<?= htmlspecialchars($col['data_admissao'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Moneda del Salario</label>
                <select name="moneda_salario">
                    <option value="GS"  <?= ($col['moneda_salario'] ?? 'GS') === 'GS'  ? 'selected' : '' ?>>Guaraní (Gs.)</option>
                    <option value="USD" <?= ($col['moneda_salario'] ?? '') === 'USD' ? 'selected' : '' ?>>Dólar (USD)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Salario Base</label>
                <input type="number" name="salario" step="0.01" min="0"
                       value="<?= htmlspecialchars($col['salario'] ?? '0') ?>">
                <small style="color:#6b7280;font-size:11px">En la moneda seleccionada</small>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="status">
                    <option value="ativo"   <?= ($col['status'] ?? 'ativo') === 'ativo'   ? 'selected' : '' ?>>Activo</option>
                    <option value="inativo" <?= ($col['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="/colaboradores/index.php" class="btn btn-outline">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Máscara de teléfono Paraguay: (09XX - XXX-XXX)
document.getElementById('telefone').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '').substring(0, 10);
    let out = '';
    if (v.length > 0) out = '(' + v.substring(0, 4);
    if (v.length >= 4) out += ' - ' + v.substring(4, 7);
    if (v.length >= 7) out += '-' + v.substring(7, 10);
    e.target.value = out;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
