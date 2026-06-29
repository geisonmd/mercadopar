-- Migração v4 — Tornar data_vencimento opcional em financeiro
ALTER TABLE financeiro MODIFY COLUMN data_vencimento DATE NULL;
