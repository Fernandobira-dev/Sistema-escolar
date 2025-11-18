<?php
// Verifica se a sessão não foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Inicia a sessão apenas se ainda não estiver ativa
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Escolar - Minhas Presenças</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Adicione o seu arquivo de estilos customizados, se necessário -->
    <link rel="stylesheet" href="path/to/your/custom-style.css">
</head>
<body>
    <!-- Barra de navegação -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <!-- Logo ou nome do sistema -->
            <a class="navbar-brand" href="index.php">Sistema Escolar</a>

            <!-- Botão de alternância para dispositivos móveis -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Alternar navegação">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Links de navegação -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Link para o início -->
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Início</a>
                    </li>

                    <!-- Link para a página de presenças -->
                    <li class="nav-item">
                        <a class="nav-link" href="">Minhas Presenças</a>
                    </li>

                    <!-- Link para a matrícula -->
                    <li class="nav-item">
                        <a class="nav-link" href="matricula.php">Matrícula</a>
                    </li>

                    <!-- Verifica se o aluno está logado -->
                    <?php if (isset($_SESSION['aluno_id'])): ?>
                        <!-- Se estiver logado, mostra o link para Logout -->
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Sair</a>
                        </li>
                    <?php else: ?>
                        <!-- Se não estiver logado, mostra o link para Login -->
                        
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Scripts do Bootstrap -->
    <script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script></body>
</html>
