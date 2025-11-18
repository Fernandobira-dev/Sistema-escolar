<?php
session_start();

include 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_pessoa'])) {
    echo "Usuário não logado!";
    exit;
}

$id_pessoa = $_SESSION['id_pessoa'];


// Busca dados do professor
$sql_professor = "SELECT 
                    p.id_pessoa, p.nome, p.sexo, p.num_bi, p.data_nasc, p.morada, 
                    p.nome_pai, p.nome_mae, p.naturalidade, p.tel_1, p.tel_2, p.email, p.foto, 
                    pr.num_agente, pr.formacao, pr.ativo
                 FROM pessoa p
                 INNER JOIN professor pr ON pr.id_pessoa = p.id_pessoa
                 WHERE p.id_pessoa = ?";
$stmt_professor = $conn->prepare($sql_professor);
if (!$stmt_professor) {
    die('Erro ao preparar consulta: ' . $conn->error);
}
$stmt_professor->bind_param("i", $id_pessoa);
$stmt_professor->execute();
$result_professor = $stmt_professor->get_result();

if ($result_professor->num_rows > 0) {
    $professor = $result_professor->fetch_assoc();
    $mensagemBoasVindas = "Bem-vindo(a), " . $professor['nome'] . "!";
} else {
    echo "Nenhum dado encontrado para este professor!";
    exit;
}

if (!isset($_SESSION['id_pessoa'])) {
    header("Location: login.php");
    exit;
}


$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'setembro';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$id_professor_logado = $_SESSION['id_pessoa'] ?? 0;

$filtros = [
    'id_disc' => $_POST['id_disc'] ?? null,
    'id_curso' => $_POST['id_curso'] ?? null,
    'id_classe' => $_POST['id_classe'] ?? null,
    'id_trimestre' => $_POST['id_trimestre'] ?? null,
    'id_turma' => $_POST['id_turma'] ?? null,
];

// Messages array for feedback
$messages = [];

// Lançamento de notas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lancar_notas'])) {
    $notas = $_POST['nota'] ?? [];
    $id_prova = $_POST['id_prova'] ?? 0;
    $id_trimestre = $_POST['id_trimestre'] ?? 0;
    $id_turma = $_POST['id_turma'] ?? 0;
    $id_curso = $_POST['id_curso'] ?? 0;
    $id_classe = $_POST['id_classe'] ?? 0;
    $data_lancamento = date('Y-m-d H:i:s');
    
    $notas_lancadas = 0;
    $notas_atualizadas = 0;
    $erros = 0;
    
    try {
        $conn->begin_transaction();
        
        foreach ($notas as $id_aluno => $disciplinas) {
            foreach ($disciplinas as $id_disc => $nota) {
                if (!empty($nota) && is_numeric($nota)) {
                    // Verificar se já existe uma nota para este aluno/disciplina/prova/trimestre
                    $stmt = $conn->prepare("SELECT id FROM minipauta WHERE id_aluno = ? AND id_prova = ? AND id_trimestre = ? AND id_disc = ?");
                    $stmt->bind_param("iiii", $id_aluno, $id_prova, $id_trimestre, $id_disc);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        // Atualizar nota existente
                        $stmt = $conn->prepare("UPDATE minipauta SET nota = ?, data_lancamento = ? WHERE id_aluno = ? AND id_prova = ? AND id_trimestre = ? AND id_disc = ?");
                        $stmt->bind_param("dsiiii", $nota, $data_lancamento, $id_aluno, $id_prova, $id_trimestre, $id_disc);
                        if ($stmt->execute()) {
                            $notas_atualizadas++;
                        } else {
                            $erros++;
                        }
                    } else {
                        // Inserir nova nota
                        $stmt = $conn->prepare("INSERT INTO minipauta (nota, data_lancamento, id_prova, id_professor, id_trimestre, id_aluno, id_disc) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("dsiiiii", $nota, $data_lancamento, $id_prova, $id_professor_logado, $id_trimestre, $id_aluno, $id_disc);
                        if ($stmt->execute()) {
                            $notas_lancadas++;
                        } else {
                            $erros++;
                        }
                    }
                }
            }
        }
        
        $conn->commit();
        
        // Preparar mensagem de sucesso
        $total_operacoes = $notas_lancadas + $notas_atualizadas;
        if ($total_operacoes > 0) {
            $mensagem_sucesso = "Operação realizada com sucesso! ";
            if ($notas_lancadas > 0) {
                $mensagem_sucesso .= "$notas_lancadas nota(s) lançada(s). ";
            }
            if ($notas_atualizadas > 0) {
                $mensagem_sucesso .= "$notas_atualizadas nota(s) atualizada(s). ";
            }
            if ($erros > 0) {
                $mensagem_sucesso .= "Porém, $erros erro(s) ocorreram.";
            }
            $_SESSION['mensagem_sucesso'] = $mensagem_sucesso;
        } else {
            $_SESSION['mensagem_info'] = "Nenhuma nota foi processada. Verifique se preencheu os campos corretamente.";
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensagem_erro'] = "Erro ao processar as notas: " . $e->getMessage();
    }
    
    // Redirect to avoid form resubmission
    $redirect_url = $_SERVER['PHP_SELF'] . "?turma=" . $id_turma . "&curso=" . $id_curso . "&classe=" . $id_classe . "&trimestre=" . $id_trimestre . "&prova=" . $id_prova;
    header("Location: " . $redirect_url);
    exit();
}

// Buscar turmas atribuídas ao professor COM informações de curso e classe
$turmas = [];
$sql = "SELECT DISTINCT t.id, t.nome_turma, a.id_curso, c.nome_curso, a.id_classe, cl.nome_classe
        FROM turma t
        JOIN aluno a ON t.id = a.id_turma
        JOIN curso c ON a.id_curso = c.id
        JOIN classe cl ON a.id_classe = cl.id
        JOIN atribuicao_disc ad ON a.id_curso = ad.id_curso AND a.id_classe = ad.id_classe
        WHERE ad.id_prof = ?
        GROUP BY t.id, a.id_curso, a.id_classe
        ORDER BY t.nome_turma";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_professor_logado);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("Erro ao buscar turmas: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $turmas[] = $row;
}

// Buscar cursos atribuídos ao professor
$cursos = [];
$sql = "SELECT DISTINCT c.id, c.nome_curso 
        FROM curso c
        JOIN atribuicao_disc ad ON c.id = ad.id_curso
        WHERE ad.id_prof = ?
        ORDER BY c.nome_curso";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_professor_logado);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $cursos[] = $row;
}

// Buscar classes atribuídas ao professor
$classes = [];
$sql = "SELECT DISTINCT cl.id, cl.nome_classe 
        FROM classe cl
        JOIN atribuicao_disc ad ON cl.id = ad.id_classe
        WHERE ad.id_prof = ?
        ORDER BY cl.nome_classe";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_professor_logado);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
// Buscar disciplinas para o professor logado
$disciplinas = [];
$sql = "SELECT d.id, d.nome_disc 
        FROM disciplina d
        JOIN atribuicao_disc ad ON d.id = ad.id_disc
        WHERE ad.id_prof = ?
        ORDER BY d.nome_disc";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_professor_logado);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $disciplinas[] = $row;
}

// Buscar trimestres
$trimestres = [];
$result = $conn->query("SELECT id, num_tri FROM trimestre ORDER BY num_tri");
while ($row = $result->fetch_assoc()) {
    $trimestres[] = $row;
}

// Buscar tipos de provas
$tipos_provas = [
    ['id' => 1, 'nome' => 'MAC'],
    ['id' => 2, 'nome' => 'Prova Prof.'],
    ['id' => 3, 'nome' => 'Prova Trim.']
];

function calcularMedia($notas) {
    if (empty($notas)) return null;
    return array_sum($notas) / count($notas);
}

// Verificar se a requisição é AJAX para carregar tabela dinâmica
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'carregar_tabela') {
    $id_turma = $_POST['id_turma'] ?? 0;
    $id_curso = $_POST['id_curso'] ?? 0;
    $id_classe = $_POST['id_classe'] ?? 0;
    $id_trimestre = $_POST['id_trimestre'] ?? 0;
    $id_prova = $_POST['id_prova'] ?? 0;
    
    // Verificar se todos os filtros necessários foram fornecidos
    if (empty($id_turma) || empty($id_curso) || empty($id_classe) || empty($id_trimestre) || empty($id_prova)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        exit();
    }
    
    // Buscar alunos da turma selecionada
    $sql = "SELECT a.id_pessoa, p.nome, p.sexo 
            FROM aluno a
            JOIN pessoa p ON a.id_pessoa = p.id_pessoa
            WHERE a.id_turma = ? AND a.id_curso = ? AND a.id_classe = ?
            ORDER BY p.nome";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $id_turma, $id_curso, $id_classe);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $alunos = [];
    while ($row = $result->fetch_assoc()) {
        $alunos[$row['id_pessoa']] = $row;
    }
    
    // Buscar notas existentes
    $notas_existentes = [];
    if (!empty($alunos)) {
        $alunos_ids = array_keys($alunos);
        $placeholders = implode(',', array_fill(0, count($alunos_ids), '?'));
        
        $sql = "SELECT id_aluno, id_disc, nota 
                FROM minipauta 
                WHERE id_prova = ? AND id_trimestre = ? AND id_aluno IN ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        $types = 'ii' . str_repeat('i', count($alunos_ids));
        $params = array_merge([$id_prova, $id_trimestre], $alunos_ids);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $notas_existentes[$row['id_aluno']][$row['id_disc']] = $row['nota'];
        }
    }
    
    // Buscar informações do curso e classe
    $stmt = $conn->prepare("SELECT nome_curso FROM curso WHERE id = ?");
    $stmt->bind_param("i", $id_curso);
    $stmt->execute();
    $result = $stmt->get_result();
    $curso_nome = $result->fetch_assoc()['nome_curso'] ?? 'Desconhecido';
    
    $stmt = $conn->prepare("SELECT nome_classe FROM classe WHERE id = ?");
    $stmt->bind_param("i", $id_classe);
    $stmt->execute();
    $result = $stmt->get_result();
    $classe_nome = $result->fetch_assoc()['nome_classe'] ?? 'Desconhecida';
    
    $stmt = $conn->prepare("SELECT nome_turma FROM turma WHERE id = ?");
    $stmt->bind_param("i", $id_turma);
    $stmt->execute();
    $result = $stmt->get_result();
    $turma_nome = $result->fetch_assoc()['nome_turma'] ?? 'Desconhecida';
    
    // Buscar nome do tipo de prova
    $tipo_prova_nome = '';
    foreach ($tipos_provas as $tipo) {
        if ($tipo['id'] == $id_prova) {
            $tipo_prova_nome = $tipo['nome'];
            break;
        }
    }
    
    $stmt = $conn->prepare("SELECT num_tri FROM trimestre WHERE id = ?");
    $stmt->bind_param("i", $id_trimestre);
    $stmt->execute();
    $result = $stmt->get_result();
    $trimestre_num = $result->fetch_assoc()['num_tri'] ?? 'Desconhecido';
    
    // Gerar HTML da tabela
    ob_start();
    ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4>Lançamento de Notas</h4>
                <span><?= count($alunos) ?> alunos encontrados</span>
            </div>
        </div>
        <div class="card-body">
            
            
            <form method="POST" id="form-notas">
                <input type="hidden" name="id_prova" value="<?= $id_prova ?>">
                <input type="hidden" name="id_trimestre" value="<?= $id_trimestre ?>">
                <input type="hidden" name="id_curso" value="<?= $id_curso ?>">
                <input type="hidden" name="id_classe" value="<?= $id_classe ?>">
                <input type="hidden" name="id_turma" value="<?= $id_turma ?>">
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" rowspan="2">Nº</th>
                                <th class="text-center" rowspan="2">Nome</th>
                                <th class="text-center" rowspan="2">Sexo</th>
                                <th class="text-center" colspan="<?= count($disciplinas) ?>">Disciplinas</th>
                            </tr>
                            <tr>
                                <?php foreach ($disciplinas as $disciplina): ?>
                                    <th class="text-center"><?= $disciplina['nome_disc'] ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $contador = 1; ?>
                            <?php foreach ($alunos as $aluno): 
                                $notas = $notas_existentes[$aluno['id_pessoa']] ?? [];
                                
                                // Calcular média apenas se houver notas
                                $notas_array = [];
                                foreach ($disciplinas as $disciplina) {
                                    $nota_disc = $notas[$disciplina['id']] ?? '';
                                    if ($nota_disc !== '') $notas_array[] = $nota_disc;
                                }
                                
                                $media = empty($notas_array) ? null : array_sum($notas_array) / count($notas_array);
                                $situacao = $media !== null ? ($media >= 10 ? 'Com Aproveitamento' : 'Sem Aproveitamento') : 'N/A';
                                $classe_situacao = $situacao == 'Com Aproveitamento' ? 'transita' : ($situacao == 'Sem Aproveitamento' ? 'nao-transita' : '');
                            ?>
                            <tr>
                                <td class="text-center"><?= $contador++ ?></td>
                                <td><?= $aluno['nome'] ?></td>
                                <td class="text-center"><?= $aluno['sexo'] ?></td>
                                <?php foreach ($disciplinas as $disciplina): 
                                    $nota_value = $notas[$disciplina['id']] ?? '';
                                ?>
                                <td>
                                    <input type="number" name="nota[<?= $aluno['id_pessoa'] ?>][<?= $disciplina['id'] ?>]" value="<?= $nota_value ?>" 
                                           class="form-control form-control-sm nota-input" min="0" max="20" step="0.1" 
                                           placeholder="0.0">
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" name="lancar_notas" class="btn btn-success btn-lg">
                            <i class="bi bi-save"></i> Lançar Notas
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg ms-2" onclick="limparNotas()">
                            <i class="bi bi-arrow-clockwise"></i> Limpar Campos
                        </button>
                        <button type="button" class="btn btn-info btn-lg ms-2" onclick="validarNotas()">
                            <i class="bi bi-check-circle"></i> Validar Notas
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Função para limpar todas as notas
        function limparNotas() {
            if (confirm('Tem certeza que deseja limpar todas as notas preenchidas?')) {
                $('.nota-input').val('');
                $('.media-cell').html('<strong>-</strong>');
                $('.aproveitamento-cell').html('N/A').removeClass('transita nao-transita');
            }
        }
        
        // Função para validar notas antes do envio
        function validarNotas() {
            let notasPreenchidas = 0;
            let notasInvalidas = 0;
            
            $('.nota-input').each(function() {
                const valor = $(this).val();
                if (valor !== '') {
                    notasPreenchidas++;
                    const numero = parseFloat(valor);
                    if (isNaN(numero) || numero < 0 || numero > 20) {
                        notasInvalidas++;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                }
            });
            
            let mensagem = `Validação das Notas:\n\n`;
            mensagem += `• Notas preenchidas: ${notasPreenchidas}\n`;
            if (notasInvalidas > 0) {
                mensagem += `• Notas inválidas: ${notasInvalidas}\n`;
                mensagem += `\nPor favor, corrija as notas destacadas em vermelho.`;
            } else {
                mensagem += `• Todas as notas estão válidas!\n`;
                if (notasPreenchidas > 0) {
                    mensagem += `\nVocê pode prosseguir com o lançamento.`;
                } else {
                    mensagem += `\nNenhuma nota foi preenchida ainda.`;
                }
            }
            
            alert(mensagem);
        }
        
        // Configurar manipuladores de eventos para os inputs de notas
        $('.nota-input').on('input', function() {
            const valor = parseFloat($(this).val());
            
            // Validar intervalo
            if (!isNaN(valor)) {
                if (valor < 0) $(this).val(0);
                if (valor > 20) $(this).val(20);
            }
            
            // Recalcular média da linha
            recalcularMediaLinha($(this).closest('tr'));
        });
        
        // Confirmação antes do envio
        $('#form-notas').on('submit', function(e) {
            const notasPreenchidas = $('.nota-input').filter(function() {
                return $(this).val() !== '';
            }).length;
            
            if (notasPreenchidas === 0) {
                e.preventDefault();
                alert('Por favor, preencha pelo menos uma nota antes de enviar.');
                return false;
            }
            
            const confirmacao = confirm(`Você está prestes a lançar ${notasPreenchidas} nota(s). Deseja continuar?`);
            if (!confirmacao) {
                e.preventDefault();
                return false;
            }
        });
        
        // Função para recalcular média de uma linha
        function recalcularMediaLinha(linha) {
            let soma = 0;
            let contador = 0;
            
            linha.find('.nota-input').each(function() {
                const valor = parseFloat($(this).val());
                if (!isNaN(valor) && $(this).val() !== '') {
                    soma += valor;
                    contador++;
                }
            });
            
            if (contador > 0) {
                const media = soma / contador;
                linha.find('.media-cell').html('<strong>' + media.toFixed(1) + '</strong>');
                
                const aproveitamento = media >= 10 ? 'Com Aproveitamento' : 'Sem Aproveitamento';
                const classeAproveitamento = media >= 10 ? 'transita' : 'nao-transita';
                
                linha.find('.aproveitamento-cell')
                    .html(aproveitamento)
                    .removeClass('transita nao-transita')
                    .addClass(classeAproveitamento);
            } else {
                linha.find('.media-cell').html('<strong>-</strong>');
                linha.find('.aproveitamento-cell')
                    .html('N/A')
                    .removeClass('transita nao-transita');
            }
        }
    </script>
    <?php
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lançamento de Notas</title>
    <link href="bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
     <style>
        :root {
            --primary-color:rgb(59, 127, 228);
            --secondary-color:rgb(59, 127, 228);
            --accent-color: #f3f6fa;
            --text-color: #333;
            --light-text: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 80px;
            color: var(--text-color);
        }
        
        /* Header styling */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            z-index: 1030;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            padding: 0 20px;
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
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-name {
            font-size: 0.9rem;
            margin: 0;
        }
        
        .user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        /* Sidebar styling */
        .sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            height: calc(100vh - 70px);
            width: 250px;
            background-color: white;
            box-shadow: var(--box-shadow);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1020;
            overflow-y: auto;
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .sidebar .nav-link {
            color: var(--text-color);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
            color: var(--secondary-color);
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        
        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        
        /* Main content styling */
        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
            padding: 20px;
        }
        
        .main-content.sidebar-active {
            margin-left: 250px;
        }
        
        .professor-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .professor-card-header {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        
        .professor-card-header h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        
        .professor-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: var(--box-shadow);
            margin: 0 auto;
        }
        
        .professor-card-body {
            padding: 30px;
        }
        
        .info-group {
            margin-bottom: 25px;
        }
        
        .info-group h3 {
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 12px;
        }
        
        .info-label {
            width: 40%;
            font-weight: 600;
            color: var(--light-text);
        }
        
        .info-value {
            width: 60%;
        }
        
        /* Menu toggle button */
        .menu-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1040;
            width: 30px;
            height: 30px;
            background: transparent;
            border: none;
            color: white;
        }
        
        /* Footer styling */
        footer {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 0.9rem;
        }
        
        /* Responsive adjustments */
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 250px;
            }
            
            .menu-toggle {
                display: none;
            }
        }
        
        @media (max-width: 991.98px) {
            .menu-toggle {
                display: block;
            }
        }
    
        #icon-30{
          width: 60px;
          height: 50px;
          margin-right: 2%;
        }
        
        .radio-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .radio-item {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .radio-item:hover {
            background-color: #f8f9fa;
        }
        
        .radio-item.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .radio-item.pre-selected {
            background-color: #e3f2fd;
            border-color: var(--primary-color);
        }
        
        .radio-item input[type="radio"] {
            margin-right: 8px;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            margin: 30px 0;
        }
        
        .no-turmas-message {
            text-align: center;
            padding: 30px;
        }
        
        .transita {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            font-weight: bold;
        }
        
        .nao-transita {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            font-weight: bold;
        }
     
            .navbar-brand{
    color: white !important; /* Garantir que o texto dos links seja branco */
}
    </style>
</head>
<body>
    <!-- Header -->
   <header class="header">
        <button class="menu-toggle"><i class="bi bi-list fs-4"></i></button>
        
        <div class="header-content">
             <div class="logo">
            <img src="icon.png" alt="icon" id="icon-30">
                 <a class="navbar-brand" href="#">INSTITUTO POLITÉCNICO 30 DE SETEMBRO</a>
            </div>
            
            <div class="user-info">
                <p class="user-name"><?= $mensagemBoasVindas ?></p>
                <?php
                $caminhoFoto = 'uploads/' . $professor['foto'];
                if (!empty($professor['foto']) && file_exists($caminhoFoto)) {
                    echo "<img src='$caminhoFoto' alt='Foto do professor' class='user-photo'>";
                } else {
                    echo "<img src='uploads/default.jpg' alt='Foto padrão' class='user-photo'>";
                }
                ?>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-header">
            <h5>Painel do Professor</h5>
        </div>
        
        <nav class="nav flex-column">
            <a href="professor.php" class="nav-link"><i class="bi bi-house"></i>Página Inicial</a>
            <a href="lançamento_notas_professor.php" class="nav-link"><i class="bi bi-journal-bookmark-fill"></i>Lançamento de Notas</a>
            <a href="distribuição_disc.php" class="nav-link"><i class="bi bi-book"></i>Minhas Disciplinas</a>
            <a href="Comunicação_prof.php" class="nav-link"><i class="bi bi-calendar-week"></i>Calendário Acadêmico</a>
            <a href="admin_reclamacoes.php" class="nav-link"><i class="bi bi-chat-dots"></i>Reclamações</a>
         <a href="Altera_senha_professor.php" class="nav-link"><i class="bi bi-key menu-icon"></i>Segurança</a>

        </nav>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="btn btn-outline-secondary w-100"><i class="bi bi-box-arrow-left me-2"></i>Encerrar Sessão</a>
        </div>
    </aside>
    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="container-fluid">
            <!-- Messages -->
            <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= $_SESSION['mensagem_sucesso'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensagem_sucesso']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['mensagem_info'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <?= $_SESSION['mensagem_info'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensagem_info']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['mensagem_erro'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= $_SESSION['mensagem_erro'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensagem_erro']); ?>
            <?php endif; ?>

            <!-- Page Title -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    
                </h1>
                
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-book me-2"></i>
                    </h5>
                </div>
                <div class="card-body">
                    <form id="form-filtros" method="POST">
                        <input type="hidden" name="ajax_action" value="carregar_tabela">
                        
                        <div class="row">
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label for="id_turma" class="form-label">Turma</label>
                                <select class="form-select" id="id_turma" name="id_turma" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($turmas as $turma): ?>
                                        <option value="<?= $turma['id'] ?>" 
                                                data-curso="<?= $turma['id_curso'] ?>" 
                                                data-classe="<?= $turma['id_classe'] ?>">
                                            <?= $turma['nome_turma'] ?> - <?= $turma['nome_curso'] ?> (<?= $turma['nome_classe'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label for="id_curso" class="form-label">Curso</label>
                                <select class="form-select" id="id_curso" name="id_curso" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($cursos as $curso): ?>
                                        <option value="<?= $curso['id'] ?>"><?= $curso['nome_curso'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label for="id_classe" class="form-label">Classe</label>
                                <select class="form-select" id="id_classe" name="id_classe" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($classes as $classe): ?>
                                        <option value="<?= $classe['id'] ?>"><?= $classe['nome_classe'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label for="id_trimestre" class="form-label">Trimestre</label>
                                <select class="form-select" id="id_trimestre" name="id_trimestre" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($trimestres as $trimestre): ?>
                                        <option value="<?= $trimestre['id'] ?>"><?= $trimestre['num_tri'] ?>º Trimestre</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label for="id_prova" class="form-label">Tipo de Prova</label>
                                <select class="form-select" id="id_prova" name="id_prova" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($tipos_provas as $tipo): ?>
                                        <option value="<?= $tipo['id'] ?>"><?= $tipo['nome'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100" id="btn-carregar">
                                    <i class="bi bi-search me-1"></i>
                                    <span class="btn-text">Carregar Minipauta</span>
                                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Container para a tabela de notas -->
            <div id="tabela-container">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-clipboard-data text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">Selecione os campos acima para carregar as notas</h5>
                        <p class="text-muted">Preencha todos os campos e clique em "Carregar" para visualizar a tabela de lançamento de notas.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    


    <!-- Scripts -->
    <script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
        }

        // Auto-fill curso and classe when turma is selected
        $('#id_turma').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const curso = selectedOption.data('curso');
            const classe = selectedOption.data('classe');
            
            if (curso) {
                $('#id_curso').val(curso);
            }
            if (classe) {
                $('#id_classe').val(classe);
            }
        });

        // Form submission handler
        $('#form-filtros').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const btnCarregar = $('#btn-carregar');
            const btnText = btnCarregar.find('.btn-text');
            const spinner = btnCarregar.find('.spinner-border');
            
            // Show loading state
            btnCarregar.prop('disabled', true);
            btnText.text('Carregando...');
            spinner.removeClass('d-none');
            
            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#tabela-container').html(response.html);
                    } else {
                        alert('Erro: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição:', error);
                    alert('Erro ao carregar a tabela. Tente novamente.');
                },
                complete: function() {
                    // Hide loading state
                    btnCarregar.prop('disabled', false);
                    btnText.text('Carregar');
                    spinner.addClass('d-none');
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Initialize tooltips
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>