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

// Buscar todas as disciplinas
$disciplina_result = $conn->query("SELECT nome_disc, descricao FROM disciplina");
if (!$disciplina_result) {
    die("Erro na consulta de disciplina: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplinas Disponíveis</title>
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css"></head>
    <style>
        body {
            padding-top: 100px;
            background-color: #f4f6f9;
        }

        .tema-azul {
            background: linear-gradient(to right, rgb(60, 150, 253), rgb(70, 83, 253));
            color: white;
        }

        .cabecalho-boas-vindas {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px;
            z-index: 1051;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(146, 83, 83, 0.2);
        }

        .cabecalho-boas-vindas h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: #3c96fd;
            transition: transform 0.3s ease;
            padding-top: 80px;
            z-index: 1050;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .menu-toggle {
                display: block;
            }
        }

        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0) !important;
            }

            .menu-toggle {
                display: none;
            }
        }

        .sidebar .nav-link {
            color: white;
            margin: 10px 0;
            padding: 10px;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }

        .menu-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background-color: #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .container {
            margin-left: 270px;
        }

        @media (max-width: 991.98px) {
            .container {
                margin-left: 0;
            }
        }

        footer {
            background: linear-gradient(to right, rgb(30, 63, 151), rgb(69, 139, 252));
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 50px;
        }
        .form-wrapper {
    max-width: 600px;
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    margin-left: 40px;
    margin-right: 0;
}

    </style>
    <body>
    <header class="bg-primary text-white text-center py-3">
        <h1>Disciplinas Disponíveis</h1>
    </header>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Nome da Disciplina</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($disciplina = $disciplina_result->fetch_assoc()) : ?>
                            <tr>
                                <td><?= $disciplina['nome_disc'] ?></td>
                                <td><?= $disciplina['descricao'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
   
  
</body>
</html>

<?php
// Fecha a conexão ao banco de dados
$conn->close();
?>
