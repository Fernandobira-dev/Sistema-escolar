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

// Buscar todas as disciplinas
$disciplina_result = $conn->query("SELECT nome_disc, descricao FROM disciplina");
if (!$disciplina_result) {
    die("Erro na consulta de disciplina: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplinas Disponíveis</title>
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css"></head>
<body>
<div class="sidebar tema-azul" id="sidebarMenu">
    <nav class="nav flex-column p-3">
        <a href="Estudante.php" class="nav-link"><i class="bi bi-house me-2"></i>Início</a>

        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                <i class="bi bi-person me-2"></i>Estudante
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="aaa.php"><i class="bi bi-house-door me-2"></i>Início</a></li>
                <li><a class="dropdown-item" href="altera_senha.php"><i class="bi bi-key me-2"></i>Modificar Senha</a></li>
                <li><a class="dropdown-item" href="Dados_pessoais.php"><i class="bi bi-info-circle me-2"></i>Ver Dados Pessoais</a></li>
                <li><a class="dropdown-item" href="reclamacao.php"><i class="bi bi-chat-dots me-2"></i>Reclamação</a></li>
            </ul>
        </div>

        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                <i class="bi bi-building me-2"></i>Secretaria
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="visualizar_notas.php"><i class="bi bi-file-earmark-bar-graph me-2"></i>Ver Notas</a></li>
                <li><a class="dropdown-item" href="atribuiçao.php"><i class="bi bi-journal-text me-2"></i>Disciplinas</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-calendar-event me-2"></i>Calendário</a></li>
                <li><a class="dropdown-item" href="ver_matricula.php"><i class="bi bi-card-checklist me-2"></i>Ver Matrícula</a></li>
            </ul>
        </div>

        <!-- Oferta Letiva -->
        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                <i class="bi bi-book me-2"></i>Oferta Letiva
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="informática.html"><i class="bi bi-laptop me-2"></i>Informática</a></li>
                <li><a class="dropdown-item" href="electricidade.html"><i class="bi bi-lightning-charge me-2"></i>Electricidade</a></li>
            </ul>
        </div>

        <a href="duvidas.html" class="nav-link"><i class="bi bi-question-circle me-2"></i>Dúvidas?</a>
        <a href="logout.php" class="nav-link mt-2"><i class="bi bi-box-arrow-left me-2"></i>Sair</a>
    </nav>
</div>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Nome da Disciplina</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($disciplina = $disciplina_result->fetch_assoc()) : ?>
                            <tr>
                                <td><?= $disciplina['nome_disc'] ?></td>
                                <td><?= $disciplina['descricao'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <a href="Professor.php" class="btn btn-secondary mt-3">Voltar</a>
    <footer class="bg-dark text-white text-center py-3 mt-4">
        <p>&copy; 2025 Sistema Escolar. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

<?php
// Fecha a conexão ao banco de dados
$conn->close();
?>
