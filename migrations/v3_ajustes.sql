-- ============================================================
-- Migración v3 — Ajustes (MercadoPar)
-- Ejecutar después de v2_paraguay.sql
-- ============================================================

-- 1. Contrato PDF directo en colaboradores (un contrato por colaborador)
ALTER TABLE colaboradores
    ADD COLUMN IF NOT EXISTS contrato_pdf VARCHAR(255) NULL AFTER moneda_salario;

-- 2. Factura PDF en movimientos financieros
ALTER TABLE financeiro
    ADD COLUMN IF NOT EXISTS factura_pdf VARCHAR(255) NULL AFTER observacoes;

-- Para MySQL 5.7 (sin IF NOT EXISTS):
-- ALTER TABLE colaboradores ADD COLUMN contrato_pdf VARCHAR(255) NULL;
-- ALTER TABLE financeiro ADD COLUMN factura_pdf VARCHAR(255) NULL;
