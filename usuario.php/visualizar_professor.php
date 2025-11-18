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

// Configuração da paginação
$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Termo de pesquisa
$termo_pesquisa = isset($_GET['pesquisa']) ? $conn->real_escape_string($_GET['pesquisa']) : '';

// Construir a consulta SQL
$sql_count = "
    SELECT COUNT(*) as total
    FROM professor
    INNER JOIN pessoa ON professor.id_pessoa = pessoa.id_pessoa
";

$sql = "
    SELECT professor.id_pessoa, pessoa.nome, professor.num_agente, professor.formacao, professor.ativo
    FROM professor
    INNER JOIN pessoa ON professor.id_pessoa = pessoa.id_pessoa
";

// Adicionar condição de pesquisa se houver um termo
if (!empty($termo_pesquisa)) {
    $condicao = " WHERE pessoa.nome LIKE '%$termo_pesquisa%' OR professor.num_agente LIKE '%$termo_pesquisa%' OR professor.formacao LIKE '%$termo_pesquisa%'";
    $sql_count .= $condicao;
    $sql .= $condicao;
}

// Adicionar limite e offset para paginação
$sql .= " ORDER BY pessoa.nome ASC LIMIT $registros_por_pagina OFFSET $offset";

// Executar consultas
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_registros = $row_count['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Professores</title>
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
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
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #2c3e50;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background-color: #3498db;
            color: white;
            font-weight: 500;
            border: none;
            padding: 12px;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        .table td {
            padding: 12px;
            vertical-align: middle;
            border-color: #f1f1f1;
        }
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            font-size: 0.8rem;
            border-radius: 30px;
        }
        .badge-success {
            background-color: #27ae60;
            color: white;
        }
        .badge-danger {
            background-color: #e74c3c;
            color: white;
        }
        .btn-action {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            margin: 0 3px;
        }
        .btn-activate {
            background-color: #2ecc71;
            border-color: #2ecc71;
            color: white;
        }
        .btn-activate:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            color: white;
        }
        .btn-deactivate {
            background-color: #f39c12;
            border-color: #f39c12;
            color: white;
        }
        .btn-deactivate:hover {
            background-color: #e67e22;
            border-color: #e67e22;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            border-radius: 10px;
            background-color: #fafafa;
            border: 1px dashed #ddd;
        }
        .empty-state i {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 15px;
        }
        .page-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            position: relative;
            display: inline-block;
        }
        .page-title:after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background-color: #3498db;
            bottom: -10px;
            left: 0;
        }
        /* Estilos para paginação */
        .pagination .page-item.active .page-link {
            background-color: #3498db;
            border-color: #3498db;
        }
        .pagination .page-link {
            color: #3498db;
        }
        .pagination .page-link:focus {
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
    </style>
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
    
    <a href="lançamento_notas_admin.php"><i class="bi bi-pencil-square"></i> Lançamento de Notas</a>
    <a href="listar_matriculas.php"><i class="bi bi-card-list"></i> Visualizar Matrículas</a>
    <a href="visualizar_professor.php" class="active"><i class="bi bi-person-lines-fill"></i> Visualizar Professores</a>
    <a href="pedagogico.php"><i class="bi bi-calendar-check"></i> Calendário Acadêmico</a>
    <a href="atribuicao_disc.php"><i class="bi bi-person-workspace"></i> Atribuir Disciplinas</a>
       
    <a href="atualizar_senha.php"><i class="bi bi-key"></i> Cadastrar Usuário</a>
</div>

<!-- Conteúdo Principal -->
<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh; margin-left: 270px;">
    <div class="container">
        <div class="container py-5">
            <div class="row mb-4">
                <div class="col">
                    <h2 class="page-title">Gestão de Professores</h2>
                </div>
                <div class="col-auto">
                    <a href="cadastro_professor.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Cadastrar Professor
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-badge me-2"></i>Professores Cadastrados</span>
                    <form method="GET" action="" class="d-flex">
                        <div class="input-group" style="width: 300px;">
                            <input type="text" class="form-control" name="pesquisa" placeholder="Buscar professor..." value="<?= htmlspecialchars($termo_pesquisa) ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col" width="30%">Nome do Professor</th>
                                        <th scope="col" width="15%">Nº Agente</th>
                                        <th scope="col" width="30%">Formação Acadêmica</th>
                                        <th scope="col" width="15%">Estado</th>
                                        <th scope="col" width="10%">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <?php 
                                                        $initials = strtoupper(substr($row['nome'], 0, 1));
                                                        echo $initials;
                                                        ?>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($row['nome']) ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($row['num_agente']) ?></td>
                                            <td><?= htmlspecialchars($row['formacao']) ?></td>
                                            <td>
                                                <?php if($row['ativo']): ?>
                                                    <span class="badge badge-success">
                                                        <i class="bi bi-check-circle me-1"></i>Ativo
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">
                                                        <i class="bi bi-x-circle me-1"></i>Inativo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <?php if ($row['ativo']): ?>
                                                        <a href="alterar_status_professor.php?id=<?= $row['id_pessoa'] ?>&status=0" class="btn btn-sm btn-deactivate btn-action" title="Desativar" onclick="return confirm('Deseja desativar o acesso deste professor?');">
                                                            <i class="bi bi-person-dash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="alterar_status_professor.php?id=<?= $row['id_pessoa'] ?>&status=1" class="btn btn-sm btn-activate btn-action" title="Ativar">
                                                            <i class="bi bi-person-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Exibindo <?= $result->num_rows ?> Professores
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <!-- Botão Anterior -->
                                    <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $pagina_atual - 1 ?><?= !empty($termo_pesquisa) ? '&pesquisa=' . urlencode($termo_pesquisa) : '' ?>" aria-label="Anterior">
                                            <span aria-hidden="true">Anterior</span>
                                        </a>
                                    </li>
                                    
                                    <!-- Páginas numeradas -->
                                    <?php 
                                    // Mostrar até 5 páginas
                                    $inicio_paginacao = max(1, $pagina_atual - 2);
                                    $fim_paginacao = min($total_paginas, $inicio_paginacao + 4);
                                    $inicio_paginacao = max(1, $fim_paginacao - 4);
                                    
                                    for ($i = $inicio_paginacao; $i <= $fim_paginacao; $i++): 
                                    ?>
                                        <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>">
                                            <a class="page-link" href="?pagina=<?= $i ?><?= !empty($termo_pesquisa) ? '&pesquisa=' . urlencode($termo_pesquisa) : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Botão Próximo -->
                                    <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $pagina_atual + 1 ?><?= !empty($termo_pesquisa) ? '&pesquisa=' . urlencode($termo_pesquisa) : '' ?>" aria-label="Próximo">
                                            <span aria-hidden="true">Próximo</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php else: ?>
                        <div class="empty-state p-5">
                            <i class="bi bi-person-x-fill mb-3"></i>
                            <h5>Nenhum professor encontrado</h5>
                            <p class="text-muted">
                                <?php if (!empty($termo_pesquisa)): ?>
                                    Não foram encontrados professores correspondentes à sua pesquisa.
                                    <a href="visualizar_professor.php" class="btn btn-sm btn-outline-primary mt-2">Limpar pesquisa</a>
                                <?php else: ?>
                                    Não existem professores cadastrados no sistema. Clique no botão "Adicionar Professor" para começar.
                                <?php endif; ?>
                            </p>
                            <?php if (empty($termo_pesquisa)): ?>
                                <a href="cadastro_professor.php" class="btn btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-2"></i>Adicionar Primeiro Professor
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>

<script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
// Script para manter o estado da pesquisa e paginação em todos os links
document.addEventListener('DOMContentLoaded', function() {
    // Adiciona o termo de pesquisa atual aos links de ação do professor se necessário
    const actionLinks = document.querySelectorAll('.btn-action');
    const currentSearch = '<?= !empty($termo_pesquisa) ? "&pesquisa=" . urlencode($termo_pesquisa) : "" ?>';
    const currentPage = '<?= "&pagina=" . $pagina_atual ?>';
    
    actionLinks.forEach(link => {
        const href = link.getAttribute('href');
        link.setAttribute('href', href + currentPage + currentSearch);
    });
});
</script>
</body>
</html>