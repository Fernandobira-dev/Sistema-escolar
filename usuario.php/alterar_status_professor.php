<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$id_pessoa = $_GET['id'] ?? null;
$novo_status = $_GET['status'] ?? null;

if ($id_pessoa !== null && ($novo_status === '0' || $novo_status === '1')) {
    $sql = "UPDATE professor SET ativo = ? WHERE id_pessoa = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $novo_status, $id_pessoa);
    $stmt->execute();
}

$conn->close();
header("Location: visualizar_professor.php");
exit;
?>
