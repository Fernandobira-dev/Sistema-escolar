<?php
include 'db.php';

$turma = $_POST['turma'] ?? '';
$classe = $_POST['classe'] ?? '';

if (!$turma || !$classe) {
    echo "<div class='alert alert-warning'>Turma ou classe não selecionada.</div>";
    exit;
}

$sql = "SELECT 
            p.id_pessoa, 
            p.nome, 
            p.sexo
        FROM matricula m
        JOIN aluno a ON a.id_pessoa = m.id_aluno
        JOIN pessoa p ON p.id_pessoa = a.id_pessoa
        WHERE m.id_turma = ? AND m.id_classe = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $turma, $classe);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo "<div class='alert alert-info'>Nenhum aluno encontrado para a turma e classe selecionadas.</div>";
    exit;
}
?>

<form method="POST" action="salvar_notas.php">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Sexo</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($a = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= $a['id_pessoa'] ?></td>
                    <td><?= htmlspecialchars($a['nome']) ?></td>
                    <td><?= htmlspecialchars($a['sexo']) ?></td>
                    <td>
                        <input type="hidden" name="id_aluno[]" value="<?= $a['id_pessoa'] ?>">
                        <input type="number" step="0.1" name="nota[]" class="form-control" required>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <button type="submit" class="btn btn-success">Salvar Todas Notas</button>
</form>
