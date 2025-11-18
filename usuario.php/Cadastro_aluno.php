<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Função para montar <option>
function getOptions($conn, $query, $valueField, $textField) {
    $options = '';
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $options .= "<option value='{$row[$valueField]}'>{$row[$textField]}</option>";
        }
    }
    return $options;
}

// Buscar cursos
$sqlCursos = "SELECT id, nome_curso FROM curso";
$resultCursos = $conn->query($sqlCursos);
$cursos = [];
if ($resultCursos && $resultCursos->num_rows > 0) {
    while ($row = $resultCursos->fetch_assoc()) {
        $cursos[] = $row;
    }
}

// Buscar classes
$sqlClasses = "SELECT id, nome_classe FROM classe";
$resultClasses = $conn->query($sqlClasses);
$classes = [];
if ($resultClasses && $resultClasses->num_rows > 0) {
    while ($row = $resultClasses->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Buscar salas
$sqlSalas = "SELECT id, num_sala FROM sala";
$resultSalas = $conn->query($sqlSalas);
$salas = [];
if ($resultSalas && $resultSalas->num_rows > 0) {
    while ($row = $resultSalas->fetch_assoc()) {
        $salas[] = $row;
    }
}

// Buscar anos letivos
$sqlAnos = "SELECT id, nome FROM ano_lectivo";
$resultAnos = $conn->query($sqlAnos);
$anos = [];
if ($resultAnos && $resultAnos->num_rows > 0) {
    while ($row = $resultAnos->fetch_assoc()) {
        $anos[] = $row;
    }
}

// Função para calcular idade a partir da data de nascimento
function calcularIdade($dataNascimento) {
    $hoje = new DateTime();
    $nascimento = new DateTime($dataNascimento);
    $idade = $hoje->diff($nascimento);
    return $idade->y; // Retorna anos
}

// Função para determinar a turma com base na idade, classe e curso - VERSÃO MELHORADA
function determinarTurma($conn, $idade, $classe, $curso, $idSala) {
    $periodoDesejado = ($idade < 16) ? 'manhã' : 'tarde';
    
    // Log para debugging
    error_log("DEBUG: determinarTurma - Idade: $idade, Classe ID: $classe, Curso ID: $curso, Período: $periodoDesejado, Sala ID: $idSala");
    
    // Buscar informações da classe
    $sqlClasse = "SELECT nome_classe FROM classe WHERE id = ?";
    $stmtClasse = $conn->prepare($sqlClasse);
    
    if ($stmtClasse === false) {
        error_log("DEBUG: Erro ao preparar consulta SQL para classe: " . $conn->error);
        $nomeClasse = $classe . "ª classe"; // Fallback seguro
    } else {
        $stmtClasse->bind_param("i", $classe);
        $stmtClasse->execute();
        $resultClasse = $stmtClasse->get_result();
        
        if ($resultClasse->num_rows > 0) {
            $rowClasse = $resultClasse->fetch_assoc();
            $nomeClasse = $rowClasse['nome_classe'];
        } else {
            $nomeClasse = $classe . "ª classe";
            error_log("DEBUG: Classe ID $classe não encontrada no banco, usando fallback");
        }
    }
    
    // Extrair número da classe para uso no mapeamento
    $numeroClasse = preg_replace('/[^0-9]/', '', $nomeClasse);
    
    // Buscar informações do curso
    $sqlCurso = "SELECT nome_curso FROM curso WHERE id = ?";
    $stmtCurso = $conn->prepare($sqlCurso);
    
    if ($stmtCurso === false) {
        error_log("DEBUG: Erro ao preparar consulta SQL para curso: " . $conn->error);
        $nomeCurso = "Curso #" . $curso; // Fallback seguro
    } else {
        $stmtCurso->bind_param("i", $curso);
        $stmtCurso->execute();
        $resultCurso = $stmtCurso->get_result();
        
        if ($resultCurso->num_rows > 0) {
            $rowCurso = $resultCurso->fetch_assoc();
            $nomeCurso = $rowCurso['nome_curso'];
        } else {
            $nomeCurso = "Curso #" . $curso;
            error_log("DEBUG: Curso ID $curso não encontrado no banco, usando fallback");
        }
    }
    
    // DEBUG: Log dos nomes obtidos
    error_log("DEBUG: Nome do Curso: $nomeCurso, Nome da Classe: $nomeClasse, Número da Classe extraído: $numeroClasse");
    
    // Gerar o prefixo da turma com base no curso e classe
    $prefixoTurma = '';
    
    // Verificar o tipo de curso para determinar o prefixo correto
    if (stripos($nomeCurso, 'informática') !== false || 
        stripos($nomeCurso, 'informatica') !== false) {
        // Curso de Informática
        $prefixoTurma = ($periodoDesejado == 'manhã') ? "CIM$numeroClasse" : "CIT$numeroClasse";
    } 
    elseif (stripos($nomeCurso, 'instalação') !== false || 
            stripos($nomeCurso, 'instalacao') !== false || 
            stripos($nomeCurso, 'eléctrica') !== false || 
            stripos($nomeCurso, 'eletrica') !== false ||
            stripos($nomeCurso, 'elétrica') !== false) {
        // Curso de Instalações Elétricas
        $prefixoTurma = "EIE$numeroClasse";
        $prefixoTurma .= ($periodoDesejado == 'manhã') ? "M" : "T";
    }
    elseif (stripos($nomeCurso, 'eletrônica') !== false || 
            stripos($nomeCurso, 'electronica') !== false || 
            stripos($nomeCurso, 'eletrónica') !== false) {
        // Curso de Eletrônica
        $prefixoTurma = "ELT$numeroClasse";
        $prefixoTurma .= ($periodoDesejado == 'manhã') ? "M" : "T";
    }
    else {
        // Outros cursos - criar um prefixo genérico
        // Usar as primeiras letras do nome do curso
        $palavras = explode(' ', $nomeCurso);
        $sigla = '';
        foreach ($palavras as $palavra) {
            if (strlen($palavra) > 3) { // Ignorar palavras curtas como "de", "do", etc.
                $sigla .= strtoupper(substr($palavra, 0, 1));
            }
        }
        if (strlen($sigla) < 2) {
            $sigla = strtoupper(substr($nomeCurso, 0, 3)); // Pelo menos 3 letras
        }
        $prefixoTurma = $sigla . $numeroClasse;
        $prefixoTurma .= ($periodoDesejado == 'manhã') ? "M" : "T";
    }
    
    error_log("DEBUG: Prefixo da turma gerado: $prefixoTurma");
    
    // Buscar período pelo nome
    $sqlPeriodo = "SELECT id FROM periodo WHERE nome LIKE ?";
    $periodoParam = "%$periodoDesejado%";
    $stmtPeriodo = $conn->prepare($sqlPeriodo);
    
    if ($stmtPeriodo === false) {
        error_log("DEBUG: Erro ao preparar consulta para período: " . $conn->error);
        // Tentar criar o período diretamente
        $idPeriodo = criarPeriodo($conn, $periodoDesejado);
    } else {
        $stmtPeriodo->bind_param("s", $periodoParam);
        $stmtPeriodo->execute();
        $resultPeriodo = $stmtPeriodo->get_result();
        
        if ($resultPeriodo->num_rows === 0) {
            // Se o período não existir, criá-lo
            $idPeriodo = criarPeriodo($conn, $periodoDesejado);
        } else {
            $rowPeriodo = $resultPeriodo->fetch_assoc();
            $idPeriodo = $rowPeriodo['id'];
        }
    }
    
    if ($idPeriodo === null) {
        return [
            'id_turma' => null,
            'id_periodo' => null,
            'nome_turma' => null,
            'mensagem' => "Erro ao verificar ou criar período. Contate o administrador."
        ];
    }
    
    // NOVA IMPLEMENTAÇÃO: Buscar turmas existentes para este curso e classe
    // 1. Buscar turmas que começam com o prefixo gerado
    $sqlBuscarTurmas = "SELECT t.id, t.nome_turma, 
                      (SELECT COUNT(*) FROM matricula WHERE id_turma = t.id) AS total_alunos
                      FROM turma t
                      WHERE t.nome_turma LIKE ?
                      ORDER BY t.nome_turma ASC";
    
    $prefixoBusca = $prefixoTurma . "%";
    $stmtBuscarTurmas = $conn->prepare($sqlBuscarTurmas);
    
    if ($stmtBuscarTurmas === false) {
        error_log("DEBUG: Erro ao preparar consulta para buscar turmas: " . $conn->error);
        return [
            'id_turma' => null,
            'id_periodo' => null,
            'nome_turma' => null,
            'mensagem' => "Erro ao buscar turmas existentes. Contate o administrador."
        ];
    }
    
    $stmtBuscarTurmas->bind_param("s", $prefixoBusca);
    $stmtBuscarTurmas->execute();
    $resultTurmas = $stmtBuscarTurmas->get_result();
    
    $turmasExistentes = [];
    $temTurmaDisponivel = false;
    $idTurmaDisponivel = null;
    $nomeTurmaDisponivel = null;
    
    // Verificar turmas existentes
    if ($resultTurmas->num_rows > 0) {
        while ($rowTurma = $resultTurmas->fetch_assoc()) {
            $turmasExistentes[] = [
                'id' => $rowTurma['id'],
                'nome' => $rowTurma['nome_turma'],
                'total_alunos' => $rowTurma['total_alunos']
            ];
            
            // Verificar se esta turma tem vaga (menos de 60 alunos)
            if ($rowTurma['total_alunos'] < 60 && !$temTurmaDisponivel) {
                $temTurmaDisponivel = true;
                $idTurmaDisponivel = $rowTurma['id'];
                $nomeTurmaDisponivel = $rowTurma['nome_turma'];
            }
        }
    }
    
    // Debug das turmas encontradas
    if (!empty($turmasExistentes)) {
        error_log("DEBUG: Turmas existentes encontradas para o prefixo '$prefixoBusca': " . json_encode($turmasExistentes));
    } else {
        error_log("DEBUG: Nenhuma turma existente encontrada para o prefixo '$prefixoBusca'");
    }
    
    // 2. Se encontrou uma turma com vagas, usar esta
    if ($temTurmaDisponivel) {
        error_log("DEBUG: Usando turma existente com vagas: $nomeTurmaDisponivel (ID: $idTurmaDisponivel)");
        return [
            'id_turma' => $idTurmaDisponivel,
            'id_periodo' => $idPeriodo,
            'nome_turma' => $nomeTurmaDisponivel,
            'mensagem' => "Aluno alocado na turma $nomeTurmaDisponivel do período $periodoDesejado."
        ];
    }
    
    // 3. Caso não exista turma ou todas estejam cheias, criar uma nova turma
    $novaTurmaNome = '';
    
    if (empty($turmasExistentes)) {
        // Nenhuma turma existe, criar a primeira (com sufixo A)
        $novaTurmaNome = $prefixoTurma . "A";
    } else {
        // Turmas existem mas estão cheias, criar a próxima letra
        $ultimaTurma = end($turmasExistentes);
        $ultimoNome = $ultimaTurma['nome'];
        $ultimaLetra = substr($ultimoNome, -1);
        
        // Verificar se é uma letra
        if (ctype_alpha($ultimaLetra)) {
            $novaLetra = chr(ord($ultimaLetra) + 1); // Incrementa a letra (A->B, B->C, etc.)
            $novaTurmaNome = substr($ultimoNome, 0, -1) . $novaLetra;
        } else {
            // Caso não consiga determinar a letra, adiciona um A
            $novaTurmaNome = $prefixoTurma . "A";
        }
    }
    
    // Criar a nova turma
    error_log("DEBUG: Criando nova turma: $novaTurmaNome");
    
    $sqlNovaTurma = "INSERT INTO turma (nome_turma, capacidade, id_sala) VALUES (?, 60, ?)";
    $stmtNovaTurma = $conn->prepare($sqlNovaTurma);
    
    if ($stmtNovaTurma === false) {
        error_log("DEBUG: Erro ao preparar consulta para criar turma: " . $conn->error);
        return [
            'id_turma' => null,
            'id_periodo' => null,
            'nome_turma' => null,
            'mensagem' => "Erro ao criar nova turma. Contate o administrador."
        ];
    }
    
    $stmtNovaTurma->bind_param("si", $novaTurmaNome, $idSala);
    
    if ($stmtNovaTurma->execute()) {
        $idNovaTurma = $conn->insert_id;
        error_log("DEBUG: Nova turma criada com sucesso. ID: $idNovaTurma, Nome: $novaTurmaNome");
        
        return [
            'id_turma' => $idNovaTurma,
            'id_periodo' => $idPeriodo,
            'nome_turma' => $novaTurmaNome,
            'mensagem' => "Nova turma $novaTurmaNome foi criada no período $periodoDesejado para acomodar o aluno."
        ];
    } else {
        error_log("DEBUG: Erro ao criar nova turma: " . $stmtNovaTurma->error);
        return [
            'id_turma' => null,
            'id_periodo' => null,
            'nome_turma' => null,
            'mensagem' => "Erro ao criar nova turma. Contate o administrador."
        ];
    }
}

// Função auxiliar para criar período quando não existe
function criarPeriodo($conn, $nomePeriodo) {
    error_log("DEBUG: Período '$nomePeriodo' não encontrado no banco. Tentando criar.");
    
    $sqlNovoPeriodo = "INSERT INTO periodo (nome) VALUES (?)";
    $stmtNovoPeriodo = $conn->prepare($sqlNovoPeriodo);
    
    if ($stmtNovoPeriodo === false) {
        error_log("DEBUG: Erro ao preparar consulta para criar período: " . $conn->error);
        return null;
    }
    
    $stmtNovoPeriodo->bind_param("s", $nomePeriodo);
    
    if ($stmtNovoPeriodo->execute()) {
        $idPeriodo = $conn->insert_id;
        error_log("DEBUG: Novo período criado com ID: $idPeriodo");
        return $idPeriodo;
    } else {
        error_log("DEBUG: Erro ao criar novo período: " . $stmtNovoPeriodo->error);
        return null;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Dados recebidos
    $sexo = $_POST['sexo'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $num_bi = $_POST['num_bi'] ?? '';
    $data_nasc = $_POST['data_nasc'] ?? '';
    $morada = $_POST['morada'] ?? '';
    $nome_pai = $_POST['nome_pai'] ?? '';
    $nome_mae = $_POST['nome_mae'] ?? '';
    $naturalidade = $_POST['naturalidade'] ?? '';
    $tel_1 = $_POST['tel_1'] ?? '';
    $tel_2 = $_POST['tel_2'] ?? '';
    $email = $_POST['email'] ?? '';
    $num_processo = $_POST['num_processo'] ?? '';
    $id_curso = $_POST['id_curso'] ?? null;
    $id_classe = $_POST['id_classe'] ?? null;
    $id_sala = $_POST['id_sala'] ?? null;

    // Upload da foto
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $diretorio = 'uploads/';
        if (!is_dir($diretorio)) mkdir($diretorio, 0755, true);

        $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nome_arquivo = uniqid('foto_') . '.' . strtolower($extensao);
        $caminho_completo = $diretorio . $nome_arquivo;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminho_completo)) {
            $foto = $caminho_completo;
        } else {
            $mensagem = "Erro ao fazer upload da foto.";
        }
    }

    // Verificar se tudo foi preenchido
    if (!empty($nome) && !empty($num_bi) && !empty($num_processo) && !empty($foto)
        && $id_curso && $id_classe && $id_sala) {
        
        try {
            // Calcular idade do aluno
            $idade = calcularIdade($data_nasc);
            
            // 1. Inserir na tabela pessoa
            $sqlPessoa = "INSERT INTO pessoa (sexo, nome, num_bi, data_nasc, morada, nome_pai, nome_mae, naturalidade, tel_1, tel_2, email, foto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtPessoa = $conn->prepare($sqlPessoa);
            $stmtPessoa->bind_param("ssssssssssss", $sexo, $nome, $num_bi, $data_nasc, $morada, $nome_pai, $nome_mae, $naturalidade, $tel_1, $tel_2, $email, $foto);
            
            if (!$stmtPessoa->execute()) {
                throw new Exception("Erro ao inserir na tabela pessoa: " . $stmtPessoa->error);
            }
            
            // Obter o id da pessoa inserida
            $id_pessoa = $conn->insert_id;
            
            if (!$id_pessoa) {
                throw new Exception("Erro: Não foi possível obter o ID da pessoa cadastrada.");
            }
            
            // 3. Determinar turma com base na idade, classe e curso - UTILIZANDO A SALA SELECIONADA
            $resultadoTurma = determinarTurma($conn, $idade, $id_classe, $id_curso, $id_sala);
            
            // Se não encontrou turma disponível
            if ($resultadoTurma['id_turma'] === null) {
                throw new Exception($resultadoTurma['mensagem']);
            }
            
            // Atribuir turma e período determinados
            $id_turma = $resultadoTurma['id_turma'];
            $id_periodo = $resultadoTurma['id_periodo'];
            $nome_turma = $resultadoTurma['nome_turma'];
            
            // 2. Inserir na tabela aluno - AGORA COM O ID DA TURMA
            $sqlAluno = "INSERT INTO aluno (id_pessoa, num_processo, id_curso, id_turma, id_classe) VALUES (?, ?, ?, ?, ?)";
            $stmtAluno = $conn->prepare($sqlAluno);
            $stmtAluno->bind_param("isiii", $id_pessoa, $num_processo, $id_curso, $id_turma, $id_classe);
            
            if (!$stmtAluno->execute()) {
                throw new Exception("Erro ao inserir na tabela aluno: " . $stmtAluno->error);
            }
            
            // Ano Letivo
            $sqlAno = "SELECT id FROM anolectivo ORDER BY id DESC LIMIT 1";
            $resultAno = $conn->query($sqlAno);
            if (!$resultAno || $resultAno->num_rows == 0) {
                throw new Exception("Erro: Não há anos letivos cadastrados no sistema.");
            }
            $rowAno = $resultAno->fetch_assoc();
            $id_ano = $rowAno['id'];
            
            // Inserir na tabela matrícula
            $sqlMatricula = "INSERT INTO matricula (data, id_pessoa, id_curso, id_classe, id_periodo, id_sala, id_turma, id_ano) 
                            VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)";
            $stmtMatricula = $conn->prepare($sqlMatricula);
            
            if (!$stmtMatricula) {
                throw new Exception("Erro ao preparar consulta de matrícula: " . $conn->error);
            }
            
            $stmtMatricula->bind_param("iiiiiii", $id_pessoa, $id_curso, $id_classe, $id_periodo, $id_sala, $id_turma, $id_ano);
            
            if (!$stmtMatricula->execute()) {
                throw new Exception("Erro ao inserir na matrícula: " . $stmtMatricula->error);
            }
            
            $mensagem = "Aluno cadastrado com sucesso! " . $resultadoTurma['mensagem'];
            
        } catch (Exception $e) {
            $mensagem = "Erro: " . $e->getMessage();
        }
    } else {
        $mensagem = "Por favor, preencha todos os campos obrigatórios corretamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Aluno</title>
    <link rel="stylesheet" href="../bootstrap-icons/font/bootstrap-icons.css">
    <link href="bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
       :root {
        --primary: #0d6efd;
            --secondary: #0062cc;
            --accent: #4285f4;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --sidebar-width: 280px;
            --header-height: 70px;
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            color: white;
            z-index: 999;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo-container {
            padding: 0px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar .logo {
            width: 60px;
            height: 50px;
            background-color: white;
            border-radius: 50%;
            padding: 5px;
            margin-bottom: 10px;
            
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 3px solid white;
        }

      

        .sidebar i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .sidebar .menu-category {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
            padding: 20px 25px 10px;
            margin-top: 10px;
        }

        /* Header Styling */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px 0 calc(var(--sidebar-width) + 30px);
            z-index: 998;
            transition: all var(--transition-speed);
        }

        .header .user-info {
            display: flex;
            align-items: center;
        }

        .header .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
        }

        .header .user-name {
            font-weight: 500;
            color: var(--dark);
        }

        .header .user-role {
            font-size: 12px;
            color: #6c757d;
        }

        .toggle-sidebar {
            background-color: transparent;
            border: none;
            color: var(--primary);
            font-size: 20px;
            cursor: pointer;
            display: none;
        }

        /* Content Styling */
        .content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            padding-top: calc(var(--header-height) + 30px);
            min-height: 100vh;
            transition: all var(--transition-speed);
        }

        .page-title {
            margin-bottom: 25px;
            color: var(--primary);
            font-weight: 600;
        }

        .stats-row {
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 25px;
            transition: transform 0.3s;
            border-left: 4px solid var(--accent);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.primary {
            border-left-color: var(--primary);
        }

        .stat-card.success {
            border-left-color: var(--success);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.info {
            border-left-color: var(--info);
        }

        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background-color: rgba(66, 133, 244, 0.1);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-card.primary .stat-icon {
            background-color: rgba(26, 47, 94, 0.1);
            color: var(--primary);
        }

        .stat-card.success .stat-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .stat-card.warning .stat-icon {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .stat-card.info .stat-icon {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .stat-card .stat-title {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .stat-card .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
        }

        /* Feature Cards Styling */
        .feature-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            height: 100%;
            transition: all 0.3s;
            border-top: 4px solid var(--accent);
            display: flex;
            flex-direction: column;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .feature-card.primary {
            border-top-color: var(--primary);
        }

        .feature-card.success {
            border-top-color: var(--success);
        }

        .feature-card.warning {
            border-top-color: var(--warning);
        }

        .feature-card.info {
            border-top-color: var(--info);
        }

        .feature-card.danger {
            border-top-color: var(--danger);
        }

        .feature-card .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background-color: rgba(66, 133, 244, 0.1);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .feature-card.primary .feature-icon {
            background-color: rgba(26, 47, 94, 0.1);
            color: var(--primary);
        }

        .feature-card.success .feature-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .feature-card.warning .feature-icon {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .feature-card.info .feature-icon {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .feature-card.danger .feature-icon {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .feature-card .feature-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .feature-card .feature-description {
            color: #6c757d;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .btn-feature {
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
        }

        .btn-feature i {
            margin-right: 8px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #153057;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-warning {
            background-color: var(--warning);
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-info {
            background-color: var(--info);
            color: white;
        }

        .btn-info:hover {
            background-color: #138496;
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        /* Footer Styling */
        footer {
            margin-top: 50px;
            padding: 20px 0;
            background-color: white;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .header {
                padding-left: 30px;
            }

            .content {
                margin-left: 0;
            }

            .toggle-sidebar {
                display: block;
            }

            .content.sidebar-active {
                margin-left: var(--sidebar-width);
            }

            .header.sidebar-active {
                padding-left: calc(var(--sidebar-width) + 30px);
            }
        }

        @media (max-width: 767.98px) {
            .header {
                padding: 0 15px;
            }

            .content {
                padding: 20px;
                padding-top: calc(var(--header-height) + 20px);
            }

            .user-role {
                display: none;
            }
        }
    </style>
<body>


<div class="sidebar" id="sidebar">
    <div class="logo-container">
        
        <h5>Instituto Politécnico<br>"30 de Setembro"</h5>
    </div>
    
    <a href="admin.php"><i class="bi bi-house-door"></i> Início</a>
    <a href="cadastro_professor.php"><i class="bi bi-person-plus"></i> Cadastrar Professores</a>
    <a href="Cadastro_aluno.php"><i class="bi bi-person"></i> Cadastrar Alunos</a>
    <a href="cadastro_disciplina.php"><i class="bi bi-book"></i> Cadastrar Disciplinas</a>
    <a href="turma.php"><i class="bi bi-people-fill"></i> Gestão de Turmas</a>
    
    <a href="lançamento_notas_admin.php" class="active"><i class="bi bi-pencil-square"></i> Lançamento de Notas</a>
    <a href="listar_matriculas.php"><i class="bi bi-card-list"></i> Visualizar Matrículas</a>
    <a href="visualizar_professor.php"><i class="bi bi-person-lines-fill"></i> Visualizar Professores</a>
    <a href="pedagogico.php"><i class="bi bi-calendar-check"></i> Calendário Acadêmico</a>
    <a href="atribuicao_disc.php"><i class="bi bi-person-workspace"></i> Atribuir Disciplinas</a>
       
    <a href="atualizar_senha.php"><i class="bi bi-key"></i> Cadastrar Usuário</a>
    </div>

<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh; margin-left: 29px;">
   

    <?php if(isset($mensagem)): ?>
        <div class="alert <?php echo (strpos($mensagem, 'sucesso') !== false) ? 'alert-success' : 'alert-danger'; ?>" role="alert">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
            <h2 class="page-title">Cadastro de Aluno</h2>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="sexo" class="form-label">Sexo</label>
                        <select class="form-select" id="sexo" name="sexo" required>
                            <option value="">Selecione</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Feminino</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="num_bi" class="form-label">Número do BI</label>
                        <input type="text" class="form-control" id="num_bi" name="num_bi" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="data_nasc" class="form-label">Data de Nascimento </label>
                        <input type="date" class="form-control" id="data_nasc" name="data_nasc" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="naturalidade" class="form-label">Naturalidade</label>
                        <input type="text" class="form-control" id="naturalidade" name="naturalidade">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="morada" class="form-label">Morada</label>
                        <input type="text" class="form-control" id="morada" name="morada">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome_pai" class="form-label">Nome do Pai</label>
                        <input type="text" class="form-control" id="nome_pai" name="nome_pai">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nome_mae" class="form-label">Nome da Mãe</label>
                        <input type="text" class="form-control" id="nome_mae" name="nome_mae">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="tel_1" class="form-label">Telefone Princpal</label>
                        <input type="tel" class="form-control" id="tel_1" name="tel_1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="tel_2" class="form-label">Telefone Alternativo</label>
                        <input type="tel" class="form-control" id="tel_2" name="tel_2">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="foto" class="form-label">Foto do Aluno</label>
                        <input type="file" class="form-control" id="foto" name="foto" accept="image/*" required>
                    </div>
                </div>
                <h5>Informação do Aluno</h5>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="num_processo" class="form-label">Número de Processo </label>
                        <input type="text" class="form-control" id="num_processo" name="num_processo" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="id_curso" class="form-label">Curso </label>
                        <select class="form-select" id="id_curso" name="id_curso" required>
                            <option value="">Selecione</option>
                            <?php foreach($cursos as $curso): ?>
                                <option value="<?php echo $curso['id']; ?>"><?php echo $curso['nome_curso']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="id_classe" class="form-label">Classe </label>
                        <select class="form-select" id="id_classe" name="id_classe" required>
                            <option value="">Selecione</option>
                            <?php foreach($classes as $classe): ?>
                                <option value="<?php echo $classe['id']; ?>"><?php echo $classe['nome_classe']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                 <!-- Sala -->
                 <div class="col-md-6">
                    <label for="id_sala" class="form-label">Sala:</label>
                    <select name="id_sala" class="form-select" required>
                        <option value="">Selecione</option>
                        <?= getOptions($conn, "SELECT id, num_sala FROM sala", "id", "num_sala") ?>
                    </select>
                </div>

             

                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Cadastrar Aluno
                        </button>
                      
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>



<script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle sidebar
    document.getElementById('toggleSidebar').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('content').classList.toggle('sidebar-active');
        document.getElementById('header').classList.toggle('sidebar-active');
    });
</script>
</body>
</html>