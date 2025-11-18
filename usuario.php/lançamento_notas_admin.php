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

// Verificar se o usuário é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 'admin') {
    header("Location: login.php");
    exit();
}

$filtros = [
    'id_disc' => $_POST['id_disc'] ?? null,
    'id_curso' => $_POST['id_curso'] ?? null,
    'id_classe' => $_POST['id_classe'] ?? null,
    'id_trimestre' => $_POST['id_trimestre'] ?? null,
    'id_turma' => $_POST['id_turma'] ?? null,
    'id_professor' => $_POST['id_professor'] ?? null,
];

// Lançamento de notas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lancar_notas'])) {
    $notas = $_POST['nota'] ?? [];
    $data_lancamento = date('Y-m-d H:i:s');
    $id_professor = $_POST['id_professor'] ?? 0;

    foreach ($notas as $id_aluno => $provas) {
        foreach ($provas as $id_prova => $nota) {
            if (!empty($nota)) {
                $stmt = $conn->prepare("SELECT id FROM minipauta WHERE id_aluno = ? AND id_prova = ? AND id_trimestre = ? AND id_disc = ?");
                $stmt->bind_param("iiii", $id_aluno, $id_prova, $filtros['id_trimestre'], $filtros['id_disc']);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $stmt = $conn->prepare("UPDATE minipauta SET nota = ?, data_lancamento = ? WHERE id_aluno = ? AND id_prova = ? AND id_trimestre = ? AND id_disc = ?");
                    $stmt->bind_param("dsiiii", $nota, $data_lancamento, $id_aluno, $id_prova, $filtros['id_trimestre'], $filtros['id_disc']);
                } else {
                    $stmt = $conn->prepare("INSERT INTO minipauta (nota, data_lancamento, id_prova, id_professor, id_trimestre, id_aluno, id_disc) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("dsiiiii", $nota, $data_lancamento, $id_prova, $id_professor, $filtros['id_trimestre'], $id_aluno, $filtros['id_disc']);
                }
                $stmt->execute();
            }
        }
    }

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Buscar todas as turmas (sem restrição por professor)
$turmas = [];
$sql = "SELECT DISTINCT t.id, t.nome_turma, a.id_curso, c.nome_curso, a.id_classe, cl.nome_classe
        FROM turma t
        JOIN aluno a ON t.id = a.id_turma
        JOIN curso c ON a.id_curso = c.id
        JOIN classe cl ON a.id_classe = cl.id
        GROUP BY t.id, a.id_curso, a.id_classe
        ORDER BY t.nome_turma";

$result = $conn->query($sql);

if ($result === false) {
    die("Erro ao buscar turmas: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $turmas[] = $row;
}

// Buscar todos os cursos
$cursos = [];
$sql = "SELECT id, nome_curso FROM curso ORDER BY nome_curso";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $cursos[] = $row;
}

// Buscar todas as classes
$classes = [];
$sql = "SELECT id, nome_classe FROM classe ORDER BY nome_classe";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

// Buscar todas as disciplinas
$disciplinas = [];
$sql = "SELECT id, nome_disc FROM disciplina ORDER BY nome_disc";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $disciplinas[] = $row;
}

// Buscar todos os professores
$professores = [];
$sql = "SELECT p.id_pessoa, p.nome 
        FROM pessoa p 
        JOIN professor pr ON p.id_pessoa = pr.id_pessoa
        ORDER BY p.nome";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $professores[] = $row;
}

// Buscar trimestres
$trimestres = [];
$result = $conn->query("SELECT id, num_tri FROM trimestre ORDER BY num_tri");
while ($row = $result->fetch_assoc()) {
    $trimestres[] = $row;
}

function calcularMedia($notas) {
    if (empty($notas)) return null;
    return array_sum($notas) / count($notas);
}

// Verificar se a requisição é AJAX para carregar tabela dinâmica
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'carregar_tabela') {
    $id_turma = $_POST['id_turma'] ?? 0;
    $id_curso = $_POST['id_curso'] ?? 0;
    $id_classe = $_POST['id_classe'] ?? 0;
    $id_trimestre = $_POST['id_trimestre'] ?? 0;
    $id_disc = $_POST['id_disc'] ?? 0;
    $id_professor = $_POST['id_professor'] ?? 0;
    
    // Verificar se todos os filtros necessários foram fornecidos
    if (empty($id_turma) || empty($id_curso) || empty($id_classe) || empty($id_trimestre) || empty($id_disc) || empty($id_professor)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        exit();
    }
    
    // Buscar alunos da turma selecionada
    $sql = "SELECT a.id_pessoa, p.nome, p.sexo 
            FROM aluno a
            JOIN pessoa p ON a.id_pessoa = p.id_pessoa
            WHERE a.id_turma = ? AND a.id_curso = ? AND a.id_classe = ?
            ORDER BY p.nome";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $id_turma, $id_curso, $id_classe);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $alunos = [];
    while ($row = $result->fetch_assoc()) {
        $alunos[$row['id_pessoa']] = $row;
    }
    
    // Buscar notas existentes
    $notas_existentes = [];
    if (!empty($alunos)) {
        $alunos_ids = array_keys($alunos);
        $placeholders = implode(',', array_fill(0, count($alunos_ids), '?'));
        
        $sql = "SELECT id_aluno, id_prova, nota 
                FROM minipauta 
                WHERE id_disc = ? AND id_trimestre = ? AND id_aluno IN ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        $types = 'ii' . str_repeat('i', count($alunos_ids));
        $params = array_merge([$id_disc, $id_trimestre], $alunos_ids);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $notas_existentes[$row['id_aluno']][$row['id_prova']] = $row['nota'];
        }
    }
    
    // Buscar informações do curso e classe
    $stmt = $conn->prepare("SELECT nome_curso FROM curso WHERE id = ?");
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $result = $stmt->get_result();
    $curso_nome = $result->fetch_assoc()['nome_curso'] ?? 'Desconhecido';
    
    $stmt = $conn->prepare("SELECT nome_classe FROM classe WHERE id = ?");
    $stmt->bind_param("i", $id_classe);
    $stmt->execute();
    $result = $stmt->get_result();
    $classe_nome = $result->fetch_assoc()['nome_classe'] ?? 'Desconhecida';
    
    $stmt = $conn->prepare("SELECT nome_turma FROM turma WHERE id = ?");
    $stmt->bind_param("i", $id_turma);
    $stmt->execute();
    $result = $stmt->get_result();
    $turma_nome = $result->fetch_assoc()['nome_turma'] ?? 'Desconhecida';
    
    $stmt = $conn->prepare("SELECT nome_disc FROM disciplina WHERE id = ?");
    $stmt->bind_param("i", $id_disc);
    $stmt->execute();
    $result = $stmt->get_result();
    $disciplina_nome = $result->fetch_assoc()['nome_disc'] ?? 'Desconhecida';
    
    $stmt = $conn->prepare("SELECT num_tri FROM trimestre WHERE id = ?");
    $stmt->bind_param("i", $id_trimestre);
    $stmt->execute();
    $result = $stmt->get_result();
    $trimestre_num = $result->fetch_assoc()['num_tri'] ?? 'Desconhecido';
    
    $stmt = $conn->prepare("SELECT p.nome FROM pessoa p JOIN professor pr ON p.id_pessoa = pr.id_pessoa WHERE p.id_pessoa = ?");
    $stmt->bind_param("i", $id_professor);
    $stmt->execute();
    $result = $stmt->get_result();
    $professor_nome = $result->fetch_assoc()['nome'] ?? 'Desconhecido';
    
    // Gerar HTML da tabela
    ob_start();
    ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4>Lançamento de Notas (Administrador)</h4>
                <span><?= count($alunos) ?> alunos encontrados</span>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <h5>Curso: <?= $curso_nome ?> | Classe: <?= $classe_nome ?> | Turma: <?= $turma_nome ?></h5>
                <h5>Disciplina: <?= $disciplina_nome ?> | <?= $trimestre_num ?>º Trimestre | Professor: <?= $professor_nome ?></h5>
            </div>
            
            <form method="POST" id="form-notas">
                <input type="hidden" name="id_disc" value="<?= $id_disc ?>">
                <input type="hidden" name="id_trimestre" value="<?= $id_trimestre ?>">
                <input type="hidden" name="id_curso" value="<?= $id_curso ?>">
                <input type="hidden" name="id_classe" value="<?= $id_classe ?>">
                <input type="hidden" name="id_turma" value="<?= $id_turma ?>">
                <input type="hidden" name="id_professor" value="<?= $id_professor ?>">
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" rowspan="2">Nº</th>
                                <th class="text-center" rowspan="2">Nome</th>
                                <th class="text-center" rowspan="2">Sexo</th>
                                <th class="text-center" colspan="3">Mini-Pauta</th>
                                <th class="text-center" rowspan="2">Média</th>
                                <th class="text-center" rowspan="2">Aproveitamento</th>
                            </tr>
                            <tr>
                                <th class="text-center">MAC</th>
                                <th class="text-center">Prova Prof.</th>
                                <th class="text-center">Prova Trim.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $contador = 1; ?>
                            <?php foreach ($alunos as $aluno): 
                                $notas = $notas_existentes[$aluno['id_pessoa']] ?? [];
                                $nota_1 = $notas[1] ?? '';
                                $nota_2 = $notas[2] ?? '';
                                $nota_3 = $notas[3] ?? '';
                                
                                // Calcular média apenas se todas as notas existirem
                                $notas_array = [];
                                if ($nota_1 !== '') $notas_array[] = $nota_1;
                                if ($nota_2 !== '') $notas_array[] = $nota_2;
                                if ($nota_3 !== '') $notas_array[] = $nota_3;
                                
                                $media = empty($notas_array) ? null : array_sum($notas_array) / count($notas_array);
                                $situacao = $media !== null ? ($media >= 10 ? 'Com Aproveitamento' : 'Sem Aproveitamento') : 'N/A';
                                $classe_situacao = $situacao == 'Com Aproveitamento' ? 'text-success' : ($situacao == 'Sem Aproveitamento' ? 'text-danger' : '');
                            ?>
                            <tr>
                                <td class="text-center"><?= $contador++ ?></td>
                                <td><?= $aluno['nome'] ?></td>
                                <td class="text-center"><?= $aluno['sexo'] ?></td>
                                <td>
                                    <input type="number" name="nota[<?= $aluno['id_pessoa'] ?>][1]" value="<?= $nota_1 ?>" 
                                           class="form-control form-control-sm nota-input" min="0" max="20" step="0.1">
                                </td>
                                <td>
                                    <input type="number" name="nota[<?= $aluno['id_pessoa'] ?>][2]" value="<?= $nota_2 ?>" 
                                           class="form-control form-control-sm nota-input" min="0" max="20" step="0.1">
                                </td>
                                <td>
                                    <input type="number" name="nota[<?= $aluno['id_pessoa'] ?>][3]" value="<?= $nota_3 ?>" 
                                           class="form-control form-control-sm nota-input" min="0" max="20" step="0.1">
                                </td>
                                <td class="text-center"><strong><?= $media !== null ? number_format($media, 1) : '-' ?></strong></td>
                                <td class="text-center <?= $classe_situacao ?>"><?= $situacao ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="lancar_notas" class="btn btn-success">
                    <i class="bi bi-save"></i> Lançar Notas
                </button>
            </form>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lançamento de Notas - Administrador</title>
    <link href="bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
        
        
      
           
        
    

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-header">
            <h5>Painel de Administração</h5>
        </div>
        
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
    </div
        
        <div class="sidebar-footer">
            <a href="logout.php" class="btn btn-outline-secondary w-100"><i class="bi bi-box-arrow-left me-2"></i>Encerrar Sessão</a>
        </div>
    </aside>
    <div class="d-flex justify-content- align-items-" style="min-height: 10vh; margin-left: 290px;">
    <div class="container mt-4 main-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                    </div>
                    <div class="card-body">
                        <p>Como administrativo, você pode lançar notas para qualquer turma, classe e curso. Selecione os critérios abaixo:</p>
                    </div>
                </div>
            </div>
        </div>

      <div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5><i class="bi bi-grid-3x3"></i> Selecione uma Turma</h5>
    </div>
    <div class="card-body">
        <?php if (count($turmas) > 0): ?>
        <div class="row mb-3">
            <?php foreach ($turmas as $turma): ?>
                <div class="col-md-3 mb-2">
                    <button type="button" class="btn btn-outline-primary w-100 turma-btn" 
                            data-id="<?= $turma['id'] ?>" 
                            data-nome="<?= $turma['nome_turma'] ?>"
                            data-curso="<?= $turma['id_curso'] ?>"
                            data-classe="<?= $turma['id_classe'] ?>">
                        <i class="bi bi-people-fill"></i>
                        <?= $turma['nome_turma'] ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-turmas-message">
            <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
            <h4 class="mt-2">Nenhuma turma encontrada</h4>
            <p>Não há turmas cadastradas no sistema no momento.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<div id="filtro-container" style="display: none;">
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 id="turma-selecionada-titulo"></h5>
        </div>
        <div class="card-body">
            <form id="form-filtros" class="row g-3">
                <input type="hidden" id="id_turma" name="id_turma" value="">
                <input type="hidden" name="ajax_action" value="carregar_tabela">
                
                <div class="col-md-6">
                    <label for="id_curso" class="form-label fw-bold">Curso:</label>
                    <select class="form-select" id="id_curso" name="id_curso" required>
                        <option value="">Selecione um Curso</option>
                        <?php foreach ($cursos as $curso): ?>
                            <option value="<?= $curso['id'] ?>"><?= $curso['nome_curso'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="id_classe" class="form-label fw-bold">Classe:</label>
                    <select class="form-select" id="id_classe" name="id_classe" required>
                        <option value="">Selecione uma Classe</option>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?= $classe['id'] ?>"><?= $classe['nome_classe'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="id_disc" class="form-label fw-bold">Disciplina:</label>
                    <select class="form-select" id="id_disc" name="id_disc" required>
                        <option value="">Selecione uma Disciplina</option>
                        <?php foreach ($disciplinas as $disciplina): ?>
                            <option value="<?= $disciplina['id'] ?>"><?= $disciplina['nome_disc'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="id_trimestre" class="form-label fw-bold">Trimestre:</label>
                    <select class="form-select" id="id_trimestre" name="id_trimestre" required>
                        <option value="">Selecione um Trimestre</option>
                        <?php foreach ($trimestres as $trimestre): ?>
                            <option value="<?= $trimestre['id'] ?>"><?= $trimestre['num_tri'] ?>º Trimestre</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="id_professor" class="form-label fw-bold">Professor Responsável:</label>
                    <select class="form-select" id="id_professor" name="id_professor" required>
                        <option value="">Selecione um Professor</option>
                        <?php foreach ($professores as $professor): ?>
                            <option value="<?= $professor['id_pessoa'] ?>"><?= $professor['nome'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary" id="btnBuscarAlunos">
                        <i class="bi bi-search"></i> Buscar Alunos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="tabela-alunos-container" class="mt-4">
    </div>
        
     
        </div>
        
        <div id="resultado-container"></div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Esconder o filtro-container e o tabela-alunos-container inicialmente
    $('#filtro-container').hide();
    $('#tabela-alunos-container').hide();

    // Quando um botão de turma é clicado
    $('.turma-btn').on('click', function() {
        // Remover a classe 'active' de todos os botões de turma
        $('.turma-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
        // Adicionar a classe 'active' e mudar a cor do botão clicado
        $(this).addClass('active btn-primary').removeClass('btn-outline-primary');

        const idTurma = $(this).data('id');
        const nomeTurma = $(this).data('nome');
        const idCurso = $(this).data('curso');
        const idClasse = $(this).data('classe');

        // Preencher os campos ocultos e os selects
        $('#id_turma').val(idTurma);
        $('#turma-selecionada-titulo').text(`Turma Selecionada: ${nomeTurma}`);
        
        // Preencher os selects de Curso e Classe com os valores da turma selecionada
        // Isso pode ser útil se a turma já tiver um curso/classe padrão associado,
        // mas o usuário ainda pode alterar nos dropdowns.
        // Se você não quer que eles sejam preenchidos automaticamente, pode remover estas linhas.
        $('#id_curso').val(idCurso);
        $('#id_classe').val(idClasse);

        // Mostrar o filtro-container
        $('#filtro-container').show();
        $('#tabela-alunos-container').hide(); // Esconde a tabela anterior ao selecionar nova turma
    });

    // Evento de submit do formulário de filtros
    $('#form-filtros').on('submit', function(e) {
        e.preventDefault(); // Evita o envio padrão do formulário

        // Coleta os dados do formulário
        const formData = $(this).serialize();

        // Faz a requisição AJAX
        $.ajax({
            url: 'lançamento_notas_admin.php', // O mesmo arquivo PHP
            type: 'POST',
            data: formData,
            dataType: 'json', // Espera uma resposta JSON
            beforeSend: function() {
                // Opcional: Mostrar um spinner de carregamento
                $('#tabela-alunos-container').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>');
                $('#tabela-alunos-container').show();
            },
            success: function(response) {
                if (response.success) {
                    $('#tabela-alunos-container').html(response.html);
                    $('#tabela-alunos-container').show();
                } else {
                    $('#tabela-alunos-container').html(`<div class="alert alert-danger">${response.message}</div>`);
                    $('#tabela-alunos-container').show();
                }
            },
            error: function(xhr, status, error) {
                console.error("Erro na requisição AJAX:", status, error);
                console.log(xhr.responseText); // Para depuração
                $('#tabela-alunos-container').html('<div class="alert alert-danger">Ocorreu um erro ao carregar os alunos. Tente novamente.</div>');
                $('#tabela-alunos-container').show();
            }
        });
    });
});
</script>
</body>
</html>