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
    header("Location: lançamento_notas_admin.php");
    exit();
}

// Excluir nota
if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $conn->query("DELETE FROM minipauta WHERE id=$id");
    header("Location: lançamento_notas_admin.php");
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
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            background-color: #f4f7fc;
            overflow-x: hidden;
        }
        .sidebar {
            width: 220px;
            background-color: #0d6efd;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            font-size: 15px;
        }
        .sidebar a:hover {
            background-color: #0a58ca;
        }
        .main-content {
            margin-left: 220px;
            padding: 30px;
            flex-grow: 1;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            margin-left: 220px;
        }
        footer {
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 10px;
            position: fixed;
            bottom: 0;
            width: calc(100% - 220px);
            margin-left: 220px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <a href="admin.php" class="active"><i class="bi bi-house-door"></i> Início</a>
    <a href="cadastro_professor.php"><i class="bi bi-person-plus"></i> Cadastrar Professores</a>
    <a href="Cadastro_aluno.php"><i class="bi bi-person"></i> Cadastrar Alunos</a>
    <a href="cadastro_disciplina.php"><i class="bi bi-pencil"></i> Cadastrar Disciplinas</a>
    <a href="turma.php" class="active"><i class="bi bi-people-fill"></i> Cadastrar Turmas</a>

    <a href="lançamento_notas_admin.php"><i class="bi bi-pencil-square"></i> Publicar Notas</a>
    <a href="listar_matriculas.php"><i class="bi bi-eye"></i> Visualizar Matriculas</a>
    <a href="visualizar_professor.php"><i class="bi bi-eye"></i> Visualizar Professores</a>
    <a href="pedagogico.php" class="active"><i class="bi bi-megaphone-fill me-2"></i>Publicar Calendario</a>
    <a href="atualizar_senha.php"><i class="bi bi-key"></i> Cadastrar Usuario</a>
    <a href="atribuicao_disc.php"><i class="bi bi-pencil"></i> Atribuir Disciplinas</a>

</div>
<!-- Header -->



<!-- Main content -->
<div class="main-content">
    <form method="POST" class="bg-white p-4 rounded shadow-sm mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="id_aluno" class="form-label">Aluno:</label>
                <select name="id_aluno" id="id_aluno" class="form-select" required>
                    <?php
                    $alunos = $conn->query("SELECT a.id_pessoa, p.nome FROM aluno a JOIN pessoa p ON a.id_pessoa = p.id_pessoa");
                    while ($a = $alunos->fetch_assoc()) {
                        echo "<option value='{$a['id_pessoa']}'>{$a['nome']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="id_disc" class="form-label">Disciplina:</label>
                <select name="id_disc" id="id_disc" class="form-select" required>
                    <?php
                    $disciplinas = $conn->query("SELECT id, nome_disc FROM disciplina");
                    while ($d = $disciplinas->fetch_assoc()) {
                        echo "<option value='{$d['id']}'>{$d['nome_disc']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="id_prova" class="form-label">Prova:</label>
                <select name="id_prova" id="id_prova" class="form-select">
                    <?php
                    $provas = $conn->query("SELECT id, nome_prova FROM prova");
                    while ($p = $provas->fetch_assoc()) {
                        echo "<option value='{$p['id']}'>{$p['nome_prova']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="id_trimestre" class="form-label">Trimestre:</label>
                <select name="id_trimestre" id="id_trimestre" class="form-select">
                    <?php
                    $trimestres = $conn->query("SELECT id, num_tri FROM trimestre");
                    while ($t = $trimestres->fetch_assoc()) {
                        echo "<option value='{$t['id']}'>{$t['num_tri']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="id_professor" class="form-label">Professor:</label>
                <select name="id_professor" id="id_professor" class="form-select">
                    <?php
                    $professores = $conn->query("SELECT pf.id_pessoa, p.nome FROM professor pf JOIN pessoa p ON pf.id_pessoa = p.id_pessoa");
                    while ($pr = $professores->fetch_assoc()) {
                        echo "<option value='{$pr['id_pessoa']}'>{$pr['nome']}</option>";
                    }
                    ?>
                </select>
            </div>


            <div class="col-md-6">
                <label for="nota" class="form-label">Nota:</label>
                <input type="number" name="nota" id="nota" class="form-control" min="0" max="20" step="0.1" required>
            </div>

            <div class="col-12 d-flex justify-content-between">
                <button type="submit" name="adicionar" class="btn btn-primary">Lançar Nota</button>
                
            </div>
        </div>
    </form>

    <h4>Pesquisar por nome do aluno:</h4>
    <input type="text" id="pesquisaAluno" class="form-control mb-3" placeholder="Digite o nome do aluno...">

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
<script>
    document.getElementById('pesquisaAluno').addEventListener('keyup', function () {
        var filtro = this.value.toLowerCase();
        var linhas = document.querySelectorAll('#tabelaNotas tbody tr');

        linhas.forEach(function (linha) {
            var nomeAluno = linha.children[3].textContent.toLowerCase();
            linha.style.display = nomeAluno.includes(filtro) ? '' : 'none';
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
