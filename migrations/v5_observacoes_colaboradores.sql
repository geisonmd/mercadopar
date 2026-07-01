-- Migración v5 — Observaciones en colaboradores
ALTER TABLE colaboradores ADD COLUMN IF NOT EXISTS observacoes TEXT NULL AFTER status;

-- Para MySQL 5.7 (sin IF NOT EXISTS):
-- ALTER TABLE colaboradores ADD COLUMN observacoes TEXT NULL;
