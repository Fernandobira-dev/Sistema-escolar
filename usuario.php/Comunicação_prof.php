<?php
session_start();
include 'conexao.php';

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

if (!isset($_SESSION['id_pessoa'])) {
    header("Location: login.php");
    exit;
}

$query = "SELECT * FROM comunicados ORDER BY data_evento DESC, id DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Acadêmico - Comunicados</title>
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <style>
    

        :root {
            --primary-color:rgb(59, 127, 228);
            --secondary-color:rgb(59, 127, 228);
            --tertiary-color:rgb(28, 89, 223);
            --accent-color: #f39c12;
            --light-gray: #f8f9fa;
            --text-dark: #343a40;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f1c40f;
        }
        
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f7fa;
            color: var(--text-dark);
            padding-top: 70px;
        }
        
        /* Header styles */
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

        /* Main Content */
      

       
        /* Card Styles */
        .card {
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            border: none;
            box-shadow: var(--shadow-md);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Toggle Button */
        .menu-toggle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            z-index: 1050;
        }

        .menu-toggle:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin-bottom: 1.5rem;
        }

        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
        }

        .custom-table thead th {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            font-weight: 500;
            text-align: left;
            border: none;
            position: relative;
        }

        .custom-table thead th:first-child {
            border-top-left-radius: 0.5rem;
        }

        .custom-table thead th:last-child {
            border-top-right-radius: 0.5rem;
        }

        .custom-table tbody tr {
            transition: var(--transition);
        }

        .custom-table tbody tr:hover {
            background-color: rgba(37, 99, 235, 0.05);
        }

        .custom-table tbody td {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .custom-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 0.5rem;
        }

        .custom-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 0.5rem;
        }

        /* Course Info Card */
        .course-info {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }

        .course-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .course-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }

        .course-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .course-detail-item {
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
        }

        .course-detail-icon {
            margin-right: 0.5rem;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background-color: rgba(37, 99, 235, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        /* Breadcrumb */
        .breadcrumb-container {
            margin-bottom: 1.5rem;
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            text-decoration: underline;
        }

        .breadcrumb-item.active {
            color: var(--text-light);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-sm);
        }

        .empty-icon {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-description {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        /* Responsive Styles */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

           

            .header-content {
                padding-left: 0;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        /* Discipline Status */
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .status-active {
            background-color: var(--success-color);
        }

        .status-inactive {
            background-color: var(--text-light);
        }

        /* Professor Badge */
        .professor-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .professor-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }

        /* Print Button */
        .print-button {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-dark);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .print-button:hover {
            background-color: var(--light-bg);
        }

        @media print {
            .sidebar, .header, .menu-toggle, .footer, .print-button, .breadcrumb-container {
                display: none !important;
            }

        

            body {
                background-color: white !important;
            }
        }

           .navbar-brand{
    color: white !important; /* Garantir que o texto dos links seja branco */
}
        
    </style>
</head>
<body>
  
<?php
// Arquivo: includes/header.php
// Este arquivo contém o cabeçalho e navbar que será incluído em todas as páginas

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['id_pessoa'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Acadêmico - <?php echo $page_title ?? 'Sistema Escolar'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body>
    <!-- Navbar -->
   <header class="header">
      
        
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
        <div class="container-fluid">
            <div class="page-header"> <br><br>
                <h1 class="page-title">Comunicados e Calendário</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="Estudante.php">Início</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Comunicados</li>
                    </ol>
                </nav>
            </div>

            <?php 
            // Verifica se a consulta retornou algum resultado
            if ($result && $result->num_rows > 0) {
                // Reposiciona o ponteiro do resultado no início
                $result->data_seek(0);
                
                // Inicia os contadores
                $numComunicados = 0;
                $numCalendarios = 0;
                
                // Conta os tipos de comunicados
                while ($row = $result->fetch_assoc()) {
                    if (in_array($row['tipo'], ['Calendário de Aulas', 'Calendário de Provas'])) {
                        $numCalendarios++;
                    } else {
                        $numComunicados++;
                    }
                }
                
                // Reposiciona o ponteiro do resultado no início novamente
                $result->data_seek(0);
            ?>
            
            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <i class="bi bi-bell-fill fs-1 me-3"></i>
                            <div>
                                <h6 class="card-title mb-0">Total de Comunicados</h6>
                                <h2 class="mb-0"><?php echo $numComunicados + $numCalendarios; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <i class="bi bi-calendar-event fs-1 me-3"></i>
                            <div>
                                <h6 class="card-title mb-0">Calendários</h6>
                                <h2 class="mb-0"><?php echo $numCalendarios; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <i class="bi bi-megaphone-fill fs-1 me-3"></i>
                            <div>
                                <h6 class="card-title mb-0">Avisos</h6>
                                <h2 class="mb-0"><?php echo $numComunicados; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body d-flex align-items-center">
                            <i class="bi bi-check-circle-fill fs-1 me-3"></i>
                            <div>
                                <h6 class="card-title mb-0">Último Update</h6>
                                <p class="mb-0"><?php echo date('d/m/Y'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <?php
                    // Reposiciona o ponteiro do resultado no início novamente
                    $result->data_seek(0);
                    
                    // Exibe os comunicados
                    while ($row = $result->fetch_assoc()): 
                        $cardHeaderClass = '';
                        $cardIcon = '';
                        
                        // Define a classe e ícone com base no tipo de comunicado
                        if ($row['tipo'] == 'Calendário de Aulas') {
                            $cardHeaderClass = 'primary';
                            $cardIcon = 'bi-calendar-week';
                        } elseif ($row['tipo'] == 'Calendário de Provas') {
                            $cardHeaderClass = 'info';
                            $cardIcon = 'bi-clipboard-check';
                        } elseif ($row['tipo'] == 'Evento') {
                            $cardHeaderClass = 'evento';
                            $cardIcon = 'bi-stars';
                        } elseif ($row['tipo'] == 'Aviso') {
                            $cardHeaderClass = 'aviso';
                            $cardIcon = 'bi-exclamation-circle';
                        } else {
                            $cardHeaderClass = 'comunicado';
                            $cardIcon = 'bi-bell';
                        }
                    ?>
                    <div class="card mb-4">
                        <div class="card-header <?php echo $cardHeaderClass; ?> d-flex align-items-center">
                            <i class="bi <?php echo $cardIcon; ?> me-2"></i>
                            <div>
                                <strong><?= htmlspecialchars($row['tipo']) ?></strong> - <?= htmlspecialchars($row['titulo']) ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (in_array($row['tipo'], ['Calendário de Aulas', 'Calendário de Provas'])): ?>
                                <?php $linhas = explode("\n", trim($row['conteudo'])); ?>
                                <div class="table-responsive table-container">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">ID</th>
                                                <th style="width: 20%;">Classe</th>
                                                <th style="width: 20%;">Disciplina</th>
                                                <th style="width: 15%;">Data</th>
                                                <th style="width: 15%;">Hora</th>
                                                <th style="width: 25%;">Observações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($linhas as $linha): ?>
                                                <?php preg_match('/(\d+)\)\s*Classe:\s*(.*?),\s*Disciplina:\s*(.*?),\s*Data:\s*(.*?),\s*Hora:\s*(.*?),\s*Obs:\s*(.*)/', $linha, $matches); ?>
                                                <?php if ($matches): ?>
                                                    <tr>
                                                        <td><?= $matches[1] ?></td>
                                                        <td><?= htmlspecialchars($matches[2]) ?></td>
                                                        <td><?= htmlspecialchars($matches[3]) ?></td>
                                                        <td><?= htmlspecialchars($matches[4]) ?></td>
                                                        <td><?= htmlspecialchars($matches[5]) ?></td>
                                                        <td><?= htmlspecialchars($matches[6]) ?></td>
                                                    </tr>
                                                <?php else: ?>
                                                    <tr><td colspan="6"><?= htmlspecialchars($linha) ?></td></tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="comunicado-content">
                                    <?= nl2br(htmlspecialchars($row['conteudo'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($row['data_evento']): ?>
                        <div class="card-footer text-muted">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar3 me-2"></i>
                                <span class="event-date">Data do evento: <?= htmlspecialchars($row['data_evento']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php 
            } else {
                // Caso não haja comunicados
                echo '<div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Não há comunicados disponíveis no momento.
                      </div>';
            }
            ?>
        </div>
    </main>

    

    <script src="bootstrap-5.0.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Dropdown menu for sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.menu-item-has-children');
            
            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    this.classList.toggle('open');
                    const submenu = this.nextElementSibling;
                    if (submenu.classList.contains('submenu')) {
                        e.preventDefault();
                        submenu.classList.toggle('open');
                    }
                });
            });
        });
    </script>
</body>
</html>