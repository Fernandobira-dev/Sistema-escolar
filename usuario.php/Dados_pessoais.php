<?php
session_start();
include 'conexao.php';

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

$sql = "SELECT p.id_pessoa, p.foto, p.nome, p.sexo, p.num_bi, p.data_nasc, p.morada, 
        p.nome_pai, p.nome_mae, p.naturalidade, p.tel_1, p.tel_2, p.email
        FROM pessoa p
        INNER JOIN aluno a ON a.id_pessoa = p.id_pessoa
        WHERE a.id_pessoa = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_pessoa);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se encontrou algum dado
if ($result->num_rows > 0) {
    $aluno = $result->fetch_assoc();
    $nome_aluno = $aluno['nome'];
    $primeiro_nome = explode(' ', $nome_aluno)[0];
    $mensagemBoasVindas = "Bem-vindo(a), " . $primeiro_nome;
} else {
    header("Location: login.php?erro=dados");
    exit;
}

$stmt->close();
$conn->close();



// Cálculo do ano letivo
$mes = date('m');
$ano_atual = date('Y');
if ($mes >= 9) {
    $ano_letivo = $ano_atual . '/' . ($ano_atual + 1);
} else {
    $ano_letivo = ($ano_atual - 1) . '/' . $ano_atual;
}

// Função para verificar a página atual
function is_active($page) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page == $page) ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>30 de Setembro - <?= $primeiro_nome ?></title>
    <!-- Bootstrap -->
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color:rgb(59, 127, 228);
            --secondary-color:rgb(59, 139, 243);
            --accent-color: #3b82f6;
            --text-color: #1e293b;
            --light-color: #f8fafc;
            --sidebar-width: 280px;
            --header-height: 70px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: var(--text-color);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
           
        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
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
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .user-info:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
       .user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .user-name {
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .ano-letivo-badge {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            z-index: 1020;
            overflow-y: auto;
            transform: translateX(-100%);
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .menu-btn {
            border: none;
            background: transparent;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 1rem;
        }
        
        .nav-item {
            margin-bottom: 0.3rem;
        }
        
        .nav-link {
            color: var(--text-color);
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border-radius: 8px;
            margin: 0 0.8rem;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(37, 99, 235, 0.08);
            color: var(--primary-color);
        }
        
        .nav-link i {
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-divider {
            margin: 1rem 0;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .sidebar-header {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            padding: 1.2rem 1.5rem 0.5rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 0;
            margin-top: var(--header-height);
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .main-content.sidebar-active {
            margin-left: var(--sidebar-width);
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .page-title {
            font-weight: 600;
            font-size: 1.75rem;
            margin-bottom: 2rem;
            color: var(--text-color);
        }

        /* Card */
        .profile-card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-light);
        }

        .profile-info h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .profile-info p {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-bottom: 0.25rem;
        }

        
        .edit-photo-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .edit-photo-btn:hover {
            background-color: var(--secondary-color);
        }
        
        
        .profile-info h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .profile-info p {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .info-item {
            margin-bottom: 1.2rem;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            font-size: 1rem;
            font-weight: 500;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.8rem 0;
        }
        
        .dropdown-item {
            padding: 0.6rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: rgba(37, 99, 235, 0.08);
            color: var(--primary-color);
        }
        
        .dropdown-item i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
            border-radius: 12px;
        }
        
        /* Responsive */
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: var(--sidebar-width);
            }
            
            .menu-btn {
                display: none;
            }
        }
        
        @media (max-width: 991px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-name, .ano-letivo-badge {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1.5rem 1rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
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

  
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.8rem 0;
        }
        
        .dropdown-item {
            padding: 0.6rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: rgba(37, 99, 235, 0.08);
            color: var(--primary-color);
        }
        
        .dropdown-item i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }


    </style>
</head>
<body>


    <!-- Header -->
    <header class="header">
        <button class="menu-btn" id="menu-toggle">
            <i class="bi bi-list"></i>
        </button>
        
        <div class="header-content">
            <div class="logo">
            <img src="icon.png" alt="icon" id="icon-30">
                 <a class="navbar-brand" href="#">INSTITUTO POLITÉCNICO 30 DE SETEMBRO</a>
            </div>
            
            <div class="header-right">
               
                
                <div class="dropdown">
                    <div class="user-info" data-bs-toggle="dropdown">
                        <?php if (!empty($aluno['foto'])): ?>
                            <img src="<?= $aluno['foto'] ?>" alt="Foto do aluno" class="user-photo">
                        <?php else: ?>
                            <div class="user-photo d-flex align-items-center justify-content-center bg-primary">
                                <i class="bi bi-person-fill text-white"></i>
                            </div>
                        <?php endif; ?>
                        <div class="user-name"><?= $primeiro_nome ?></div>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="Dados_pessoais.php"><i class="bi bi-person"></i> Meu Perfil</a></li>
                        <li><a class="dropdown-item" href="altera_senha.php"><i class="bi bi-key"></i> Alterar Senha</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="px-4 py-3 d-lg-none">
            <div class="d-flex align-items-center">
                <?php if (!empty($aluno['foto'])): ?>
                    <img src="<?= $aluno['foto'] ?>" alt="Foto do aluno" class="user-photo me-2">
                <?php else: ?>
                    <div class="user-photo d-flex align-items-center justify-content-center bg-primary me-2">
                        <i class="bi bi-person-fill text-white"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="fw-semibold"><?= $nome_aluno ?></div>
                    <small class="text-muted"><?= $aluno['email'] ?></small>
                </div>
            </div>
        </div>
        
        <div class="sidebar-divider d-lg-none"></div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
            <div class="sidebar-header">Menu Principal</div>
                <a href="Estudante.php" class="nav-link <?= is_active('Estudante.php') ?>">
                    <i class="bi bi-house-door"></i>
                    <span>Início</span>
                </a>
                <div class="sidebar-header">Ária do Estudante</div>

            </li>
            <li class="nav-item">
                <a href="Dados_pessoais.php" class="nav-link <?= is_active('Dados_pessoais.php') ?>">
                <i class="bi bi-person-badge menu-icon"></i>
                <span>Meu Perfil</span>
                </a>
                <ul class="nav flex-column">
            <li class="nav-item">
                <a href="altera_senha.php" class="nav-link <?= is_active('altera_senha.php') ?>">
                <i class="bi bi-key menu-icon"></i>
                <span>Segurança</span>
                </a>
            </li>
        </ul>
            </li>
            <li class="nav-item">
                <a href="reclamacao.php" class="nav-link <?= is_active('reclamacao.php') ?>">
                <i class="bi bi-chat-left-text menu-icon"></i>
                <span>Reclamações</span>
                </a>
            </li>
            
        
        <div class="sidebar-divider"></div>
        
        <div class="sidebar-header">Secretária</div>
        <ul class="nav flex-column">
         
            <li class="nav-item">
                <a href="visualizar_notas.php" class="nav-link <?= is_active('visualizar_notas.php') ?>">
                <i class="bi bi-bar-chart menu-icon"></i>
                <span>Minhas Notas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="atribuiçao.php" class="nav-link <?= is_active('atribuiçao.php') ?>">
                <i class="bi bi-journal-text menu-icon"></i>
                <span>Disciplinas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="ver_matricula.php" class="nav-link <?= is_active('ver_matricula.php') ?>">
                <i class="bi bi-calendar-event menu-icon"></i>
                <span>Histórico Acadêmico</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Comunicação_aluno.php" class="nav-link <?= is_active('Comunicação_aluno.php') ?>">
                <i class="bi bi-card-checklist menu-icon"></i>
                <span>Calendário Acadêmico</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-divider"></div>
        
        
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                <i class="bi bi-box-arrow-right menu-icon"></i>
                <span>Sair</span>
                </a>
            </li>
        </ul>
    </div>


    <!-- Main Content -->
    <div class="d-flex justify-content-center align-items-center" style="min-height: 100vh; margin-left: 290px;">
    <!-- Conteúdo específico da página será inserido aqui -->
    <!-- Main Content -->
    <div class="container" style="max-width: 1000x;">
        <h1 class="page-title">Dados Pessoais</h1>
        
        <?php if (isset($mensagem)) : ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-card">
            <div class="profile-header">
                <img src="<?= $aluno['foto']; ?>" alt="Foto do aluno" class="profile-photo">
                <div class="profile-info">
                    <h2><?= $aluno['nome'] ?></h2>
                    <p><i class="bi bi-envelope me-2"></i><?= $aluno['email'] ?></p>
                    <p><i class="bi bi-telephone me-2"></i><?= $aluno['tel_1'] ?></p>
                </div>
            </div>
            
            <form action="" method="POST">
                <h3 class="section-title"><i class="bi bi-person-vcard"></i>Informações Pessoais</h3>
                <div class="row info-row">
                    <div class="col-md-6 info-item">
                        <div class="info-label">Nome Completo</div>
                        <div class="info-value"><?= $aluno['nome'] ?></div>
                    </div>
                    <div class="col-md-6 info-item">
                        <div class="info-label">Sexo</div>
                        <div class="info-value"><?= $aluno['sexo'] ?></div>
                    </div>
                    <div class="col-md-6 info-item">
                        <div class="info-label">Número do BI</div>
                        <div class="info-value"><?= $aluno['num_bi'] ?></div>
                    </div>
                    <div class="col-md-6 info-item">
                        <div class="info-label">Data de Nascimento</div>
                        <div class="info-value"><?= $aluno['data_nasc'] ?></div>
                    </div>
                </div>
                
                <h3 class="section-title"><i class="bi bi-geo-alt"></i>Localização</h3>
                <div class="row info-row">
                    <div class="col-md-6 info-item">
                        <div class="info-label">Morada</div>
                        <div class="info-value"><?= $aluno['morada'] ?></div>
                    </div>
                    <div class="col-md-6 info-item">
                        <div class="info-label">Naturalidade</div>
                        <div class="info-value"><?= $aluno['naturalidade'] ?></div>
                    </div>
                </div>
                
                <h3 class="section-title"><i class="bi bi-people"></i>Filiação</h3>
                <div class="row info-row">
                    <div class="col-md-6 info-item">
                        <div class="info-label">Nome do Pai</div>
                        <div class="info-value"><?= $aluno['nome_pai'] ?></div>
                    </div>
                    <div class="col-md-6 info-item">
                        <div class="info-label">Nome da Mãe</div>
                        <div class="info-value"><?= $aluno['nome_mae'] ?></div>
                    </div>
                </div>
                
                <h3 class="section-title"><i class="bi bi-telephone"></i>Contatos</h3>
                <div class="row info-row">
                    <div class="col-md-6 info-item">
                        <div class="info-label">Telefone 1</div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="tel_1" value="<?= $aluno['tel_1'] ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6 info-item">
                        <div class="info-label">Telefone 2</div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="tel_2" value="<?= $aluno['tel_2'] ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6 info-item">
                        <div class="info-label"><i class="bi bi-envelope me-2"></i>Email</div>
                        <div class="info-value"><?= $aluno['email'] ?></div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- Bootstrap JS Bundle with Popper -->
    <script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>

    <script src="bootstrap-5.0.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle Sidebar
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
        });
        
        // Handle responsive sidebar for desktop/mobile
        function checkScreenSize() {
            if (window.innerWidth >= 992) {
                sidebar.classList.add('active');
                mainContent.classList.add('sidebar-active');
            } else {
                sidebar.classList.remove('active');
                mainContent.classList.remove('sidebar-active');
            }
        }
        
        // Initial check and add resize listener
        window.addEventListener('resize', checkScreenSize);
        checkScreenSize();
    </script>
</body>
</html>











