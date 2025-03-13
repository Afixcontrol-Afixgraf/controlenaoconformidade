<?php
require_once 'config.php';

// Rota para obter todos os registros de produção
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT p.*, f.nome as nome_funcionario 
            FROM producao p 
            JOIN funcionarios f ON p.id_funcionario = f.id 
            ORDER BY p.data_registro DESC, p.id DESC";
    $result = $conexao->query($sql);
    
    $registros = [];
    while ($row = $result->fetch_assoc()) {
        $registros[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "data" => $registros
    ]);
}
// Rota para adicionar um novo registro de produção
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validação dos campos obrigatórios
    $campos_obrigatorios = ['id_funcionario', 'os', 'material', 'medida', 'quantidade', 'duracao'];
    foreach ($campos_obrigatorios as $campo) {
        if (!isset($data[$campo]) || empty($data[$campo])) {
            echo json_encode([
                "status" => "error",
                "message" => "O campo $campo é obrigatório"
            ]);
            exit;
        }
    }
    
    $id_funcionario = $data['id_funcionario'];
    $os = $data['os'];
    $material = $data['material'];
    $medida = $data['medida'];
    $quantidade = $data['quantidade'];
    $duracao = $data['duracao']; // em minutos
    
    // Calcula os pontos (eficiência)
    $pontos = $quantidade / $duracao;
    
    // Determina a medalha com base na quantidade
    $medalha = "";
    if ($quantidade >= 7000) {
        $medalha = "Ouro";
    } elseif ($quantidade >= 6000) {
        $medalha = "Prata";
    } elseif ($quantidade >= 5000) {
        $medalha = "Bronze";
    }
    
    // Insere o registro no banco de dados
    $sql = "INSERT INTO producao (data_registro, id_funcionario, os, material, medida, quantidade, duracao, pontos, medalha) 
            VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("isssiiis", $id_funcionario, $os, $material, $medida, $quantidade, $duracao, $pontos, $medalha);
    
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Produção registrada com sucesso!",
            "id" => $conexao->insert_id
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao registrar produção: " . $stmt->error
        ]);
    }
    
    $stmt->close();
}
// Rota para excluir um registro de produção
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id']) || empty($data['id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "ID do registro é obrigatório"
        ]);
        exit;
    }
    
    $id = $data['id'];
    
    $sql = "DELETE FROM producao WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Registro excluído com sucesso!"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao excluir registro: " . $stmt->error
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