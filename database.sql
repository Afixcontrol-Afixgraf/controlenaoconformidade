-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS sistema_producao;
USE sistema_producao;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel_acesso ENUM('admin', 'supervisor', 'operador') NOT NULL DEFAULT 'operador',
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de funcionários
CREATE TABLE IF NOT EXISTS funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cargo VARCHAR(50),
    setor VARCHAR(50),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de produção
CREATE TABLE IF NOT EXISTS producao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_registro DATE NOT NULL,
    id_funcionario INT NOT NULL,
    os VARCHAR(20) NOT NULL,
    material VARCHAR(100) NOT NULL,
    medida VARCHAR(50) NOT NULL,
    quantidade INT NOT NULL,
    duracao INT NOT NULL,
    pontos FLOAT NOT NULL,
    medalha VARCHAR(20),
    data_hora_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_funcionario) REFERENCES funcionarios(id)
);

-- Inserir alguns funcionários de exemplo
INSERT INTO funcionarios (nome, cargo, setor) VALUES 
    ('João Silva', 'Operador', 'Produção'),
    ('Maria Santos', 'Operadora', 'Produção'),
    ('Carlos Oliveira', 'Supervisor', 'Produção'),
    ('Ana Pereira', 'Operadora', 'Produção');

-- Inserir um usuário administrador padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES
    ('Administrador', 'admin@sistema.com', '$2y$10$8tGmGa1IYQM0YVXj2K.Xn.LQy9WyXXEHMXe7pPpEWA9CuqHvz4G4W', 'admin');

-- Inserir alguns registros de produção de exemplo
INSERT INTO producao (data_registro, id_funcionario, os, material, medida, quantidade, duracao, pontos, medalha) VALUES
    (CURDATE(), 1, 'OS-001', 'Alumínio', '10x15cm', 7500, 480, 15.63, 'Ouro'),
    (CURDATE(), 2, 'OS-002', 'Aço', '5x10cm', 6200, 420, 14.76, 'Prata'),
    (CURDATE(), 3, 'OS-003', 'Plástico', '20x30cm', 5500, 360, 15.28, 'Bronze'),
    (DATE_SUB(CURDATE(), INTERVAL 1 DAY), 4, 'OS-004', 'Madeira', '15x20cm', 4800, 300, 16.00, ''); 