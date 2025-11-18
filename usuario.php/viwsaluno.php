<?php
// Conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "setembro");

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Consultar os alunos
$sql = "SELECT * FROM pessoa";
$result = $conn->query($sql);

// Verifica se existem alunos cadastrados
if ($result->num_rows > 0) {
    $alunos = $result->fetch_all(MYSQLI_ASSOC); // Obtém todos os dados em um array associativo
} else {
    $mensagem = "Nenhum aluno cadastrado.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualização de Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css"></head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Lista de Alunos</h2>

        <?php if (isset($mensagem)) { echo "<div class='alert alert-warning'>$mensagem</div>"; } ?>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    
                    <th>Curso</th>
                    
                    
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($alunos)) : ?>
                    <?php foreach ($alunos as $aluno): ?>
                        <tr>
                            <td><?= $aluno['id_pessoa'] ?></td>
                            <td><?= $aluno['nome'] ?></td>
                           
                            <td><?= $aluno['curso'] ?></td>
                           
                            
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Nenhum aluno encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <a href="professor.php" class="btn btn-secondary mt-3">Voltar</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Fechar conexão
$conn->close();
?>
