<?php
// Configuração da conexão com o banco de dados
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistema_producao";

// Inicializa a conexão
$conexao = new mysqli($host, $usuario, $senha, $banco);

// Verifica se houve erro na conexão
if ($conexao->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Falha na conexão: " . $conexao->connect_error
    ]));
}

// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para criar as tabelas se não existirem
function criarTabelas($conexao) {
    // Tabela de usuários
    $sql_usuarios = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        nivel_acesso ENUM('admin', 'supervisor', 'operador') NOT NULL DEFAULT 'operador',
        ativo BOOLEAN NOT NULL DEFAULT TRUE,
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Tabela de funcionários
    $sql_funcionarios = "CREATE TABLE IF NOT EXISTS funcionarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        cargo VARCHAR(50),
        setor VARCHAR(50),
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Tabela de produção
    $sql_producao = "CREATE TABLE IF NOT EXISTS producao (
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
    )";
    
    // Executa as queries
    $conexao->query($sql_usuarios);
    $conexao->query($sql_funcionarios);
    $conexao->query($sql_producao);
    
    // Insere alguns funcionários de exemplo se a tabela estiver vazia
    $result = $conexao->query("SELECT COUNT(*) as total FROM funcionarios");
    $row = $result->fetch_assoc();
    
    if ($row['total'] == 0) {
        $conexao->query("INSERT INTO funcionarios (nome, cargo, setor) VALUES 
            ('João Silva', 'Operador', 'Produção'),
            ('Maria Santos', 'Operadora', 'Produção'),
            ('Carlos Oliveira', 'Supervisor', 'Produção'),
            ('Ana Pereira', 'Operadora', 'Produção')");
    }
    
    // Insere um usuário administrador padrão se a tabela estiver vazia
    $result = $conexao->query("SELECT COUNT(*) as total FROM usuarios");
    $row = $result->fetch_assoc();
    
    if ($row['total'] == 0) {
        // Senha: admin123
        $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $conexao->query("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES 
            ('Administrador', 'admin@sistema.com', '$senha_hash', 'admin')");
    }
}

// Função para verificar se o usuário está autenticado
function verificarAutenticacao() {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Usuário não autenticado"
        ]);
        exit;
    }
    return true;
}

// Função para verificar o nível de acesso do usuário
function verificarNivelAcesso($niveis_permitidos) {
    verificarAutenticacao();
    
    if (!in_array($_SESSION['nivel_acesso'], $niveis_permitidos)) {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Acesso negado. Nível de permissão insuficiente."
        ]);
        exit;
    }
    return true;
}

// Cria as tabelas se não existirem
criarTabelas($conexao);

// Configurar cabeçalhos para permitir CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Se for uma requisição OPTIONS, retornar apenas os cabeçalhos
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?> 