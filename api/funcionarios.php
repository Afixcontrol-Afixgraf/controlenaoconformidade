<?php
require_once 'config.php';

// Rota para obter todos os funcionários
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, nome FROM funcionarios ORDER BY nome";
    $stmt = $conexao->query($sql);

    $funcionarios = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

    $sql = "INSERT INTO funcionarios (nome, cargo, setor) VALUES (:nome, :cargo, :setor)";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':cargo', $cargo);
    $stmt->bindParam(':setor', $setor);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Funcionário adicionado com sucesso",
            "id" => $conexao->lastInsertId()
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao adicionar funcionário"
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Método não permitido"
    ]);
}

$conexao->close();
?>