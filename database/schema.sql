-- =============================================
-- MercadoPar Internal Tools - Schema
-- =============================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS colaboradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    rg VARCHAR(20),
    data_nascimento DATE,
    email VARCHAR(150),
    telefone VARCHAR(20),
    endereco TEXT,
    cargo VARCHAR(100),
    departamento VARCHAR(100),
    data_admissao DATE NOT NULL,
    data_demissao DATE,
    salario DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    colaborador_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'CLT',
    data_inicio DATE NOT NULL,
    data_fim DATE,
    salario DECIMAL(10,2) NOT NULL,
    cargo VARCHAR(100),
    observacoes TEXT,
    arquivo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS recibos_salario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    colaborador_id INT NOT NULL,
    mes INT NOT NULL,
    ano INT NOT NULL,
    salario_bruto DECIMAL(10,2) NOT NULL,
    inss DECIMAL(10,2) NOT NULL DEFAULT 0,
    irrf DECIMAL(10,2) NOT NULL DEFAULT 0,
    outros_descontos DECIMAL(10,2) NOT NULL DEFAULT 0,
    outros_acrescimos DECIMAL(10,2) NOT NULL DEFAULT 0,
    salario_liquido DECIMAL(10,2) NOT NULL,
    data_pagamento DATE,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_recibo (colaborador_id, mes, ano),
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS financeiro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('receita','despesa') NOT NULL,
    categoria VARCHAR(100),
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE,
    data_pagamento DATE,
    status ENUM('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
    colaborador_id INT,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE SET NULL
);

-- Usuário administrador padrão (senha: admin123 - TROQUE IMEDIATAMENTE)
INSERT IGNORE INTO users (name, email, password_hash) VALUES (
    'Administrador',
    'admin@mercadopar.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);
