<?php
session_start();
include 'conexao.php';

$erros = [];
$usuarios = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Cadastro individual
    if (isset($_POST['linha_individual'])) {
        $id_pessoa = $_POST['id_pessoa'];
        $nome_usuario = $_POST['nome_usuario'];
        $senha = $_POST['senha'];
        $tipo_usuario = $_POST['tipo_usuario'];

        if (empty($nome_usuario) || empty($senha) || empty($tipo_usuario)) {
            $erros[] = "Todos os campos são obrigatórios.";
        } else {
            $senha_criptografada = password_hash($senha, PASSWORD_DEFAULT);

            // Verificar se o nome de usuário já existe
            $sql_check = "SELECT id_pessoa FROM usuario WHERE nome_usuario = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $nome_usuario);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $erros[] = "O nome de usuário '" . htmlspecialchars($nome_usuario) . "' já está em uso.";
            } else {
                // Inserir usuário na tabela
                $sql = "INSERT INTO usuario (id_pessoa, nome_usuario, senha, tipo_usuario) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $id_pessoa, $nome_usuario, $senha_criptografada, $tipo_usuario);
                if ($stmt->execute()) {
                    $usuarios[] = $nome_usuario;
                } else {
                    $erros[] = "Erro ao cadastrar usuário " . htmlspecialchars($nome_usuario) . ": " . $stmt->error;
                }
                $stmt->close();
            }
            $stmt_check->close();
        }
    }
    // Cadastro em massa
    elseif (isset($_POST['id_pessoa'])) {
        foreach ($_POST['id_pessoa'] as $index => $id_pessoa) {
            $nome_usuario = $_POST['nome_usuario'][$index];
            $senha = $_POST['senha'][$index];
            $tipo_usuario = $_POST['tipo_usuario'][$index];

            if (empty($nome_usuario) || empty($senha) || empty($tipo_usuario)) {
                continue;
            }

            $senha_criptografada = password_hash($senha, PASSWORD_DEFAULT);

            // Verificar se o nome de usuário já existe
            $sql_check = "SELECT id_pessoa FROM usuario WHERE nome_usuario = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $nome_usuario);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $erros[] = "O nome de usuário '" . htmlspecialchars($nome_usuario) . "' já está em uso.";
            } else {
                // Inserir usuário na tabela
                $sql = "INSERT INTO usuario (id_pessoa, nome_usuario, senha, tipo_usuario) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $id_pessoa, $nome_usuario, $senha_criptografada, $tipo_usuario);
                if ($stmt->execute()) {
                    $usuarios[] = $nome_usuario;
                } else {
                    $erros[] = "Erro ao cadastrar usuário " . htmlspecialchars($nome_usuario) . ": " . $stmt->error;
                }
                $stmt->close();
            }
            $stmt_check->close();
        }
    }

    if (count($erros) == 0 && count($usuarios) > 0) {
        $sucesso = "Cadastro realizado com sucesso para: " . implode(", ", $usuarios);
    }
}

// para garantir que a lista seja atualizada
$pessoas = [];
$result = $conn->query("SELECT id_pessoa, nome FROM pessoa WHERE id_pessoa NOT IN (SELECT id_pessoa FROM usuario)");
while ($row = $result->fetch_assoc()) {
    $pessoas[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">

    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
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

  
    <div class="d-flex justify-content-center align-items-center" style="min-height: 100vh; margin-left: 290px;">
    <?php if (!empty($erros)): ?>
        <div class="alert alert-danger">
            <?php foreach ($erros as $erro): ?>
                <p><?= $erro; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($sucesso)): ?>
        <div class="alert alert-success"><?= $sucesso; ?></div>
    <?php endif; ?>
    
    <div class="container">
        <h2 class="text-center mb-4">Cadastro de Usuários</h2>
        
        <?php if (count($pessoas) > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nome da pessoa</th>
                        <th>Nome do Usuário</th>
                        <th>Senha do usuario</th>
                        <th>Tipo do Usuario</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pessoas as $pessoa): ?>
                        <tr>
                            <form method="POST" action="">
                                <td style="font-size: 18px; vertical-align: middle;"><?= htmlspecialchars($pessoa['nome']); ?></td>
                                <td>
                                    <input type="text" name="nome_usuario" class="form-control" required>
                                    <input type="hidden" name="id_pessoa" value="<?= $pessoa['id_pessoa']; ?>">
                                    <input type="hidden" name="linha_individual" value="1">
                                </td>
                                <td>
                                    <input type="password" name="senha" class="form-control" required>
                                </td>
                                <td>
                                    <select name="tipo_usuario" class="form-select" required>
                                        <option value="">Selecione</option>
                                        <option value="aluno">Aluno</option>
                                        <option value="professor">Professor</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" class="btn btn-success btn-sm">Cadastrar</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center">
                Não há pessoas disponíveis para cadastro de usuários. Todas as pessoas já possuem usuários cadastrados.
            </div>
        <?php endif; ?>
        
        <!-- Botão de Cadastro em Massa adicionado abaixo da tabela -->
        <?php if (count($pessoas) > 0): ?>
            <div class="mt-3 text-center">
                <form method="POST" action="">
                    <input type="hidden" name="cadastro_massa" value="1">
                    <button type="submit" class="btn btn-primary btn-lg">Cadastro em Massa</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>