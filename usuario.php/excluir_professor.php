<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id_pessoa = intval($_GET['id']);

    // Excluir os registros na tabela atribuicao_disc antes de excluir o professor
    $stmt = $conn->prepare("DELETE FROM atribuicao_disc WHERE id_prof = ?");
    $stmt->bind_param("i", $id_pessoa);
    $stmt->execute();
    $stmt->close();

    // Agora excluir o professor
    $stmt = $conn->prepare("DELETE FROM professor WHERE id_pessoa = ?");
    $stmt->bind_param("i", $id_pessoa);

    if ($stmt->execute()) {
        header("Location: visualizar_professor.php?msg=excluido");
        exit();
    } else {
        echo "Erro ao excluir professor: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "ID inválido.";
}

$conn->close();
?>
