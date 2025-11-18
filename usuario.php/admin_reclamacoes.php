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

if (!isset($_SESSION['id_pessoa'])) {
    header("Location: login.php");
    exit;
}

$query = "SELECT * FROM comunicados ORDER BY data_evento DESC, id DESC";
$result = $conn->query($query);


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

// Processar mudança de status se solicitada
if ($_POST['action'] ?? '' === 'change_status' && isset($_POST['reclamacao_id'], $_POST['new_status'])) {
    $stmt = $pdo->prepare("UPDATE reclamacoes SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['new_status'], $_POST['reclamacao_id']]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Filtros
$status_filter = $_GET['status'] ?? '';
$turma_filter = $_GET['turma'] ?? '';
$search = $_GET['search'] ?? '';

// Construir query com filtros
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

if ($turma_filter) {
    $where_conditions[] = "t.nome_turma = ?";
    $params[] = $turma_filter;
}

if ($search) {
    $where_conditions[] = "(p.nome LIKE ? OR r.assunto LIKE ? OR r.mensagem LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Buscar reclamações com filtros (incluindo nome da turma)
$query = "
    SELECT r.id, r.assunto, r.mensagem, r.data_envio, r.status, r.id_turma, r.created_at,
           p.nome AS nome_aluno, t.nome_turma AS turma_nome
    FROM reclamacoes r
    JOIN aluno a ON r.id_aluno = a.id_pessoa
    JOIN pessoa p ON a.id_pessoa = p.id_pessoa
    LEFT JOIN turma t ON r.id_turma = t.id
    $where_clause
    ORDER BY r.data_envio DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reclamacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar turmas para o filtro (usando nome_turma)
try {
    $turmas_stmt = $pdo->query("SELECT DISTINCT nome_turma FROM turma WHERE nome_turma IS NOT NULL ORDER BY nome_turma");
    $turmas = $turmas_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Se falhar, criar array vazio
    $turmas = [];
}

// Estatísticas
$stats_query = "SELECT status, COUNT(*) as count FROM reclamacoes GROUP BY status";
$stats_stmt = $pdo->query($stats_query);
$stats = $stats_stmt->fetchAll(PDO::FETCH_KEY_PAIR);


?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração - Reclamações</title>
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: rgb(59, 127, 228);
            --secondary-color: rgb(59, 127, 228);
            --accent-color: #f3f6fa;
            --text-color: #333;
            --light-text: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 80px;
            color: var(--text-color);
        }
        
        /* Header styling */
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
        
        /* Main content styling */
        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
            padding: 20px;
        }
        
        .main-content.sidebar-active {
            margin-left: 250px;
        }
        
        #icon-30 {
            width: 60px;
            height: 50px;
            margin-right: 2%;
        }
        
        /* Custom styles for complaints page */
        .page-header {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .page-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: var(--light-text);
            font-size: 1.1rem;
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stats-label {
            color: var(--light-text);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filters-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            flex: 1;
        }
        
        .filter-group label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
            display: block;
        }
        
        .complaints-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        
        .complaints-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 25px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .complaint-item {
            border-bottom: 1px solid #eee;
            padding: 25px;
            transition: background-color 0.2s ease;
        }
        
        .complaint-item:hover {
            background-color: #f8f9fa;
        }
        
        .complaint-item:last-child {
            border-bottom: none;
        }
        
        .complaint-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .complaint-info {
            flex-grow: 1;
        }
        
        .complaint-student {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        
        .complaint-subject {
            font-size: 1rem;
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .complaint-meta {
            display: flex;
            gap: 20px;
            align-items: center;
            font-size: 0.9rem;
            color: var(--light-text);
        }
        
        .complaint-meta i {
            margin-right: 5px;
        }
        
        .complaint-message {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 15px;
            margin: 15px 0;
            line-height: 1.6;
            color: var(--text-color);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-em-andamento { background: #d1ecf1; color: #0c5460; }
        .status-resolvido { background: #d4edda; color: #155724; }
        .status-rejeitado { background: #f8d7da; color: #721c24; }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-sm-custom {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 4px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--light-text);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
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
            
            .filter-row {
                flex-direction: column;
                gap: 20px;
            }
            
            .complaint-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .complaint-meta {
                flex-direction: column;
                gap: 10px;
                align-items: start;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
        
        @media (max-width: 576px) {
            .stats-card {
                margin-bottom: 15px;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .filters-card {
                padding: 20px;
            }
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
        <a href="admin_reclamacoes.php" class="nav-link active"><i class="bi bi-chat-dots"></i>Reclamações</a>
        <a href="Altera_senha_professor.php" class="nav-link"><i class="bi bi-key menu-icon"></i>Segurança</a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="logout.php" class="btn btn-outline-secondary w-100"><i class="bi bi-box-arrow-left me-2"></i>Encerrar Sessão</a>
    </div>
</aside>

<!-- Main Content -->
<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title"><i class="bi bi-chat-dots me-3"></i>Gestão de Reclamações</h1>
        <p class="page-subtitle">Acompanhe e gerencie as reclamações dos alunos</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-number text-warning"><?= $stats['pendente'] ?? 0 ?></div>
                <div class="stats-label">Pendentes</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-number text-info"><?= $stats['em_andamento'] ?? 0 ?></div>
                <div class="stats-label">Em Andamento</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-number text-success"><?= $stats['resolvido'] ?? 0 ?></div>
                <div class="stats-label">Resolvidas</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card">
                <div class="stats-number text-primary"><?= array_sum($stats) ?></div>
                <div class="stats-label">Total</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="search">Buscar</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="Nome do aluno, assunto ou mensagem..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Todos os status</option>
                        <option value="pendente" <?= $status_filter === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="em_andamento" <?= $status_filter === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="resolvido" <?= $status_filter === 'resolvido' ? 'selected' : '' ?>>Resolvido</option>
                        <option value="rejeitado" <?= $status_filter === 'rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="turma">Turma</label>
                    <select id="turma" name="turma" class="form-select">
                        <option value="">Todas as turmas</option>
                        <?php foreach ($turmas as $turma): ?>
                            <option value="<?= htmlspecialchars($turma['nome_turma']) ?>" <?= $turma_filter === $turma['nome_turma'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($turma['nome_turma']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Complaints List -->
    <div class="complaints-card">
        <div class="complaints-header">
            <i class="bi bi-list-ul me-2"></i>Reclamações (<?= count($reclamacoes) ?>)
        </div>
        
        <?php if (empty($reclamacoes)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>Nenhuma reclamação encontrada</h4>
                <p>Não há reclamações que correspondam aos filtros selecionados.</p>
            </div>
        <?php else: ?>
            <?php foreach ($reclamacoes as $reclamacao): ?>
                <div class="complaint-item">
                    <div class="complaint-header">
                        <div class="complaint-info">
                            <div class="complaint-student">
                                <i class="bi bi-person-fill me-2"></i><?= htmlspecialchars($reclamacao['nome_aluno']) ?>
                            </div>
                            <div class="complaint-subject"><?= htmlspecialchars($reclamacao['assunto']) ?></div>
                            <div class="complaint-meta">
                                <span><i class="bi bi-calendar3"></i><?= date("d/m/Y H:i", strtotime($reclamacao['data_envio'])) ?></span>
                                <?php if ($reclamacao['turma_nome']): ?>
                                    <span><i class="bi bi-people"></i>Turma: <?= htmlspecialchars($reclamacao['turma_nome']) ?></span>
                                <?php endif; ?>
                                <span><i class="bi bi-hash"></i>ID: <?= $reclamacao['id'] ?></span>
                            </div>
                        </div>
                        <div>
                            <span class="status-badge status-<?= $reclamacao['status'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $reclamacao['status'])) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="complaint-message">
                        <?= nl2br(htmlspecialchars($reclamacao['mensagem'])) ?>
                    </div>
                    
                    <div class="action-buttons">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="reclamacao_id" value="<?= $reclamacao['id'] ?>">
                            
                            <?php if ($reclamacao['status'] === 'pendente'): ?>
                                <button type="submit" name="new_status" value="em_andamento" 
                                        class="btn btn-info btn-sm-custom">
                                    <i class="bi bi-play-fill me-1"></i>Iniciar
                                </button>
                            <?php endif; ?>
                            
                            <?php if (in_array($reclamacao['status'], ['pendente', 'em_andamento'])): ?>
                                <button type="submit" name="new_status" value="resolvido" 
                                        class="btn btn-success btn-sm-custom">
                                    <i class="bi bi-check-circle me-1"></i>Resolver
                                </button>
                                <button type="submit" name="new_status" value="rejeitado" 
                                        class="btn btn-danger btn-sm-custom">
                                    <i class="bi bi-x-circle me-1"></i>Rejeitar
                                </button>
                            <?php endif; ?>
                            
                            <?php if (in_array($reclamacao['status'], ['resolvido', 'rejeitado'])): ?>
                                <button type="submit" name="new_status" value="pendente" 
                                        class="btn btn-warning btn-sm-custom">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Reabrir
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts -->
<script src="bootstrap-5.0.2/js/bootstrap.bundle.min.js"></script>
<script>
    // Menu toggle functionality
    document.getElementById('toggleMenu').addEventListener('click', function () {
        document.getElementById('sidebarMenu').classList.toggle('active');
        document.querySelector('.main-content').classList.toggle('sidebar-active');
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.classList.add('fade');
            setTimeout(function() {
                alert.remove();
            }, 150);
        });
    }, 5000);

    // Confirm status changes
    document.querySelectorAll('button[name="new_status"]').forEach(button => {
        button.addEventListener('click', function(e) {
            const status = this.value;
            const statusNames = {
                'em_andamento': 'Em Andamento',
                'resolvido': 'Resolvida',
                'rejeitado': 'Rejeitada',
                'pendente': 'Pendente'
            };
            
            if (!confirm(`Tem certeza que deseja marcar esta reclamação como "${statusNames[status]}"?`)) {
                e.preventDefault();
            }
        });
    });
</script>

</body>
</html>