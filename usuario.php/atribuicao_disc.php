<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$mensagem = '';

// Buscar dados para os selects
$professores = $conn->query("SELECT p.id_pessoa, p.nome FROM professor pr INNER JOIN pessoa p ON pr.id_pessoa = p.id_pessoa ORDER BY p.nome");
if (!$professores) {
    die("Erro ao buscar dados de professores: " . $conn->error);
}

$disciplinas = $conn->query("SELECT id, nome_disc FROM disciplina ORDER BY nome_disc");
if (!$disciplinas) {
    die("Erro ao buscar dados de disciplinas: " . $conn->error);
}

$anos = $conn->query("SELECT id, ano FROM anolectivo ORDER BY ano DESC");
if (!$anos) {
    die("Erro ao buscar dados de anos letivos: " . $conn->error);
}

$cursos = $conn->query("SELECT id, nome_curso FROM curso ORDER BY nome_curso");
if (!$cursos) {
    die("Erro ao buscar dados de cursos: " . $conn->error);
}

// Buscar dados para as classes
$classes = $conn->query("SELECT id, nome_classe FROM classe ORDER BY nome_classe");
if (!$classes) {
    die("Erro ao buscar dados de classes: " . $conn->error);
}

// Buscar dados para as turmas
$turmas = $conn->query("SELECT id, nome_turma FROM turma ORDER BY nome_turma");
if (!$turmas) {
    die("Erro ao buscar dados de turmas: " . $conn->error);
}

// Processar o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_prof = $_POST['id_prof'] ?? '';
    $id_disc = isset($_POST['id_disc']) ? (array) $_POST['id_disc'] : [];
    $id_anolectivo = $_POST['id_anolectivo'] ?? '';
    $id_curso = $_POST['id_curso'] ?? '';
    $id_classe = $_POST['id_classe'] ?? '';
    $id_turma = $_POST['id_turma'] ?? '';

    if ($id_prof && !empty($id_disc) && $id_anolectivo && $id_curso && $id_classe && $id_turma) {
        // Preparar a consulta para inserir várias disciplinas para o mesmo professor
        $sql = "INSERT INTO atribuicao_disc (id_prof, id_disc, id_anolectivo, id_curso, id_classe, id_turma) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($id_disc as $disciplina) {
                $stmt->bind_param("iiiiii", $id_prof, $disciplina, $id_anolectivo, $id_curso, $id_classe, $id_turma);
                if ($stmt->execute()) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errorMessage = $stmt->error;
                }
            }
            
            if ($successCount > 0 && $errorCount == 0) {
                $mensagem = "<div class='alert alert-success' role='alert'>
                                <i class='bi bi-check-circle-fill me-2'></i>
                                Disciplina(s) atribuída(s) com sucesso!
                             </div>";
            } elseif ($successCount > 0 && $errorCount > 0) {
                $mensagem = "<div class='alert alert-warning' role='alert'>
                                <i class='bi bi-exclamation-triangle-fill me-2'></i>
                                {$successCount} disciplina(s) atribuída(s) com sucesso, mas {$errorCount} falhou(aram).
                             </div>";
            } else {
                $mensagem = "<div class='alert alert-danger' role='alert'>
                                <i class='bi bi-x-circle-fill me-2'></i>
                                Erro ao atribuir disciplinas: {$errorMessage}
                             </div>";
            }
            
            $stmt->close();
        } else {
            $mensagem = "<div class='alert alert-danger' role='alert'>
                            <i class='bi bi-x-circle-fill me-2'></i>
                            Erro ao preparar a consulta: " . $conn->error . "
                         </div>";
        }
    } else {
        $mensagem = "<div class='alert alert-warning' role='alert'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        Por favor, preencha todos os campos obrigatórios.
                     </div>";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Atribuição de Disciplinas</title>
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
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


<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh; margin-left: 280px;">
<button class="toggle-sidebar d-lg-none" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Main Content -->
<div class="content-area">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-card mb-4">
                    <div class="form-header">
                        <h2><i class="bi bi-person-workspace me-2"></i>Atribuição de Disciplinas</h2>
                    </div>
                    
                    <div class="form-body">
                        <?php echo $mensagem; ?>
                        
                        <div class="alert alert-info" role="alert">
                            <div class="d-flex">
                                <div>
                                    <i class="bi bi-info-circle-fill fs-4 me-2"></i>
                                </div>
                                <div>
                                    <strong>Instruções:</strong>
                                    <p class="mb-0">Neste formulário você pode atribuir uma ou mais disciplinas a um professor específico. Selecione todos os campos obrigatórios e confirme a atribuição.</p>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" id="atribuicaoForm">
                            <div class="form-section">
                                <div class="row">
                                    <div class="col-md-12 mb-4">
                                        <label for="id_prof" class="form-label required-field">Professor</label>
                                        <select name="id_prof" id="id_prof" class="form-select select2" required>
                                            <option value="">Selecione o Professor</option>
                                            <?php while ($prof = $professores->fetch_assoc()): ?>
                                                <option value="<?= $prof['id_pessoa'] ?>"><?= $prof['nome'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="row">
                                    <div class="col-md-12 mb-4">
                                        <label for="id_disc" class="form-label required-field">Disciplinas</label>
                                        <select name="id_disc[]" id="id_disc" class="form-select select2" multiple required>
                                            <?php while ($disc = $disciplinas->fetch_assoc()): ?>
                                                <option value="<?= $disc['id'] ?>"><?= $disc['nome_disc'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle"></i> Você pode selecionar várias disciplinas mantendo a tecla Ctrl (ou Cmd) pressionada enquanto clica.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label for="id_anolectivo" class="form-label required-field">Ano Letivo</label>
                                        <select name="id_anolectivo" id="id_anolectivo" class="form-select select2" required>
                                            <option value="">Selecione o Ano Letivo</option>
                                            <?php while ($ano = $anos->fetch_assoc()): ?>
                                                <option value="<?= $ano['id'] ?>"><?= $ano['ano'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label for="id_curso" class="form-label required-field">Curso</label>
                                        <select name="id_curso" id="id_curso" class="form-select select2" required>
                                            <option value="">Selecione o Curso</option>
                                            <?php while ($curso = $cursos->fetch_assoc()): ?>
                                                <option value="<?= $curso['id'] ?>"><?= $curso['nome_curso'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label for="id_classe" class="form-label required-field">Classe</label>
                                        <select name="id_classe" id="id_classe" class="form-select select2" required>
                                            <option value="">Selecione a Classe</option>
                                            <?php while ($classe = $classes->fetch_assoc()): ?>
                                                <option value="<?= $classe['id'] ?>"><?= $classe['nome_classe'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <label for="id_turma" class="form-label required-field">Turma</label>
                                        <select name="id_turma" id="id_turma" class="form-select select2" required>
                                            <option value="">Selecione a Turma</option>
                                            <?php while ($turma = $turmas->fetch_assoc()): ?>
                                                <option value="<?= $turma['id'] ?>"><?= $turma['nome_turma'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6 mb-3">
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="limparFormulario()">
                                        <i class="bi bi-x-circle me-2"></i>Limpar Formulário
                                    </button>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-check-circle me-2"></i>Confirmar Atribuição
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center text-muted">
                    <small>&copy; <?= date('Y') ?> Instituto Politécnico "30 de Setembro". Todos os direitos reservados.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Custom JS -->
<script>
    // Initialize Select2
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
    
    // Toggle sidebar function (for mobile)
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
    }
    
    // Clear form function
    function limparFormulario() {
        document.getElementById('atribuicaoForm').reset();
        $('.select2').val(null).trigger('change');
    }
    
    // Form validation
    (function () {
        'use strict'
        
        // Fetch all forms we want to apply validation styles to
        var forms = document.querySelectorAll('.needs-validation')
        
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>
</body>
</html>