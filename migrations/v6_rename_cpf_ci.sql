-- Migración v6 — Renombrar cpf a ci (Cédula de Identidad) en colaboradores
ALTER TABLE colaboradores CHANGE COLUMN cpf ci VARCHAR(14) NOT NULL;
