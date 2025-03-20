<?php
// Configurações do cabeçalho
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Iniciar sessão
session_start();

// Incluir arquivo de configuração com a conexão PDO
require_once 'config.php';

// Obter o método HTTP
$metodo = $_SERVER['REQUEST_METHOD'];

// Processar a requisição com base no método HTTP
switch ($metodo) {
    case 'GET':
        // Verificar se é uma requisição para listar usuários ou verificar autenticação
        if (isset($_GET['verificar_auth'])) {
            verificarStatusAutenticacao();
        } elseif (isset($_GET['logout'])) {
            fazerLogout();
        } else {
            listarUsuarios();
        }
        break;
    case 'POST':
        // Verificar se é uma requisição de login ou cadastro
        if (isset($_GET['login'])) {
            fazerLogin();
        } else {
            cadastrarUsuario();
        }
        break;
    case 'PUT':
        atualizarUsuario();
        break;
    case 'DELETE':
        excluirUsuario();
        break;
    default:
        http_response_code(405);
        echo json_encode([
            "status" => "error",
            "message" => "Método não permitido"
        ]);
        break;
}

// Função para verificar nível de acesso
function verificarNivelAcesso($niveis_permitidos)
{
    // Verificar se o usuário está logado
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Usuário não autenticado"
        ]);
        exit;
    }

    // Verificar se o nível de acesso do usuário está entre os permitidos
    if (!in_array($_SESSION['nivel_acesso'], $niveis_permitidos)) {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Acesso negado. Nível de permissão insuficiente."
        ]);
        exit;
    }
}

// Função para verificar o status de autenticação do usuário atual
function verificarStatusAutenticacao()
{
    if (isset($_SESSION['usuario_id'])) {
        echo json_encode([
            "status" => "success",
            "autenticado" => true,
            "usuario" => [
                "id" => $_SESSION['usuario_id'],
                "nome" => $_SESSION['nome'],
                "email" => $_SESSION['email'],
                "nivel_acesso" => $_SESSION['nivel_acesso']
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "autenticado" => false
        ]);
    }
}

// Função para fazer login
function fazerLogin()
{
    global $conexao;

    // Obter dados do corpo da requisição
    $dados = json_decode(file_get_contents("php://input"), true);

    // Verificar se os dados necessários foram fornecidos
    if (!isset($dados['email']) || !isset($dados['senha'])) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Email e senha são obrigatórios"
        ]);
        return;
    }

    try {
        // Buscar usuário pelo email
        $stmt = $conexao->prepare("SELECT id, nome, email, senha, nivel_acesso, ativo FROM usuarios WHERE email = ?");
        $stmt->execute([$dados['email']]);

        if ($stmt->rowCount() === 0) {
            http_response_code(401);
            echo json_encode([
                "status" => "error",
                "message" => "Email ou senha incorretos"
            ]);
            return;
        }

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar se o usuário está ativo
        if (!$usuario['ativo']) {
            http_response_code(401);
            echo json_encode([
                "status" => "error",
                "message" => "Usuário desativado. Entre em contato com o administrador."
            ]);
            return;
        }

        // Verificar a senha
        if (!password_verify($dados['senha'], $usuario['senha'])) {
            http_response_code(401);
            echo json_encode([
                "status" => "error",
                "message" => "Email ou senha incorretos"
            ]);
            return;
        }

        // Armazenar dados do usuário na sessão
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];

        // Retornar dados do usuário (exceto a senha)
        unset($usuario['senha']);
        echo json_encode([
            "status" => "success",
            "message" => "Login realizado com sucesso",
            "usuario" => $usuario
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao processar login: " . $e->getMessage()
        ]);
    }
}

// Função para fazer logout
function fazerLogout()
{
    // Destruir a sessão
    session_destroy();

    echo json_encode([
        "status" => "success",
        "message" => "Logout realizado com sucesso"
    ]);
}

// Função para listar usuários
function listarUsuarios()
{
    global $conexao;

    try {
        // Verificar se o usuário tem permissão (admin ou supervisor)
        verificarNivelAcesso(['admin', 'supervisor']);

        // Buscar todos os usuários (exceto a senha)
        $stmt = $conexao->query("SELECT id, nome, email, nivel_acesso, ativo, data_cadastro FROM usuarios ORDER BY nome");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "usuarios" => $usuarios
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao listar usuários: " . $e->getMessage()
        ]);
    }
}

// Função para cadastrar um novo usuário
function cadastrarUsuario()
{
    global $conexao;

    try {
        // Verificar se o usuário tem permissão (apenas admin)
        verificarNivelAcesso(['admin']);

        // Obter dados do corpo da requisição
        $dados = json_decode(file_get_contents("php://input"), true);

        // Verificar se os dados necessários foram fornecidos
        if (!isset($dados['nome']) || !isset($dados['email']) || !isset($dados['senha']) || !isset($dados['nivel_acesso'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Dados incompletos. Nome, email, senha e nível de acesso são obrigatórios."
            ]);
            return;
        }

        // Validar o nível de acesso
        $niveis_validos = ['admin', 'supervisor', 'operador'];
        if (!in_array($dados['nivel_acesso'], $niveis_validos)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Nível de acesso inválido. Valores permitidos: admin, supervisor, operador."
            ]);
            return;
        }

        // Verificar se o email já está em uso
        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$dados['email']]);

        if ($stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Este email já está em uso."
            ]);
            return;
        }

        // Criptografar a senha
        $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);

        // Inserir o novo usuário
        $stmt = $conexao->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)");
        $resultado = $stmt->execute([
            $dados['nome'],
            $dados['email'],
            $senha_hash,
            $dados['nivel_acesso']
        ]);

        if ($resultado) {
            $id = $conexao->lastInsertId();
            echo json_encode([
                "status" => "success",
                "message" => "Usuário cadastrado com sucesso",
                "id" => $id
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Erro ao cadastrar usuário"
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao cadastrar usuário: " . $e->getMessage()
        ]);
    }
}

// Função para atualizar um usuário existente
function atualizarUsuario()
{
    global $conexao;

    try {
        // Verificar se o usuário tem permissão (apenas admin)
        verificarNivelAcesso(['admin']);

        // Obter dados do corpo da requisição
        $dados = json_decode(file_get_contents("php://input"), true);

        // Verificar se o ID foi fornecido
        if (!isset($dados['id'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "ID do usuário é obrigatório"
            ]);
            return;
        }

        // Verificar se o usuário existe
        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$dados['id']]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Usuário não encontrado"
            ]);
            return;
        }

        // Construir a query de atualização
        $campos = [];
        $valores = [];

        if (isset($dados['nome'])) {
            $campos[] = "nome = ?";
            $valores[] = $dados['nome'];
        }

        if (isset($dados['email'])) {
            // Verificar se o email já está em uso por outro usuário
            $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$dados['email'], $dados['id']]);

            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Este email já está em uso por outro usuário."
                ]);
                return;
            }

            $campos[] = "email = ?";
            $valores[] = $dados['email'];
        }

        if (isset($dados['senha']) && !empty($dados['senha'])) {
            $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            $campos[] = "senha = ?";
            $valores[] = $senha_hash;
        }

        if (isset($dados['nivel_acesso'])) {
            $niveis_validos = ['admin', 'supervisor', 'operador'];
            if (!in_array($dados['nivel_acesso'], $niveis_validos)) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Nível de acesso inválido. Valores permitidos: admin, supervisor, operador."
                ]);
                return;
            }

            $campos[] = "nivel_acesso = ?";
            $valores[] = $dados['nivel_acesso'];
        }

        if (isset($dados['ativo'])) {
            $campos[] = "ativo = ?";
            $valores[] = $dados['ativo'] ? 1 : 0;
        }

        // Se não houver campos para atualizar
        if (empty($campos)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Nenhum dado fornecido para atualização"
            ]);
            return;
        }

        // Adicionar ID ao final dos valores
        $valores[] = $dados['id'];

        // Executar a atualização
        $sql = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        $resultado = $stmt->execute($valores);

        if ($resultado) {
            echo json_encode([
                "status" => "success",
                "message" => "Usuário atualizado com sucesso"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Erro ao atualizar usuário"
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao atualizar usuário: " . $e->getMessage()
        ]);
    }
}

// Função para excluir um usuário
function excluirUsuario()
{
    global $conexao;

    try {
        // Verificar se o usuário tem permissão (apenas admin)
        verificarNivelAcesso(['admin']);

        // Obter o ID do usuário a ser excluído
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "ID do usuário é obrigatório"
            ]);
            return;
        }

        // Verificar se o usuário existe
        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Usuário não encontrado"
            ]);
            return;
        }

        // Verificar se o usuário está tentando excluir a si mesmo
        if ($_SESSION['usuario_id'] == $id) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Não é possível excluir o próprio usuário"
            ]);
            return;
        }

        // Excluir o usuário
        $stmt = $conexao->prepare("DELETE FROM usuarios WHERE id = ?");
        $resultado = $stmt->execute([$id]);

        if ($resultado) {
            echo json_encode([
                "status" => "success",
                "message" => "Usuário excluído com sucesso"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Erro ao excluir usuário"
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao excluir usuário: " . $e->getMessage()
        ]);
    }
}
?>