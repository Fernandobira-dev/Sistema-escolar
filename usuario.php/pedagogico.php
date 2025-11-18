<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['id_pessoa'])) {
    header("Location: login.php");
    exit;
}

$msg = '';
$erro = '';

// Processamento do cadastro de comunicado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo = $_POST['tipo'];
    $titulo = $_POST['titulo'];
    $conteudo = $_POST['conteudo'];
    $data_evento = !empty($_POST['data_evento']) ? $_POST['data_evento'] : null;
    
    // Verificar se há classe, curso e turma especificadas
    $classe = isset($_POST['classe']) ? $_POST['classe'] : null;
    $curso = isset($_POST['curso']) ? $_POST['curso'] : null;
    $turma = isset($_POST['turma']) ? $_POST['turma'] : null;
    
    // Verificar se a tabela comunicados tem as colunas necessárias
    try {
        $check_columns = $conn->query("SHOW COLUMNS FROM comunicados LIKE 'classe_destino'");
        if ($check_columns->num_rows == 0) {
            // Adicionar as colunas necessárias
            $conn->query("ALTER TABLE comunicados ADD COLUMN classe_destino VARCHAR(100) NULL");
            $conn->query("ALTER TABLE comunicados ADD COLUMN curso_destino VARCHAR(100) NULL");
            $conn->query("ALTER TABLE comunicados ADD COLUMN turma_destino VARCHAR(100) NULL");
        } else {
            // Verificar se existe a coluna curso_destino
            $check_curso = $conn->query("SHOW COLUMNS FROM comunicados LIKE 'curso_destino'");
            if ($check_curso->num_rows == 0) {
                // Adicionar apenas a coluna de curso
                $conn->query("ALTER TABLE comunicados ADD COLUMN curso_destino VARCHAR(100) NULL");
            }
            
            // Verificar se existe a coluna turma_destino
            $check_turma = $conn->query("SHOW COLUMNS FROM comunicados LIKE 'turma_destino'");
            if ($check_turma->num_rows == 0) {
                // Adicionar a coluna de turma
                $conn->query("ALTER TABLE comunicados ADD COLUMN turma_destino VARCHAR(100) NULL");
            }
        }
    } catch (Exception $e) {
        // Se ocorrer um erro, vamos apenas registrar e continuar
        error_log("Erro ao verificar ou adicionar colunas: " . $e->getMessage());
    }
    
    // Se for calendário de aulas e tiver classe/curso/turma, adicionar ao conteúdo
    if (($tipo === "Calendário de Aulas" || $tipo === "Calendário de Provas") && 
        !empty($classe) && !empty($turma)) {
        
        $curso_info = !empty($curso) ? ", Curso: $curso" : "";
        // Adicionar ao conteúdo a informação da classe, curso e turma
        $conteudo = "Calendário para Classe: $classe$curso_info, Turma: $turma\n\n" . $conteudo;
        
        // Tentar inserir com referência à classe, curso e turma
        try {
            $stmt = $conn->prepare("INSERT INTO comunicados (tipo, titulo, conteudo, data_evento, classe_destino, curso_destino, turma_destino) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $tipo, $titulo, $conteudo, $data_evento, $classe, $curso, $turma);
        } catch (Exception $e) {
            // Se falhar, voltar para o método sem as colunas adicionais
            $stmt = $conn->prepare("INSERT INTO comunicados (tipo, titulo, conteudo, data_evento) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $tipo, $titulo, $conteudo, $data_evento);
        }
    } else {
        // Inserção normal sem classe, curso e turma específicas
        $stmt = $conn->prepare("INSERT INTO comunicados (tipo, titulo, conteudo, data_evento) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $tipo, $titulo, $conteudo, $data_evento);
    }

    if ($stmt->execute()) {
        $msg = "Comunicado cadastrado com sucesso!";
    } else {
        $erro = "Erro ao cadastrar: " . $conn->error;
    }
}

// Processamento da exclusão de comunicado
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_stmt = $conn->prepare("DELETE FROM comunicados WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    if ($delete_stmt->execute()) {
        $msg = "Comunicado excluído com sucesso!";
    } else {
        $erro = "Erro ao excluir comunicado: " . $conn->error;
    }
}

// Buscar todas as informações de classes, cursos e turmas de uma só vez
$classes = [];
$cursos = [];
$turmas = [];
$turmas_por_classe_curso = [];

// Consulta mais eficiente para buscar todos os dados necessários de uma só vez
$query = "SELECT DISTINCT classe, curso, turma FROM turmas ORDER BY classe, curso, turma";
$result_turmas = $conn->query($query);

// Verificar se a consulta foi bem-sucedida
if ($result_turmas && $result_turmas->num_rows > 0) {
    while ($row = $result_turmas->fetch_assoc()) {
        // Adicionar classe ao array se ainda não existir
        if (!in_array($row['classe'], $classes)) {
            $classes[] = $row['classe'];
        }
        
        // Adicionar curso ao array se ainda não existir e não for vazio
        if (!empty($row['curso']) && !in_array($row['curso'], $cursos)) {
            $cursos[] = $row['curso'];
        }
        
        // Adicionar turma ao array se ainda não existir
        if (!in_array($row['turma'], $turmas)) {
            $turmas[] = $row['turma'];
        }
        
        // Organizar turmas por classe e curso para facilitar o uso no frontend
        $classe_key = $row['classe'];
        $curso_key = !empty($row['curso']) ? $row['curso'] : 'sem_curso';
        
        if (!isset($turmas_por_classe_curso[$classe_key])) {
            $turmas_por_classe_curso[$classe_key] = [];
        }
        
        if (!isset($turmas_por_classe_curso[$classe_key][$curso_key])) {
            $turmas_por_classe_curso[$classe_key][$curso_key] = [];
        }
        
        $turmas_por_classe_curso[$classe_key][$curso_key][] = $row['turma'];
    }
} else {
    // Se houver um erro ou nenhuma turma encontrada
    $erro_consulta = "Nenhuma turma encontrada ou erro na consulta: " . $conn->error;
    error_log($erro_consulta);
}

// Buscar os comunicados já cadastrados
$result = $conn->query("SELECT * FROM comunicados ORDER BY data_evento DESC");

// Convertendo o array associativo para formato JSON para uso com JavaScript no frontend
$turmas_json = json_encode($turmas_por_classe_curso);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Comunicados</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap e Ícones -->
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

    <style>
      /* Estilos CSS (mantido igual) */
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

<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh; margin-left: 270px;">
  <form method="POST">
    <h4 class="mb-4 text-primary text-center">Cadastro de Comunicado</h4>
    
    <?php if (!empty($msg)): ?>
    <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>
    
    <?php if (!empty($erro)): ?>
    <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>

    <div class="mb-3">
      <label class="form-label">Tipo de Comunicado</label>
      <select name="tipo" id="tipo-select" class="form-select" required>
        <option value="">Selecione</option>
        <option value="Aviso">Informação</option>
        <option value="Calendário de Provas">Calendário de Provas</option>
        <option value="Calendário de Aulas">Calendário de Aulas</option>
      </select>
    </div>

    <!-- Campo para classe e turma - aparecem apenas quando "Calendário de Aulas" é selecionado -->
       <!-- Campo para classe, curso e turma - aparecem apenas quando "Calendário de Aulas" ou "Calendário de Provas" é selecionado -->
       <div id="campos-aula" class="row g-3 d-none">
      <div class="col-md-4">
        <label class="form-label">Classe</label>
        <select name="classe" id="classe-select" class="form-select">
          <option value="">Selecione a Classe</option>
          <?php foreach ($classes as $classe): ?>
          <option value="<?= htmlspecialchars($classe) ?>"><?= htmlspecialchars($classe) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Curso</label>
        <select name="curso" id="curso-select" class="form-select">
          <option value="">Selecione o Curso</option>
          <?php foreach ($cursos as $curso): ?>
          <option value="<?= htmlspecialchars($curso) ?>"><?= htmlspecialchars($curso) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Turma</label>
        <select name="turma" id="turma-select" class="form-select">
          <option value="">Selecione primeiro a classe</option>
        </select>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Título</label>
      <input type="text" name="titulo" class="form-control" maxlength="100" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Conteúdo</label>
      <textarea name="conteudo" id="conteudo-textarea" class="form-control" rows="5" required></textarea>

      <div id="calendar-table" class="mt-4 d-none">
        <table class="table table-bordered align-middle text-center">
          <thead class="table-light">
            <tr>
              <th>Disciplina</th>
              <th>Data</th>
              <th>Hora</th>
              <th>Observações</th>
              <th>Ação</th>
            </tr>
          </thead>
          <tbody id="calendar-body"></tbody>
        </table>
        <button type="button" class="btn btn-success btn-sm mt-2" onclick="addRow('calendar-body')">+ Adicionar Linha</button>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Data do Evento (opcional)</label>
      <input type="date" name="data_evento" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary w-100 mt-4">
      <i class="bi bi-check-circle-fill me-2"></i> Cadastrar Comunicado
    </button>
  </form>
</div>

<div class="mt-5" style="margin-left: 300px; margin-right: 30px;">
  <h5 class="text-secondary">Comunicados Recentes</h5>
  <table class="table table-bordered mt-3">
    <thead class="table-light">
      <tr>
        <th>Título</th>
        <th>Tipo</th>
        <th>Destino</th>
        <th>Data</th>
        <th>Ação</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['titulo']) ?></td>
        <td><?= htmlspecialchars($row['tipo']) ?></td>
        <td>
          <?php 
          if (isset($row['classe_destino']) && isset($row['turma_destino']) && 
              !empty($row['classe_destino']) && !empty($row['turma_destino'])) {
              echo "Classe: " . htmlspecialchars($row['classe_destino']) . ", Turma: " . htmlspecialchars($row['turma_destino']);
          } else {
              echo "Geral";
          }
          ?>
        </td>
        <td><?= !empty($row['data_evento']) ? date('d/m/Y', strtotime($row['data_evento'])) : '-' ?></td>
        <td>
          <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir?')">
            <i class="bi bi-trash-fill me-1"></i> Excluir
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<footer>
  <p>&copy; 2025 Sistema Escolar - Setor Pedagógico</p>
</footer>

<script>
// Função para adicionar nova linha no calendário
function addRow(tableId) {
    const tbody = document.getElementById(tableId);
    if (!tbody) {
        console.error('Elemento tbody não encontrado com ID:', tableId);
        return;
    }
    
    const row = document.createElement('tr');
    
    row.innerHTML = `
        <td><input type="text" class="form-control disciplina" placeholder="Disciplina"></td>
        <td><input type="date" class="form-control data"></td>
        <td><input type="time" class="form-control hora"></td>
        <td><input type="text" class="form-control obs" placeholder="Observações"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remover</button></td>
    `;
    tbody.appendChild(row);
    
    // Atualizar o conteúdo após adicionar uma linha
    updateConteudo();
}

// Função para remover uma linha do calendário
function removeRow(btn) {
    btn.closest('tr').remove();
    updateConteudo();
}

// Quando o tipo de comunicado muda
document.getElementById('tipo-select').addEventListener('change', function() {
    const selected = this.value;
    const camposAula = document.getElementById('campos-aula');
    const calendarTable = document.getElementById('calendar-table');
    
    if (selected === 'Calendário de Aulas') {
        camposAula.classList.remove('d-none');
        calendarTable.classList.remove('d-none');
    } else if (selected === 'Calendário de Provas') {
        camposAula.classList.add('d-none');
        calendarTable.classList.remove('d-none');
    } else {
        camposAula.classList.add('d-none');
        calendarTable.classList.add('d-none');
    }
});

// Atualizar campo de turmas quando a classe for selecionada
document.getElementById('classe-select').addEventListener('change', function() {
    const classe = this.value;
    const turmaSelect = document.getElementById('turma-select');
    
    // Limpar as opções de turma
    turmaSelect.innerHTML = '<option value="">Carregando...</option>';
    
    if (classe) {
        // Fazer uma requisição AJAX para buscar as turmas da classe selecionada
        fetch(`get_turmas.php?classe=${classe}`)
            .then(response => response.json())
            .then(turmas => {
                turmaSelect.innerHTML = '<option value="">Selecione a Turma</option>';
                turmas.forEach(turma => {
                    const option = document.createElement('option');
                    option.value = turma.turma;
                    option.textContent = turma.turma;
                    turmaSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erro ao buscar turmas:', error);
                turmaSelect.innerHTML = '<option value="">Erro ao carregar turmas</option>';
            });
    } else {
        turmaSelect.innerHTML = '<option value="">Selecione primeiro a classe</option>';
    }
});

// Atualizar conteúdo com base na tabela do calendário
function updateConteudo() {
    const tipoSelect = document.getElementById('tipo-select');
    const conteudoTextarea = document.getElementById('conteudo-textarea');
    const calendarTable = document.getElementById('calendar-table');
    
    if (!calendarTable.classList.contains('d-none')) {
        let tabelaTexto = '';
        const rows = document.querySelectorAll('#calendar-body tr');
        
        // Para "Calendário de Aulas", incluir classe e turma
        if (tipoSelect.value === 'Calendário de Aulas') {
            const classeSelect = document.getElementById('classe-select');
            const turmaSelect = document.getElementById('turma-select');
            
            if (classeSelect.value && turmaSelect.value) {
                tabelaTexto += `Calendário para Classe: ${classeSelect.value}, Turma: ${turmaSelect.value}\n\n`;
            }
        }
        
        rows.forEach((row, i) => {
            const disciplina = row.querySelector('.disciplina').value;
            const data = row.querySelector('.data').value;
            const hora = row.querySelector('.hora').value;
            const obs = row.querySelector('.obs').value;
            
            const dataFormatada = data ? new Date(data).toLocaleDateString('pt-BR') : '';
            
            tabelaTexto += `${i + 1}) Disciplina: ${disciplina}, Data: ${dataFormatada}, Hora: ${hora}, Obs: ${obs}\n`;
        });
        
        conteudoTextarea.value = tabelaTexto;
    }
}

// Preparar o formulário para submissão
document.querySelector('form').addEventListener('submit', function(e) {
    updateConteudo();
});

// Configurar listeners para atualização de conteúdo em tempo real
document.addEventListener('DOMContentLoaded', function() {
    const calendarBody = document.getElementById('calendar-body');
    if (calendarBody) {
        calendarBody.addEventListener('input', function() {
            updateConteudo();
        });
    }
    
    // Adicionar listeners para os selects de classe e turma
    const classeSelect = document.getElementById('classe-select');
    const turmaSelect = document.getElementById('turma-select');
    
    if (classeSelect && turmaSelect) {
        classeSelect.addEventListener('change', updateConteudo);
        turmaSelect.addEventListener('change', updateConteudo);
    }
});

// Verificar se há campos para classe e turma no carregamento inicial
document.addEventListener('DOMContentLoaded', function() {
    const tipoSelect = document.getElementById('tipo-select');
    if (tipoSelect.value === 'Calendário de Aulas') {
        document.getElementById('campos-aula').classList.remove('d-none');
        document.getElementById('calendar-table').classList.remove('d-none');
    }
});
</script>

<?php
// Código para o arquivo get_turmas.php que será chamado via AJAX



























