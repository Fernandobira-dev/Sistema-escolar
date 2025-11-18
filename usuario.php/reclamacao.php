<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['id_pessoa'])) {
    die("Você precisa estar logado para fazer uma reclamação.");
}

$host = 'localhost';
$db = 'setembro';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Inicializa a variável $mensagem
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_aluno = $_SESSION['id_pessoa'];
    $assunto = trim($_POST['assunto']);
    $mensagem_reclamacao = trim($_POST['mensagem']);

    if (empty($assunto) || empty($mensagem_reclamacao)) {
        $mensagem = "<div class='alert alert-danger'>Preencha todos os campos.</div>";
    } else {
        // Buscar informações da turma do aluno
        $stmt_turma = $pdo->prepare("
            SELECT m.id_curso, m.id_classe, c.nome_curso, cl.nome_classe 
            FROM matricula m
            INNER JOIN curso c ON c.id = m.id_curso
            INNER JOIN classe cl ON cl.id = m.id_classe
            WHERE m.id_pessoa = ?
            ORDER BY m.id DESC
            LIMIT 1
        ");
        $stmt_turma->execute([$id_aluno]);
        $turma_info = $stmt_turma->fetch(PDO::FETCH_ASSOC);

        if ($turma_info) {
            // Inserir reclamação com informações da turma
            $stmt = $pdo->prepare("
                INSERT INTO reclamacoes (id_aluno, assunto, mensagem, id_curso, id_classe, nome_curso, nome_classe) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            

            $mensagem = "<div class='alert alert-success'>
                <i class='bi bi-check-circle me-2'></i>
                Reclamação enviada com sucesso! Sua turma ({$turma_info['nome_curso']} - {$turma_info['nome_classe']}) foi registrada automaticamente.
            </div>";
        } else {
            $mensagem = "<div class='alert alert-warning'>
                <i class='bi bi-exclamation-triangle me-2'></i>
                Não foi possível identificar sua turma. Contacte a administração.
            </div>";
        }
    }
}

// Verifica se a conexão foi estabelecida corretamente
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

// Verifica se o usuário está logado
if (!isset($_SESSION['id_pessoa'])) {
    header("Location: login.php");
    exit;
}

$id_pessoa = $_SESSION['id_pessoa'];

// Consulta SQL para obter os dados do aluno
$sql = "SELECT p.id_pessoa, p.foto, p.nome, p.num_bi, p.email, p.tel_1, p.tel_2, 
               c.nome_curso, cl.nome_classe
        FROM pessoa p
        INNER JOIN aluno a ON a.id_pessoa = p.id_pessoa
        INNER JOIN matricula m ON m.id_pessoa = a.id_pessoa
        INNER JOIN curso c ON c.id = m.id_curso
        INNER JOIN classe cl ON cl.id = m.id_classe
        WHERE a.id_pessoa = ?";

// Prepara a query e verifica se teve sucesso
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}

// Associa o parâmetro e executa a consulta
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

// Fecha o statement e a conexão
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
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .profile-img-container {
            position: relative;
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

         /* Table styles */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }
        
        .table td, .table th {
            vertical-align: middle;
            padding: 1rem;
        }
        
        .table-row-success {
            background-color: rgba(39, 174, 96, 0.1);
        }
        
        .table-row-danger {
            background-color: rgba(231, 76, 60, 0.1);
        }

         
        .card-header.bg-warning {
            background: linear-gradient(135deg, var(--accent-color), var(--warning-color)) !important;
        }

           
        /* Card styles */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--secondary-color), var(--tertiary-color));
           
            font-weight: 600;
            border-bottom: none;
            padding: 1rem 1.5rem;
        }

        /* Student Info Card */
        .student-info-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .student-info-card h6 {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .student-info-card h5 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .info-divider {
            height: 1px;
            background-color: rgba(255, 255, 255, 0.2);
            margin: 1rem 0;
        }

        /* Alert customization */
        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: rgb(21, 128, 61);
            border-left: 4px solid rgb(34, 197, 94);
        }

        .alert-warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: rgb(146, 64, 14);
            border-left: 4px solid rgb(245, 158, 11);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: rgb(153, 27, 27);
            border-left: 4px solid rgb(239, 68, 68);
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
    <main class="main-content" id="main-content">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <div class="breadcrumb-container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="estudante.php">Inicio</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reclamações</li>
                    </ol>
                </nav>
            </div>

            <!-- Student Info Card -->
           

            <!-- Success/Error Messages -->
            <?php if (!empty($mensagem)): ?>
                <?= $mensagem ?>
            <?php endif; ?>

            <!-- Complaint Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-plus-circle me-2"></i>
                        Nova Reclamação
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="assunto" class="form-label">
                                    <i class="bi bi-tag me-1"></i>
                                    Assunto da Reclamação
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="assunto" 
                                       name="assunto" 
                                       placeholder="Digite o assunto da sua reclamação"
                                       required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="mensagem" class="form-label">
                                    <i class="bi bi-chat-text me-1"></i>
                                    Descrição da Reclamação
                                </label>
                                <textarea class="form-control" 
                                          id="mensagem" 
                                          name="mensagem" 
                                          rows="6" 
                                          placeholder="Descreva detalhadamente sua reclamação..."
                                          required></textarea>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Seja específico e forneça todos os detalhes relevantes para que possamos processar sua reclamação adequadamente.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="reset" class="btn btn-outline-secondary me-md-2">
                                        <i class="bi bi-arrow-clockwise me-1"></i>
                                        Limpar
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-1"></i>
                                        Enviar Reclamação
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Complaints -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Acompanhamento da Reclamação
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Definir $id_aluno a partir da sessão
                    $id_aluno = $_SESSION['id_pessoa'];
                    
                    // Primeiro, vamos verificar a estrutura da tabela
                    try {
                        $stmt_check = $pdo->prepare("DESCRIBE reclamacoes");
                        $stmt_check->execute();
                        $columns = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Determinar qual coluna de data usar
                        $date_column = 'id'; // padrão
                        if (in_array('created_at', $columns)) {
                            $date_column = 'created_at';
                        } elseif (in_array('data_criacao', $columns)) {
                            $date_column = 'data_criacao';
                        } elseif (in_array('data_reclamacao', $columns)) {
                            $date_column = 'data_reclamacao';
                        }
                        
                        // Verificar se existe coluna status
                        $status_column = in_array('status', $columns) ? ', status' : '';
                        
                        // Buscar reclamações do aluno
                        $query = "SELECT id, assunto, mensagem" . $status_column;
                        if ($date_column != 'id') {
                            $query .= ", " . $date_column;
                        }
                        $query .= " FROM reclamacoes WHERE id_aluno = ? ORDER BY id DESC LIMIT 5";
                        
                        $stmt_reclamacoes = $pdo->prepare($query);
                        $stmt_reclamacoes->execute([$id_aluno]);
                        $reclamacoes = $stmt_reclamacoes->fetchAll(PDO::FETCH_ASSOC);
                        
                    } catch (PDOException $e) {
                        // Se der erro, vamos tentar uma query mais simples
                        $stmt_reclamacoes = $pdo->prepare("
                            SELECT id, assunto, mensagem
                            FROM reclamacoes 
                            WHERE id_aluno = ? 
                            ORDER BY id DESC 
                            LIMIT 5
                        ");
                        $stmt_reclamacoes->execute([$id_aluno]);
                        $reclamacoes = $stmt_reclamacoes->fetchAll(PDO::FETCH_ASSOC);
                        $date_column = 'id';
                    }
                    ?>

                    <?php if (count($reclamacoes) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Assunto</th>
                                        <th>Estado</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reclamacoes as $reclamacao): ?>
                                        <tr>
                                            <td>
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?php 
                                                if (isset($reclamacao[$date_column]) && $date_column != 'id') {
                                                    echo date('d/m/Y H:i', strtotime($reclamacao[$date_column]));
                                                } else {
                                                    echo "ID: " . $reclamacao['id'];
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($reclamacao['assunto']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?= substr(htmlspecialchars($reclamacao['mensagem']), 0, 100) . '...' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $status = isset($reclamacao['status']) ? $reclamacao['status'] : 'pendente';
                                                $badge_class = '';
                                                $status_text = '';
                                                
                                                switch ($status) {
                                                    case 'pendente':
                                                        $badge_class = 'bg-warning';
                                                        $status_text = 'Pendente';
                                                        break;
                                                    case 'em_andamento':
                                                        $badge_class = 'bg-info';
                                                        $status_text = 'Em Andamento';
                                                        break;
                                                    case 'resolvida':
                                                        $badge_class = 'bg-success';
                                                        $status_text = 'Resolvida';
                                                        break;
                                                    case 'rejeitada':
                                                        $badge_class = 'bg-danger';
                                                        $status_text = 'Rejeitada';
                                                        break;
                                                    default:
                                                        $badge_class = 'bg-secondary';
                                                        $status_text = 'Pendente';
                                                }
                                                ?>
                                                <span class="badge <?= $badge_class ?>">
                                                    <?= $status_text ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewComplaint(<?= $reclamacao['id'] ?>)">
                                                    <i class="bi bi-eye"></i>
                                                    Ver
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                      
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <h5 class="empty-title">Nenhuma reclamação encontrada</h5>
                            <p class="empty-description">
                                Você ainda não fez nenhuma reclamação. Use o formulário acima para enviar sua primeira reclamação.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Instituto Politécnico 30 de Setembro. Todos os direitos reservados.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Menu Toggle
        document.getElementById('menu-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menu-toggle');
            
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
                document.getElementById('main-content').classList.remove('sidebar-active');
            }
        });

        // Function to view complaint details
        function viewComplaint(id) {
            // This would typically open a modal or redirect to a detailed view
            alert('Funcionalidade de visualização será implementada. ID: ' + id);
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const assunto = document.getElementById('assunto').value.trim();
            const mensagem = document.getElementById('mensagem').value.trim();
            
            if (assunto.length < 5) {
                e.preventDefault();
                alert('O assunto deve ter pelo menos 5 caracteres.');
                return false;
            }
            
            if (mensagem.length < 20) {
                e.preventDefault();
                alert('A descrição deve ter pelo menos 20 caracteres.');
                return false;
            }
        });

        // Character counter for textarea
        const textarea = document.getElementById('mensagem');
        const maxLength = 1000;
        
        // Create character counter element
        const counterDiv = document.createElement('div');
        counterDiv.className = 'form-text text-end';
        counterDiv.innerHTML = '<span id="char-count">0</span>/' + maxLength + ' caracteres';
        textarea.parentNode.appendChild(counterDiv);
        
        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            document.getElementById('char-count').textContent = currentLength;
            
            if (currentLength > maxLength * 0.9) {
                counterDiv.classList.add('text-warning');
            } else {
                counterDiv.classList.remove('text-warning');
            }
            
            if (currentLength > maxLength) {
                this.value = this.value.substring(0, maxLength);
                counterDiv.classList.add('text-danger');
            } else {
                counterDiv.classList.remove('text-danger');
            }
        });
    </script>
</body>
</html>