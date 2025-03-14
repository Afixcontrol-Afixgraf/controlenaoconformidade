<?php
require_once 'config.php';

// Verificar autenticação
$usuario = [
    'id' => $_SESSION['usuario_id'],
    'nivel_acesso' => $_SESSION['nivel_acesso']
];

// Apenas administradores podem acessar as configurações
if (!verificarNivelAcesso(['admin'])) {
    exit;
}

try {
    global $conexao;

    // GET - Listar configurações
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT * FROM config_medalhas ORDER BY ordem";
        $result = $conexao->query($sql);
        $configuracoes = [];

        while ($row = $result->fetch_assoc()) {
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
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conexao->prepare($sql);
        $stmt->bind_param(
            'sidsi',
            $dados['nome'],
            $dados['quantidade_minima'],
            $dados['bonus_pontos'],
            $dados['cor'],
            $dados['ordem']
        );
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
                SET nome = ?, 
                    quantidade_minima = ?, 
                    bonus_pontos = ?, 
                    cor = ?, 
                    ordem = ?,
                    ativo = ?
                WHERE id = ?";

        $stmt = $conexao->prepare($sql);
        $stmt->bind_param(
            'sidsiii',
            $dados['nome'],
            $dados['quantidade_minima'],
            $dados['bonus_pontos'],
            $dados['cor'],
            $dados['ordem'],
            $dados['ativo'],
            $dados['id']
        );
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Configuração atualizada com sucesso!'
        ]);
    }

    // DELETE - Excluir configuração
    else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $sql = "DELETE FROM config_medalhas WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param('i', $id);
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