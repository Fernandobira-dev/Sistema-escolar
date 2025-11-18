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
    die("Acesso negado. Por favor, faça login como professor.");
}

$conn->set_charset("utf8");

$id_professor = $_SESSION['id_pessoa'];

$sql = "SELECT
            d.nome_disc AS disciplina,
            c.nome_curso AS curso,
            a.ano AS ano_letivo,
            cl.nome_classe AS classe,
            t.nome_turma AS turma   -- Adicionando o campo turma
        FROM atribuicao_disc ad
        JOIN disciplina d ON ad.id_disc = d.id
        JOIN curso c ON ad.id_curso = c.id
        JOIN anolectivo a ON ad.id_anolectivo = a.id
        JOIN classe cl ON ad.id_classe = cl.id
        JOIN turma t ON ad.id_turma = t.id   -- JOIN com a tabela turma
        WHERE ad.id_prof = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar a consulta: " . $conn->error);
}

$stmt->bind_param("i", $id_professor);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Disciplinas</title>
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link href="../bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: rgb(59, 127, 228);
            --secondary-color: rgb(59, 127, 228);
            --accent-color: #f3f6fa;
            --text-color: #333;
            --light-text: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --header-bg: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            --table-header-bg: var(--primary-color);
            --table-header-text: white;
            --table-row-hover-bg: #e9ecef;
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
            background: var(--header-bg);
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
            background: var(--header-bg);
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

        #icon-30 {
            width: 60px;
            height: 50px;
            margin-right: 2%;
        }

        .navbar-brand {
            color: white !important;
        }

        /* Table Specific Styling */
        .disciplines-table-container {
            padding: 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin: 20px auto; /* Centering the table container */
            max-width: 1200px; /* Optional: limit max width */
        }

        .disciplines-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .disciplines-table thead {
            background-color: var(--table-header-bg);
            color: var(--table-header-text);
        }

        .disciplines-table th,
        .disciplines-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .disciplines-table th {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .disciplines-table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .disciplines-table tbody tr:hover {
            background-color: var(--table-row-hover-bg);
            cursor: default;
        }

        .alert-warning {
            margin-top: 20px;
            padding: 15px;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
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
    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-header">
            <h5>Painel do Professor</h5>
        </div>

        <nav class="nav flex-column">
            <a href="professor.php" class="nav-link"><i class="bi bi-house"></i>Página Inicial</a>
            <a href="lançamento_notas_professor.php" class="nav-link"><i class="bi bi-journal-bookmark-fill"></i>Lançamento de Notas</a>
            <a href="distribuição_disc.php" class="nav-link active"><i class="bi bi-book"></i>Minhas Disciplinas</a>
            <a href="Comunicação_prof.php" class="nav-link"><i class="bi bi-calendar-week"></i>Calendário Acadêmico</a>
            <a href="admin_reclamacoes.php" class="nav-link"><i class="bi bi-chat-dots"></i>Reclamações</a>
              <a href="Altera_senha_professor.php" class="nav-link"><i class="bi bi-key menu-icon"></i>Segurança</a>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="btn btn-outline-secondary w-100"><i class="bi bi-box-arrow-left me-2"></i>Encerrar Sessão</a>
        </div>
    </aside>

    <div class="main-content" id="mainContent">
        <div class="container-fluid">
            <h2 class="mb-4 text-center">Disciplinas Atribuídas</h2>
            <div class="disciplines-table-container">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table class="disciplines-table">
                        <thead>
                            <tr>
                                <th>Disciplina</th>
                                <th>Curso</th>
                                <th>Classe</th>
                                <th>Turma</th>
                                <th>Ano Letivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['disciplina']) ?></td>
                                    <td><?= htmlspecialchars($row['curso']) ?></td>
                                    <td><?= htmlspecialchars($row['classe']) ?></td>
                                    <td><?= htmlspecialchars($row['turma']) ?></td>
                                    <td><?= htmlspecialchars($row['ano_letivo']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-warning text-center" role="alert">
                        Nenhuma disciplina atribuída até o momento.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebarMenu');
            const mainContent = document.getElementById('mainContent');
            const menuToggle = document.querySelector('.menu-toggle');

            // Function to toggle sidebar and main content margin
            const toggleSidebar = () => {
                sidebar.classList.toggle('active');
                mainContent.classList.toggle('sidebar-active');
            };

            // Event listener for the menu toggle button
            menuToggle.addEventListener('click', toggleSidebar);

            // Set initial state based on screen width
            if (window.innerWidth >= 992) {
                sidebar.classList.add('active');
                mainContent.classList.add('sidebar-active');
            }

            // Adjust on window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 992) {
                    sidebar.classList.add('active');
                    mainContent.classList.add('sidebar-active');
                } else {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('sidebar-active');
                }
            });
        });
    </script>
</body>
</html>