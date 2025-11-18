<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Adicionar nova nota
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar'])) {
    $id_aluno = $_POST['id_aluno'] ?? '';
    $id_disc = $_POST['id_disc'] ?? '';
    $id_prova = $_POST['id_prova'] ?? '';
    $id_professor = $_POST['id_professor'] ?? '';
    $id_trimestre = $_POST['id_trimestre'] ?? '';
    $nota = $_POST['nota'] ?? '';
    $data_lancamento = date("Y-m-d H:i:s");

    if (!empty($id_aluno) && !empty($id_disc) && is_numeric($nota)) {
        $sql = "INSERT INTO minipauta (nota, data_lancamento, id_prova, id_professor, id_trimestre, id_aluno, id_disc) 
                VALUES ('$nota', '$data_lancamento', '$id_prova', '$id_professor', '$id_trimestre', '$id_aluno', '$id_disc')";
        if (!$conn->query($sql)) {
            die("Erro ao inserir nota: " . $conn->error);
        }
    }
    header("Location: relatorio.php");
    exit();
}

// Excluir nota
if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $conn->query("DELETE FROM minipauta WHERE id=$id");
    header("Location: relatorio.php");
    exit();
}

// Consulta das notas
$sql = "SELECT 
    m.id,
    m.nota,
    m.data_lancamento,
    pessoa_aluno.nome AS nome_aluno,
    pessoa_prof.nome AS nome_professor,
    pr.nome_prova,
    t.num_tri,
    d.nome_disc AS nome_disciplina
FROM minipauta m
JOIN aluno a ON m.id_aluno = a.id_pessoa
JOIN pessoa pessoa_aluno ON a.id_pessoa = pessoa_aluno.id_pessoa
JOIN professor pf ON m.id_professor = pf.id_pessoa
JOIN pessoa pessoa_prof ON pf.id_pessoa = pessoa_prof.id_pessoa
JOIN prova pr ON m.id_prova = pr.id
JOIN trimestre t ON m.id_trimestre = t.id
JOIN disciplina d ON m.id_disc = d.id
";

$result = $conn->query($sql);
if (!$result) {
    die("Erro ao consultar SQL: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançamento de Notas</title>
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    <style>
        :root {
        --primary: #0d6efd;
            --secondary: #0062cc;
            --accent: #4285f4;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --sidebar-width: 280px;
            --header-height: 70px;
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            color: white;
            z-index: 999;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo-container {
            padding: 0px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar .logo {
            width: 60px;
            height: 50px;
            background-color: white;
            border-radius: 50%;
            padding: 5px;
            margin-bottom: 10px;
            
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 3px solid white;
        }

      

        .sidebar i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .sidebar .menu-category {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
            padding: 20px 25px 10px;
            margin-top: 10px;
        }

        /* Header Styling */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px 0 calc(var(--sidebar-width) + 30px);
            z-index: 998;
            transition: all var(--transition-speed);
        }

        .header .user-info {
            display: flex;
            align-items: center;
        }

        .header .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
        }

        .header .user-name {
            font-weight: 500;
            color: var(--dark);
        }

        .header .user-role {
            font-size: 12px;
            color: #6c757d;
        }

        .toggle-sidebar {
            background-color: transparent;
            border: none;
            color: var(--primary);
            font-size: 20px;
            cursor: pointer;
            display: none;
        }

        /* Content Styling */
        .content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            padding-top: calc(var(--header-height) + 30px);
            min-height: 100vh;
            transition: all var(--transition-speed);
        }

        .page-title {
            margin-bottom: 25px;
            color: var(--primary);
            font-weight: 600;
        }

        .stats-row {
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 25px;
            transition: transform 0.3s;
            border-left: 4px solid var(--accent);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.primary {
            border-left-color: var(--primary);
        }

        .stat-card.success {
            border-left-color: var(--success);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.info {
            border-left-color: var(--info);
        }

        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background-color: rgba(66, 133, 244, 0.1);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-card.primary .stat-icon {
            background-color: rgba(26, 47, 94, 0.1);
            color: var(--primary);
        }

        .stat-card.success .stat-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .stat-card.warning .stat-icon {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .stat-card.info .stat-icon {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .stat-card .stat-title {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .stat-card .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
        }

        /* Feature Cards Styling */
        .feature-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            height: 100%;
            transition: all 0.3s;
            border-top: 4px solid var(--accent);
            display: flex;
            flex-direction: column;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .feature-card.primary {
            border-top-color: var(--primary);
        }

        .feature-card.success {
            border-top-color: var(--success);
        }

        .feature-card.warning {
            border-top-color: var(--warning);
        }

        .feature-card.info {
            border-top-color: var(--info);
        }

        .feature-card.danger {
            border-top-color: var(--danger);
        }

        .feature-card .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background-color: rgba(66, 133, 244, 0.1);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .feature-card.primary .feature-icon {
            background-color: rgba(26, 47, 94, 0.1);
            color: var(--primary);
        }

        .feature-card.success .feature-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .feature-card.warning .feature-icon {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .feature-card.info .feature-icon {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .feature-card.danger .feature-icon {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .feature-card .feature-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .feature-card .feature-description {
            color: #6c757d;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .btn-feature {
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
        }

        .btn-feature i {
            margin-right: 8px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #153057;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-warning {
            background-color: var(--warning);
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-info {
            background-color: var(--info);
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        /* Footer Styling */
        footer {
            margin-top: 50px;
            padding: 20px 0;
            background-color: white;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .header {
                padding-left: 30px;
            }

            .content {
                margin-left: 0;
            }

            .toggle-sidebar {
                display: block;
            }

            .content.sidebar-active {
                margin-left: var(--sidebar-width);
            }

            .header.sidebar-active {
                padding-left: calc(var(--sidebar-width) + 30px);
            }
        }

        @media (max-width: 767.98px) {
            .header {
                padding: 0 15px;
            }

            .content {
                padding: 20px;
                padding-top: calc(var(--header-height) + 20px);
            }

            .user-role {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo-container">
        
        <h5>Instituto Politécnico<br>"30 de Setembro"</h5>
    </div>
    
    <a href="admin.php"><i class="bi bi-house-door"></i> Início</a>
    <a href="cadastro_professor.php"><i class="bi bi-person-plus"></i> Cadastrar Professores</a>
    <a href="Cadastro_aluno.php"><i class="bi bi-person"></i> Cadastrar Alunos</a>
    <a href="cadastro_disciplina.php"><i class="bi bi-book"></i> Cadastrar Disciplinas</a>
    <a href="turma.php"><i class="bi bi-people-fill"></i> Gestão de Turmas</a>
    
    <a href="lançamento_notas_admin.php" class="active"><i class="bi bi-pencil-square"></i> Lançamento de Notas</a>
    <a href="listar_matriculas.php"><i class="bi bi-card-list"></i> Visualizar Matrículas</a>
    <a href="visualizar_professor.php"><i class="bi bi-person-lines-fill"></i> Visualizar Professores</a>
    <a href="pedagogico.php"><i class="bi bi-calendar-check"></i> Calendário Acadêmico</a>
    <a href="atribuicao_disc.php"><i class="bi bi-person-workspace"></i> Atribuir Disciplinas</a>
       
    <a href="atualizar_senha.php"><i class="bi bi-key"></i> Cadastrar Usuário</a>
    </div>

    
<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh; margin-left: 299px;">
   <div class="main-content">
<h4>Pesquisar por nome do aluno:</h4>
    <div class="d-flex align-items-center mb-3">
        <label for="pesquisaAluno" class="me-2 fw-bold">Pesquisar aluno:</label>
        <input type="text" id="pesquisaAluno" class="form-control" placeholder="Digite o nome do aluno..." style="width: 300px;">
    </div>

    <!-- Tabela -->
    <div class="table-responsive">
        <table class="table table-striped table-bordered" id="tabelaNotas">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Aluno</th>
                    <th>Disciplina</th>
                    <th>Professor</th>
                    <th>Prova</th>
                    <th>Trimestre</th>
                    <th>Nota</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['nome_aluno'] ?></td> <!-- Aqui está o nome do aluno -->
                        <td><?= $row['nome_disciplina'] ?></td>
                        <td><?= $row['nome_professor'] ?></td>
                        <td><?= $row['nome_prova'] ?></td>
                        <td><?= $row['num_tri'] ?></td>
                        <td><?= $row['nota'] ?></td> <!-- Aqui está a nota -->
                        <td><?= $row['data_lancamento'] ?></td>
                        <td><a href="?excluir=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Excluir</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Script de pesquisa -->
<<script>
    document.getElementById('pesquisaAluno').addEventListener('keyup', function () {
        var filtro = this.value.toLowerCase();
        var linhas = document.querySelectorAll('#tabelaNotas tbody tr');

        linhas.forEach(function (linha) {
            var nomeAluno = linha.children[1].textContent.toLowerCase(); // Corrigido para coluna correta
            linha.style.display = nomeAluno.includes(filtro) ? '' : 'none';
        });
    });
</script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
