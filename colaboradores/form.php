<?php
$pageTitle = 'Colaborador — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$col = [];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM colaboradores WHERE id = ?');
    $stmt->execute([$id]);
    $col = $stmt->fetch();
    if (!$col) { header('Location: /colaboradores'); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'nome'            => trim($_POST['nome'] ?? ''),
        'cpf'             => trim($_POST['cedula'] ?? ''),
        'rg'              => '',
        'data_nascimento' => $_POST['data_nascimento'] ?: null,
        'email'           => trim($_POST['email'] ?? ''),
        'telefone'        => trim($_POST['telefone'] ?? ''),
        'endereco'        => trim($_POST['endereco'] ?? ''),
        'cargo'           => trim($_POST['cargo'] ?? ''),
        'departamento'    => trim($_POST['departamento'] ?? ''),
        'data_admissao'   => $_POST['data_admissao'] ?? '',
        'salario'         => (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9,.]/', '', $_POST['salario'] ?? '0')),
        'moneda_salario'  => in_array($_POST['moneda_salario'] ?? '', ['GS','USD']) ? $_POST['moneda_salario'] : 'GS',
        'status'          => in_array($_POST['status'] ?? '', ['ativo','inativo']) ? $_POST['status'] : 'ativo',
    ];

    if (!$fields['nome'])          $errors[] = 'El nombre es obligatorio.';
    if (!$fields['cpf'])           $errors[] = 'La Cédula de Identidad es obligatoria.';
    if (!$fields['data_admissao']) $errors[] = 'La fecha de ingreso es obligatoria.';

    // Upload contrato PDF
    $contrato_pdf = $col['contrato_pdf'] ?? null;
    if (!empty($_FILES['contrato_pdf']['name'])) {
        $ext = strtolower(pathinfo($_FILES['contrato_pdf']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $errors[] = 'El contrato debe ser un archivo PDF.';
        } elseif ($_FILES['contrato_pdf']['size'] > 20 * 1024 * 1024) {
            $errors[] = 'El contrato no puede superar 20 MB.';
        } else {
            $uploadDir = __DIR__ . '/../uploads/contratos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'contrato_' . ($id ?: 'new') . '_' . time() . '.pdf';
            if (move_uploaded_file($_FILES['contrato_pdf']['tmp_name'], $uploadDir . $filename)) {
                if (!empty($col['contrato_pdf'])) @unlink($uploadDir . basename($col['contrato_pdf']));
                $contrato_pdf = $filename;
            } else {
                $errors[] = 'Error al subir el contrato.';
            }
        }
    }

    if (!$errors) {
        if ($id) {
            $sql = 'UPDATE colaboradores SET nome=?,cpf=?,rg=?,data_nascimento=?,email=?,telefone=?,
                    endereco=?,cargo=?,departamento=?,data_admissao=?,salario=?,moneda_salario=?,status=?,contrato_pdf=? WHERE id=?';
            db()->prepare($sql)->execute([
                $fields['nome'], $fields['cpf'], $fields['rg'], $fields['data_nascimento'],
                $fields['email'], $fields['telefone'], $fields['endereco'], $fields['cargo'],
                $fields['departamento'], $fields['data_admissao'], $fields['salario'],
                $fields['moneda_salario'], $fields['status'], $contrato_pdf, $id
            ]);
        } else {
            $sql = 'INSERT INTO colaboradores (nome,cpf,rg,data_nascimento,email,telefone,
                    endereco,cargo,departamento,data_admissao,salario,moneda_salario,status,contrato_pdf)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            db()->prepare($sql)->execute([
                $fields['nome'], $fields['cpf'], $fields['rg'], $fields['data_nascimento'],
                $fields['email'], $fields['telefone'], $fields['endereco'], $fields['cargo'],
                $fields['departamento'], $fields['data_admissao'], $fields['salario'],
                $fields['moneda_salario'], $fields['status'], $contrato_pdf
            ]);
        }
        header('Location: /colaboradores?saved=1');
        exit;
    }
    $col = array_merge($col ?: [], $_POST);
    $col['contrato_pdf'] = $contrato_pdf;
}

$moneda = $col['moneda_salario'] ?? 'GS';
$salario_fmt = '';
if (!empty($col['salario']) && (float)$col['salario'] > 0) {
    $salario_fmt = $moneda === 'USD'
        ? number_format((float)$col['salario'], 2, '.', ',')
        : number_format((float)$col['salario'], 0, ',', '.');
}
?>
<div class="page-header">
    <h1><?= $id ? 'Editar Colaborador' : 'Nuevo Colaborador' ?></h1>
    <a href="/colaboradores" class="btn btn-outline">← Volver</a>
</div>

<?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
    <form method="POST" enctype="multipart/form-data">

        <h3 style="margin-bottom:16px;font-size:15px;color:#374151">Datos Personales</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="nome" required value="<?= htmlspecialchars($col['nome'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Cédula de Identidad *</label>
                <input type="text" name="cedula" id="cedula-input" required
                       value="<?= htmlspecialchars($col['cpf'] ?? '') ?>"
                       placeholder="7.968.714">
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
                <select name="moneda_salario" id="moneda-sel" onchange="updateSalarioPlaceholder()">
                    <option value="GS"  <?= $moneda === 'GS'  ? 'selected' : '' ?>>Guaraní (Gs.)</option>
                    <option value="USD" <?= $moneda === 'USD' ? 'selected' : '' ?>>Dólar (USD)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Salario Base</label>
                <input type="text" name="salario" id="salario-input"
                       value="<?= htmlspecialchars($salario_fmt) ?>"
                       placeholder="0" autocomplete="off">
                <small id="salario-hint" style="color:#6b7280;font-size:11px">
                    <?= $moneda === 'USD' ? 'En dólares (ej: 1,000.00)' : 'En guaraníes (ej: 5.000.000)' ?>
                </small>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="status">
                    <option value="ativo"   <?= ($col['status'] ?? 'ativo') === 'ativo'   ? 'selected' : '' ?>>Activo</option>
                    <option value="inativo" <?= ($col['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <h3 style="margin:24px 0 16px;font-size:15px;color:#374151">Contrato</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Archivo de Contrato PDF <span style="color:#9ca3af;font-weight:400">(opcional)</span></label>
                <input type="file" name="contrato_pdf" accept=".pdf"
                       style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;background:#fff">
                <?php if (!empty($col['contrato_pdf'])): ?>
                    <small style="color:#057a55">✓ Contrato adjunto —
                        <a href="/colaboradores/contrato_download?id=<?= $id ?>" target="_blank">ver contrato</a> |
                        subir nuevo para reemplazar
                    </small>
                <?php else: ?>
                    <small style="color:#6b7280;font-size:11px">Máximo 20 MB.</small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="/colaboradores" class="btn btn-outline">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Máscara C.I.: 7.968.714
const cedulaInput = document.getElementById('cedula-input');
cedulaInput.addEventListener('blur', function() {
    let raw = this.value.replace(/\D/g, '');
    if (raw) this.value = parseInt(raw, 10).toLocaleString('es-PY');
});
cedulaInput.addEventListener('focus', function() {
    this.value = this.value.replace(/\D/g, '');
});

// Máscara teléfono Paraguay
document.getElementById('telefone').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '').substring(0, 10);
    let out = '';
    if (v.length > 0) out = '(' + v.substring(0, 4);
    if (v.length >= 4) out += ' - ' + v.substring(4, 7);
    if (v.length >= 7) out += '-' + v.substring(7, 10);
    e.target.value = out;
});

// Máscara salario según moneda
const salarioInput = document.getElementById('salario-input');
const monedaSel = document.getElementById('moneda-sel');

function updateSalarioPlaceholder() {
    const isUSD = monedaSel.value === 'USD';
    salarioInput.placeholder = isUSD ? '1,000.00' : '5.000.000';
    document.getElementById('salario-hint').textContent = isUSD
        ? 'En dólares (ej: 1,000.00)' : 'En guaraníes (ej: 5.000.000)';
    salarioInput.value = '';
}

salarioInput.addEventListener('blur', function() {
    const isUSD = monedaSel.value === 'USD';
    let raw = this.value.replace(/[^0-9.]/g, '');
    if (!raw) return;
    const num = parseFloat(raw);
    if (isNaN(num)) return;
    this.value = isUSD
        ? num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})
        : Math.round(num).toLocaleString('es-PY');
});

salarioInput.addEventListener('focus', function() {
    this.value = this.value.replace(/[^0-9.]/g, '');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
