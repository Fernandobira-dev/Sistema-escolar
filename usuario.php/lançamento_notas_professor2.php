<?php
session_start();
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$id_professor_logado = $_SESSION['id_pessoa'] ?? 0;
$nome_prof = '';
if ($id_professor_logado) {
    $res_prof = $conn->query("SELECT nome FROM pessoa WHERE id_pessoa = $id_professor_logado");
    if ($res_prof && $res_prof->num_rows > 0) {
        $row_prof = $res_prof->fetch_assoc();
        $nome_prof = $row_prof['nome'];
    }
}

// Carrega os cursos
$cursos = $conn->query("SELECT id, nome_curso FROM curso");

// Curso selecionado via GET (para filtrar alunos)
$id_curso_selecionado = $_GET['id_curso'] ?? '';

// Alunos filtrados por curso (se selecionado)
if (!empty($id_curso_selecionado)) {
    $alunos = $conn->query("SELECT a.id_pessoa, p.nome 
                            FROM aluno a 
                            JOIN pessoa p ON a.id_pessoa = p.id_pessoa 
                            WHERE a.id_curso = $id_curso_selecionado");
} else {
    $alunos = $conn->query("SELECT a.id_pessoa, p.nome 
                            FROM aluno a 
                            JOIN pessoa p ON a.id_pessoa = p.id_pessoa");
}

// Adiciona nota
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar'])) {
    $id_aluno = $_POST['id_aluno'] ?? '';
    $id_disc = $_POST['id_disc'] ?? '';
    $id_prova = $_POST['id_prova'] ?? '';
    $id_trimestre = $_POST['id_trimestre'] ?? '';
    $nota = $_POST['nota'] ?? '';
    $data_lancamento = date("Y-m-d H:i:s");

    if (!empty($id_aluno) && !empty($id_disc) && is_numeric($nota)) {
        $sql = "INSERT INTO minipauta (nota, data_lancamento, id_prova, id_professor, id_trimestre, id_aluno, id_disc) 
                VALUES ('$nota', '$data_lancamento', '$id_prova', '$id_professor_logado', '$id_trimestre', '$id_aluno', '$id_disc')";
        $conn->query($sql);
    }
    header("Location: lançamento_notas_professor.php?id_curso=$id_curso_selecionado");
    exit();
}

// Atualiza nota
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar'])) {
    $id_nota = intval($_POST['id_nota']);
    $nova_nota = floatval($_POST['nova_nota']);

    $verifica = $conn->query("SELECT id FROM minipauta WHERE id = $id_nota AND id_professor = $id_professor_logado");
    if ($verifica && $verifica->num_rows > 0) {
        $conn->query("UPDATE minipauta SET nota = '$nova_nota' WHERE id = $id_nota");
    }
    header("Location: lançamento_notas_professor.php?id_curso=$id_curso_selecionado");
    exit();
}

// Disciplinas do professor
$sql_disc = "SELECT d.id, d.nome_disc 
             FROM disciplina d
             JOIN atribuicao_disc ad ON ad.id_disc = d.id
             WHERE ad.id_prof = $id_professor_logado";
$disciplinas_result = $conn->query($sql_disc);

// Notas lançadas
$sql = "SELECT 
    m.id,
    m.nota,
    m.data_lancamento,
    pessoa_aluno.nome AS nome_aluno,
    pr.nome_prova,
    t.num_tri,
    d.nome_disc AS nome_disciplina
FROM minipauta m
JOIN aluno a ON m.id_aluno = a.id_pessoa
JOIN pessoa pessoa_aluno ON a.id_pessoa = pessoa_aluno.id_pessoa
JOIN prova pr ON m.id_prova = pr.id
JOIN trimestre t ON m.id_trimestre = t.id
JOIN disciplina d ON m.id_disc = d.id
WHERE m.id_professor = $id_professor_logado";
$result = $conn->query($sql);

?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançamento de Notas</title>
    <link href="bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
            :root {
            --primary-color: #1a3a5f;
            --secondary-color: #2c82c9;
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
        
        .logo-container {
            display: flex;
            align-items: center;
        }
        
        .logo-text {
            font-size: 1.2rem;
            font-weight: 600;
            margin-left: 15px;
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
    </style>
</head>
<body>

<header class="header">
        <button class="menu-toggle"><i class="bi bi-list fs-4"></i></button>
        
        <div class="header-content">
            <div class="logo-container">
            <img src="icon.png" alt="icon" id="icon-30">                <span class="logo-text">Instituto Politecnico 2039"30 de Setembro"</span>
            </div>
            
            <div class="user-info">
               
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
        </nav>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="btn btn-outline-secondary w-100"><i class="bi bi-box-arrow-left me-2"></i>Encerrar Sessão</a>
        </div>
    </aside>
    <div class="d-flex justify-content-center align-items-center" style="min-height: 10vh; margin-left: 270px;">
    <div class="container mt-5">
        <h2>Bem-vindo(a), Professor <?= htmlspecialchars($nome_prof) ?></h2>

        <div class="formulario-notas">
            <h4>Lançar Nova Nota</h4>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Aluno:</label>
                        <select name="id_aluno" class="form-select">
                            <?php
                            $alunos = $conn->query("SELECT a.id_pessoa, p.nome FROM aluno a JOIN pessoa p ON a.id_pessoa = p.id_pessoa");
                            while ($a = $alunos->fetch_assoc()) {
                                echo "<option value='{$a['id_pessoa']}'>" . htmlspecialchars($a['nome']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Disciplina:</label>
                        <select name="id_disc" class="form-select">
                            <?php
                            while ($d = $disciplinas_result->fetch_assoc()) {
                                echo "<option value='{$d['id']}'>" . htmlspecialchars($d['nome_disc']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Trimestre:</label>
                        <select name="id_trimestre" class="form-select">
                            <?php
                            $trimestres = $conn->query("SELECT id, num_tri FROM trimestre");
                            while ($t = $trimestres->fetch_assoc()) {
                                echo "<option value='{$t['id']}'>" . htmlspecialchars($t['num_tri']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                       
                    <div class="card mb-3">
    <div class="card-header">Turma</div>
    <div class="card-body">
        <?php
            $turmas = $conn->query("SELECT id, nome_turma FROM turma");
            if ($turmas && $turmas->num_rows > 0):
                while ($turma = $turmas->fetch_assoc()):
        ?>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="id_turma" id="turma_<?= $turma['id'] ?>" value="<?= $turma['id'] ?>" required>
                <label class="form-check-label" for="turma_<?= $turma['id'] ?>"><?= $turma['nome_turma'] ?></label>
            </div>
        <?php endwhile; endif; ?>
    </div>
</div>
                    <div class="col-md-6 mb-3">
                        <label>Prova:</label>
                        <select name="id_prova" class="form-select">
                            <?php
                            $provas = $conn->query("SELECT id, nome_prova FROM prova");
                            while ($p = $provas->fetch_assoc()) {
                                echo "<option value='{$p['id']}'>" . htmlspecialchars($p['nome_prova']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                    <div class="col-md-6 mb-3">
                    <div class="card mb-3">
    <div class="card-header">Curso</div>
    <div class="card-body">
        <?php foreach ($cursos as $curso): ?>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="id_curso" id="curso_<?= $curso['id'] ?>" value="<?= $curso['id'] ?>" required>
                <label class="form-check-label" for="curso_<?= $curso['id'] ?>"><?= $curso['nome_curso'] ?></label>
            </div>
        <?php endforeach; ?>
    </div>
</div>

    </form>
</div>


                    <div class="col-md-6 mb-3">
                        <label>Nota:</label>
                        <input type="number" name="nota" step="0.1" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="adicionar" class="btn btn-primary">Lançar Nota</button>
            </form>
        </div> <br><br>
        <div class="main-content">

    <div class="d-flex align-items-center mb-3">
        <label for="pesquisaAluno" class="me-2 fw-bold">Pesquisar aluno:</label>
        <input type="text" id="pesquisaAluno" class="form-control" placeholder="Digite o nome do aluno..." style="width: 300px;">
    </div>
        <div class="table-container">
            <h4>Minhas Notas Lançadas</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Disciplina</th>
                        <th>Prova</th>
                        <th>Trimestre</th>
                       
                        <th>Nota</th>
                        <th>Editar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nome_aluno']) ?></td>
                            <td><?= htmlspecialchars($row['nome_disciplina']) ?></td>
                            <td><?= htmlspecialchars($row['nome_prova']) ?></td>
                            <td><?= htmlspecialchars($row['num_tri']) ?></td>
                            <td><?= $row['nota'] ?></td>
                            <td>
                                <form method="POST" class="d-flex">
                                    <input type="hidden" name="id_nota" value="<?= $row['id'] ?>">
                                    <input type="number" name="nova_nota" value="<?= $row['nota'] ?>" step="0.1" class="form-control me-2" required>
                                    <button type="submit" name="editar" class="btn btn-sm btn-success">Salvar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        &copy; <?= date("Y") ?> Sistema de Notas - Todos os direitos reservados.
    </footer>

    <script>
        document.querySelector('.menu-toggle').addEventListener('click', () => {
            document.getElementById('sidebarMenu').classList.toggle('active');
        });
    </script>
</body>
</html>
