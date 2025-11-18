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
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM disciplinas WHERE id=$id");
    $disciplina = $result->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_disciplina = $_POST['nome_disciplina'];
    $descricao = $_POST['descricao'];

    $conn->query("UPDATE disciplinas SET nome_disciplina='$nome_disciplina', descricao='$descricao' WHERE id=$id");
    header("Location: grenciamento_professor.php");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Disciplina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css"></head>
<body>
    <div class="container mt-5">
        <h2>Editar Disciplina</h2>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nome da Disciplina</label>
                <input type="text" name="nome_disciplina" class="form-control" value="<?= $disciplina['nome_disciplina'] ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control"><?= $disciplina['descricao'] ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </form>
        <a href="grenciamento_professor.php" class="btn btn-secondary mt-3">Voltar</a>
    </div>

</body>
</html>
