<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexão com o banco de dados
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Erro de conexão com o banco de dados: " . $conn->connect_error);
}

// Verificação de tabelas
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array(MYSQLI_NUM)) {
    $tables[] = $row[0];
}

$debug_info = "";
if (!in_array('aluno', $tables)) {
    $debug_info .= "<div class='alert alert-danger'>A tabela 'aluno' não existe no banco de dados!</div>";
}
if (!in_array('pessoa', $tables)) {
    $debug_info .= "<div class='alert alert-danger'>A tabela 'pessoa' não existe no banco de dados!</div>";
}

// PROCESSAMENTO
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = date('Y-m-d');
    $id_periodo = $_POST['id_periodo'];
    $id_sala = $_POST['id_sala'];
    $id_turma = $_POST['id_turma'];

    if (isset($_POST['alunos'])) {
        foreach ($_POST['alunos'] as $id_aluno) {
            // Atualizar ou inserir apenas o id_turma na tabela aluno
            $checkAluno = $conn->prepare("SELECT id_pessoa FROM aluno WHERE id_pessoa = ?");
            $checkAluno->bind_param("i", $id_aluno);
            $checkAluno->execute();
            $checkResult = $checkAluno->get_result();

            if ($checkResult->num_rows > 0) {
                $updateAluno = $conn->prepare("UPDATE aluno SET id_turma = ? WHERE id_pessoa = ?");
                if ($updateAluno) {
                    $updateAluno->bind_param("ii", $id_turma, $id_aluno);
                    $updateAluno->execute();
                }
            } else {
                $insertAluno = $conn->prepare("INSERT INTO aluno (id_pessoa, id_turma) VALUES (?, ?)");
                $insertAluno->bind_param("ii", $id_aluno, $id_turma);
                $insertAluno->execute();
            }

            // Verificar se já existe matrícula
            $verificar = $conn->prepare("SELECT id FROM matricula WHERE id_pessoa = ?");
            $verificar->bind_param("i", $id_aluno);
            $verificar->execute();
            $result = $verificar->get_result();

            if ($result->num_rows > 0) {
                // Atualizar matrícula existente
                $updateMatricula = $conn->prepare("
                    UPDATE matricula 
                    SET id_periodo = ?, id_sala = ?, id_turma = ? 
                    WHERE id_pessoa = ?
                ");
                if ($updateMatricula) {
                    $updateMatricula->bind_param("iiii", $id_periodo, $id_sala, $id_turma, $id_aluno);
                    $updateMatricula->execute();
                }
            } else {
                // Inserir nova matrícula
                $stmt = $conn->prepare("
                    INSERT INTO matricula (data, id_pessoa, id_periodo, id_sala, id_turma)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("siiii", $data, $id_aluno, $id_periodo, $id_sala, $id_turma);
                $stmt->execute();
            }
        }

        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    Atribuição de turma realizada com sucesso!
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                    Nenhum aluno selecionado!
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
}

// Função para montar options
function getOptions($conn, $query, $valueField, $textField) {
    $result = $conn->query($query);
    $options = "";
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $options .= "<option value='{$row[$valueField]}'>{$row[$textField]}</option>";
        }
    }
    return $options;
}

// Calcular idade
function calcularIdade($data_nascimento) {
    if (empty($data_nascimento) || $data_nascimento == '0000-00-00') return 'N/A';
    try {
        $hoje = new DateTime();
        $nascimento = new DateTime($data_nascimento);
        $idade = $hoje->diff($nascimento);
        return $idade->y;
    } catch (Exception $e) {
        return 'N/A';
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Matrícula de Aluno</title>
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    <!-- CSS styles omitted for brevity -->
</head>
<style>
        :root {
            --primary: #1a2f5e;
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
            transition: all var(--transition-speed);
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

<!-- Menu Lateral -->
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

<!-- Conteúdo Principal -->
<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh; margin-left: 270px;">
      
        <?php if (isset($msg)) echo $msg; ?>
        <?php echo $debug_info; ?>
        
        <form method="POST" class="row g-3 bg-white p-4 rounded shadow-sm">
        <h2 class="mb-4">Matrícula de Aluno</h2>
        <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Idade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Verificar a estrutura da tabela pessoa
                    $columns_query = "SHOW COLUMNS FROM pessoa";
                    $columns_result = $conn->query($columns_query);
                    $has_data_nascimento = false;
                    $date_column = '';
                    
                    if ($columns_result) {
                        while ($column = $columns_result->fetch_assoc()) {
                            if ($column['Field'] == 'data_nascimento') {
                                $has_data_nascimento = true;
                                break;
                            }
                            // Procurar por um campo alternativo que possa representar a data de nascimento
                            if (strpos(strtolower($column['Field']), 'data') !== false || 
                                strpos(strtolower($column['Field']), 'nasc') !== false) {
                                $date_column = $column['Field'];
                            }
                        }
                    }
                    
                    // Adaptar a consulta com base na estrutura da tabela
                    if ($has_data_nascimento) {
                        $alunos_query = "SELECT a.id_pessoa, p.nome, p.data_nascimento 
                                        FROM aluno a 
                                        JOIN pessoa p ON a.id_pessoa = p.id_pessoa";
                    } else if (!empty($date_column)) {
                        $alunos_query = "SELECT a.id_pessoa, p.nome, p.{$date_column} as data_nascimento 
                                        FROM aluno a 
                                        JOIN pessoa p ON a.id_pessoa = p.id_pessoa";
                    } else {
                        // Se não encontrar coluna de data de nascimento, exibir sem a idade
                        $alunos_query = "SELECT a.id_pessoa, p.nome 
                                        FROM aluno a 
                                        JOIN pessoa p ON a.id_pessoa = p.id_pessoa";
                    }
                    
                    $alunos = $conn->query($alunos_query);
                    
                    if (!$alunos) {
                        echo "<tr><td colspan='4' class='text-center text-danger'>Erro na consulta: " . $conn->error . "</td></tr>";
                        echo "<tr><td colspan='4' class='text-center'>SQL executado: " . $alunos_query . "</td></tr>";
                        
                        // Verificar se as tabelas têm dados
                        echo "<tr><td colspan='4' class='text-center'><strong>Verificando tabelas...</strong></td></tr>";
                        
                        // Listar colunas da tabela pessoa
                        echo "<tr><td colspan='4' class='text-center'><strong>Colunas da tabela pessoa:</strong></td></tr>";
                        $columns_query = "SHOW COLUMNS FROM pessoa";
                        $columns_result = $conn->query($columns_query);
                        if ($columns_result) {
                            $columns_list = "";
                            while ($column = $columns_result->fetch_assoc()) {
                                $columns_list .= $column['Field'] . ", ";
                            }
                            echo "<tr><td colspan='4' class='text-center'>" . rtrim($columns_list, ", ") . "</td></tr>";
                        }
                        
                        $count_pessoa = $conn->query("SELECT COUNT(*) as total FROM pessoa");
                        if ($count_pessoa) {
                            $pessoa_total = $count_pessoa->fetch_assoc()['total'];
                            echo "<tr><td colspan='4' class='text-center'>Tabela 'pessoa' contém $pessoa_total registros</td></tr>";
                        } else {
                            echo "<tr><td colspan='4' class='text-center text-danger'>Erro ao verificar tabela 'pessoa': " . $conn->error . "</td></tr>";
                        }
                        
                        $count_aluno = $conn->query("SELECT COUNT(*) as total FROM aluno");
                        if ($count_aluno) {
                            $aluno_total = $count_aluno->fetch_assoc()['total']; 
                            echo "<tr><td colspan='4' class='text-center'>Tabela 'aluno' contém $aluno_total registros</td></tr>";
                        } else {
                            echo "<tr><td colspan='4' class='text-center text-danger'>Erro ao verificar tabela 'aluno': " . $conn->error . "</td></tr>";
                        }
                        
                        // Tentar consulta simplificada apenas com o join
                        echo "<tr><td colspan='4' class='text-center'><strong>Tentando consulta simples...</strong></td></tr>";
                        $simple_query = "SELECT a.id_pessoa, p.nome FROM aluno a JOIN pessoa p ON a.id_pessoa = p.id_pessoa";
                        $simple_result = $conn->query($simple_query);
                        if (!$simple_result) {
                            echo "<tr><td colspan='4' class='text-center text-danger'>Erro na consulta simples: " . $conn->error . "</td></tr>";
                        } else {
                            echo "<tr><td colspan='4' class='text-center text-success'>Consulta simples funcionou! Encontrou " . $simple_result->num_rows . " alunos</td></tr>";
                        }
                    } else if ($alunos->num_rows == 0) {
                        echo "<tr><td colspan='4' class='text-center'>Nenhum aluno encontrado. Verifique se há registros nas tabelas 'aluno' e 'pessoa'.</td></tr>";
                    } else {
                        while ($a = $alunos->fetch_assoc()) {
                            echo "<tr>
                                    <td><input type='checkbox' name='alunos[]' value='{$a['id_pessoa']}'></td>
                                    <td>{$a['id_pessoa']}</td>
                                    <td>{$a['nome']}</td>";
                            
                            // Se tiver data de nascimento, mostrar idade
                            if (isset($a['data_nascimento'])) {
                                $idade = calcularIdade($a['data_nascimento']);
                                echo "<td>{$idade} anos</td>";
                            } else {
                                echo "<td>N/A</td>";
                            }
                            
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>

           

                <!-- Período -->
                <div class="col-md-6">
                    <label for="id_periodo" class="form-label">Período:</label>
                    <select name="id_periodo" class="form-select" required>
                        <option value="">Selecione</option>
                        <?= getOptions($conn, "SELECT id, nome FROM periodo", "id", "nome") ?>
                    </select>
                </div>

                <!-- Sala -->
                <div class="col-md-6">
                    <label for="id_sala" class="form-label">Sala:</label>
                    <select name="id_sala" class="form-select" required>
                        <option value="">Selecione</option>
                        <?= getOptions($conn, "SELECT id, num_sala FROM sala", "id", "num_sala") ?>
                    </select>
                </div>

                <!-- Turma (com radio buttons) -->
                <div class="col-md-6">
                    <label class="form-label">Turma:</label>
                    <div class="d-flex flex-wrap">
                        <?php
                        $turmas = $conn->query("SELECT id, nome_turma FROM turma");
                        if ($turmas && $turmas->num_rows > 0) {
                            while ($t = $turmas->fetch_assoc()) {
                                echo "<div class='form-check turma-radio mx-2'>
                                        <input class='form-check-input' type='radio' name='id_turma' value='{$t['id']}' id='turma{$t['id']}' required>
                                        <label class='form-check-label' for='turma{$t['id']}'>
                                            {$t['nome_turma']}
                                        </label>
                                      </div>";
                            }
                        } else {
                            echo "<div class='alert alert-warning'>Nenhuma turma disponível</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Botões -->
            <div class="col-12 d-flex justify-content-between mt-4">
                <button type="submit" class="btn btn-primary px-4">Matricular</button>
                <button type="submit" class="btn btn-primary px-4">Matricular</button>            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Seleciona ou desmarca todos os checkboxes
    document.getElementById('selectAll').addEventListener('click', function (e) {
        let checkboxes = document.querySelectorAll('input[name="alunos[]"]');
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = e.target.checked;
        });
    });
    
    // Função para testar as tabelas do banco de dados
    function testDatabaseTables() {
        fetch('check_tables.php')
            .then(response => response.json())
            .then(data => {
                let html = '<ul class="list-group">';
                if (data.success) {
                    Object.keys(data.tables).forEach(table => {
                        let status = data.tables[table] ? 
                            '<span class="text-success">✓ Existe</span>' : 
                            '<span class="text-danger">✗ Não existe</span>';
                        html += `<li class="list-group-item">Tabela '${table}': ${status}</li>`;
                    });
                } else {
                    html += `<li class="list-group-item text-danger">${data.message}</li>`;
                }
                html += '</ul>';
                document.getElementById('tableInfo').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('tableInfo').innerHTML = 
                    `<div class="alert alert-danger">Erro ao verificar tabelas: ${error}</div>`;
            });
    }
</script>

</body>
</html>