<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Verificar se o nome foi passado
if (!isset($_GET['nome']) || empty($_GET['nome'])) {
    die("Nome da pessoa não informado.");
}

$nome = $_GET['nome'];

// Buscar os dados da pessoa
$stmt = $conn->prepare("SELECT * FROM pessoa WHERE nome = ?");
$stmt->bind_param("s", $nome);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Pessoa não encontrada ou nome duplicado.");
}

$pessoa = $result->fetch_assoc();

// Atualizar dados se formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sexo = $_POST['sexo'] ?? '';
    $nome_pai = $_POST['nome_pai'] ?? '';
    $nome_mae = $_POST['nome_mae'] ?? '';
    $naturalidade = $_POST['naturalidade'] ?? '';
    $email = $_POST['email'] ?? '';
    $num_bi = $_POST['num_bi'] ?? '';
    $tel_1 = $_POST['tel_1'] ?? '';
    $tel_2 = $_POST['tel_2'] ?? '';

    $stmt = $conn->prepare("UPDATE pessoa SET sexo=?, nome_pai=?, nome_mae=?, naturalidade=?, email=?, num_bi=?, tel_1=?, tel_2=? WHERE nome=?");
    $stmt->bind_param("sssssssss", $sexo, $nome_pai, $nome_mae, $naturalidade, $email, $num_bi, $tel_1, $tel_2, $nome);

    if ($stmt->execute()) {
        header("Location: matricula_aluno.php");
        exit;
    } else {
        echo "Erro ao atualizar: " . $stmt->error;
    }
}
?>
