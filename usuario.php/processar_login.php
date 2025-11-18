<?php
session_start();
include 'conexao.php'; // Ajuste o caminho se necessário

// Só executa se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Pega e limpa os dados enviados
    $nome = strtolower(trim($_POST['nome']));
    $password = $_POST['password'];

    // Prepara a consulta com o nome correto da coluna
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE LOWER(nome_usuario) = ? LIMIT 1");

    if (!$stmt) {
        die("Erro na preparação da consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        // Verifica a senha com password_verify
        if (password_verify($password, $usuario['senha'])) {
            // Guarda os dados na sessão
            $_SESSION['id_pessoa'] = $usuario['id_pessoa']; // <-- você disse que não tem id_usuario
            $_SESSION['nome_usuario'] = $usuario['nome_usuario'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

            // Redireciona conforme o tipo de usuário
            switch ($usuario['tipo_usuario']) {
                case 'Admin':
                    header("Location: admin.php");
                    break;
                case 'Professor':
                    header("Location: professor.php");
                    break;
                case 'Aluno':
                    header("Location: estudante.php");
                    break;
                default:
                    echo "Tipo de usuário desconhecido.";
                    break;
            }
            exit;
        } else {
            echo "<script>alert('Nome de usuário ou senha incorretos.'); window.location.href = 'login.php';</script>";
        }
    } else {
        echo "<script>alert('Nome de usuário ou senha incorretos.'); window.location.href = 'login.php';</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: login.php");
    exit;
}
