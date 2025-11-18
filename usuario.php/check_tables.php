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

// Verificar se as tabelas necessárias existem
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

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = date('Y-m-d');
    $id_ano = $_POST['id_ano'];
    $id_periodo = $_POST['id_periodo'];
    $id_sala = $_POST['id_sala'];
    $id_turma = $_POST['id_turma'];
   
    if (isset($_POST['alunos'])) {
        foreach ($_POST['alunos'] as $id_aluno) {
            // Verificar se o aluno já está matriculado na mesma turma/ano/período
            $verificar = $conn->prepare("SELECT id FROM matricula WHERE id_pessoa = ? AND id_ano = ? AND id_periodo = ? AND id_turma = ?");
            $verificar->bind_param("iiii", $id_aluno, $id_ano, $id_periodo, $id_turma);
            $verificar->execute();
            $result = $verificar->get_result();
            
            if ($result->num_rows > 0) {
                $msg = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                            Um ou mais alunos já estão matriculados nesta turma!
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
                continue; // Pula este aluno e vai para o próximo
            }
            
            $sql = "INSERT INTO matricula (data, id_pessoa, id_ano, id_periodo, id_sala, id_turma)
            VALUES (?, ?, ?, ?, ?, ?)";
                        
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siiiii", $data, $id_aluno, $id_ano, $id_periodo, $id_sala, $id_turma);

            if ($stmt->execute()) {
                $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            Matrícula realizada com sucesso!
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
            } else {
                $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            Erro ao matricular: " . $stmt->error . "
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
            }
            $stmt->close();
        }
    } else {
        $msg = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                    Nenhum aluno selecionado!
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
}

// Função para gerar os options dos selects
function getOptions($conn, $query, $valueField, $textField) {
    $result = $conn->query($query);
    $options = "";
    if ($result && $result !== false && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $options .= "<option value='{$row[$valueField]}'>{$row[$textField]}</option>";
        }
    } else {
        // Registra o erro para debug
        error_log("Query error in getOptions: " . $conn->error . " - SQL: " . $query);
    }
    return $options;
}

// Função para calcular idade a partir da data de nascimento
function calcularIdade($data_nascimento) {
    $hoje = new DateTime();
    $nascimento = new DateTime($data_nascimento);
    $idade = $hoje->diff($nascimento);
    return $idade->y;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Matrícula de Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- CSS styles omitted for brevity -->
</head>
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
<div class="d-flex justify-content-center align-items-center" style="min-height: 10vh; margin-left: 270px;">
    <div class="container">
        <h2 class="mb-4">Matrícula de Aluno</h2>
        <?php if (isset($msg)) echo $msg; ?>
        <?php echo $debug_info; ?>
        
        <!-- Informações de Diagnóstico -->
        <div class="bg-light p-3 mb-4 rounded">
            <h5>Informações do Sistema:</h5>
            <ul>
                <li>Status da conexão com o banco de dados: <?php echo $conn->connect_errno ? 'Falha' : 'OK'; ?></li>
                <li>Nome do banco de dados: <?php echo $dbname; ?></li>
                <li>
                    <button class="btn btn-sm btn-info" type="button" onclick="testDatabaseTables()">
                        Verificar Tabelas do Banco
                    </button>
                    <div id="tableInfo" class="mt-2"></div>
                </li>
            </ul>
        </div>

        <form method="POST" class="row g-3 bg-white p-4 rounded shadow-sm">
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
                    // Exibindo todos os alunos cadastrados com idade ao invés da data de nascimento
                    $alunos_query = "SELECT a.id_pessoa, p.nome, p.data_nascimento 
                                    FROM aluno a 
                                    JOIN pessoa p ON a.id_pessoa = p.id_pessoa";
                    $alunos = $conn->query($alunos_query);
                    
                    if (!$alunos) {
                        echo "<tr><td colspan='4' class='text-center text-danger'>Erro na consulta: " . $conn->error . "</td></tr>";
                        echo "<tr><td colspan='4' class='text-center'>SQL executado: " . $alunos_query . "</td></tr>";
                        
                        // Verificar se as tabelas têm dados
                        echo "<tr><td colspan='4' class='text-center'><strong>Verificando tabelas...</strong></td></tr>";
                        
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
                    } else if ($alunos->num_rows == 0) {
                        echo "<tr><td colspan='4' class='text-center'>Nenhum aluno encontrado. Verifique se há registros nas tabelas 'aluno' e 'pessoa'.</td></tr>";
                    } else {
                        while ($a = $alunos->fetch_assoc()) {
                            $idade = calcularIdade($a['data_nascimento']);
                            echo "<tr>
                                    <td><input type='checkbox' name='alunos[]' value='{$a['id_pessoa']}'></td>
                                    <td>{$a['id_pessoa']}</td>
                                    <td>{$a['nome']}</td>
                                    <td>{$idade} anos</td>
                                  </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>

            <!-- Seletor de dados da matrícula -->
            <div class="row g-3">
                <!-- Ano Letivo -->
                <div class="col-md-6">
                    <label for="id_ano" class="form-label">Ano Letivo:</label>
                    <select name="id_ano" class="form-select" required>
                        <option value="">Selecione</option>
                        <?= getOptions($conn, "SELECT id, ano FROM anolectivo", "id", "ano") ?>
                    </select>
                </div>

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
                <a href="Cadastro_aluno.php" class="btn btn-success">Cadastrar Novo Aluno</a>
            </div>
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