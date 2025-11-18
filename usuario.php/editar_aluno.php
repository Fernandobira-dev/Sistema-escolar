<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$id = $_GET['id'] ?? null;
$mensagem = '';

if (!$id) {
    header("Location: index.php");
    exit();
}

// Buscar os dados do aluno
$result = $conn->query("SELECT * FROM alunos WHERE id = $id");
$aluno = $result->fetch_assoc();

// Atualizar os dados quando o formulário for enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $curso = $_POST['curso'];
    $classe = $_POST['classe'];
    $turma_id = $_POST['turma_id'];

    $sql = "UPDATE alunos SET nome='$nome', curso='$curso', classe='$classe', turma_id='$turma_id' WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        $mensagem = "Dados atualizados com sucesso!";
    } else {
        $mensagem = "Erro ao atualizar: " . $conn->error;
    }
}

// Buscar todas as turmas para o select
$turmas = $conn->query("SELECT * FROM turmas");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css"></head>
<body>
    <div class="container mt-5">
        <h2>Editar Aluno</h2>

        <?php if (!empty($mensagem)) : ?>
            <div class="alert alert-success"><?= $mensagem ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" class="form-control" value="<?= $aluno['nome'] ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Curso</label>
                <input type="text" name="curso" class="form-control" value="<?= $aluno['curso'] ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Classe</label>
                <input type="text" name="classe" class="form-control" value="<?= $aluno['classe'] ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Turma</label>
                <select name="turma_id" class="form-control" required>
                    <?php while ($turma = $turmas->fetch_assoc()) : ?>
                        <option value="<?= $turma['id'] ?>" <?= ($aluno['turma_id'] == $turma['id']) ? 'selected' : '' ?>>
                            <?= $turma['nome_turma'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </form>
        <a href="grenciamento_professor.php" class="btn btn-secondary mt-3">Voltar</a>
    </div>
</body>
</html>

<?php $conn->close(); ?>
