<?php
session_start();
include 'conexao.php';

// Verifica se o professor está logado
if (!isset($_SESSION['id_pessoa'])) {
    echo "Professor não logado!";
    exit;
}

$id_pessoa = $_SESSION['id_pessoa'];
$mensagemErro = "";
$mensagemSucesso = "";
$nome_usuario = "";

// Buscar nome de usuário atual
$sqlUsuario = "SELECT nome_usuario FROM usuario WHERE id_pessoa = ?";
$stmtUsuario = $conn->prepare($sqlUsuario);

if ($stmtUsuario) {
    $stmtUsuario->bind_param("i", $id_pessoa);
    $stmtUsuario->execute();
    $stmtUsuario->bind_result($nome_usuario);
    $stmtUsuario->fetch();
    $stmtUsuario->close();
} else {
    $mensagemErro = "Erro ao buscar nome de usuário: " . $conn->error;
}

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nova_senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');

    if (empty($nova_senha) || empty($confirmar_senha)) {
        $mensagemErro = "Todos os campos são obrigatórios!";
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagemErro = "As senhas não coincidem!";
    } else {
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        $sqlUpdate = "UPDATE usuario SET senha = ? WHERE id_pessoa = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);

        if ($stmtUpdate === false) {
            $mensagemErro = "Erro na preparação da consulta: " . $conn->error;
        } else {
            $stmtUpdate->bind_param("si", $nova_senha_hash, $id_pessoa);

            if ($stmtUpdate->execute()) {
                $mensagemSucesso = "Senha atualizada com sucesso!";
            } 

            $stmtUpdate->close();
        }
    }
}

// Buscar dados do professor
$sql_professor = "SELECT 
                    p.id_pessoa, p.nome, p.sexo, p.num_bi, p.data_nasc, p.morada, 
                    p.nome_pai, p.nome_mae, p.naturalidade, p.tel_1, p.tel_2, p.email, p.foto, 
                    pr.num_agente, pr.formacao, pr.ativo
                 FROM pessoa p
                 INNER JOIN professor pr ON pr.id_pessoa = p.id_pessoa
                 WHERE p.id_pessoa = ?";
$stmt_professor = $conn->prepare($sql_professor);
if (!$stmt_professor) {
    die('Erro ao preparar consulta: ' . $conn->error);
}
$stmt_professor->bind_param("i", $id_pessoa);
$stmt_professor->execute();
$result_professor = $stmt_professor->get_result();

if ($result_professor->num_rows > 0) {
    $professor = $result_professor->fetch_assoc();
    $mensagemBoasVindas = "Bem-vindo(a), " . $professor['nome'] . "!";
} else {
    echo "Nenhum dado encontrado para este professor!";
    exit;
}

$conn->close();

// Cálculo do ano letivo
$mes = date('m');
$ano_atual = date('Y');
$ano_letivo = ($mes >= 9) ? "$ano_atual/" . ($ano_atual + 1) : ($ano_atual - 1) . "/$ano_atual";

// Função utilitária para verificar a página atual
function is_active($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>30 de Setembro - Prof</title>
    <!-- Bootstrap -->
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    
     <style>
        :root {
            --primary-color:rgb(59, 127, 228);
            --secondary-color:rgb(59, 127, 228);
            --accent-color: #f3f6fa;
            --text-color: #333;
            --light-text: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 80px;
            color: var(--text-color);
        }
        
        /* Header styling */
         .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            z-index: 1030;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 0.5rem;
            font-size: 1.8rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-name {
            font-size: 0.9rem;
            margin: 0;
        }
        
        .user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        /* Sidebar styling */
        .sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            height: calc(100vh - 70px);
            width: 250px;
            background-color: white;
            box-shadow: var(--box-shadow);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1020;
            overflow-y: auto;
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .sidebar .nav-link {
            color: var(--text-color);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
            color: var(--secondary-color);
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        
        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        
        /* Main content styling */
        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
            padding: 20px;
        }
        
        .main-content.sidebar-active {
            margin-left: 250px;
        }
        
        .professor-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .professor-card-header {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        
        .professor-card-header h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        
        .professor-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: var(--box-shadow);
            margin: 0 auto;
        }
        
        .professor-card-body {
            padding: 30px;
        }
        
        .info-group {
            margin-bottom: 25px;
        }
        
        .info-group h3 {
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 12px;
        }
        
        .info-label {
            width: 40%;
            font-weight: 600;
            color: var(--light-text);
        }
        
        .info-value {
            width: 60%;
        }
        
        /* Menu toggle button */
        .menu-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1040;
            width: 30px;
            height: 30px;
            background: transparent;
            border: none;
            color: white;
        }
        
        /* Footer styling */
        footer {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 0.9rem;
        }
        
        /* Responsive adjustments */
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 250px;
            }
            
            .menu-toggle {
                display: none;
            }
        }
        
        @media (max-width: 991.98px) {
            .menu-toggle {
                display: block;
            }
        }
    
        #icon-30{
          width: 60px;
          height: 50px;
          margin-right: 2%;
        }

        /* Estilos para o alerta de sucesso */
        .alert-success {
            border-left: 4px solid #28a745;
        }

        /* Estilos para os campos de telefone editáveis */
        .editable-field {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            margin-top: 5px;
        }

        .editable-field:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 127, 228, 0.25);
        }

        
            .navbar-brand{
    color: white !important; /* Garantir que o texto dos links seja branco */
}
    </style>
</head>
<body>

    <!-- Header -->
      <!-- Header -->
    <header class="header">
        <button class="menu-toggle"><i class="bi bi-list fs-4"></i></button>
        
        <div class="header-content">
             <div class="logo">
            <img src="icon.png" alt="icon" id="icon-30">
                 <a class="navbar-brand" href="#">INSTITUTO POLITÉCNICO 30 DE SETEMBRO</a>
            </div>
            
            <div class="user-info">
                <p class="user-name"><?= $mensagemBoasVindas ?></p>
                <?php
                $caminhoFoto = 'uploads/' . $professor['foto'];
                if (!empty($professor['foto']) && file_exists($caminhoFoto)) {
                    echo "<img src='$caminhoFoto' alt='Foto do professor' class='user-photo'>";
                } else {
                    echo "<img src='uploads/default.jpg' alt='Foto padrão' class='user-photo'>";
                }
                ?>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-header">
            <h5>Painel do Professor</h5>
        </div>
        
        <nav class="nav flex-column">
            <a href="professor.php" class="nav-link"><i class="bi bi-house"></i>Página Inicial</a>
            <a href="lançamento_notas_professor.php" class="nav-link"><i class="bi bi-journal-bookmark-fill"></i>Lançamento de Notas</a>
            <a href="distribuição_disc.php" class="nav-link"><i class="bi bi-book"></i>Minhas Disciplinas</a>
            <a href="Comunicação_prof.php" class="nav-link"><i class="bi bi-calendar-week"></i>Calendário Acadêmico</a>
            <a href="admin_reclamacoes.php" class="nav-link"><i class="bi bi-chat-dots"></i>Reclamações</a>
            <a href="Altera_senha_professor.php" class="nav-link"><i class="bi bi-key menu-icon"></i>Segurança</a>

        </nav>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="btn btn-outline-secondary w-100"><i class="bi bi-box-arrow-left me-2"></i>Encerrar Sessão</a>
        </div>
    </aside>
    <!-- Main Content -->
    <main class="main-content" id="main-content">
        <div class="container-fluid">
            
            <!-- Mensagens de Feedback -->
            <?php if (isset($mensagemSucesso)): ?>
                <div class="alert alert-success alert-dismissible fade show fade-in" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= $mensagemSucesso ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

           
            

            <!-- Card de Alteração de Senha -->
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-key me-2"></i>
                                Alterar Senha de Acesso
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="alterarSenhaForm">
                                <!-- Campo Usuário (readonly) -->
                                <div class="mb-3">
                                    <label for="usuario" class="form-label">Nome de Usuário</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <input type="text" class="form-control" id="usuario" value="<?= htmlspecialchars($nome_usuario) ?>" readonly>
                                    </div>
                                </div>

                                <!-- Nova Senha -->
                                <div class="mb-3">
                                    <label for="senha" class="form-label">Nova Senha</label>
                                    <div class="input-group form-password-wrapper">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="senha" name="senha" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleSenha">
                                            <i class="bi bi-eye" id="eyeIcon1"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength mt-2" id="passwordStrength"></div>
                                    <small class="form-text text-muted">
                                        A senha deve ter pelo menos 8 caracteres, incluindo letras e números.
                                    </small>
                                </div>

                                <!-- Confirmar Senha -->
                                <div class="mb-4">
                                    <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock-fill"></i>
                                        </span>
                                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmarSenha">
                                            <i class="bi bi-eye" id="eyeIcon2"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback" id="confirmFeedback"></div>
                                </div>
                                  <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                   
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-check-lg"></i> Alterar Senha
                                    </button>
                                </div>
                                 <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Recomendações de Segurança</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Use senhas únicas para cada serviço
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Evite informações pessoais na senha, como datas de nascimento
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Troque suas senhas periodicamente
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                                    Nunca compartilhe suas credenciais com terceiros
                                </li>
                            </ul>
                        </div> 

                               
                             
                            </form>
                             
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle Sidebar
        document.getElementById('menu-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
        });

        // Toggle Password Visibility
        document.getElementById('toggleSenha').addEventListener('click', function() {
            const senha = document.getElementById('senha');
            const eyeIcon = document.getElementById('eyeIcon1');
            
            if (senha.type === 'password') {
                senha.type = 'text';
                eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                senha.type = 'password';
                eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });

        document.getElementById('toggleConfirmarSenha').addEventListener('click', function() {
            const confirmarSenha = document.getElementById('confirmar_senha');
            const eyeIcon = document.getElementById('eyeIcon2');
            
            if (confirmarSenha.type === 'password') {
                confirmarSenha.type = 'text';
                eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                confirmarSenha.type = 'password';
                eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });

        // Password Strength Indicator
        document.getElementById('senha').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            
            let strength = 0;
            
            // Check password criteria
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update strength bar
            strengthBar.className = 'password-strength';
            
            if (strength === 0) {
                strengthBar.style.display = 'none';
            } else {
                strengthBar.style.display = 'block';
                if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                } else if (strength === 3) {
                    strengthBar.classList.add('strength-fair');
                } else if (strength === 4) {
                    strengthBar.classList.add('strength-good');
                } else {
                    strengthBar.classList.add('strength-strong');
                }
            }
        });

        // Password Confirmation Validation
        document.getElementById('confirmar_senha').addEventListener('input', function() {
            const senha = document.getElementById('senha').value;
            const confirmarSenha = this.value;
            const feedback = document.getElementById('confirmFeedback');
            
            if (confirmarSenha && senha !== confirmarSenha) {
                this.classList.add('is-invalid');
                feedback.textContent = 'As senhas não coincidem.';
            } else {
                this.classList.remove('is-invalid');
                feedback.textContent = '';
            }
        });

        // Form Validation
        document.getElementById('alterarSenhaForm').addEventListener('submit', function(e) {
            const senha = document.getElementById('senha').value;
            const confirmarSenha = document.getElementById('confirmar_senha').value;
            
            // Basic validation
            if (senha.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres.');
                return false;
            }
            
            if (senha !== confirmarSenha) {
                e.preventDefault();
                alert('As senhas não coincidem.');
                return false;
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processando...';
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>

</body>
</html>