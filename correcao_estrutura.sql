-- Remover a foreign key existente
ALTER TABLE producao DROP FOREIGN KEY producao_ibfk_1;

-- Limpar dados de exemplo da tabela de produção
TRUNCATE TABLE producao;

-- Alterar a referência para a tabela usuarios
ALTER TABLE producao ADD CONSTRAINT fk_producao_usuario 
FOREIGN KEY (id_funcionario) REFERENCES usuarios(id);

-- Remover dados de exemplo da tabela funcionarios
TRUNCATE TABLE funcionarios;

-- Remover a tabela funcionarios que não está sendo usada
DROP TABLE funcionarios; 