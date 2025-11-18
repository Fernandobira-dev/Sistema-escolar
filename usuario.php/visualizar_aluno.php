<?php
// Conexão com o banco de dados
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Verificar se o formulário de pesquisa foi enviado
$nome = "";
if (isset($_POST['nome'])) {
    $nome = $_POST['nome'];

    // Consulta os dados filtrados pelo nome do aluno
    $sql = "SELECT p.nome AS aluno_nome, a.num_processo 
            FROM aluno a
            JOIN pessoa p ON a.id_pessoa = p.id_pessoa
            WHERE p.nome LIKE '%$nome%'";
} else {
    // Se não houver pesquisa, mostra todos os alunos
    $sql = "SELECT p.nome AS aluno_nome, a.num_processo 
            FROM aluno a
            JOIN pessoa p ON a.id_pessoa = p.id_pessoa";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Alunos</title>
   
    <link rel="stylesheet" href="bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">

    <link href="../bootstrap-5.0.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">        <style>
body {
            padding-top: 100px;
            background-color: #f4f6f9;
        }

        .tema-azul {
            background: linear-gradient(to right,rgb(60, 150, 253),rgb(70, 83, 253));
            color: white;
        }

        .cabecalho-boas-vindas {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px;
            z-index: 1051;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(146, 83, 83, 0.2);
        }

        .cabecalho-boas-vindas i {
            font-size: 2.5rem;
        }

        .cabecalho-boas-vindas h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            transition: transform 0.3s ease;
            transform: translateX(-100%);
            z-index: 1050;
            padding-top: 80px;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar .nav-link {
            color: white;
            margin: 10px 0;
            padding: 10px;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }

        .menu-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background-color: #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        .student-info {
            max-width: 500px;
            margin: 30px auto;
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .student-info h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .img-thumbnail {
            border-radius: 50%;
            max-width: 150px;
        }

        .ano-letivo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #fff;
            background-color: #333;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        footer {
    background: linear-gradient(to right,rgb(30, 63, 151),rgb(69, 139, 252)); /* Azul gradiente */
    color: white;
    text-align: center;
    padding: 1rem;
    margin-top: 50px;
}
</style>
</head>
<body>
<button class="menu-toggle"><i class="bi bi-list fs-4"></i></button>

<!-- Cabeçalho de boas-vindas -->
<div class="cabecalho-boas-vindas tema-azul">
    <i class="bi bi-person-circle"></i>
    <h1><?= $mensagemBoasVindas ?></h1>
</div>

<!-- Sidebar -->
<!-- Sidebar -->
<div class="sidebar tema-azul" id="sidebarMenu">
<nav class="nav flex-column p-3">
    <a href="Estudante.php" class="nav-link"><i class="bi bi-house me-2"></i>Início</a>

    <div class="nav-item dropdown">
        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person me-2"></i>Estudante
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="aaa.php"><i class="bi bi-house-door me-2"></i>Inicio</a></li>
            <li><a class="dropdown-item" href="altera_senha.php"><i class="bi bi-key me-2"></i>Modificar Senha</a></li>
            <li><a class="dropdown-item" href="Dados_pessoais.php"><i class="bi bi-info-circle me-2"></i>Ver Dados Pessoais</a></li>
            <li><a class="dropdown-item" href="reclamacao.php"><i class="bi bi-chat-dots me-2"></i>Reclamação</a></li>
        </ul>
    </div>

    <div class="nav-item dropdown">
        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-building me-2"></i>Secretaria
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="visualizar_notas.php"><i class="bi bi-file-earmark-bar-graph me-2"></i>Ver Notas</a></li>
            <li><a class="dropdown-item" href="atribuiçao.php"><i class="bi bi-journal-text me-2"></i>Disciplinas</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-calendar-event me-2"></i>Calendário</a></li>
            <li><a class="dropdown-item" href="ver_matricula.php"><i class="bi bi-card-checklist me-2"></i>Ver Matrícula</a></li>
        </ul>
    </div>

    <div class="nav-item dropdown">
        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-book me-2"></i>Oferta Letiva
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="informática.html"><i class="bi bi-laptop me-2"></i>Informática</a></li>
            <li><a class="dropdown-item" href="electricidade.html"><i class="bi bi-lightning-charge me-2"></i>Electricidade</a></li>
        </ul>
    </div>

    <a href="duvidas.html" class="nav-link"><i class="bi bi-question-circle me-2"></i>Dúvidas?</a>
    <a href="logout.php" class="nav-link mt-2"><i class="bi bi-box-arrow-left me-2"></i>Sair</a>
</nav>
</div>

    <div class="container">
        <!-- Formulário de pesquisa -->
        <form method="POST" action="" class="mb-4">
            <div class="input-group">
                <input type="text" name="nome" class="form-control" placeholder="Digite o nome do aluno" value="<?= htmlspecialchars($nome) ?>" required>
                <button type="submit" class="btn btn-primary">Pesquisar</button>
            </div>
        </form>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nome do Aluno</th>
                            <th>Número do Processo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['aluno_nome']) ?></td>
                                <td><?= htmlspecialchars($row['num_processo']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">
                Nenhum aluno encontrado com esse nome.
            </div>
        <?php endif; ?>

        <a href="admin.php" class="btn btn-secondary mt-3">Voltar</a>
    </div>

    <div class="footer-baixo" style="margin-top:20%;">
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p>&copy; 2025 Sistema Escolar. Todos os direitos reservados.</p>
    </footer></div>
</body>
</html>
c
    <script>
        const sidebar = document.getElementById('sidebarMenu');
        const toggleBtn = document.querySelector('.menu-toggle');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    </script>
<?php
$conn->close();
?>
