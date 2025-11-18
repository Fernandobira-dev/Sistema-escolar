<?php
session_start();
include 'conexao.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$mensagemErro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = $_POST['identificador'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Tenta Admin
    $stmt = $conn->prepare("SELECT id_pessoa, senha FROM usuario WHERE nome_usuario = ? AND tipo_usuario = 'admin'");
    $stmt->bind_param("s", $identificador);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['id_pessoa'] = $usuario['id_pessoa'];
            $_SESSION['tipo_usuario'] = 'admin';
            header("Location: admin.php");
            exit;
        }
    }

    // Tenta Professor
    $stmt = $conn->prepare("SELECT p.id_pessoa, u.senha, p.ativo FROM usuario u 
                            JOIN professor p ON p.id_pessoa = u.id_pessoa 
                            WHERE p.num_agente = ?");
    $stmt->bind_param("s", $identificador);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Verifica se o professor está ativo
        if ($usuario['ativo'] == 0) {
            $mensagemErro = "Sua conta foi desativada. Entre em contato com o administrador.";
        } else {
            // Se estiver ativo, verifica a senha
            if (password_verify($senha, $usuario['senha'])) {
                $_SESSION['id_pessoa'] = $usuario['id_pessoa'];
                $_SESSION['tipo_usuario'] = 'professor';
                header("Location: professor.php");
                exit;
            } else {
                $mensagemErro = "Credenciais inválidas. Verifique o identificador e a senha.";
            }
        }
    }

    // Tenta Aluno
    $stmt = $conn->prepare("SELECT a.id_pessoa, u.senha FROM usuario u 
                            JOIN aluno a ON a.id_pessoa = u.id_pessoa 
                            WHERE a.num_processo = ?");
    $stmt->bind_param("s", $identificador);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['id_pessoa'] = $usuario['id_pessoa'];
            $_SESSION['tipo_usuario'] = 'aluno';
            header("Location: estudante.php");
            exit;
        }
    }

    $mensagemErro = "Credenciais inválidas. Verifique o identificador e a senha.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<style>
    #icon-30 {
        width: 75px;
        height: 60px;
        margin-right: 2%;
    }

    .login-card {
        max-width: 400px; /* Reduz a largura do formulário */
        margin: 0 auto;    /* Centraliza na horizontal */
    }
    
</style>

    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary navbar-text-white">
        <div class="container">
            <img src="icon.png" alt="icon" id="icon-30">
            <a class="navbar-brand" href="#">INSTITUTO POLITECNICO 30 DE SETEMBRO</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                   <a class="navbar-brand" href="../Index.htm/index.html">Início</a>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
    <div class="card shadow-sm p-4 login-card">
        <h2 class="mb-4 text-center text-primary">Acesso</h2>

        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger"><?= $mensagemErro ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="identificador" class="form-label">Usuário</label>
                
                <input type="text" class="form-control" id="identificador" name="identificador" placeholder="Digite aqui" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
    </div>
</div>

        </div>
    </div>
    <script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
