<?php
require_once 'conexao.php';
require_once 'autenticacao.php';

// Verificar autenticação
$usuario = verificarAutenticacao();
if (!$usuario || ($usuario['nivel_acesso'] !== 'admin' && $usuario['nivel_acesso'] !== 'supervisor')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado']);
    exit;
}

try {
    $pdo = getConexao();

    // Obter o primeiro dia do mês atual
    $primeiroDiaMes = date('Y-m-01');

    // Consulta para obter o ranking do mês atual
    $sql = "SELECT 
                u.id,
                u.nome,
                COUNT(CASE WHEN p.medalha = 'Ouro' THEN 1 END) as medalhas_ouro,
                COUNT(CASE WHEN p.medalha = 'Prata' THEN 1 END) as medalhas_prata,
                COUNT(CASE WHEN p.medalha = 'Bronze' THEN 1 END) as medalhas_bronze,
                SUM(p.quantidade) as total_pecas,
                SUM(p.pontos) as pontos
            FROM usuarios u
            LEFT JOIN producao p ON u.id = p.id_funcionario 
                AND p.data_registro >= :primeiro_dia_mes
            WHERE u.nivel_acesso = 'funcionario'
            GROUP BY u.id, u.nome
            ORDER BY pontos DESC
            LIMIT 3";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['primeiro_dia_mes' => $primeiroDiaMes]);
    $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $ranking
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao carregar ranking: ' . $e->getMessage()
    ]);
}