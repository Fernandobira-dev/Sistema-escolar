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

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">

    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            background-color: rgb(15, 128, 241);
            color: white;
            padding-top: 15px;
            font-size: 16px;
            overflow-y: auto; /* Para permitir rolagem caso o menu ultrapasse o tamanho da tela */
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
        }

        .sidebar a:hover {
            background-color: rgb(19, 57, 95);
        }

        .sidebar .active {
            background-color: #007bff;
        }

        .content {
            margin-left: 270px;
            padding: 20px;
            padding-top: 120px;
            min-height: calc(100vh - 150px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .card-custom {
            box-shadow: 0px 3px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            font-size: 14px;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 10px;
            background-color: rgb(41, 147, 253);
            font-size: 14px;
            color: #6c757d;
        }

        .sidebar h4 {
            color: white;
            text-align: center;
            font-size: 20px;
            margin-bottom: 30px;
        }

        .sidebar i {
            margin-right: 10px;
            font-size: 18px;
        }

        .card-custom h5 {
            font-size: 16px;
        }

        .card-custom p {
            font-size: 14px;
        }

        .header {
            background-color: rgb(28, 138, 248);
            color: white;
            padding: 10px 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-info {
            display: inline-block;
            font-size: 14px;
            padding: 5px 15px;
            margin: 0;
        }

        .header .btn {
            margin-left: auto;
        }
        .btn-visualizacao:hover {
    background-color: #084298;
    text-decoration: none;
}
.header .btn {
            margin-left: auto;
        }
        .btn-visualizacao {
    display: inline-block;
    padding: 8px 16px;
    background-color: #0d6efd; /* Azul do Bootstrap */
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.3s ease;
}

    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4>Menu Administrativo</h4> <br><br>
    <a href="admin_viws.php" class="active"><i class="bi bi-house-door"></i> Início</a>
    <a href="curso_disc.php"><i class="bi bi-eye"></i> Visualizar Todas as Disciplinas</a>
    <a href="inserir_curso_disc.php"><i class="bi bi-person"></i>Inserir Displinas nos Cursos</a>
</div>

<!-- Header -->
<div class="header">
    <div class="alert alert-info">
        <i class="bi bi-person-circle"></i> Bem-vindo, <?= htmlspecialchars($admin_name) ?>!
        <div>
            <h4>Diretor Pedagógico</h4>
        </div>
    </div>
    <a href="logout.php" class="btn btn-danger mb-3 text-white"><i class="bi bi-box-arrow-right"></i> Sair</a>
</div>

<!-- Conteúdo principal -->
<div class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="card card-custom text-center p-3">
            <i class="bi bi-pencil-square" style="font-size: 2.5rem;"></i>
                <h5 class="mt-3">Lançamento de Notas Pedagogico</h5>
                <p>Atualize as notas dos alunos.</p>
                <a href="lançamento_notas_admin.php"class="btn-visualizacao mt-2"> Publicar Notas</a>
 </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom text-center p-3">
                <i class="bi bi-person-check" style="font-size: 2.5rem;"></i>
                <h5 class="mt-3">Frequência</h5>
                <p>Registre e consulte a presença dos alunos.</p>
                <a href="" class="btn-visualizacao mt-2"> Frquencia</a>

            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom text-center p-3">
            <i class="bi bi-eye" style="font-size: 2.5rem;"></i>
                <h5 class="mt-3">Visualizar Disciplina do Curso</h5>
                <p>Gerencie os Cursos.</p>
                <a href="admin_viws.php" class="btn-visualizacao mt-2">Visualizações</a>
                </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card card-custom text-center p-3">
            <i class="bi bi-person-plus" style="font-size: 2.5rem;"></i>
                <h5 class="mt-3">Cadastro de Professores</h5>
                <p>Gerencie os professores da instituição.</p>
                <a href="cadastro_professor.php" class="btn-visualizacao mt-2">Cadastrar Professores</a>

            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom text-center p-3">
            <i class="bi bi-people-fill"style="font-size: 2.5rem;"></i>
                <h5 class="mt-3">Turmas</h5>
                <p>Gerencie e organize as turmas da escola.</p>
                <a href="turma.php" class="btn-visualizacao mt-2"> Cadastrar Turmas</a>

            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom text-center p-3">
                <i class="bi bi-graph-up-arrow" style="font-size: 2.5rem;"></i>
                <h5 class="mt-3">Relatórios</h5>
                <p>Gere relatórios detalhados de desempenho.</p>
                <a href="relatorio.php" class="btn-visualizacao mt-2"> Relatório</a>

            </div>
        </div>
    </div>
</div>

<!-- Rodapé -->
<div class="footer-baixo">
    <footer class="bg-color text-white text-center py-3 mt-5">
        <p>&copy; 2025 Sistema Escolar. Todos os direitos reservados.</p>
    </footer>
</div>

<!-- Scripts -->
<script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
    const links = document.querySelectorAll('.sidebar a');
    links.forEach(link => {
        link.addEventListener('click', () => {
            links.forEach(link => link.classList.remove('active'));
            link.classList.add('active');
        });
    });
</script>
</body>
</html>
