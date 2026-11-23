CREATE DATABASE IF NOT EXISTS supermercado_app
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE supermercado_app;

CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    descricao VARCHAR(500) NULL,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantidade INT UNSIGNED NOT NULL DEFAULT 0,
    categoria_id INT UNSIGNED NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id)
      REFERENCES categorias(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_produtos_nome (nome),
    INDEX idx_produtos_categoria (categoria_id)
) ENGINE=InnoDB;

INSERT IGNORE INTO categorias (nome) VALUES
('Mercearia'), ('Hortifruti'), ('Bebidas'), ('Limpeza'), ('Higiene pessoal');
