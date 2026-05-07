-- =====================================================================
-- Diario de Pets - Schema do banco de dados
-- =====================================================================

-- Cria o banco com suporte a UTF-8 completo (incluindo emojis)
CREATE DATABASE IF NOT EXISTS diario_pets
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE diario_pets;

-- ---------------------------------------------------------------------
-- Tabela: usuarios
-- Guarda os donos dos pets (login do sistema)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    senha_hash  VARCHAR(255) NOT NULL,
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabela: pets
-- Cada pet pertence a um usuario
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pets (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id       INT UNSIGNED NOT NULL,
    nome             VARCHAR(100) NOT NULL,
    especie          ENUM('cao','gato','coelho','ave','outro') NOT NULL,
    especie_outro    VARCHAR(50) DEFAULT NULL,
    raca             VARCHAR(100) DEFAULT NULL,
    data_nascimento  DATE DEFAULT NULL,
    peso             DECIMAL(5,2) DEFAULT NULL,
    foto             VARCHAR(255) DEFAULT NULL,
    observacoes      TEXT DEFAULT NULL,
    criado_em        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pets_usuario_id (usuario_id),
    CONSTRAINT fk_pets_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabela: registros
-- Cada registro (alimentacao, banho, vet, etc) pertence a um pet
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS registros (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pet_id      INT UNSIGNED NOT NULL,
    tipo        ENUM('alimentacao','limpeza','veterinario','medicacao','banho','outro') NOT NULL,
    data_hora   DATETIME NOT NULL,
    descricao   TEXT DEFAULT NULL,
    custo       DECIMAL(10,2) DEFAULT NULL,
    criado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_registros_pet_id (pet_id),
    KEY idx_registros_data_hora (data_hora),
    CONSTRAINT fk_registros_pet
        FOREIGN KEY (pet_id) REFERENCES pets(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
