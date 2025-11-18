<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "setembro";

// Conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verificar se houve uma solicitação para excluir
if (isset($_GET['delete'])) {
    $id_curso = $_GET['delete'];
    $id_disc = $_GET['disc'];
    $id_classe = $_GET['classe'];

    // Consulta para excluir o registro específico
    $sql_delete = "DELETE FROM curso_disciplina WHERE id_curso = ? AND id_disc = ? AND id_classe = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("iii", $id_curso, $id_disc, $id_classe);

    if ($stmt->execute()) {
        // Se a exclusão for bem-sucedida, redireciona para evitar repetição da ação
        header("Location: curso_disc.php");
        exit();
    } else {
        echo "Erro ao excluir: " . $conn->error;
    }

    // Fechar o statement
    $stmt->close();
}

// Variáveis para armazenar os filtros
$curso_filtrado = isset($_POST['id_curso']) ? $_POST['id_curso'] : '';
$classe_filtrada = isset($_POST['id_classe']) ? $_POST['id_classe'] : '';

// Consulta para cursos e classes
$sql_cursos = "SELECT id, nome_curso FROM curso";
$sql_classes = "SELECT id, nome_classe FROM classe";

// Executando as consultas para preencher os selects
$result_cursos = $conn->query($sql_cursos);
$result_classes = $conn->query($sql_classes);

// Consulta principal com filtros para curso e classe
$sql = "SELECT 
            c.nome_curso, 
            d.nome_disc, 
            cl.nome_classe, 
            cd.condicao,
            cd.id_classe, 
            cd.id_curso,
            cd.id_disc
        FROM curso_disciplina cd
        JOIN curso c ON cd.id_curso = c.id
        JOIN disciplina d ON cd.id_disc = d.id
        JOIN classe cl ON cd.id_classe = cl.id";

// Se houver curso e classe filtrados, adicione a cláusula WHERE
$where_conditions = [];
if ($curso_filtrado != '') {
    $where_conditions[] = "cd.id_curso = $curso_filtrado";
}
if ($classe_filtrada != '') {
    $where_conditions[] = "cd.id_classe = $classe_filtrada";
}

// Adiciona a condição WHERE se existirem filtros
if (count($where_conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$result = $conn->query($sql);
if (!$result) {
    die("Erro na consulta: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Tabela de Cursos e Disciplinas</title>
  <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
</head>
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
    </div
    <div class="d-flex justify-content-center align-items-center" style="min-height: 100vh; margin-left: 270px;">

<div style="max-width: 700px; margin-left: 290px; margin-right: 0;">
<a class="navbar-brand" href="#">Sistema de Cursos</a>
  <!-- Formulário -->
  <form method="POST" action="" class="mb-3" style="margin-bottom: 15px;">
    <div class="row align-items-end">
      <div class="col-md-5">
        <label for="id_curso" class="form-label" style="font-size: 14px;">Curso</label>
        <select name="id_curso" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Curso</option>
          <?php
          if ($result_cursos->num_rows > 0) {
              while ($row = $result_cursos->fetch_assoc()) {
                  $selected = ($curso_filtrado == $row['id']) ? 'selected' : '';
                  echo "<option value='{$row['id']}' {$selected}>{$row['nome_curso']}</option>";
              }
          }
          ?>
        </select>
      </div>
      <div class="col-md-5">
        <label for="id_classe" class="form-label" style="font-size: 14px;">Classe</label>
        <select name="id_classe" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Classe</option>
          <?php
          if ($result_classes->num_rows > 0) {
              while ($row = $result_classes->fetch_assoc()) {
                  $selected = ($classe_filtrada == $row['id']) ? 'selected' : '';
                  echo "<option value='{$row['id']}' {$selected}>{$row['nome_classe']}</option>";
              }
          }
          ?>
        </select>
      </div>
      <div class="col-md-2 text-end">
        <button type="submit" class="btn btn-sm btn-primary mt-3">Filtrar</button>
      </div>
    </div>
  </form>

  <!-- Tabela -->
  <div style="overflow-x: auto;">
    <table class="table table-bordered table-striped table-sm" style="font-size: 12px;">
      <thead class="table-dark">
        <tr>
          <th style="white-space: nowrap;">Curso</th>
          <th style="white-space: nowrap;">Disciplina</th>
          <th style="white-space: nowrap;">Classe</th>
          <th style="white-space: nowrap;">Condição</th>
          <th style="white-space: nowrap; width: 75px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $condicao = match ($row['condicao']) {
                    1 => 'Ativo',
                    2 => 'Inativo',
                    3 => 'Concluído',
                    default => 'Desconhecida'
                };

                echo "<tr>
                        <td>{$row['nome_curso']}</td>
                        <td>{$row['nome_disc']}</td>
                        <td>{$row['nome_classe']}</td>
                        <td>{$condicao}</td>
                        <td>
                            <a href='?delete={$row['id_curso']}&disc={$row['id_disc']}&classe={$row['id_classe']}' class='btn btn-danger btn-sm'>Excluir</a>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='text-center'>Nenhum dado encontrado</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

</div>


</div>


  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
