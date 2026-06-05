<?php
$pageTitle = 'Contrato — MercadoPar';
require_once __DIR__ . '/../includes/header.php';

$colaborador_id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM colaboradores WHERE id = ?');
$stmt->execute([$colaborador_id]);
$col = $stmt->fetch();
if (!$col) { header('Location: /colaboradores/index.php'); exit; }

$errors = [];

// Contratos actuales
$contratos = db()->prepare('SELECT * FROM contratos WHERE colaborador_id = ? ORDER BY data_inicio DESC');
$contratos->execute([$colaborador_id]);
$contratos = $contratos->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo        = $_POST['tipo'] ?? 'Indefinido';
    $data_inicio = $_POST['data_inicio'] ?? '';
    $data_fim    = $_POST['data_fim'] ?: null;
    $salario     = (float)str_replace(['.', ','], ['', '.'], $_POST['salario'] ?? '0');
    $cargo       = trim($_POST['cargo'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $arquivo_pdf = null;

    if (!$data_inicio) $errors[] = 'La fecha de inicio es obligatoria.';

    // Upload del PDF
    if (!empty($_FILES['arquivo_pdf']['name'])) {
        $ext = strtolower(pathinfo($_FILES['arquivo_pdf']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $errors[] = 'Solo se permiten archivos PDF.';
        } elseif ($_FILES['arquivo_pdf']['size'] > 10 * 1024 * 1024) {
            $errors[] = 'El archivo no puede superar 10 MB.';
        } else {
            $uploadDir = __DIR__ . '/../uploads/contratos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'contrato_' . $colaborador_id . '_' . time() . '.pdf';
            if (move_uploaded_file($_FILES['arquivo_pdf']['tmp_name'], $uploadDir . $filename)) {
                $arquivo_pdf = $filename;
            } else {
                $errors[] = 'Error al subir el archivo. Verifique los permisos del directorio.';
            }
        }
    }

    if (!$errors) {
        db()->prepare(
            'INSERT INTO contratos (colaborador_id,tipo,data_inicio,data_fim,salario,cargo,observacoes,arquivo_pdf)
             VALUES (?,?,?,?,?,?,?,?)'
        )->execute([$colaborador_id, $tipo, $data_inicio, $data_fim, $salario, $cargo, $observacoes, $arquivo_pdf]);
        header("Location: /colaboradores/contrato.php?id=$colaborador_id&saved=1");
        exit;
    }
}
?>
<div class="page-header">
    <h1>Contratos — <?= htmlspecialchars($col['nome']) ?></h1>
    <a href="/colaboradores/index.php" class="btn btn-outline">← Volver</a>
</div>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Contrato registrado exitosamente.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:420px 1fr;gap:20px;align-items:start">
    <div class="form-card">
        <h3 style="margin-bottom:16px;font-size:15px">Nuevo Contrato</h3>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group" style="margin-bottom:12px">
                <label>Tipo de Contrato</label>
                <select name="tipo">
                    <option value="Indefinido">Indefinido</option>
                    <option value="Determinado">Plazo Determinado</option>
                    <option value="Eventual">Eventual</option>
                    <option value="Pasante">Pasante</option>
                    <option value="Autónomo">Autónomo</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label>Cargo en el Contrato</label>
                <input type="text" name="cargo" value="<?= htmlspecialchars($col['cargo'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label>Salario en el Contrato (Gs.)</label>
                <input type="number" name="salario" step="1" min="0"
                       value="<?= htmlspecialchars($col['salario'] ?? '0') ?>">
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label>Fecha de Inicio *</label>
                <input type="date" name="data_inicio" required value="<?= $col['data_admissao'] ?>">
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label>Fecha de Fin (si aplica)</label>
                <input type="date" name="data_fim">
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label>Archivo de Contrato (PDF)</label>
                <input type="file" name="arquivo_pdf" accept=".pdf"
                       style="padding:6px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;background:#fff">
                <small style="color:#6b7280;font-size:11px">Máximo 10 MB. Solo archivos PDF.</small>
            </div>
            <div class="form-group" style="margin-bottom:16px">
                <label>Observaciones</label>
                <textarea name="observacoes"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Registrar Contrato</button>
        </form>
    </div>

    <div>
        <h3 style="margin-bottom:14px;font-size:15px">Historial de Contratos</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Tipo</th><th>Cargo</th><th>Salario</th><th>Inicio</th><th>Fin</th><th>Archivo</th></tr>
                </thead>
                <tbody>
                <?php if ($contratos): foreach ($contratos as $ct): ?>
                    <tr>
                        <td><?= htmlspecialchars($ct['tipo']) ?></td>
                        <td><?= htmlspecialchars($ct['cargo'] ?? '—') ?></td>
                        <td>Gs. <?= number_format($ct['salario'], 0, ',', '.') ?></td>
                        <td><?= date('d/m/Y', strtotime($ct['data_inicio'])) ?></td>
                        <td><?= $ct['data_fim'] ? date('d/m/Y', strtotime($ct['data_fim'])) : '—' ?></td>
                        <td>
                            <?php if (!empty($ct['arquivo_pdf'])): ?>
                                <a href="/colaboradores/contrato_download.php?id=<?= $ct['id'] ?>"
                                   target="_blank" class="btn btn-outline btn-sm">📄 Descargar</a>
                            <?php else: ?>
                                <span style="color:#9ca3af;font-size:12px">Sin archivo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#6b7280;padding:16px">Ningún contrato registrado.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
