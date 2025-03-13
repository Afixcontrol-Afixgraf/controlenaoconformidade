<?php
require_once 'config.php';

// Rota para obter todos os funcionários
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, nome FROM funcionarios ORDER BY nome";
    $result = $conexao->query($sql);
    
    $funcionarios = [];
    while ($row = $result->fetch_assoc()) {
        $funcionarios[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "data" => $funcionarios
    ]);
}
// Rota para adicionar um novo funcionário
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['nome']) || empty($data['nome'])) {
        echo json_encode([
            "status" => "error",
            "message" => "O nome do funcionário é obrigatório"
        ]);
        exit;
    }
    
    $nome = $data['nome'];
    $cargo = isset($data['cargo']) ? $data['cargo'] : '';
    $setor = isset($data['setor']) ? $data['setor'] : '';
    
    $sql = "INSERT INTO funcionarios (nome, cargo, setor) VALUES (?, ?, ?)";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("sss", $nome, $cargo, $setor);
    
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Funcionário adicionado com sucesso",
            "id" => $conexao->insert_id
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao adicionar funcionário: " . $stmt->error
        ]);
    }
    
    $stmt->close();
}
else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Método não permitido"
    ]);
}

$conexao->close();
?> 