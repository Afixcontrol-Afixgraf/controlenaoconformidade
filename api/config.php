<?php
// Conexão com o banco de dados
$host = '127.0.0.1';
$dbname = 'sistema_producao';
$username = 'root';
$password = 'admin';

try {
    $conexao = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Conexão falhou: ' . $e->getMessage()]);
    exit;
}
