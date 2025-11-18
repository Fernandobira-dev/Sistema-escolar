<?php
session_start();

if (!isset($_SESSION['id_pessoa'])) {
    header("Location: login.php");
    exit;
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$admin_id = $_SESSION['id_pessoa'];

$sql = "SELECT nome_usuario FROM usuario WHERE id_pessoa = ? AND tipo_usuario = 'admin'";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}

$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $admin_name = $user['nome_usuario'];
} else {
    $admin_name = "Usuário";
}

// Buscar estatísticas do sistema para exibir no dashboard
$stats = [
    'alunos' => 0,
    'professores' => 0,
    'turmas' => 0,
    'disciplinas' => 0
];
// Buscar turmas - Admin vê todas as turmas
$turmas = [];
$sql = "SELECT id, nome_turma FROM turma ORDER BY nome_turma";
$result = $conn->query($sql);

if ($result === false) {
    die("Erro ao buscar turmas: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $turmas[] = $row;
}
$professores = [];
$sql = "SELECT p.id_pessoa, p.nome 
        FROM pessoa p 
        JOIN professor pr ON p.id_pessoa = pr.id_pessoa 
        ORDER BY p.nome";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $professores[] = $row;
}

// Total de alunos
$query = "SELECT COUNT(*) as total FROM aluno";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['alunos'] = $row['total'];
}

// Total de professores
$query = "SELECT COUNT(DISTINCT id_prof) as total FROM atribuicao_disc";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['professores'] = $row['total'];
}

// Total de turmas
$query = "SELECT COUNT(*) as total FROM turma";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['turmas'] = $row['total'];
}

// Total de disciplinas
$query = "SELECT COUNT(*) as total FROM disciplina";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $stats['disciplinas'] = $row['total'];
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | Instituto Politécnico 30 de Setembro</title>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    
     
    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --info: #0dcaf0;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #212529;
            --bs-body-bg: #f8f9fa;
            --sidebar-width: 280px;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar-wrapper {
            width: 26%;
            min-height: 100vh;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            transition: margin-left 0.25s ease-out;
        }

        .sidebar-heading {
            padding: 1rem 1.25rem;
            font-size: 1.2rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.85);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-nav {
            padding-left: 0;
            list-style: none;
        }

        .sidebar-nav .nav-item {
            margin-bottom: 0.2rem;
        }

        .sidebar-nav .nav-link {
            padding: 0.8rem 1.25rem;
            color: rgba(255, 255, 255, 0.65);
            display: flex;
            align-items: center;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            color: rgba(255, 255, 255, 0.95);
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: var(--primary);
        }

        .sidebar-nav .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
            width: 1.5rem;
            text-align: center;
        }

        #page-content-wrapper {
            min-width: 100vw;
            transition: margin-left 0.25s ease-out;
        }

        #wrapper.toggled .sidebar-wrapper {
            margin-left: calc(-1 * var(--sidebar-width));
        }

        @media (min-width: 992px) {
            #page-content-wrapper {
                min-width: 0;
                width: 100%;
            }

            #wrapper:not(.toggled) .sidebar-wrapper {
                margin-left: 0;
            }
        }

        .navbar {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            background-color: white;
        }

        .avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary);
            color: white;
            font-weight: bold;
            border-radius: 50%;
        }

        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        .stat-icon {
            display: inline-flex;
            width: 60px;
            height: 60px;
            border-radius: 12px;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .feature-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            height: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .feature-card .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }

        .feature-icon {
            display: inline-flex;
            width: 60px;
            height: 60px;
            border-radius: 12px;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .bg-icon-primary {
            color: var(--primary);
            background-color: rgba(13, 110, 253, 0.1);
        }

        .bg-icon-success {
            color: var(--success);
            background-color: rgba(25, 135, 84, 0.1);
        }

        .bg-icon-warning {
            color: var(--warning);
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-icon-info {
            color: var(--info);
            background-color: rgba(13, 202, 240, 0.1);
        }

        .bg-icon-danger {
            color: var(--danger);
            background-color: rgba(220, 53, 69, 0.1);
        }

        .border-start-primary {
            border-left: 4px solid var(--primary) !important;
        }

        .border-start-success {
            border-left: 4px solid var(--success) !important;
        }

        .border-start-warning {
            border-left: 4px solid var(--warning) !important;
        }

        .border-start-info {
            border-left: 4px solid var(--info) !important;
        }

        .border-start-danger {
            border-left: 4px solid var(--danger) !important;
        }

        .border-top-primary {
            border-top: 4px solid var(--primary) !important;
        }

        .border-top-success {
            border-top: 4px solid var(--success) !important;
        }

        .border-top-warning {
            border-top: 4px solid var(--warning) !important;
        }

        .border-top-info {
            border-top: 4px solid var(--info) !important;
        }

        .border-top-danger {
            border-top: 4px solid var(--danger) !important;
        }

        .feature-description {
            flex-grow: 1;
            color: var(--secondary);
        }

        footer {
            background-color: white;
            border-top: 1px solid #dee2e6;
            padding: 1rem 0;
            margin-top: 2rem;
        }
        #icon-30{
          width: 60px;
          height: 50px;
          margin-right: 2%;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="sidebar-wrapper">
            <div class="sidebar-heading text-center py-9">
            <img src="icon.png" alt="icon" id="icon-30"><br>
              INSTITUTO POLITÉCNICO Nº2039 "30 DE SETEMBRO"
            </div>
            <div class="list-group list-group-flush sidebar-nav">
                <a href="admin.php" class="nav-link active">
                    <i class="bi bi-house-door"></i> Início
                </a>
                <a href="cadastro_professor.php" class="nav-link">
                    <i class="bi bi-person-plus"></i> Cadastrar Professores
                </a>
                <a href="Cadastro_aluno.php" class="nav-link">
                    <i class="bi bi-person"></i> Cadastrar Alunos
                </a>
                <a href="cadastro_disciplina.php" class="nav-link">
                    <i class="bi bi-book"></i> Cadastrar Disciplinas
                </a>
                <a href="turma.php" class="nav-link">
                    <i class="bi bi-people-fill"></i> Gestão de Turmas
                </a>
                <a href="lançamento_notas_admin.php" class="nav-link">
                    <i class="bi bi-pencil-square"></i> Lançamento de Notas
                </a>
                <a href="listar_matriculas.php" class="nav-link">
                    <i class="bi bi-card-list"></i> Visualizar Matrículas
                </a>
                <a href="visualizar_professor.php" class="nav-link">
                    <i class="bi bi-person-lines-fill"></i> Visualizar Professores
                </a>
                <a href="pedagogico.php" class="nav-link">
                    <i class="bi bi-calendar-check"></i> Calendário Acadêmico
                </a>
                <a href="atribuicao_disc.php" class="nav-link">
                    <i class="bi bi-person-workspace"></i> Atribuir Disciplinas
                </a>
                <a href="atualizar_senha.php" class="nav-link">
                    <i class="bi bi-key"></i> Cadastrar Usuário
                </a>
                
               
            </div>
        </div>
        
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light sticky-top">
                <div class="container-fluid">
                  
                    
                    <div class="ms-auto d-flex align-items-center">
                        
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar me-2">
                                    <?= strtoupper(substr($admin_name, 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($admin_name) ?></div>
                                    <div class="small text-muted">Diretor Pedagógico</div>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="user-dropdown">
                              <!--  <li><a class="dropdown-item" href="perfil_admin.php"><i class="bi bi-person me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="configuracoes.php"><i class="bi bi-gear me-2"></i>Configurações</a></li>-->
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-speedometer2 me-2"></i>Painel do Pedagógico
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="admin.php">Inicio</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Painel</li>
                        </ol>
                    </nav>
                </div>

                <!-- Stats Row -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-start-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon bg-icon-primary">
                                            <i class="bi bi-mortarboard-fill"></i>
                                        </div>
                                        <div class="text-uppercase text-muted small mb-1">Total de Alunos</div>
                                        <div class="h2 mb-0 fw-bold"><?= $stats['alunos'] ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-success small">
                                            <i class="bi bi-arrow-up me-1"></i>12% 
                                        </div>
                                        <div class="text-muted small">desde o último mês</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-start-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon bg-icon-warning">
                                            <i class="bi bi-people-fill"></i>
                                        </div>
                                        <div class="text-uppercase text-muted small mb-1">Total de Turmas</div>
                                        <div class="h2 mb-0 fw-bold"><?= count($turmas) ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-success small">
                                            <i class="bi bi-arrow-up me-1"></i>5% 
                                        </div>
                                        <div class="text-muted small">desde o último mês</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-start-info h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon bg-icon-info">
                                            <i class="bi bi-book-half"></i>
                                        </div>
                                        <div class="text-uppercase text-muted small mb-1">Total de Disciplinas</div>
                                        <div class="h2 mb-0 fw-bold"><?= $stats['disciplinas'] ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-success small">
                                            <i class="bi bi-arrow-up me-1"></i>8% 
                                        </div>
                                        <div class="text-muted small">desde o último mês</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-start-success h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon bg-icon-success">
                                            <i class="bi bi-person-workspace"></i>
                                        </div>
                                        <div class="text-uppercase text-muted small mb-1">Total de Professores</div>
                                        <div class="h2 mb-0 fw-bold"><?= count($professores) ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-success small">
                                            <i class="bi bi-arrow-up me-1"></i>3% 
                                        </div>
                                        <div class="text-muted small">desde o último mês</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overview Row -->
               

                <!-- Feature Cards -->
                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Gestão Acadêmica</h5>
                    <div class="row g-4">
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card border-top-primary">
                                <div class="card-body">
                                    <div class="feature-icon bg-icon-primary mb-3">
                                        <i class="bi bi-pencil-square"></i>
                                    </div>
                                    <h5 class="card-title fw-bold">Lançamento de Notas</h5>
                                    <p class="feature-description">Publique as notas dos alunos e gerencie o lançamento de avaliações no sistema acadêmico.</p>
                                    <a href="lançamento_notas_admin.php" class="btn btn-primary mt-auto">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Acessar
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card border-top-success">
                                <div class="card-body">
                                    <div class="feature-icon bg-icon-success mb-3">
                                        <i class="bi bi-person-check"></i>
                                    </div>
                                    <h5 class="card-title fw-bold">Inserir Disciplina</h5>
                                    <p class="feature-description">Adicione as disciplinas nos cursos e gerencie a estrutura curricular da instituição.</p>
                                    <a href="inserir_curso_disc.php" class="btn btn-success mt-auto">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Acessar
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card border-top-info">
                                <div class="card-body">
                                    <div class="feature-icon bg-icon-info mb-3">
                                        <i class="bi bi-eye"></i>
                                    </div>
                                    <h5 class="card-title fw-bold">Visualizar Disciplinas</h5>
                                    <p class="feature-description">Consulte as disciplinas por curso, suas cargas horárias e professores responsáveis.</p>
                                    <a href="curso_disc.php" class="btn btn-info mt-auto text-white">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Acessar
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card border-top-warning">
                                <div class="card-body">
                                    <div class="feature-icon bg-icon-warning mb-3">
                                        <i class="bi bi-person-plus"></i>
                                    </div>
                                    <h5 class="card-title fw-bold">Cadastro de Professores</h5>
                                    <p class="feature-description">Adicione novos professores ao sistema e gerencie o corpo docente da instituição.</p>
                                    <a href="cadastro_professor.php" class="btn btn-warning mt-auto text-dark">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Acessar
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card border-top-primary">
                                <div class="card-body">
                                    <div class="feature-icon bg-icon-primary mb-3">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <h5 class="card-title fw-bold">Gestão de Turmas</h5>
                                    <p class="feature-description">Organize e gerencie as turmas, definindo horários e espaços acadêmicos.</p>
                                    <a href="turma.php" class="btn btn-primary mt-auto">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Acessar
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="card feature-card border-top-danger">
                                <div class="card-body">
                                    <div class="feature-icon bg-icon-danger mb-3">
                                        <i class="bi bi-graph-up-arrow"></i>
                                    </div>
                                    <h5 class="card-title fw-bold">Relatórios Acadêmicos</h5>
                                    <p class="feature-description">Gere e analise relatórios detalhados sobre o desempenho acadêmico e indicadores institucionais.</p>
                                    <a href="relatorio.php" class="btn btn-danger mt-auto">
                                        <i class="bi bi-arrow-right-circle me-1"></i> Acessar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
    </div>

    <script src="bootstrap-5.0.2/js/bootstrap.bundle.min.js"></script>
    <script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle sidebar
        document.getElementById("menu-toggle").addEventListener("click", function(e) {
            e.preventDefault();
            document.getElementById("wrapper").classList.toggle("toggled");
        });

        // Set active nav link
        document.addEventListener("DOMContentLoaded", function() {
            const currentPath = window.location.pathname.split("/").pop();
            const navLinks = document.querySelectorAll(".sidebar-nav .nav-link");
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute("href");
                if (linkHref === currentPath) {
                    link.classList.add("active");
                } else {
                    link.classList.remove("active");
                }
            });

            // Handle responsive sidebar on page load
            function checkWindowSize() {
                if (window.innerWidth < 992) {
                    document.getElementById("wrapper").classList.add("toggled");
                } else {
                    document.getElementById("wrapper").classList.remove("toggled");
                }
            }
            
            // Initial check
            checkWindowSize();
            
            // Check on resize
            window.addEventListener("resize", checkWindowSize);
        });
    </script>
     <script>
        // Toggle sidebar
        document.getElementById("menu-toggle").addEventListener("click", function(e) {
            e.preventDefault();
            document.getElementById("wrapper").classList.toggle("toggled");
        });

        // Set active nav link
        document.addEventListener("DOMContentLoaded", function() {
            const currentPath = window.location.pathname.split("/").pop();
            const navLinks = document.querySelectorAll(".sidebar-nav .nav-link");
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute("href");
                if (linkHref === currentPath) {
                    link.classList.add("active");
                } else {
                    link.classList.remove("active");
                }
            });

            // Handle responsive sidebar on page load
            function checkWindowSize() {
                if (window.innerWidth < 992) {
                    document.getElementById("wrapper").classList.add("toggled");
                } else {
                    document.getElementById("wrapper").classList.remove("toggled");
                }
            }
            
            // Initial check
            checkWindowSize();
            
            // Check on resize
            window.addEventListener("resize", checkWindowSize);
        });
    </script>
</body>
</html>