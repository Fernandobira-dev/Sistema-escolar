<?php
include 'conexao.php'; // Conexão com o banco de dados

// Verifique se o ID foi fornecido
if (isset($_GET['id'])) {
    $id_pessoa = $_GET['id'];

    // Consulta SQL para buscar a foto
    $sql = "SELECT foto FROM pessoa WHERE id_pessoa = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_pessoa);
    $stmt->execute();
    $stmt->store_result();

    // Verifique se encontrou o aluno
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($foto);
        $stmt->fetch();

        // Verifique se há uma foto e se ela não é nula
        if (!empty($foto)) {
            // Defina o tipo de conteúdo (se for uma imagem JPEG, por exemplo)
            header('Content-Type: image/jpeg');
            echo $foto; // Envie a imagem como resposta
        } else {
            // Caso não haja foto, envie uma imagem padrão ou um código de erro
            header('Content-Type: image/jpeg');
            readfile('path/to/default-image.jpg'); // Imagem padrão
        }
    } else {
        echo "Foto não encontrada!";
    }

    $stmt->close();
} else {
    echo "ID não fornecido!";
}

$conn->close();
?>
