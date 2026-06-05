<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$recibo_id = (int)($_GET['recibo_id'] ?? 0);
$stmt = db()->prepare(
    'SELECT r.*, c.nome, c.cpf, c.cargo, c.departamento
     FROM recibos_salario r JOIN colaboradores c ON c.id = r.colaborador_id
     WHERE r.id = ?'
);
$stmt->execute([$recibo_id]);
$r = $stmt->fetch();
if (!$r) { echo 'Recibo não encontrado.'; exit; }

$meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$descontos_total = $r['inss'] + $r['irrf'] + $r['outros_descontos'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo <?= $meses[$r['mes']] . '/' . $r['ano'] ?> — <?= htmlspecialchars($r['nome']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; max-width: 760px; margin: 40px auto; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 7px 10px; font-size: 12px; }
        th { background: #f0f0f0; text-align: left; }
        .total-row td { font-weight: bold; background: #f9f9f9; }
        .assinatura { display: flex; justify-content: space-between; margin-top: 50px; }
        .assinatura-box { text-align: center; width: 45%; }
        .linha { border-top: 1px solid #000; margin-bottom: 4px; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>MercadoPar</h1>
            <div>CNPJ: ____._____._____/____-__</div>
        </div>
        <div style="text-align:right">
            <strong>RECIBO DE SALÁRIO</strong><br>
            <?= $meses[$r['mes']] . '/' . $r['ano'] ?>
        </div>
    </div>

    <button onclick="window.print()" style="margin-bottom:20px;padding:8px 16px;cursor:pointer">Imprimir</button>

    <table>
        <tr><th style="width:25%">Funcionário</th><td colspan="3"><?= htmlspecialchars($r['nome']) ?></td></tr>
        <tr><th>CPF</th><td><?= htmlspecialchars($r['cpf']) ?></td><th>Cargo</th><td><?= htmlspecialchars($r['cargo'] ?? '—') ?></td></tr>
        <tr><th>Departamento</th><td><?= htmlspecialchars($r['departamento'] ?? '—') ?></td>
            <th>Competência</th><td><?= $meses[$r['mes']] . '/' . $r['ano'] ?></td></tr>
    </table>

    <table>
        <thead>
            <tr><th>Descrição</th><th style="width:160px;text-align:right">Vencimentos</th><th style="width:160px;text-align:right">Descontos</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Salário Base</td>
                <td style="text-align:right">R$ <?= number_format($r['salario_bruto'], 2, ',', '.') ?></td>
                <td></td>
            </tr>
            <?php if ($r['outros_acrescimos'] > 0): ?>
            <tr>
                <td>Outros Acréscimos</td>
                <td style="text-align:right">R$ <?= number_format($r['outros_acrescimos'], 2, ',', '.') ?></td>
                <td></td>
            </tr>
            <?php endif; ?>
            <?php if ($r['inss'] > 0): ?>
            <tr>
                <td>INSS</td>
                <td></td>
                <td style="text-align:right">R$ <?= number_format($r['inss'], 2, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($r['irrf'] > 0): ?>
            <tr>
                <td>IRRF</td>
                <td></td>
                <td style="text-align:right">R$ <?= number_format($r['irrf'], 2, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($r['outros_descontos'] > 0): ?>
            <tr>
                <td>Outros Descontos</td>
                <td></td>
                <td style="text-align:right">R$ <?= number_format($r['outros_descontos'], 2, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($r['observacoes']): ?>
            <tr><td colspan="3"><em><?= htmlspecialchars($r['observacoes']) ?></em></td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>TOTAL LÍQUIDO</td>
                <td style="text-align:right">R$ <?= number_format($r['salario_bruto'] + $r['outros_acrescimos'], 2, ',', '.') ?></td>
                <td style="text-align:right">R$ <?= number_format($descontos_total, 2, ',', '.') ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="2"><strong>SALÁRIO LÍQUIDO A RECEBER</strong></td>
                <td style="text-align:right;font-size:15px">R$ <?= number_format($r['salario_liquido'], 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if ($r['data_pagamento']): ?>
    <p><strong>Data de Pagamento:</strong> <?= date('d/m/Y', strtotime($r['data_pagamento'])) ?></p>
    <?php endif; ?>

    <div class="assinatura">
        <div class="assinatura-box">
            <div class="linha"></div>
            <strong>MercadoPar</strong><br>Empregador
        </div>
        <div class="assinatura-box">
            <div class="linha"></div>
            <strong><?= htmlspecialchars($r['nome']) ?></strong><br>Empregado — CPF: <?= htmlspecialchars($r['cpf']) ?>
        </div>
    </div>
</body>
</html>
