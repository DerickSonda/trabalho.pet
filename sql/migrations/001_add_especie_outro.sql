-- =====================================================================
-- Migracao 001 - Adiciona coluna especie_outro na tabela pets
-- =====================================================================
-- Quando o usuario escolhe "outro" no formulario de cadastro do pet,
-- ele agora pode digitar QUAL especie e: hamster, peixe, cobra, etc.
-- Essa coluna guarda esse texto livre, limitado a 50 caracteres.
--
-- Como aplicar (banco ja existente):
--   1. Abra o phpMyAdmin
--   2. Selecione o banco "diario_pets"
--   3. Aba "SQL" e cole/execute o conteudo deste arquivo
--
-- Para um banco zerado, basta importar o sql/schema.sql atualizado -
-- a coluna ja vem incluida la.
-- =====================================================================

USE diario_pets;

ALTER TABLE pets
    ADD COLUMN especie_outro VARCHAR(50) DEFAULT NULL
    AFTER especie;
