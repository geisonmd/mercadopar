-- ============================================================
-- Migración v2 — Adaptaciones Paraguay (MercadoPar)
-- Ejecutar una sola vez en el servidor de producción
-- ============================================================

-- 1. Campo moneda_salario en colaboradores (GS o USD)
ALTER TABLE colaboradores
    ADD COLUMN IF NOT EXISTS moneda_salario VARCHAR(5) NOT NULL DEFAULT 'GS' AFTER salario;

-- 2. Campo arquivo_pdf en contratos
ALTER TABLE contratos
    ADD COLUMN IF NOT EXISTS arquivo_pdf VARCHAR(255) NULL AFTER observacoes;

-- 3. Nuevas columnas en recibos_salario para Guaraní/USD
ALTER TABLE recibos_salario
    ADD COLUMN IF NOT EXISTS moneda       VARCHAR(5)       NOT NULL DEFAULT 'GS'  AFTER observacoes,
    ADD COLUMN IF NOT EXISTS tipo_cambio  DECIMAL(10,2)    NOT NULL DEFAULT 0     AFTER moneda,
    ADD COLUMN IF NOT EXISTS salario_gs   DECIMAL(15,2)    NOT NULL DEFAULT 0     AFTER tipo_cambio,
    ADD COLUMN IF NOT EXISTS items_json   TEXT             NULL                   AFTER salario_gs;

-- Nota: ADD COLUMN IF NOT EXISTS requiere MySQL 8.0+
-- Si está en MySQL 5.7, usar las versiones sin IF NOT EXISTS:
--
-- ALTER TABLE colaboradores ADD COLUMN moneda_salario VARCHAR(5) NOT NULL DEFAULT 'GS' AFTER salario;
-- ALTER TABLE contratos ADD COLUMN arquivo_pdf VARCHAR(255) NULL;
-- ALTER TABLE recibos_salario
--     ADD COLUMN moneda VARCHAR(5) NOT NULL DEFAULT 'GS',
--     ADD COLUMN tipo_cambio DECIMAL(10,2) NOT NULL DEFAULT 0,
--     ADD COLUMN salario_gs DECIMAL(15,2) NOT NULL DEFAULT 0,
--     ADD COLUMN items_json TEXT NULL;
