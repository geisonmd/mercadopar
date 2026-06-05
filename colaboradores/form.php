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
        'nome'          => trim($_POST['nome'] ?? ''),
        'cpf'           => preg_replace('/\D/', '', $_POST['cpf'] ?? ''),
        'rg'            => trim($_POST['rg'] ?? ''),
        'data_nascimento'=> $_POST['data_nascimento'] ?: null,
        'email'         => trim($_POST['email'] ?? ''),
        'telefone'      => trim($_POST['telefone'] ?? ''),
        'endereco'      => trim($_POST['endereco'] ?? ''),
        'cargo'         => trim($_POST['cargo'] ?? ''),
        'departamento'  => trim($_POST['departamento'] ?? ''),
        'data_admissao' => $_POST['data_admissao'] ?? '',
        'salario'       => (float)str_replace(['.', ','], ['', '.'], $_POST['salario'] ?? '0'),
        'status'        => in_array($_POST['status'] ?? '', ['ativo','inativo']) ? $_POST['status'] : 'ativo',
    ];

    if (!$fields['nome']) $errors[] = 'Nome é obrigatório.';
    if (strlen($fields['cpf']) !== 11) $errors[] = 'CPF inválido.';
    if (!$fields['data_admissao']) $errors[] = 'Data de admissão é obrigatória.';

    if (!$errors) {
        // Formata CPF para armazenamento com máscara
        $cpf = $fields['cpf'];
        $fields['cpf'] = substr($cpf,0,3).'.'.substr($cpf,3,3).'.'.substr($cpf,6,3).'-'.substr($cpf,9,2);

        if ($id) {
            $sql = 'UPDATE colaboradores SET nome=?,cpf=?,rg=?,data_nascimento=?,email=?,telefone=?,
                    endereco=?,cargo=?,departamento=?,data_admissao=?,salario=?,status=? WHERE id=?';
            $params = array_values($fields);
            $params[] = $id;
        } else {
            $sql = 'INSERT INTO colaboradores (nome,cpf,rg,data_nascimento,email,telefone,
                    endereco,cargo,departamento,data_admissao,salario,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
            $params = array_values($fields);
        }
        db()->prepare($sql)->execute($params);
        header('Location: /colaboradores/index.php?saved=1');
        exit;
    }
    $col = array_merge($col ?: [], $_POST);
}
?>
<div class="page-header">
    <h1><?= $id ? 'Editar Colaborador' : 'Novo Colaborador' ?></h1>
    <a href="/colaboradores/index.php" class="btn btn-outline">← Voltar</a>
</div>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
    <form method="POST">
        <h3 style="margin-bottom:16px;font-size:15px;color:#374151">Dados Pessoais</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Nome completo *</label>
                <input type="text" name="nome" required value="<?= htmlspecialchars($col['nome'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>CPF *</label>
                <input type="text" name="cpf" required data-mask="cpf"
                       value="<?= htmlspecialchars($col['cpf'] ?? '') ?>" placeholder="000.000.000-00">
            </div>
            <div class="form-group">
                <label>RG</label>
                <input type="text" name="rg" value="<?= htmlspecialchars($col['rg'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Data de Nascimento</label>
                <input type="date" name="data_nascimento" value="<?= htmlspecialchars($col['data_nascimento'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" value="<?= htmlspecialchars($col['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="text" name="telefone" data-mask="phone"
                       value="<?= htmlspecialchars($col['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label>Endereço</label>
                <input type="text" name="endereco" value="<?= htmlspecialchars($col['endereco'] ?? '') ?>">
            </div>
        </div>

        <h3 style="margin:24px 0 16px;font-size:15px;color:#374151">Dados Profissionais</h3>
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
                <label>Data de Admissão *</label>
                <input type="date" name="data_admissao" required value="<?= htmlspecialchars($col['data_admissao'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Salário (R$)</label>
                <input type="number" name="salario" step="0.01" min="0"
                       value="<?= htmlspecialchars($col['salario'] ?? '0') ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="ativo"   <?= ($col['status'] ?? 'ativo') === 'ativo'   ? 'selected' : '' ?>>Ativo</option>
                    <option value="inativo" <?= ($col['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="/colaboradores/index.php" class="btn btn-outline">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
