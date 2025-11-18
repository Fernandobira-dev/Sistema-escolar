<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_pessoa'])) {
    echo "Usuário não logado!";
    exit;
}

$id_pessoa = $_SESSION['id_pessoa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Atualiza os números de telefone no banco de dados
    $tel_1 = $_POST['tel_1'];
    $tel_2 = $_POST['tel_2'];

    $update_sql = "UPDATE pessoa SET tel_1 = ?, tel_2 = ? WHERE id_pessoa = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $tel_1, $tel_2, $id_pessoa);

    if ($stmt->execute()) {
        $mensagem = "Dados atualizados com sucesso!";
    } else {
        $mensagem = "Erro ao atualizar dados!";
    }
    $stmt->close();
}

// Verifica o tipo de usuário
$sql_usuario = "SELECT tipo_usuario FROM usuario WHERE id_pessoa = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
if (!$stmt_usuario) {
    die('Erro na consulta de tipo de usuário: ' . $conn->error);
}
$stmt_usuario->bind_param("i", $id_pessoa);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if ($result_usuario->num_rows > 0) {
    $usuario = $result_usuario->fetch_assoc();
    if (strtolower($usuario['tipo_usuario']) !== 'professor') {
        echo "Acesso restrito. Apenas professores podem acessar esta página.";
        exit;
    }
} else {
    echo "Erro ao verificar tipo de usuário.";
    exit;
}

// Busca dados do professor
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

$stmt_usuario->close();
$stmt_professor->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Professor</title>
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link href="../bootstrap-5.0.2/css/bootstrap.min.css" rel="stylesheet">
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

            .navbar-brand{
    color: white !important; /* Garantir que o texto dos links seja branco */
}
    </style>
</head>
<body>
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
    <main class="main-content" id="mainContent">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="professor-card">
                        <div class="professor-card-header">
                            <h2>Perfil do Professor</h2>
                            <?php
                            if (!empty($professor['foto']) && file_exists($caminhoFoto)) {
                                echo "<img src='$caminhoFoto' alt='Foto do professor' class='professor-photo mb-3'>";
                            } else {
                                echo "<img src='uploads/default.jpg' alt='Foto padrão' class='professor-photo mb-3'>";
                            }
                            ?>
                            <h4><?= $professor['nome'] ?></h4>
                            <p class="text-muted"><?= $professor['formacao'] ?></p>
                        </div>
                        
                        <div class="professor-card-body">
                            <div class="info-group">
                                <h3><i class="bi bi-person me-2"></i>Informações Pessoais</h3>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">Nome Completo:</div>
                                            <div class="info-value"><?= $professor['nome'] ?></div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">Sexo:</div>
                                            <div class="info-value"><?= $professor['sexo'] ?></div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">Data de Nascimento:</div>
                                            <div class="info-value"><?= date("d/m/Y", strtotime($professor['data_nasc'])) ?></div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">Naturalidade:</div>
                                            <div class="info-value"><?= $professor['naturalidade'] ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">Número de BI:</div>
                                            <div class="info-value"><?= $professor['num_bi'] ?></div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">Nome do Pai:</div>
                                            <div class="info-value"><?= $professor['nome_pai'] ?></div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">Nome da Mãe:</div>
                                            <div class="info-value"><?= $professor['nome_mae'] ?></div>
                                        </div>
                                        
                                        <div class="info-row">
                                            <div class="info-label">Morada:</div>
                                            <div class="info-value"><?= $professor['morada'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-group">
                                <h3><i class="bi bi-telephone me-2"></i>Contatos</h3>
                                
                                <div class="row info-row">
                    <div class="col-md-6 info-item">
                        <div class="info-label">Telefone Principal</div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="tel_1" value="<?= $professor['tel_1'] ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6 info-item">
                        <div class="info-label">Telefone Alternativo</div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="tel_2" value="<?= $professor['tel_2'] ?>" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar Alterações
                    </button>
                </div>
                                <div class="info-row">
                                    <div class="info-label">Email:</div>
                                    <div class="info-value"><?= $professor['email'] ?></div>
                                </div>
                            </div>
                    
                </div>
                            
                            <div class="info-group">
                                <h3><i class="bi bi-briefcase me-2"></i>Informações Profissionais</h3>
                                
                                <div class="info-row">
                                    <div class="info-label">Número de Agente:</div>
                                    <div class="info-value"><?= $professor['num_agente'] ?></div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">Formação Acadêmica:</div>
                                    <div class="info-value"><?= $professor['formacao'] ?></div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">Estado:</div>
                                    <div class="info-value">
                                        <?php if($professor['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

  

    <script>
        // Toggle sidebar
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.getElementById('sidebarMenu').classList.toggle('active');
            document.getElementById('mainContent').classList.toggle('sidebar-active');
        });
        
        // Auto-toggle for mobile on page load
        window.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth >= 992) {
                document.getElementById('sidebarMenu').classList.add('active');
                document.getElementById('mainContent').classList.add('sidebar-active');
            }
        });
    </script>
</body>
</html>