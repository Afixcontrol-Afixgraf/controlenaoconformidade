<?php
require_once 'config.php';

session_start();

// Verificar autenticação
$usuario = [
    'id' => $_SESSION['usuario_id'] ?? null,
    'nivel_acesso' => $_SESSION['nivel_acesso'] ?? null
];

// Apenas administradores podem acessar as configurações
if ($usuario['nivel_acesso'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    global $conexao;

    // GET - Listar configurações
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT * FROM config_medalhas ORDER BY ordem";
        $stmt = $conexao->query($sql);
        $configuracoes = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $configuracoes[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $configuracoes
        ]);
    }

    // POST - Criar nova configuração
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dados = json_decode(file_get_contents('php://input'), true);

        $sql = "INSERT INTO config_medalhas (nome, quantidade_minima, bonus_pontos, cor, ordem) 
                VALUES (:nome, :quantidade_minima, :bonus_pontos, :cor, :ordem)";

        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':quantidade_minima', $dados['quantidade_minima']);
        $stmt->bindParam(':bonus_pontos', $dados['bonus_pontos']);
        $stmt->bindParam(':cor', $dados['cor']);
        $stmt->bindParam(':ordem', $dados['ordem']);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Configuração criada com sucesso!'
        ]);
    }

    // PUT - Atualizar configuração
    else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $dados = json_decode(file_get_contents('php://input'), true);

        $sql = "UPDATE config_medalhas 
                SET nome = :nome, 
                    quantidade_minima = :quantidade_minima, 
                    bonus_pontos = :bonus_pontos, 
                    cor = :cor, 
                    ordem = :ordem,
                    ativo = :ativo
                WHERE id = :id";

        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':quantidade_minima', $dados['quantidade_minima']);
        $stmt->bindParam(':bonus_pontos', $dados['bonus_pontos']);
        $stmt->bindParam(':cor', $dados['cor']);
        $stmt->bindParam(':ordem', $dados['ordem']);
        $stmt->bindParam(':ativo', $dados['ativo']);
        $stmt->bindParam(':id', $dados['id']);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Configuração atualizada com sucesso!'
        ]);
    }

    // DELETE - Excluir configuração
    else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $sql = "DELETE FROM config_medalhas WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Configuração excluída com sucesso!'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao processar requisição: ' . $e->getMessage()
    ]);
}