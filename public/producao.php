<?php
// Configuração da conexão com o banco de dados
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistema_producao";

// Inicializa a conexão
$conexao = new mysqli($host, $usuario, $senha, $banco);

// Verifica se houve erro na conexão
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// Função para criar as tabelas se não existirem
function criarTabelas($conexao) {
    // Tabela de funcionários
    $sql_funcionarios = "CREATE TABLE IF NOT EXISTS funcionarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        cargo VARCHAR(50),
        setor VARCHAR(50),
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Tabela de produção
    $sql_producao = "CREATE TABLE IF NOT EXISTS producao (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data_registro DATE NOT NULL,
        id_funcionario INT NOT NULL,
        os VARCHAR(20) NOT NULL,
        material VARCHAR(100) NOT NULL,
        medida VARCHAR(50) NOT NULL,
        quantidade INT NOT NULL,
        duracao INT NOT NULL,
        pontos FLOAT NOT NULL,
        medalha VARCHAR(20),
        data_hora_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_funcionario) REFERENCES funcionarios(id)
    )";
    
    // Executa as queries
    $conexao->query($sql_funcionarios);
    $conexao->query($sql_producao);
    
    // Insere alguns funcionários de exemplo se a tabela estiver vazia
    $result = $conexao->query("SELECT COUNT(*) as total FROM funcionarios");
    $row = $result->fetch_assoc();
    
    if ($row['total'] == 0) {
        $conexao->query("INSERT INTO funcionarios (nome, cargo, setor) VALUES 
            ('João Silva', 'Operador', 'Produção'),
            ('Maria Santos', 'Operadora', 'Produção'),
            ('Carlos Oliveira', 'Supervisor', 'Produção'),
            ('Ana Pereira', 'Operadora', 'Produção')");
    }
}

// Cria as tabelas se não existirem
criarTabelas($conexao);

// Processa o formulário de registro de produção
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar'])) {
    $id_funcionario = $_POST['funcionario'];
    $os = $_POST['os'];
    $material = $_POST['material'];
    $medida = $_POST['medida'];
    $quantidade = $_POST['quantidade'];
    $duracao = $_POST['duracao']; // em minutos
    
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
        $mensagem = "Produção registrada com sucesso!";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Erro ao registrar produção: " . $stmt->error;
        $tipo_mensagem = "danger";
    }
    
    $stmt->close();
}

// Processa a exclusão de registro
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    
    $sql = "DELETE FROM producao WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $mensagem = "Registro excluído com sucesso!";
        $tipo_mensagem = "success";
    } else {
        $mensagem = "Erro ao excluir registro: " . $stmt->error;
        $tipo_mensagem = "danger";
    }
    
    $stmt->close();
}

// Busca os funcionários para o select
$funcionarios = $conexao->query("SELECT id, nome FROM funcionarios ORDER BY nome");

// Busca os registros de produção
$sql_producao = "SELECT p.*, f.nome as nome_funcionario 
                FROM producao p 
                JOIN funcionarios f ON p.id_funcionario = f.id 
                ORDER BY p.data_registro DESC, p.id DESC";
$registros = $conexao->query($sql_producao);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Registro de Produção com Timer">
    <meta name="theme-color" content="#198754">
    <title>Sistema de Produção</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #198754;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: #333;
        }
        
        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            font-weight: 600;
        }
        
        .timer-display {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin: 1rem 0;
            font-family: monospace;
        }
        
        .timer-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .timer-controls .btn {
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }
        
        .medal-gold {
            color: #FFD700;
        }
        
        .medal-silver {
            color: #C0C0C0;
        }
        
        .medal-bronze {
            color: #CD7F32;
        }
        
        .medal-icon {
            font-size: 1.5rem;
        }
        
        .progress-bar-container {
            margin: 1rem 0;
        }
        
        .progress {
            height: 25px;
            border-radius: var(--border-radius);
        }
        
        .progress-bar {
            transition: width 0.5s ease;
        }
        
        .table thead th {
            font-weight: 600;
            color: #495057;
        }
        
        .btn-acoes {
            padding: 3px 8px;
            font-size: 0.85rem;
            transition: var(--transition);
        }
        
        .btn-acoes:hover {
            transform: translateY(-2px);
        }
        
        @media (max-width: 767.98px) {
            .timer-display {
                font-size: 2rem;
            }
            
            .timer-controls {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .timer-controls .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-graph-up" aria-hidden="true"></i> Sistema de Produção
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Início</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="/producao.php">Produção</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/index.html">Não-Conformidades</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <h1>Registro de Produção</h1>
                    <a href="/" class="btn btn-outline-success mt-2 mt-md-0">
                        <i class="bi bi-house-door" aria-hidden="true"></i> Voltar para Home
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-stopwatch"></i> Timer de Produção</h5>
                    </div>
                    <div class="card-body">
                        <div class="timer-display" id="timer">00:00:00</div>
                        
                        <div class="timer-controls">
                            <button id="btnIniciar" class="btn btn-success">
                                <i class="bi bi-play-fill"></i> Iniciar
                            </button>
                            <button id="btnPausar" class="btn btn-warning" disabled>
                                <i class="bi bi-pause-fill"></i> Pausar
                            </button>
                            <button id="btnConcluir" class="btn btn-primary" disabled>
                                <i class="bi bi-check-lg"></i> Concluir
                            </button>
                            <button id="btnReiniciar" class="btn btn-danger" disabled>
                                <i class="bi bi-arrow-counterclockwise"></i> Reiniciar
                            </button>
                        </div>
                        
                        <div class="progress-bar-container">
                            <label>Progresso para medalha:</label>
                            <div class="progress">
                                <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Registro de Produção</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formProducao">
                            <div class="mb-3">
                                <label for="funcionario" class="form-label">Funcionário</label>
                                <select class="form-select" id="funcionario" name="funcionario" required>
                                    <option value="">Selecione o funcionário</option>
                                    <?php while ($funcionario = $funcionarios->fetch_assoc()): ?>
                                    <option value="<?php echo $funcionario['id']; ?>"><?php echo $funcionario['nome']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="os" class="form-label">Ordem de Serviço (OS)</label>
                                <input type="text" class="form-control" id="os" name="os" required>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="material" class="form-label">Material</label>
                                    <input type="text" class="form-control" id="material" name="material" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="medida" class="form-label">Medida</label>
                                    <input type="text" class="form-control" id="medida" name="medida" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="quantidade" class="form-label">Quantidade (peças)</label>
                                    <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="duracao" class="form-label">Duração (minutos)</label>
                                    <input type="number" class="form-control" id="duracao" name="duracao" readonly>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success" name="registrar" id="btnRegistrar" disabled>
                                    <i class="bi bi-save"></i> Registrar Produção
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Histórico de Produção</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Funcionário</th>
                                <th>OS</th>
                                <th>Material</th>
                                <th>Quantidade</th>
                                <th>Duração</th>
                                <th>Pontos</th>
                                <th>Medalha</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($registros->num_rows > 0): ?>
                                <?php while ($registro = $registros->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($registro['data_registro'])); ?></td>
                                        <td><?php echo $registro['nome_funcionario']; ?></td>
                                        <td><?php echo $registro['os']; ?></td>
                                        <td><?php echo $registro['material']; ?> (<?php echo $registro['medida']; ?>)</td>
                                        <td><?php echo number_format($registro['quantidade'], 0, ',', '.'); ?></td>
                                        <td><?php echo $registro['duracao']; ?> min</td>
                                        <td><?php echo number_format($registro['pontos'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($registro['medalha'] == 'Ouro'): ?>
                                                <i class="bi bi-award-fill medal-icon medal-gold" title="Medalha de Ouro"></i>
                                            <?php elseif ($registro['medalha'] == 'Prata'): ?>
                                                <i class="bi bi-award-fill medal-icon medal-silver" title="Medalha de Prata"></i>
                                            <?php elseif ($registro['medalha'] == 'Bronze'): ?>
                                                <i class="bi bi-award-fill medal-icon medal-bronze" title="Medalha de Bronze"></i>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?excluir=<?php echo $registro['id']; ?>" class="btn btn-outline-danger btn-acoes" onclick="return confirm('Tem certeza que deseja excluir este registro?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">Nenhum registro encontrado</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; 2023 Sistema de Produção</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Desenvolvido com <i class="bi bi-heart-fill text-danger"></i> pela Equipe de TI</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Timer de produção
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos do DOM
            const timerDisplay = document.getElementById('timer');
            const btnIniciar = document.getElementById('btnIniciar');
            const btnPausar = document.getElementById('btnPausar');
            const btnConcluir = document.getElementById('btnConcluir');
            const btnReiniciar = document.getElementById('btnReiniciar');
            const btnRegistrar = document.getElementById('btnRegistrar');
            const inputDuracao = document.getElementById('duracao');
            const inputQuantidade = document.getElementById('quantidade');
            const progressBar = document.getElementById('progressBar');
            
            // Variáveis do timer
            let startTime;
            let elapsedTime = 0;
            let timerInterval;
            let isPaused = false;
            let pauseTime;
            
            // Formata o tempo em HH:MM:SS
            function formatTime(timeInSeconds) {
                const hours = Math.floor(timeInSeconds / 3600);
                const minutes = Math.floor((timeInSeconds % 3600) / 60);
                const seconds = timeInSeconds % 60;
                
                return [
                    hours.toString().padStart(2, '0'),
                    minutes.toString().padStart(2, '0'),
                    seconds.toString().padStart(2, '0')
                ].join(':');
            }
            
            // Atualiza o display do timer
            function updateTimer() {
                const currentTime = Date.now();
                const deltaTime = Math.floor((currentTime - startTime) / 1000);
                const totalTime = elapsedTime + deltaTime;
                
                timerDisplay.textContent = formatTime(totalTime);
                
                // Atualiza o campo de duração em minutos
                const durationInMinutes = Math.ceil(totalTime / 60);
                inputDuracao.value = durationInMinutes;
                
                // Atualiza a barra de progresso se houver quantidade
                updateProgressBar();
            }
            
            // Atualiza a barra de progresso
            function updateProgressBar() {
                const quantidade = parseInt(inputQuantidade.value) || 0;
                let percentual = 0;
                
                if (quantidade >= 7000) {
                    percentual = 100;
                    progressBar.className = 'progress-bar bg-success';
                } else if (quantidade >= 6000) {
                    percentual = 85;
                    progressBar.className = 'progress-bar bg-info';
                } else if (quantidade >= 5000) {
                    percentual = 70;
                    progressBar.className = 'progress-bar bg-warning';
                } else {
                    percentual = (quantidade / 5000) * 70;
                    progressBar.className = 'progress-bar bg-danger';
                }
                
                progressBar.style.width = percentual + '%';
                progressBar.textContent = Math.round(percentual) + '%';
                progressBar.setAttribute('aria-valuenow', percentual);
            }
            
            // Inicia o timer
            btnIniciar.addEventListener('click', function() {
                if (isPaused) {
                    // Retoma de uma pausa
                    isPaused = false;
                    elapsedTime += Math.floor((pauseTime - startTime) / 1000);
                    startTime = Date.now();
                } else {
                    // Inicia do zero
                    startTime = Date.now();
                    elapsedTime = 0;
                }
                
                timerInterval = setInterval(updateTimer, 1000);
                
                // Atualiza os botões
                btnIniciar.disabled = true;
                btnPausar.disabled = false;
                btnConcluir.disabled = false;
                btnReiniciar.disabled = false;
            });
            
            // Pausa o timer
            btnPausar.addEventListener('click', function() {
                clearInterval(timerInterval);
                pauseTime = Date.now();
                isPaused = true;
                
                // Atualiza os botões
                btnIniciar.disabled = false;
                btnPausar.disabled = true;
                btnIniciar.innerHTML = '<i class="bi bi-play-fill"></i> Continuar';
            });
            
            // Conclui o timer
            btnConcluir.addEventListener('click', function() {
                clearInterval(timerInterval);
                
                // Calcula o tempo total
                const currentTime = Date.now();
                const deltaTime = Math.floor((currentTime - startTime) / 1000);
                const totalTime = elapsedTime + deltaTime;
                
                // Atualiza o display final
                timerDisplay.textContent = formatTime(totalTime);
                
                // Atualiza o campo de duração em minutos
                const durationInMinutes = Math.ceil(totalTime / 60);
                inputDuracao.value = durationInMinutes;
                
                // Atualiza os botões
                btnIniciar.disabled = true;
                btnPausar.disabled = true;
                btnConcluir.disabled = true;
                btnRegistrar.disabled = false;
            });
            
            // Reinicia o timer
            btnReiniciar.addEventListener('click', function() {
                clearInterval(timerInterval);
                timerDisplay.textContent = '00:00:00';
                elapsedTime = 0;
                isPaused = false;
                inputDuracao.value = '';
                
                // Atualiza os botões
                btnIniciar.disabled = false;
                btnIniciar.innerHTML = '<i class="bi bi-play-fill"></i> Iniciar';
                btnPausar.disabled = true;
                btnConcluir.disabled = true;
                btnReiniciar.disabled = true;
                btnRegistrar.disabled = true;
            });
            
            // Atualiza a barra de progresso quando a quantidade mudar
            inputQuantidade.addEventListener('input', updateProgressBar);
        });
    </script>
</body>

</html> 