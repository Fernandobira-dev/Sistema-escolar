<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$mensagem = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recebe os dados do formulário
    $nome_disciplina = $_POST['nome_disciplina'] ?? '';
    $descricao = $_POST['descricao'] ?? '';

    // Validação simples
    if (!empty($nome_disciplina)) {
        // Prepara a consulta SQL para inserir os dados
        $sql = "INSERT INTO disciplina (nome_disc, descricao) VALUES ('$nome_disciplina', '$descricao')";

        if ($conn->query($sql) === TRUE) {
            $mensagem = "Disciplina cadastrada com sucesso!";
        } else {
            $mensagem = "Erro ao cadastrar disciplina: " . $conn->error;
        }
    } else {
        $mensagem = "O nome da disciplina é obrigatório!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Disciplina</title>
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">

    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
          #icon-30{
          width: 60px;
          height: 50px;
          margin-right: 2%;
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
    </style>
</head>
<body>

<!-- Menu Lateral -->

<
<div class="sidebar" id="sidebar">
    <div class="sidebar-heading text-center py-9">
            <img src="icon.png" alt="icon" id="icon-30"><br>
                Instituto Politécnico<br>"30 de Setembro"
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

<!-- Conteúdo Principal -->
<div class="d-flex justify-content-center align-items-center" style="min-height: 10vh; margin-left: 270px;">
    <div class="container mt-4">

        <?php if (!empty($mensagem)) : ?>
            <div class="alert alert-<?php echo (strpos($mensagem, 'sucesso') !== false) ? 'success' : 'danger'; ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <div class="container py-5">
  <form method="POST" action="">
    <h4 class="text-center">Cadastro de Disciplina</h4>

    <div class="mb-3">
      <label class="form-label">Nome da Disciplina</label>
      <input type="text" name="nome_disciplina" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Descrição</label>
      <textarea name="descricao" class="form-control"></textarea>
    </div>

    <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
  </form>
</div>
        </div>
        
       
    </div>
</div>

<!-- Rodapé -->


</body>
</html>

<?php
// Fecha a conexão ao banco de dados
$conn->close();
?>
