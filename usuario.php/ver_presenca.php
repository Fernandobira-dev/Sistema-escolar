<?php
session_start(); // Garante que a sessão do aluno esteja ativa


$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Simulação: Aluno autenticado (troque pelo ID da sessão real)
$aluno_id = $_SESSION['aluno_id'] ?? 1;

// Buscar presença do aluno
$stmt = $conn->prepare("SELECT data, presente FROM presencas WHERE aluno_id = ? ORDER BY data DESC");
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Presenças</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>



<div class="container mt-4">
    <h2 class="text-center">Minhas Presenças</h2>
    <table class="table table-bordered mt-3">
        <thead class="table-dark">
            <tr>
                <th>Data</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($presenca = $result->fetch_assoc()) : ?>
                <tr>
                    <td><?= date("d/m/Y", strtotime($presenca['data'])) ?></td>
                    <td class="<?= $presenca['presente'] ? 'text-success' : 'text-danger' ?>">
                        <?= $presenca['presente'] ? '✔ Presente' : '❌ Ausente' ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>



<?php require 'footer.php'; // Rodapé ?>

</body>
</html>

<?php
$conn->close();
?>
