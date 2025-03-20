<?php
require_once 'config.php';

session_start();

// Verificar autenticação
$usuario = [
    'id' => $_SESSION['usuario_id'] ?? null,
    'nivel_acesso' => $_SESSION['nivel_acesso'] ?? null
];

if (!$usuario['id']) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Não autenticado']);
    exit;
}

try {
    global $conexao;

    // GET - Listar registros
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $id_funcionario = isset($_GET['id_funcionario']) ? $_GET['id_funcionario'] : null;
        $filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';

        // Construir a consulta base
        $sql = "SELECT 
                p.*, 
                u.nome as nome_funcionario,
                SEC_TO_TIME(p.duracao) as duracao_formatada
                FROM producao p 
                JOIN usuarios u ON p.id_funcionario = u.id 
                WHERE 1=1";
        $params = [];
        $where = [];

        // Aplicar filtro por funcionário se não for admin/supervisor
        if ($usuario['nivel_acesso'] !== 'admin' && $usuario['nivel_acesso'] !== 'supervisor') {
            $where[] = "p.id_funcionario = :usuario_id";
            $params[':usuario_id'] = $usuario['id'];
        }
        // Se for admin/supervisor e um ID específico foi solicitado
        elseif ($id_funcionario) {
            $where[] = "p.id_funcionario = :id_funcionario";
            $params[':id_funcionario'] = intval($id_funcionario);
        }

        // Aplicar filtros de data
        switch ($filtro) {
            case 'hoje':
                $where[] = "DATE(p.data_registro) = CURDATE()";
                break;
            case 'semana':
                $where[] = "YEARWEEK(p.data_registro) = YEARWEEK(CURDATE())";
                break;
            case 'mes':
                $where[] = "YEAR(p.data_registro) = YEAR(CURDATE()) AND MONTH(p.data_registro) = MONTH(CURDATE())";
                break;
        }

        // Adicionar condições WHERE se houver
        if (!empty($where)) {
            $sql .= " AND " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY p.data_registro DESC";

        $stmt = $conexao->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        $registros = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Usar a duração formatada
            $row['duracao'] = $row['duracao_formatada'];
            unset($row['duracao_formatada']);
            $registros[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $registros
        ]);
    }

    // POST - Criar registro
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dados = json_decode(file_get_contents('php://input'), true);

        // Validar se o usuário está registrando para si mesmo
        if ($dados['id_funcionario'] != $usuario['id'] && $usuario['nivel_acesso'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Não autorizado a registrar para outro funcionário']);
            exit;
        }

        // Converter duração de HH:MM:SS para segundos
        $partes_tempo = explode(':', $dados['duracao']);
        $duracao_segundos = ($partes_tempo[0] * 3600) + ($partes_tempo[1] * 60) + $partes_tempo[2];

        // Buscar configurações de medalhas ativas ordenadas por quantidade mínima (decrescente)
        $sql = "SELECT * FROM config_medalhas WHERE ativo = 1 ORDER BY quantidade_minima DESC";
        $stmt = $conexao->query($sql);

        // Calcular medalha e pontos baseado nas configurações
        $medalha = null;
        $bonus_pontos = 1;
        $pontos = $dados['quantidade'];

        while ($config = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($dados['quantidade'] >= $config['quantidade_minima']) {
                $medalha = $config['nome'];
                $bonus_pontos = $config['bonus_pontos'];
                break;
            }
        }

        // Aplicar bônus aos pontos
        $pontos *= $bonus_pontos;

        $sql = "INSERT INTO producao (id_funcionario, os, material, medida, quantidade, duracao, medalha, pontos, data_registro) 
                VALUES (:id_funcionario, :os, :material, :medida, :quantidade, :duracao, :medalha, :pontos, NOW())";

        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':id_funcionario', $dados['id_funcionario'], PDO::PARAM_INT);
        $stmt->bindParam(':os', $dados['os']);
        $stmt->bindParam(':material', $dados['material']);
        $stmt->bindParam(':medida', $dados['medida']);
        $stmt->bindParam(':quantidade', $dados['quantidade'], PDO::PARAM_INT);
        $stmt->bindParam(':duracao', $duracao_segundos, PDO::PARAM_INT);
        $stmt->bindParam(':medalha', $medalha);
        $stmt->bindParam(':pontos', $pontos);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Registro criado com sucesso!'
        ]);
    }

    // DELETE - Excluir registro
    else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $dados = json_decode(file_get_contents('php://input'), true);

        // Verificar se o usuário pode excluir o registro
        if ($usuario['nivel_acesso'] !== 'admin' && $usuario['nivel_acesso'] !== 'supervisor') {
            $sql = "SELECT id_funcionario FROM producao WHERE id = :id LIMIT 1";
            $stmt = $conexao->prepare($sql);
            $stmt->bindParam(':id', $dados['id'], PDO::PARAM_INT);
            $stmt->execute();
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registro || $registro['id_funcionario'] != $usuario['id']) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Não autorizado a excluir este registro']);
                exit;
            }
        }

        $sql = "DELETE FROM producao WHERE id = :id LIMIT 1";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':id', $dados['id'], PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Registro excluído com sucesso!'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao processar requisição: ' . $e->getMessage()
    ]);
}