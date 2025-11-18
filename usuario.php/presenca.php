<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Cadastro de presença
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aluno_id'])) {
    $aluno_id = $_POST['aluno_id'];
    $data = date('Y-m-d');
    $presente = isset($_POST['presente']) ? 1 : 0;
    
    // Inserir presença no banco
    $stmt = $conn->prepare("INSERT INTO presencas (aluno_id, data, presente) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $aluno_id, $data, $presente);
    
    if ($stmt->execute()) {
        $mensagem = "Presença registrada com sucesso!";
    } else {
        $mensagem = "Erro ao registrar presença: " . $stmt->error;
    }
    $stmt->close();
}

// Consultar presenças
$presencas_result = $conn->query("SELECT presencas.*, alunos.nome FROM presencas JOIN alunos ON presencas.aluno_id = alunos.id ORDER BY presencas.data DESC");

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Presença</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <header class="bg-primary text-white text-center py-3">
        <h1>Registro de Presença</h1>
    </header>
    
    <div class="container mt-4">
        <?php if (!empty($mensagem)) : ?>
            <div class="alert alert-success"><?= $mensagem ?></div>
        <?php endif; ?>
        
        <!-- Formulário de registro de presença -->
        <div class="card">
            <div class="card-body">
                <h3>Registrar Presença</h3>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Selecione o Aluno</label>
                        <select name="aluno_id" class="form-control" required>
                            <option value="">Escolha um aluno</option>
                            <?php
                            $alunos_result = $conn->query("SELECT * FROM alunos");
                            while ($aluno = $alunos_result->fetch_assoc()) :
                            ?>
                                <option value="<?= $aluno['id'] ?>"><?= htmlspecialchars($aluno['nome']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Presença</label><br>
                        <input type="checkbox" name="presente" value="1"> Presente
                    </div>
                    <button type="submit" class="btn btn-primary">Registrar Presença</button>
                </form>
            </div>
        </div>

        <!-- Consulta de presenças -->
        <h2 class="mt-4">Histórico de Presenças</h2>
        <table class="table table-bordered mt-3">
            <thead class="table-dark">
                <tr>
                    <th>Aluno</th>
                    <th>Data</th>
                    <th>Presença</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($presenca = $presencas_result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($presenca['nome']) ?></td>
                        <td><?= htmlspecialchars($presenca['data']) ?></td>
                        <td><?= $presenca['presente'] ? 'Presente' : 'Ausente' ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <a href="ff.html">Voltar na Pagina inicial</a>
    <footer class="bg-dark text-white text-center py-3 mt-4">
        <p>&copy; 2025 Sistema Escolar. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

<?php
// Fecha a conexão ao banco de dados
$conn->close();
?>
