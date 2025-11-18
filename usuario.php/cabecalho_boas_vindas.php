<?php


$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$id_aluno_logado = $_SESSION['id_pessoa'];

// Buscar as informações do aluno
$query = "SELECT nome, foto FROM pessoa WHERE id_pessoa = $id_aluno_logado";
$result = $conn->query($query);
$aluno = $result->fetch_assoc();

$mensagemBoasVindas = "Bem-vindo(a), " . $aluno['nome'] . "!";
?>

<!-- Cabeçalho de boas-vindas -->
<div class="cabecalho-boas-vindas tema-azul">
    <img src="<?= $aluno['foto']; ?>" alt="Foto do professor" width="30" class="img-thumbnail">
    <h1><?= $mensagemBoasVindas ?></h1>
</div>
<style>
   .cabecalho-boas-vindas {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    padding: 15px 10px; /* aumenta a altura do cabeçalho */
    z-index: 1051;
    display: flex;
    justify-content: flex-end; /* <-- Alinha tudo à direita */
    gap: 8px;
    box-shadow: 0 2px 10px rgba(199, 0, 0, 0.2);
    font-size: 0.90em; /* letra mais discreta */
    color:white;
    
    
}



.cabecalho-boas-vindas h1 {
    margin: 0;
    font-size: 1rem; /* letra reduzida */
    font-weight: normal; /* deixa mais clean */
  
}
  .img-thumbnail {
            border-radius: 50%;
            max-width: 120px;
            
        }
</style>