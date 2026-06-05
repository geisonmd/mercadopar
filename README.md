# MercadoPar Internal Tools

Sistema interno para gestão de colaboradores e financeiro.

## Módulos

- **Dashboard** — visão geral com indicadores do mês
- **Colaboradores** — cadastro, busca, contratos e recibos de salário
- **Financeiro** — contas a pagar/receber, fluxo de caixa por mês

## Configuração no cPanel

### 1. Banco de dados

No cPanel, importe o schema via phpMyAdmin:
```
Gerenciador de Banco de Dados MySQL → phpMyAdmin → Importar → database/schema.sql
```

### 2. Arquivo `.env`

Copie `.env.example` para `.env` e preencha com seus dados:
```
DB_HOST=localhost
DB_NAME=cpanelusuario_nomedoseudb
DB_USER=cpanelusuario_usuario
DB_PASS=sua_senha
APP_SECRET=chave_aleatoria_longa
```

> No cPanel, o nome do banco e usuário geralmente têm o prefixo do usuário cPanel.

### 3. Upload dos arquivos

Faça upload de todos os arquivos para `public_html/` (ou subdiretório desejado).

### 4. Primeiro acesso

- URL: `http://mercadopar.geisondreon.com/login.php`
- E-mail: `admin@mercadopar.com`
- Senha: `admin123`

**Troque a senha imediatamente após o primeiro login.**

Para gerar novo hash: `echo password_hash('nova_senha', PASSWORD_DEFAULT);`

## Estrutura

```
/
├── index.php               # Dashboard
├── login.php / logout.php
├── .htaccess               # Segurança
├── .env                    # Credenciais (NÃO versionar)
├── config/database.php     # Conexão PDO
├── config/auth.php         # Sessão e autenticação
├── includes/header.php footer.php
├── assets/css/app.css assets/js/app.js
├── colaboradores/          # Listagem, form, contratos, recibos
├── financeiro/             # Lançamentos, form, pagar, delete
└── database/schema.sql     # Estrutura do banco
```
