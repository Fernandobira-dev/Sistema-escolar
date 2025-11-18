<?php
session_start();
include 'conexao.php';

// Check if user is logged in
if (!isset($_SESSION['id_pessoa'])) {
    header("Location: login.php");
    exit();
}

// Base de dados conexão
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$id_aluno_logado = $_SESSION['id_pessoa'];

// Notas agrupadas por disciplina e trimestre (cada disciplina aparece apenas uma vez por trimestre)
$notas_result = $conn->query("
    SELECT 
        d.id as disciplina_id,
        p_aluno.nome AS aluno_nome, 
        d.nome_disc AS disciplina_nome, 
        p_prof.nome AS professor_nome, 
        t.num_tri AS trimestre,
        ROUND(AVG(m.nota), 1) AS media,
        COUNT(m.nota) AS total_notas
    FROM minipauta m
    JOIN aluno a ON m.id_aluno = a.id_pessoa
    JOIN pessoa p_aluno ON a.id_pessoa = p_aluno.id_pessoa
    JOIN disciplina d ON m.id_disc = d.id
    JOIN professor pf ON m.id_professor = pf.id_pessoa
    JOIN pessoa p_prof ON pf.id_pessoa = p_prof.id_pessoa
    JOIN prova pr ON m.id_prova = pr.id
    JOIN trimestre t ON m.id_trimestre = t.id
    WHERE m.id_aluno = $id_aluno_logado
    GROUP BY d.id, t.num_tri, p_prof.nome
    ORDER BY t.num_tri, d.nome_disc
");

// Initialize variables
$totais_trimestres = [];
$medias_final = [];
$media_final = 0;
$situacao_final = 'Não Apto';
$transito_terceiro_trimestre = 'Não Apto para Trânsito';
$total_disciplinas = 0;
$soma_medias = 0;
$soma_trimestres = [];
$total_disciplinas_trimestre = [];
$disciplinas_processadas = [];

// Process query results
if ($notas_result && $notas_result->num_rows > 0) {
    while ($row = $notas_result->fetch_assoc()) {
        $trimestre = $row['trimestre'];
        $disciplina_id = $row['disciplina_id'];
        
        // Add the note to its respective trimester
        if (!isset($totais_trimestres[$trimestre])) {
            $totais_trimestres[$trimestre] = [];
        }
        
        $totais_trimestres[$trimestre][] = $row;
        
        // Track unique disciplines per trimester to calculate average correctly
        $disciplina_key = $trimestre . '-' . $disciplina_id;
        if (!isset($disciplinas_processadas[$disciplina_key])) {
            $disciplinas_processadas[$disciplina_key] = true;
            
            // Add to the trimester average calculation
            if (!isset($soma_trimestres[$trimestre])) {
                $soma_trimestres[$trimestre] = 0;
                $total_disciplinas_trimestre[$trimestre] = 0;
            }
            
            $soma_trimestres[$trimestre] += $row['media'];
            $total_disciplinas_trimestre[$trimestre]++;
            
            // Add to the overall average calculation
            $soma_medias += $row['media'];
            $total_disciplinas++;
        }
    }
    
    // Calculate average per trimester
    foreach ($soma_trimestres as $trimestre => $soma) {
        $medias_final[$trimestre] = $soma / $total_disciplinas_trimestre[$trimestre];
    }
    
    // Calculate overall average
    if ($total_disciplinas > 0) {
        $media_final = $soma_medias / $total_disciplinas;
    }
    
    // Determine final status
    $situacao_final = $media_final >= 10 ? 'Transita' : 'Não Transita';
    
    // Determine class transition status (if 3rd trimester exists)
    if (isset($medias_final[3])) {
        if ($medias_final[3] >= 13) {
            $transito_terceiro_trimestre = 'Ensino Médio Concluido';
        } elseif ($medias_final[3] >= 10) {
            $transito_terceiro_trimestre = 'Apto para Trânsitar';
        } else {
            $transito_terceiro_trimestre = 'Não Apto para Trânsitar';
        }
    }
}

// Fallback if no data was found
if (empty($totais_trimestres)) {
    // Create empty structure for template
    $totais_trimestres = [
        1 => [],
        2 => [],
        3 => []
    ];
    
    $medias_final = [
        1 => 0,
        2 => 0,
        3 => 0
    ];
}

// Verifica se a conexão foi estabelecida corretamente
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

// Verifica se o usuário está logado
if (!isset($_SESSION['id_pessoa'])) {
    header("Location: login.php");
    exit;
}

$id_pessoa = $_SESSION['id_pessoa'];

// Consulta SQL para obter os dados do aluno
$sql = "SELECT p.id_pessoa, p.foto, p.nome, p.num_bi, p.email, p.tel_1, p.tel_2, 
               c.nome_curso, cl.nome_classe
        FROM pessoa p
        INNER JOIN aluno a ON a.id_pessoa = p.id_pessoa
        INNER JOIN matricula m ON m.id_pessoa = a.id_pessoa
        INNER JOIN curso c ON c.id = m.id_curso
        INNER JOIN classe cl ON cl.id = m.id_classe
        WHERE a.id_pessoa = ?";

// Prepara a query e verifica se teve sucesso
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}

// Associa o parâmetro e executa a consulta
$stmt->bind_param("i", $id_pessoa);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se encontrou algum dado
if ($result->num_rows > 0) {
    $aluno = $result->fetch_assoc();
    $nome_aluno = $aluno['nome'];
    $primeiro_nome = explode(' ', $nome_aluno)[0];
    $mensagemBoasVindas = "Bem-vindo(a), " . $primeiro_nome;
} else {
    header("Location: login.php?erro=dados");
    exit;
}

// Fecha o statement e a conexão
$stmt->close();
$conn->close();

// Cálculo do ano letivo
$mes = date('m');
$ano_atual = date('Y');
if ($mes >= 9) {
    $ano_letivo = $ano_atual . '/' . ($ano_atual + 1);
} else {
    $ano_letivo = ($ano_atual - 1) . '/' . $ano_atual;
}

// Função para verificar a página atual
function is_active($page) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page == $page) ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>30 de Setembro - <?= $primeiro_nome ?></title>
    <!-- Bootstrap -->
    <link rel="stylesheet" href="bootstrap-5.0.2-dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    
    <style>
        :root {
          --primary-color:rgb(17, 131, 245);
            --secondary-color:rgb(52, 96, 219);
            --tertiary-color:rgb(28, 89, 223);
            --accent-color: #f39c12;
            --light-gray: #f8f9fa;
            --text-dark: #343a40;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f1c40f;
            --sidebar-width: 280px;
            --header-height: 70px;
        }
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f7fa;
            color: var(--text-dark);
            padding-top: 70px;
        }
        
        /* Header styles */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            height: 57px;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            color: white !important;
        }
        
        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
        }

            .main-content {
                margin-left: 0;
            }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 0.5rem;
            font-size: 1.8rem;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .user-info:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .user-name {
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .ano-letivo-badge {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background-color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            z-index: 1020;
            overflow-y: auto;
            transform: translateX(-100%);
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .menu-btn {
            border: none;
            background: transparent;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 1rem;
        }
        
        .nav-item {
            margin-bottom: 0.3rem;
        }
        
        .nav-link {
            color: var(--text-color);
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border-radius: 8px;
            margin: 0 0.8rem;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(37, 99, 235, 0.08);
            color: var(--primary-color);
        }
        
        .nav-link i {
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-divider {
            margin: 1rem 0;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .sidebar-header {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            padding: 1.2rem 1.5rem 0.5rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 0;
            margin-top: var(--header-height);
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .main-content.sidebar-active {
            margin-left: var(--sidebar-width);
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .profile-img-container {
            position: relative;
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .edit-photo-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .edit-photo-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .profile-info h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .profile-info p {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .info-item {
            margin-bottom: 1.2rem;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            font-size: 1rem;
            font-weight: 500;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.8rem 0;
        }
        
        .dropdown-item {
            padding: 0.6rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: rgba(37, 99, 235, 0.08);
            color: var(--primary-color);
        }
        
        .dropdown-item i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
            border-radius: 12px;
        }
        
        /* Responsive */
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: var(--sidebar-width);
            }
            
            .menu-btn {
                display: none;
            }
        }
        
        @media (max-width: 991px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-name, .ano-letivo-badge {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1.5rem 1rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
        #icon-30{
          width: 60px;
          height: 50px;
          margin-right: 2%;
        }

          .navbar-brand{
    color: white !important; /* Garantir que o texto dos links seja branco */
}


        /* Card styles */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--secondary-color), var(--tertiary-color));
            color: white;
            font-weight: 600;
            border-bottom: none;
            padding: 1rem 1.5rem;
        }
        
        .card-header.bg-warning {
            background: linear-gradient(135deg, var(--accent-color), var(--warning-color)) !important;
        }
        
        /* Table styles */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }
        
        .table td, .table th {
            vertical-align: middle;
            padding: 1rem;
        }
        
        .table-row-success {
            background-color: rgba(39, 174, 96, 0.1);
        }
        
        .table-row-danger {
            background-color: rgba(231, 76, 60, 0.1);
        }
        
        .badge {
            font-weight: 600;
            padding: 0.5rem 0.8rem;
            border-radius: 30px;
        }
        
        /* Footer styles */
        footer {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 0;
            margin-left: 280px;
            margin-top: 2rem;
        }
        
        @media (max-width: 991.98px) {
            footer {
                margin-left: 0;
            }
        }
        
        /* Utilities */
        .section-header {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--secondary-color);
            display: inline-block;
        }
        
        .text-accent {
            color: var(--accent-color);
        }
        
        .trimester-summary {
            background-color: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border-left: 5px solid var(--secondary-color);
        }

        /* User info in sidebar */
        .user-info {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
            font-weight: 700;
        }

         /* Card styles */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--secondary-color), var(--tertiary-color));
            color: white;
            font-weight: 600;
            border-bottom: none;
            padding: 1rem 1.5rem;
        }
        
        .card-header.bg-warning {
            background: linear-gradient(135deg, var(--accent-color), var(--warning-color)) !important;
        }
        
        /* Table styles */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }
        
        .table td, .table th {
            vertical-align: middle;
            padding: 1rem;
        }
        
        .table-row-success {
            background-color: rgba(39, 174, 96, 0.1);
        }
        
        .table-row-danger {
            background-color: rgba(231, 76, 60, 0.1);
        }
        
        .badge {
            font-weight: 600;
            padding: 0.5rem 0.8rem;
            border-radius: 30px;
        }

         /* Card Styles */
        .card {
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            border: none;
            box-shadow: var(--shadow-md);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }

         .trimester-summary {
            background-color: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border-left: 5px solid var(--secondary-color);
        }
 /* User info in sidebar */
        .user-info {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Toggle Button */
        .menu-toggle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            z-index: 1050;
        }

        .menu-toggle:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }

       /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin-bottom: 1.5rem;
        }
        

        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
        }

        .custom-table thead th {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            font-weight: 500;
            text-align: left;
            border: none;
            position: relative;
        }

        .custom-table thead th:first-child {
            border-top-left-radius: 0.5rem;
        }

        .custom-table thead th:last-child {
            border-top-right-radius: 0.5rem;
        }

        .custom-table tbody tr {
            transition: var(--transition);
        }

        .custom-table tbody tr:hover {
            background-color: rgba(37, 99, 235, 0.05);
        }

        .custom-table tbody td {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .custom-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 0.5rem;
        }

        .custom-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 0.5rem;
        }

        /* Course Info Card */
        .course-info {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }

        .course-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .course-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }

        .course-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .course-detail-item {
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
        }

        .course-detail-icon {
            margin-right: 0.5rem;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-.stat-card {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-title {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 500;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(37, 99, 235, 0.1);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.stat-description {
    font-size: 0.85rem;
    color: #64748b;
}

/* Grade badges and indicators */
.grade-badge {
    padding: 0.35rem 0.7rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-block;
}

.grade-high {
    background-color: rgba(39, 174, 96, 0.15);
    color: var(--success-color);
}

.grade-medium {
    background-color: rgba(241, 196, 15, 0.15);
    color: var(--warning-color);
}

.grade-low {
    background-color: rgba(231, 76, 60, 0.15);
    color: var(--danger-color);
}

/* Status indicators */
.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}

.status-success {
    background-color: var(--success-color);
}

.status-warning {
    background-color: var(--warning-color);
}

.status-danger {
    background-color: var(--danger-color);
}

/* Progress Bar */
.progress-container {
    background-color: #e9ecef;
    border-radius: 50px;
    height: 8px;
    width: 100%;
    margin: 1rem 0;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    border-radius: 50px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    transition: width 0.5s ease;
}

/* Trimester tabs */
.trimester-tabs {
    display: flex;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.trimester-tab {
    padding: 0.8rem 1.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
}

.trimester-tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.trimester-tab:hover:not(.active) {
    color: var(--secondary-color);
    border-bottom-color: rgba(37, 99, 235, 0.3);
}

/* Final status card */
.final-status-card {
    border-radius: 10px;
    padding: 1.5rem;
    margin-top: 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.status-transita {
    background-color: rgba(39, 174, 96, 0.1);
    border: 2px solid var(--success-color);
}

.status-nao-transita {
    background-color: rgba(231, 76, 60, 0.1);
    border: 2px solid var(--danger-color);
}

.status-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.status-transita .status-icon {
    color: var(--success-color);
}

.status-nao-transita .status-icon {
    color: var(--danger-color);
}

.status-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.status-description {
    font-size: 1rem;
    margin-bottom: 1rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    opacity: 0.8;
}

/* Media badges */
.media-badge {
    display: inline-block;
    min-width: 60px;
    text-align: center;
    padding: 0.5rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1rem;
}

.media-excellent {
    background-color: rgba(39, 174, 96, 0.15);
    color: var(--success-color);
}

.media-good {
    background-color: rgba(52, 152, 219, 0.15);
    color: #3498db;
}

.media-average {
    background-color: rgba(241, 196, 15, 0.15);
    color: var(--warning-color);
}

.media-poor {
    background-color: rgba(231, 76, 60, 0.15);
    color: var(--danger-color);
}

/* Print styles */
@media print {
    .sidebar, .header, .no-print {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        break-inside: avoid;
    }
    
    body {
        background-color: white !important;
        padding-top: 0 !important;
    }
    
    footer {
        margin-left: 0 !important;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.5s ease forwards;
}

/* Responsiveness */
@media (max-width: 992px) {
    .main-content {
        padding: 1.5rem;
    }
    
    .stats-container {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 768px) {
    .course-details {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .trimester-summary {
        padding: 1rem;
    }
    
    .card-header, .card-body {
        padding: 1.25rem;
    }
    
    .custom-table thead th,
    .custom-table tbody td {
        padding: 0.75rem;
    }
}

@media (max-width: a576px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .trimester-tabs {
        overflow-x: auto;
        white-space: nowrap;
    }
}

/* Additional utility classes */
.text-primary { color: var(--primary-color) !important; }
.text-secondary { color: var(--secondary-color) !important; }
.text-success { color: var(--success-color) !important; }
.text-danger { color: var(--danger-color) !important; }
.text-warning { color: var(--warning-color) !important; }

.bg-primary { background-color: var(--primary-color) !important; }
.bg-secondary { background-color: var(--secondary-color) !important; }
.bg-success { background-color: var(--success-color) !important; }
.bg-danger { background-color: var(--danger-color) !important; }
.bg-warning { background-color: var(--warning-color) !important; }

.font-weight-bold { font-weight: 700 !important; }
.font-weight-medium { font-weight: 500 !important; }

.rounded-lg { border-radius: 0.5rem !important; }
.rounded-xl { border-radius: 1rem !important; }

.shadow-sm { box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08) !important; }
.shadow-md { box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1) !important; }
.shadow-lg { box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important; }

/* Toast notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    padding: 1rem;
    margin-bottom: 0.75rem;
    min-width: 300px;
    display: flex;
    align-items: center;
    animation: slideIn 0.3s ease forwards;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.toast-success {
    border-left: 4px solid var(--success-color);
}

.toast-error {
    border-left: 4px solid var(--danger-color);
}

.toast-warning {
    border-left: 4px solid var(--warning-color);
}

.toast-info {
    border-left: 4px solid var(--primary-color);
}

.toast-icon {
    margin-right: 0.75rem;
    font-size: 1.2rem;
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.toast-message {
    font-size: 0.9rem;
    color: #64748b;
}

.toast-close {
    background: transparent;
    border: none;
    color: #64748b;
    cursor: pointer;
    font-size: 1.2rem;
} 
</style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <button class="menu-btn" id="menu-toggle">
            <i class="bi bi-list"></i>
        </button>
        
        <div class="header-content">
            <div class="logo">
            <img src="icon.png" alt="icon" id="icon-30">
                 <a class="navbar-brand" href="#">INSTITUTO POLITÉCNICO 30 DE SETEMBRO</a>
            </div>
            
            <div class="header-right">
               
                
                <div class="dropdown">
                    <div class="user-info" data-bs-toggle="dropdown">
                        <?php if (!empty($aluno['foto'])): ?>
                            <img src="<?= $aluno['foto'] ?>" alt="Foto do aluno" class="user-photo">
                        <?php else: ?>
                            <div class="user-photo d-flex align-items-center justify-content-center bg-primary">
                                <i class="bi bi-person-fill text-white"></i>
                            </div>
                        <?php endif; ?>
                        <div class="user-name"><?= $primeiro_nome ?></div>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="Dados_pessoais.php"><i class="bi bi-person"></i> Meu Perfil</a></li>
                        <li><a class="dropdown-item" href="altera_senha.php"><i class="bi bi-key"></i> Alterar Senha</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="px-4 py-3 d-lg-none">
            <div class="d-flex align-items-center">
                <?php if (!empty($aluno['foto'])): ?>
                    <img src="<?= $aluno['foto'] ?>" alt="Foto do aluno" class="user-photo me-2">
                <?php else: ?>
                    <div class="user-photo d-flex align-items-center justify-content-center bg-primary me-2">
                        <i class="bi bi-person-fill text-white"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="fw-semibold"><?= $nome_aluno ?></div>
                    <small class="text-muted"><?= $aluno['email'] ?></small>
                </div>
            </div>
        </div>
        
        <div class="sidebar-divider d-lg-none"></div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
            <div class="sidebar-header">Menu Principal</div>
                <a href="Estudante.php" class="nav-link <?= is_active('Estudante.php') ?>">
                    <i class="bi bi-house-door"></i>
                    <span>Início</span>
                </a>
                <div class="sidebar-header">Ária do Estudante</div>

            </li>
            <li class="nav-item">
                <a href="Dados_pessoais.php" class="nav-link <?= is_active('Dados_pessoais.php') ?>">
                <i class="bi bi-person-badge menu-icon"></i>
                <span>Meu Perfil</span>
                </a>
                <ul class="nav flex-column">
            <li class="nav-item">
                <a href="altera_senha.php" class="nav-link <?= is_active('altera_senha.php') ?>">
                <i class="bi bi-key menu-icon"></i>
                <span>Segurança</span>
                </a>
            </li>
        </ul>
            </li>
            <li class="nav-item">
                <a href="reclamacao.php" class="nav-link <?= is_active('reclamacao.php') ?>">
                <i class="bi bi-chat-left-text menu-icon"></i>
                <span>Reclamações</span>
                </a>
            </li>
            
        
        <div class="sidebar-divider"></div>
        
        <div class="sidebar-header">Secretária</div>
        <ul class="nav flex-column">
         
            <li class="nav-item">
                <a href="visualizar_notas.php" class="nav-link <?= is_active('visualizar_notas.php') ?>">
                <i class="bi bi-bar-chart menu-icon"></i>
                <span>Minhas Notas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="atribuiçao.php" class="nav-link <?= is_active('atribuiçao.php') ?>">
                <i class="bi bi-journal-text menu-icon"></i>
                <span>Disciplinas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="ver_matricula.php" class="nav-link <?= is_active('ver_matricula.php') ?>">
                <i class="bi bi-calendar-event menu-icon"></i>
                <span>Histórico Acadêmico</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Comunicação_aluno.php" class="nav-link <?= is_active('Comunicação_aluno.php') ?>">
                <i class="bi bi-card-checklist menu-icon"></i>
                <span>Calendário Acadêmico</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-divider"></div>
        
        
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                <i class="bi bi-box-arrow-right menu-icon"></i>
                <span>Sair</span>
                </a>
            </li>
        </ul>
    </div>
 <!-- Main Content -->
      <div class="d-flex justify-content-center align-items-center" style="min-height: 10vh; margin-left: 200px;  max-width: 100%; ">

 <div class="table-responsive" style="max-width: 55%; margin: 0 auto; ">
    
        

        <div class="row">
            <div class="col-md-4">
                
                    <div class="card-body">
                        
                </div>
                
               
                    <div class="card-body">
                        <canvas id="trimestreChart" height="0"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-0">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center " style="width:15000px;">
                        <h5 class="card-title mb-0"> 
                            <i class="bi bi-clipboard-data me-2"></i>
                            Minhas Notas
                        </h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary trimestre-btn active" data-trimestre="all">
                                Todos
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary trimestre-btn" data-trimestre="1">
                                1º Tri
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary trimestre-btn" data-trimestre="2">
                                2º Tri
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary trimestre-btn" data-trimestre="3">
                                3º Tri
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Trimestre sections will be added here dynamically -->
                        <?php foreach ($totais_trimestres as $trimestre => $notas): ?>
                        <div class="trimestre-section" data-trimestre="<?= $trimestre ?>">
                            <div class="trimester-summary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="bi bi-calendar3 me-2"></i>
                                        <?= $trimestre ?>e
                                    </h5>
                                    <h6 class="mb-0">
                                        Média: <span class="badge bg-<?= $medias_final[$trimestre] >= 10 ? 'success' : 'danger' ?>">
                                            <?= number_format($medias_final[$trimestre], 1) ?>
                                        </span>
                                    </h6>
                                </div>
                            </div>
                                
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Disciplina</th>
                                            <th>Professor</th>
                                            <th>Média </th>
                                            <th>Aproveitamento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($notas as $row):
                                            $situacao = $row['media'] < 10 ? "Sem Aproveitamento" : "Com Aproveitamento";
                                            $row_class = $row['media'] < 10 ? "table-row-danger" : "table-row-success";
                                            $badge_class = $row['media'] < 10 ? "bg-danger" : "bg-success";
                                        ?>
                                        <tr class="<?= $row_class ?>">
                                            <td>
                                                <strong><?= $row['disciplina_nome'] ?></strong>
                                            </td>
                                            <td><?= $row['professor_nome'] ?></td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-<?= $row['media'] < 10 ? 'danger' : 'success' ?>" 
                                                        role="progressbar" 
                                                        style="width: <?= ($row['media']/20)*100 ?>%;" 
                                                        aria-valuenow="<?= $row['media'] ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="20">
                                                    </div>
                                                </div>
                                                <small class="mt-1 d-block text-center"><?= $row['media'] ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= $badge_class ?>">
                                                    <?= $situacao ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
// Retrieve the student's class from the database
$classe_aluno = $aluno['nome_classe'] ?? ''; // Getting class name from the existing $aluno array

// Extract the numeric part of the class (assuming format like "10ª Classe")
$classe_numero = intval($classe_aluno);

// Determine status based on class and final situation
$status_texto = '';
$status_icon = '';
$status_class = '';

if (in_array($classe_numero, [10, 11, 12])) {
    // For 10th, 11th, and 12th grades
    if ($situacao_final === 'Transita') {
        $status_texto = 'Ensino Medio Não Concluido';
        $status_icon = 'arrow-repeat';
        $status_class = 'info';
    } else {
        $status_texto = 'Ensino Medio Não Concluido';
        $status_icon = 'x-octagon';
        $status_class = 'danger';
    }
} elseif ($classe_numero === 13) {
    // For 13th grade (final year)
    if ($situacao_final === 'Transita') {
        $status_texto = 'Ensino Médio Concluído';
        $status_icon = 'mortarboard';
        $status_class = 'success';
    } else {
        $status_texto = 'Ensino Medio Não Concluido';
        $status_icon = 'x-octagon';
        $status_class = 'danger';
    }
} else {
    // Default fallback
    $status_texto = 'Status Indeterminado';
    $status_icon = 'question-circle';
    $status_class = 'warning';
}

// Badge class for the final average
$badge_class_final = $media_final >= 10 ? 'success' : 'danger';
$icon_final = $media_final >= 10 ? 'check-circle' : 'x-circle';
?>

<!-- The card displaying the final results -->
<div class="card">
  <div class="card-header bg-warning text-dark ">
    <i class="bi bi-trophy me-2"></i>Classificação Final
  </div>
  <div class="card-body">
    <div class="row align-items-center text-center">
      <div class="col-md-4">
        <div class="display-1 mb-0"><?= number_format($media_final, 1) ?></div>
        <p class="text-muted">Média Final</p>
      </div>
      <div class="col-md-4 border-start border-end">
        <div class="h3 mb-2">
          <span class="badge bg-<?= $badge_class_final ?> p-2">
            <i class="bi bi-<?= $icon_final ?> me-1"></i> <?= $situacao_final ?>
          </span>
        </div>
        <p class="text-muted">Classificação Final</p>
      </div>
      <div class="col-md-4">
        <div class="h4 mb-2">
          <span class="badge bg-<?= $status_class ?> p-2">
            <i class="bi bi-<?= $status_icon ?> me-1"></i> <?= $status_texto ?>
          </span>
        </div>
        <p class="text-muted">Estado de Conclusão</p>
      </div>
    </div>
  </div>
</div>
  
            </div>
        </div>
    </div>


    <!-- Bootstrap JS Bundle with Popper -->
    <script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>

    <script src="bootstrap-5.0.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle Sidebar
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
        });
        
        // Handle responsive sidebar for desktop/mobile
        function checkScreenSize() {
            if (window.innerWidth >= 992) {
                sidebar.classList.add('active');
                mainContent.classList.add('sidebar-active');
            } else {
                sidebar.classList.remove('active');
                mainContent.classList.remove('sidebar-active');
            }
        }
        
        // Initial check and add resize listener
        window.addEventListener('resize', checkScreenSize);
        checkScreenSize();
    </script>
</body>
</html>