<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$contrato_id = (int)($_GET['contrato_id'] ?? 0);
$stmt = db()->prepare(
    'SELECT ct.*, c.nome, c.cpf, c.rg, c.data_nascimento, c.endereco, c.email, c.telefone
     FROM contratos ct JOIN colaboradores c ON c.id = ct.colaborador_id
     WHERE ct.id = ?'
);
$stmt->execute([$contrato_id]);
$ct = $stmt->fetch();
if (!$ct) { echo 'Contrato não encontrado.'; exit; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Contrato — <?= htmlspecialchars($ct['nome']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #000; max-width: 760px; margin: 40px auto; line-height: 1.7; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 6px; }
        .subtitle { text-align: center; margin-bottom: 30px; color: #444; }
        .section { margin-bottom: 20px; }
        .section-title { font-weight: bold; text-transform: uppercase; font-size: 11px; letter-spacing: .06em; border-bottom: 1px solid #000; margin-bottom: 10px; padding-bottom: 3px; }
        .field { margin-bottom: 6px; }
        .field span { font-weight: bold; }
        .clausulas p { text-align: justify; margin-bottom: 10px; }
        .assinaturas { display: flex; justify-content: space-between; margin-top: 60px; }
        .assinatura-box { text-align: center; width: 45%; }
        .assinatura-box .linha { border-top: 1px solid #000; margin-bottom: 6px; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <h1>CONTRATO DE TRABALHO — <?= strtoupper(htmlspecialchars($ct['tipo'])) ?></h1>
    <p class="subtitle">MercadoPar &mdash; <?= date('d/m/Y') ?></p>

    <button onclick="window.print()" style="margin-bottom:20px;padding:8px 16px;cursor:pointer">Imprimir</button>

    <div class="section">
        <div class="section-title">Dados do Empregado</div>
        <div class="field"><span>Nome:</span> <?= htmlspecialchars($ct['nome']) ?></div>
        <div class="field"><span>CPF:</span> <?= htmlspecialchars($ct['cpf']) ?></div>
        <div class="field"><span>RG:</span> <?= htmlspecialchars($ct['rg'] ?? '—') ?></div>
        <?php if ($ct['data_nascimento']): ?>
        <div class="field"><span>Data de Nascimento:</span> <?= date('d/m/Y', strtotime($ct['data_nascimento'])) ?></div>
        <?php endif; ?>
        <div class="field"><span>Endereço:</span> <?= htmlspecialchars($ct['endereco'] ?? '—') ?></div>
    </div>

    <div class="section">
        <div class="section-title">Dados do Contrato</div>
        <div class="field"><span>Cargo:</span> <?= htmlspecialchars($ct['cargo'] ?? '—') ?></div>
        <div class="field"><span>Salário:</span> R$ <?= number_format($ct['salario'], 2, ',', '.') ?></div>
        <div class="field"><span>Início:</span> <?= date('d/m/Y', strtotime($ct['data_inicio'])) ?></div>
        <div class="field"><span>Término:</span> <?= $ct['data_fim'] ? date('d/m/Y', strtotime($ct['data_fim'])) : 'Indeterminado' ?></div>
        <?php if ($ct['observacoes']): ?>
        <div class="field"><span>Observações:</span> <?= nl2br(htmlspecialchars($ct['observacoes'])) ?></div>
        <?php endif; ?>
    </div>

    <div class="section clausulas">
        <div class="section-title">Cláusulas</div>
        <p>1. O presente contrato é celebrado entre a empresa <strong>MercadoPar</strong> (doravante "EMPREGADOR") e o(a) colaborador(a) identificado(a) acima (doravante "EMPREGADO").</p>
        <p>2. O EMPREGADO exercerá as funções de <strong><?= htmlspecialchars($ct['cargo'] ?? 'colaborador') ?></strong>, comprometendo-se a desempenhar suas atividades com dedicação, competência e ética.</p>
        <p>3. A remuneração mensal é de <strong>R$ <?= number_format($ct['salario'], 2, ',', '.') ?></strong>, paga até o 5º dia útil do mês subsequente.</p>
        <p>4. O presente contrato obedecerá às disposições da Consolidação das Leis do Trabalho (CLT) e demais legislações aplicáveis.</p>
        <p>5. Qualquer alteração das condições aqui pactuadas somente terá validade se formalizada por escrito e assinada por ambas as partes.</p>
    </div>

    <div class="assinaturas">
        <div class="assinatura-box">
            <div class="linha"></div>
            <strong>MercadoPar</strong><br>Empregador
        </div>
        <div class="assinatura-box">
            <div class="linha"></div>
            <strong><?= htmlspecialchars($ct['nome']) ?></strong><br>Empregado
        </div>
    </div>
</body>
</html>
