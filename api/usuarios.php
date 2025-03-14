<?php
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

// Função para verificar o status de autenticação do usuário atual
function verificarStatusAutenticacao() {
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
function fazerLogin() {
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
    
    // Escapar os dados para evitar SQL Injection
    $email = $conexao->real_escape_string($dados['email']);
    
    // Buscar usuário pelo email
    $sql = "SELECT id, nome, email, senha, nivel_acesso, ativo FROM usuarios WHERE email = '$email'";
    $resultado = $conexao->query($sql);
    
    if ($resultado->num_rows === 0) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Email ou senha incorretos"
        ]);
        return;
    }
    
    $usuario = $resultado->fetch_assoc();
    
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
}

// Função para fazer logout
function fazerLogout() {
    // Destruir a sessão
    session_destroy();
    
    echo json_encode([
        "status" => "success",
        "message" => "Logout realizado com sucesso"
    ]);
}

// Função para listar usuários
function listarUsuarios() {
    global $conexao;
    
    // Verificar se o usuário tem permissão (admin ou supervisor)
    verificarNivelAcesso(['admin', 'supervisor']);
    
    // Buscar todos os usuários (exceto a senha)
    $sql = "SELECT id, nome, email, nivel_acesso, ativo, data_cadastro FROM usuarios ORDER BY nome";
    $resultado = $conexao->query($sql);
    
    $usuarios = [];
    while ($usuario = $resultado->fetch_assoc()) {
        $usuarios[] = $usuario;
    }
    
    echo json_encode([
        "status" => "success",
        "usuarios" => $usuarios
    ]);
}

// Função para cadastrar um novo usuário
function cadastrarUsuario() {
    global $conexao;
    
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
    
    // Escapar os dados para evitar SQL Injection
    $nome = $conexao->real_escape_string($dados['nome']);
    $email = $conexao->real_escape_string($dados['email']);
    $nivel_acesso = $conexao->real_escape_string($dados['nivel_acesso']);
    
    // Verificar se o email já está em uso
    $sql = "SELECT id FROM usuarios WHERE email = '$email'";
    $resultado = $conexao->query($sql);
    
    if ($resultado->num_rows > 0) {
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
    $sql = "INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES ('$nome', '$email', '$senha_hash', '$nivel_acesso')";
    
    if ($conexao->query($sql)) {
        $id = $conexao->insert_id;
        echo json_encode([
            "status" => "success",
            "message" => "Usuário cadastrado com sucesso",
            "id" => $id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao cadastrar usuário: " . $conexao->error
        ]);
    }
}

// Função para atualizar um usuário existente
function atualizarUsuario() {
    global $conexao;
    
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
    
    // Escapar os dados para evitar SQL Injection
    $id = $conexao->real_escape_string($dados['id']);
    
    // Verificar se o usuário existe
    $sql = "SELECT id FROM usuarios WHERE id = $id";
    $resultado = $conexao->query($sql);
    
    if ($resultado->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Usuário não encontrado"
        ]);
        return;
    }
    
    // Construir a query de atualização
    $campos = [];
    
    if (isset($dados['nome'])) {
        $nome = $conexao->real_escape_string($dados['nome']);
        $campos[] = "nome = '$nome'";
    }
    
    if (isset($dados['email'])) {
        $email = $conexao->real_escape_string($dados['email']);
        
        // Verificar se o email já está em uso por outro usuário
        $sql = "SELECT id FROM usuarios WHERE email = '$email' AND id != $id";
        $resultado = $conexao->query($sql);
        
        if ($resultado->num_rows > 0) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Este email já está em uso por outro usuário."
            ]);
            return;
        }
        
        $campos[] = "email = '$email'";
    }
    
    if (isset($dados['senha']) && !empty($dados['senha'])) {
        $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);
        $campos[] = "senha = '$senha_hash'";
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
        
        $nivel_acesso = $conexao->real_escape_string($dados['nivel_acesso']);
        $campos[] = "nivel_acesso = '$nivel_acesso'";
    }
    
    if (isset($dados['ativo'])) {
        $ativo = $dados['ativo'] ? 1 : 0;
        $campos[] = "ativo = $ativo";
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
    
    // Executar a atualização
    $sql = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id = $id";
    
    if ($conexao->query($sql)) {
        echo json_encode([
            "status" => "success",
            "message" => "Usuário atualizado com sucesso"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao atualizar usuário: " . $conexao->error
        ]);
    }
}

// Função para excluir um usuário
function excluirUsuario() {
    global $conexao;
    
    // Verificar se o usuário tem permissão (apenas admin)
    verificarNivelAcesso(['admin']);
    
    // Obter o ID do usuário a ser excluído
    $id = isset($_GET['id']) ? $conexao->real_escape_string($_GET['id']) : null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "ID do usuário é obrigatório"
        ]);
        return;
    }
    
    // Verificar se o usuário existe
    $sql = "SELECT id FROM usuarios WHERE id = $id";
    $resultado = $conexao->query($sql);
    
    if ($resultado->num_rows === 0) {
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
    $sql = "DELETE FROM usuarios WHERE id = $id";
    
    if ($conexao->query($sql)) {
        echo json_encode([
            "status" => "success",
            "message" => "Usuário excluído com sucesso"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao excluir usuário: " . $conexao->error
        ]);
    }
}
?> 