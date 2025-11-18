-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 15-Maio-2025 às 13:22
-- Versão do servidor: 10.4.27-MariaDB
-- versão do PHP: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `setembro`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `aluno`
--

CREATE TABLE `aluno` (
  `id_pessoa` int(11) NOT NULL,
  `num_processo` varchar(85) DEFAULT NULL,
  `id_classe` int(11) DEFAULT NULL,
  `id_curso` int(11) DEFAULT NULL,
  `id_turma` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `aluno`
--

INSERT INTO `aluno` (`id_pessoa`, `num_processo`, `id_classe`, `id_curso`, `id_turma`) VALUES
(29, '00123', 2, 1, 4),
(30, '001234', 3, 1, 6),
(31, '0012345', 4, 1, 10),
(47, '1234567890', 2, 2, 6),
(48, '1234512345', 2, 2, 5),
(55, '00123456', 1, 1, 8),
(56, '123123', 1, 1, 8),
(57, '1231234', 1, 1, 8),
(60, '123098', 1, 1, 8),
(61, '12300', 1, 1, 8),
(62, '1234001', 1, 1, 8),
(63, '1231234', 1, 1, 8),
(64, '1234123', 1, 1, 8),
(65, '1234512', 1, 1, 8),
(67, '123456', 1, 1, 8),
(68, '654321', 1, 1, 8),
(70, '123123456', 1, 1, 8),
(74, '1234561234556', 1, 1, 8),
(75, '123', 1, 1, 8),
(143, '1231', 4, 1, 30);

-- --------------------------------------------------------

--
-- Estrutura da tabela `anolectivo`
--

CREATE TABLE `anolectivo` (
  `id` int(11) NOT NULL,
  `ano` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `anolectivo`
--

INSERT INTO `anolectivo` (`id`, `ano`) VALUES
(1, '2024/2025'),
(2, '2025/2026');

-- --------------------------------------------------------

--
-- Estrutura da tabela `atribuicao`
--

CREATE TABLE `atribuicao` (
  `id_prof` int(11) NOT NULL,
  `id_disc` int(11) DEFAULT NULL,
  `id_anolectivo` int(11) DEFAULT NULL,
  `id_turma` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_classe` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `atribuicao`
--

INSERT INTO `atribuicao` (`id_prof`, `id_disc`, `id_anolectivo`, `id_turma`, `id_curso`, `id_classe`) VALUES
(3, 2, 1, 4, 1, 2),
(3, 9, 1, 4, 2, 2),
(3, 9, 1, 5, 1, 2),
(3, 9, 1, 6, 1, 3),
(3, 6, 1, 7, 1, 3),
(9, 4, 1, 4, 1, 2),
(9, 23, 1, 4, 2, 2),
(9, 3, 1, 5, 1, 2),
(9, 4, 1, 6, 1, 3),
(9, 15, 1, 7, 1, 3),
(9, 4, 1, 10, 1, 4),
(10, 10, 1, 4, 1, 3),
(10, 3, 1, 4, 2, 2),
(10, 1, 1, 5, 1, 2),
(10, 16, 1, 5, 2, 2),
(10, 7, 1, 7, 1, 3),
(19, 15, 1, 4, 1, 2),
(19, 15, 1, 4, 2, 2),
(19, 3, 1, 5, 1, 3),
(19, 1, 1, 7, 1, 3),
(19, 5, 1, 10, 1, 4),
(19, 3, 1, 11, 1, 4),
(20, 22, 1, 5, 1, 2),
(20, 2, 1, 6, 1, 3),
(20, 12, 1, 10, 1, 4),
(21, 6, 1, 4, 2, 2),
(21, 10, 1, 5, 1, 2),
(21, 22, 1, 7, 1, 3),
(21, 1, 2, 11, 1, 4),
(22, 7, 1, 4, 1, 2),
(22, 7, 1, 4, 2, 2),
(22, 8, 1, 11, 1, 4),
(23, 6, 1, 4, 1, 2),
(23, 4, 1, 4, 2, 2),
(23, 2, 2, 10, 1, 4),
(24, 11, 2, 4, 1, 2),
(24, 23, 2, 5, 1, 2),
(24, 23, 1, 7, 1, 3),
(24, 9, 1, 10, 1, 4),
(34, 12, 1, 1, 1, 1),
(34, 22, 1, 4, 2, 2),
(34, 21, 1, 8, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `atribuicao_disc`
--

CREATE TABLE `atribuicao_disc` (
  `id_prof` int(11) NOT NULL,
  `id_disc` int(11) NOT NULL,
  `id_anolectivo` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_classe` int(11) DEFAULT NULL,
  `id_turma` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `atribuicao_disc`
--

INSERT INTO `atribuicao_disc` (`id_prof`, `id_disc`, `id_anolectivo`, `id_curso`, `id_classe`, `id_turma`) VALUES
(34, 12, 1, 1, 1, 8),
(34, 20, 1, 1, 4, 10),
(34, 21, 1, 1, 1, 8);

-- --------------------------------------------------------

--
-- Estrutura da tabela `classe`
--

CREATE TABLE `classe` (
  `id` int(11) NOT NULL,
  `nome_classe` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `classe`
--

INSERT INTO `classe` (`id`, `nome_classe`) VALUES
(1, '13ª classe'),
(2, '10ªclasse'),
(3, '11ªclasse'),
(4, '12ªclasse');

-- --------------------------------------------------------

--
-- Estrutura da tabela `comunicados`
--

CREATE TABLE `comunicados` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `conteudo` text NOT NULL,
  `data_evento` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `comunicados`
--

INSERT INTO `comunicados` (`id`, `tipo`, `titulo`, `conteudo`, `data_evento`) VALUES
(4, 'Calendário de Provas', 'Prova do 3ª Trimestre', '1) Classe: 10º, Disciplina: TIC, Data: 2025-04-22, Hora: 13:00, Obs: Sem Atraso\r\n2) Classe: 10º, Disciplina: TLP, Data: 2025-04-22, Hora: 15:00, Obs: Sem Atraso\r\n3) Classe: 11º, Disciplina: TLP, Data: 2025-04-22, Hora: 13:00, Obs: Sem Atraso\r\n4) Classe: 11º, Disciplina: SEAC, Data: 2025-04-22, Hora: 15:00, Obs: Sem Atraso\r\n5) Classe: 12º, Disciplina: TLP, Data: 2025-04-22, Hora: 13:00, Obs: Sem Atraso\r\n6) Classe: 12ª, Disciplina: SEAC, Data: 2025-04-22, Hora: 15:00, Obs: Sem Atraso\r\n', '2025-04-22'),
(7, 'Aviso', 'Defesas Dos finalistas', 'Dia 1 de Junho', '2025-05-02');

-- --------------------------------------------------------

--
-- Estrutura da tabela `curso`
--

CREATE TABLE `curso` (
  `id` int(11) NOT NULL,
  `nome_curso` varchar(75) NOT NULL,
  `codigo` varchar(45) NOT NULL,
  `descricao` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `curso`
--

INSERT INTO `curso` (`id`, `nome_curso`, `codigo`, `descricao`) VALUES
(1, 'Técnico de Informática', '1', 'Engenharia'),
(2, 'Tenico de Instalações Ectrica', '2', 'Instalação');

-- --------------------------------------------------------

--
-- Estrutura da tabela `curso_disciplina`
--

CREATE TABLE `curso_disciplina` (
  `id_curso` int(11) NOT NULL,
  `id_disc` int(11) NOT NULL,
  `id_classe` int(11) DEFAULT NULL,
  `condicao` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `curso_disciplina`
--

INSERT INTO `curso_disciplina` (`id_curso`, `id_disc`, `id_classe`, `condicao`) VALUES
(1, 1, 2, 1),
(1, 2, 2, 1),
(1, 3, 2, 1),
(1, 4, 2, 1),
(1, 5, 4, 1),
(1, 6, 2, 1),
(1, 7, 2, 1),
(1, 9, 2, 1),
(1, 10, 2, 1),
(1, 11, 2, 1),
(1, 15, 2, 1),
(1, 21, 1, 1),
(1, 22, 2, 1),
(1, 23, 2, 1),
(2, 3, 2, 1),
(2, 4, 2, 1),
(2, 6, 2, 1),
(2, 7, 2, 1),
(2, 8, 2, 1),
(2, 9, 2, 1),
(2, 10, 2, 2),
(2, 17, 2, 1),
(2, 18, 2, 1),
(2, 19, 3, 1),
(2, 20, 3, 1),
(2, 22, 2, 1),
(2, 23, 2, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `disciplina`
--

CREATE TABLE `disciplina` (
  `id` int(11) NOT NULL,
  `nome_disc` varchar(45) NOT NULL,
  `descricao` varchar(100) DEFAULT NULL,
  `id_curso` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `disciplina`
--

INSERT INTO `disciplina` (`id`, `nome_disc`, `descricao`, `id_curso`) VALUES
(1, 'TLP', 'pp', NULL),
(2, 'SEAC', 'rede', NULL),
(3, 'Fisica', 'Aula pratica de fisica', NULL),
(4, 'Matemática', '', NULL),
(5, 'TREI', 'Reparação', NULL),
(6, 'LIngua Portuguesa', 'Arte do Bem Falar', NULL),
(7, 'Quimica', 'Elementos Quimico', NULL),
(8, 'OGI', 'Empriender', NULL),
(9, 'Empreendedorismo', 'Empreende ', NULL),
(10, 'Educação Fisica', 'Exercicios ', NULL),
(11, 'TIC', 'Informática', NULL),
(12, 'PT', 'Projecto', NULL),
(14, 'Instalações Electricas', 'Electricidade', NULL),
(15, 'Inglês', 'Lingua Etrangeira', NULL),
(16, 'Electricidade', 'Fio', NULL),
(17, 'Práticas Oficinais', 'Pratica', NULL),
(18, 'Tecnologia Eletrica', 'Tecno', NULL),
(19, 'Maquinas electrica', 'Maqui', NULL),
(20, 'Desenho Tecnico ', 'desenho', NULL),
(21, 'Estagio Suprevicionado', 'Estagio', NULL),
(22, 'Eletrotecnia', 'Calculo', NULL),
(23, 'FAI', 'aa', NULL),
(25, 'Práticas Oficinais', '', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `matricula`
--

CREATE TABLE `matricula` (
  `id` int(11) NOT NULL,
  `data` date NOT NULL,
  `id_pessoa` int(11) DEFAULT NULL,
  `id_ano` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `id_periodo` int(11) NOT NULL,
  `id_sala` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `id_classe` int(11) DEFAULT NULL,
  `status` enum('ativo','concluido','reprovado') DEFAULT 'ativo',
  `data_matricula` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `matricula`
--

INSERT INTO `matricula` (`id`, `data`, `id_pessoa`, `id_ano`, `id_curso`, `id_periodo`, `id_sala`, `id_turma`, `id_classe`, `status`, `data_matricula`) VALUES
(20, '2025-04-21', 29, 1, 1, 1, 7, 4, 2, 'ativo', '2025-04-30'),
(21, '2025-04-21', 30, 1, 1, 1, 8, 7, 3, 'ativo', '2025-04-30'),
(22, '2025-04-21', 31, 1, 1, 2, 9, 10, 4, 'ativo', '2025-04-30'),
(24, '2025-04-22', 17, 1, 1, 2, 7, 8, 1, 'ativo', '2025-04-30'),
(29, '2025-04-23', 47, 1, 2, 2, 9, 5, 2, 'ativo', '2025-04-30'),
(30, '2025-04-23', 48, 1, 2, 2, 5, 5, 2, 'ativo', '2025-04-30'),
(38, '2025-04-29', 55, 1, 1, 2, 9, 8, 1, 'ativo', '2025-04-30'),
(41, '2025-05-05', 56, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-05'),
(42, '2025-05-05', 57, 1, 1, 1, 9, 8, 1, 'ativo', '2025-05-05'),
(45, '2025-05-05', 60, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-05'),
(46, '2025-05-06', 61, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-06'),
(47, '2025-05-06', 62, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-06'),
(48, '2025-05-06', 63, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-06'),
(49, '2025-05-06', 64, 1, 1, 1, 9, 8, 1, 'ativo', '2025-05-06'),
(50, '2025-05-06', 65, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-06'),
(52, '2025-05-06', 67, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-06'),
(53, '2025-05-06', 68, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-06'),
(55, '2025-05-06', 70, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-06'),
(59, '2025-05-06', 74, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-06'),
(60, '2025-05-06', 75, 1, 1, 2, 9, 8, 1, 'ativo', '2025-05-06'),
(124, '2025-05-14', 143, 2, 1, 2, 10, 30, 4, 'ativo', '2025-05-14');

-- --------------------------------------------------------

--
-- Estrutura da tabela `minipauta`
--

CREATE TABLE `minipauta` (
  `id` int(11) NOT NULL,
  `nota` float NOT NULL,
  `data_lancamento` datetime NOT NULL,
  `id_prova` int(11) NOT NULL,
  `id_professor` int(11) NOT NULL,
  `id_trimestre` int(11) NOT NULL,
  `id_aluno` int(11) NOT NULL,
  `id_disc` int(11) NOT NULL,
  `id_pessoa` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `minipauta`
--

INSERT INTO `minipauta` (`id`, `nota`, `data_lancamento`, `id_prova`, `id_professor`, `id_trimestre`, `id_aluno`, `id_disc`, `id_pessoa`) VALUES
(92, 12, '2025-04-24 17:26:16', 2, 53, 2, 47, 1, NULL),
(93, 15, '2025-04-24 17:26:16', 2, 53, 2, 48, 1, NULL),
(94, 9, '2025-04-24 17:28:00', 3, 53, 3, 47, 2, NULL),
(95, 5, '2025-04-24 17:28:00', 3, 53, 3, 48, 2, NULL),
(99, 1, '2025-04-25 18:57:50', 2, 34, 1, 47, 21, NULL),
(100, 1, '2025-04-25 18:57:50', 2, 34, 1, 48, 21, NULL),
(101, 1, '2025-04-25 19:06:58', 3, 34, 1, 47, 1, NULL),
(102, 1, '2025-04-25 19:06:58', 3, 34, 1, 48, 1, NULL),
(103, 1, '2025-04-25 19:07:36', 2, 34, 1, 47, 1, NULL),
(110, 12, '2025-04-25 21:46:46', 2, 34, 2, 47, 5, NULL),
(111, 12, '2025-04-25 21:46:46', 3, 34, 2, 47, 5, NULL),
(112, 12, '2025-04-25 21:46:46', 1, 34, 2, 48, 5, NULL),
(113, 12, '2025-04-25 21:46:46', 2, 34, 2, 48, 5, NULL),
(114, 12, '2025-04-25 21:46:46', 3, 34, 2, 48, 5, NULL),
(121, 13, '2025-05-05 19:30:53', 1, 34, 2, 55, 21, NULL),
(122, 14, '2025-05-05 19:30:53', 2, 34, 2, 55, 21, NULL),
(123, 17, '2025-05-05 19:30:53', 3, 34, 2, 55, 21, NULL),
(125, 15, '2025-05-01 10:02:51', 1, 34, 1, 55, 4, NULL),
(126, 15, '2025-05-01 10:02:51', 2, 34, 1, 55, 4, NULL),
(127, 17, '2025-05-01 10:02:51', 3, 34, 1, 55, 4, NULL),
(128, 17, '2025-05-05 19:17:16', 2, 34, 1, 55, 12, NULL),
(129, 14, '2025-05-05 19:17:16', 3, 34, 1, 55, 12, NULL),
(130, 12, '2025-05-14 00:58:34', 1, 34, 3, 55, 12, NULL),
(131, 12, '2025-05-14 00:58:34', 2, 34, 3, 55, 12, NULL),
(132, 7, '2025-05-14 00:58:34', 3, 34, 3, 55, 12, NULL),
(133, 10, '2025-05-14 00:58:34', 1, 34, 3, 56, 12, NULL),
(134, 8, '2025-05-14 00:58:34', 2, 34, 3, 56, 12, NULL),
(135, 10, '2025-05-14 00:58:34', 3, 34, 3, 56, 12, NULL),
(136, 14, '2025-05-14 00:58:34', 1, 34, 3, 57, 12, NULL),
(137, 10, '2025-05-14 00:58:34', 2, 34, 3, 57, 12, NULL),
(140, 14, '2025-05-05 19:17:16', 2, 34, 1, 60, 12, NULL),
(141, 13, '2025-05-05 19:17:16', 3, 34, 1, 60, 12, NULL),
(143, 17, '2025-05-08 12:32:18', 2, 34, 1, 60, 21, NULL),
(144, 13, '2025-05-08 12:32:18', 3, 34, 1, 60, 21, NULL),
(145, 12, '2025-05-05 19:18:44', 1, 34, 2, 56, 12, NULL),
(146, 12, '2025-05-05 19:18:44', 2, 34, 2, 56, 12, NULL),
(147, 9, '2025-05-05 19:18:44', 3, 34, 2, 56, 12, NULL),
(148, 12, '2025-05-05 19:18:44', 1, 34, 2, 57, 12, NULL),
(149, 17, '2025-05-05 19:18:44', 2, 34, 2, 57, 12, NULL),
(150, 4, '2025-05-05 19:18:44', 3, 34, 2, 57, 12, NULL),
(152, 14, '2025-05-05 19:18:44', 2, 34, 2, 60, 12, NULL),
(153, 12, '2025-05-05 19:18:44', 3, 34, 2, 60, 12, NULL),
(154, 20, '2025-05-05 19:18:44', 1, 34, 2, 55, 12, NULL),
(155, 14, '2025-05-05 19:18:44', 2, 34, 2, 55, 12, NULL),
(156, 12, '2025-05-05 19:18:44', 3, 34, 2, 55, 12, NULL),
(157, 13, '2025-05-05 19:30:53', 1, 34, 2, 60, 21, NULL),
(158, 13, '2025-05-05 19:30:53', 2, 34, 2, 60, 21, NULL),
(159, 12, '2025-05-05 19:30:53', 3, 34, 2, 60, 21, NULL),
(160, 10, '2025-05-06 10:51:00', 4, 34, 1, 55, 12, NULL),
(162, 10, '2025-05-08 12:32:18', 2, 34, 1, 56, 21, NULL),
(163, 10, '2025-05-08 12:32:18', 3, 34, 1, 56, 21, NULL),
(164, 4, '2025-05-08 12:32:18', 1, 34, 1, 57, 21, NULL),
(165, 13, '2025-05-08 12:32:18', 2, 34, 1, 57, 21, NULL),
(166, 8, '2025-05-08 12:32:18', 3, 34, 1, 57, 21, NULL),
(167, 8, '2025-05-08 12:32:18', 1, 34, 1, 62, 21, NULL),
(168, 10, '2025-05-08 12:32:18', 2, 34, 1, 62, 21, NULL),
(169, 12, '2025-05-08 12:32:18', 3, 34, 1, 62, 21, NULL),
(170, 15, '2025-05-08 12:32:18', 1, 34, 1, 74, 21, NULL),
(171, 17, '2025-05-08 12:32:18', 2, 34, 1, 74, 21, NULL),
(172, 6, '2025-05-08 12:32:18', 3, 34, 1, 74, 21, NULL),
(173, 14, '2025-05-08 12:32:18', 1, 34, 1, 61, 21, NULL),
(174, 10, '2025-05-08 12:32:18', 2, 34, 1, 61, 21, NULL),
(175, 11, '2025-05-08 12:32:18', 3, 34, 1, 61, 21, NULL),
(176, 9, '2025-05-08 12:32:18', 1, 34, 1, 75, 21, NULL),
(177, 8, '2025-05-08 12:32:18', 2, 34, 1, 75, 21, NULL),
(178, 10, '2025-05-08 12:32:18', 3, 34, 1, 75, 21, NULL),
(179, 12, '2025-05-08 12:32:18', 1, 34, 1, 64, 21, NULL),
(180, 15, '2025-05-08 12:32:18', 2, 34, 1, 64, 21, NULL),
(181, 13, '2025-05-08 12:32:18', 3, 34, 1, 64, 21, NULL),
(182, 10, '2025-05-08 12:32:18', 1, 34, 1, 63, 21, NULL),
(183, 11, '2025-05-08 12:32:18', 2, 34, 1, 63, 21, NULL),
(184, 9, '2025-05-08 12:32:18', 3, 34, 1, 63, 21, NULL),
(185, 10, '2025-05-08 12:32:18', 1, 34, 1, 70, 21, NULL),
(186, 9, '2025-05-08 12:32:18', 2, 34, 1, 70, 21, NULL),
(187, 19, '2025-05-08 12:32:18', 3, 34, 1, 70, 21, NULL),
(188, 7, '2025-05-08 12:32:18', 1, 34, 1, 68, 21, NULL),
(189, 10, '2025-05-08 12:32:18', 2, 34, 1, 68, 21, NULL),
(190, 10, '2025-05-08 12:32:18', 3, 34, 1, 68, 21, NULL),
(191, 15, '2025-05-08 12:32:18', 1, 34, 1, 65, 21, NULL),
(192, 10, '2025-05-08 12:32:18', 2, 34, 1, 65, 21, NULL),
(193, 13, '2025-05-08 12:32:18', 3, 34, 1, 65, 21, NULL),
(194, 20, '2025-05-08 12:32:18', 1, 34, 1, 67, 21, NULL),
(195, 15, '2025-05-08 12:32:18', 2, 34, 1, 67, 21, NULL),
(196, 18, '2025-05-08 12:32:18', 3, 34, 1, 67, 21, NULL),
(197, 20, '2025-05-08 12:32:18', 1, 34, 1, 55, 21, NULL),
(198, 20, '2025-05-08 12:32:18', 2, 34, 1, 55, 21, NULL),
(199, 20, '2025-05-08 12:32:18', 3, 34, 1, 55, 21, NULL),
(200, 12, '2025-05-12 15:49:09', 4, 34, 3, 55, 12, NULL),
(201, 20, '2025-05-14 00:58:34', 1, 34, 3, 60, 12, NULL),
(202, 15, '2025-05-14 00:58:34', 2, 34, 3, 60, 12, NULL),
(203, 12, '2025-05-14 00:58:34', 3, 34, 3, 60, 12, NULL),
(204, 14, '2025-05-14 00:58:58', 1, 34, 3, 60, 21, NULL),
(205, 11, '2025-05-14 00:58:58', 2, 34, 3, 60, 21, NULL),
(206, 17, '2025-05-14 00:58:58', 3, 34, 3, 60, 21, NULL),
(207, 2, '2025-05-14 15:30:00', 1, 34, 1, 31, 12, NULL),
(208, 16, '2025-05-14 15:30:00', 2, 34, 1, 31, 12, NULL),
(209, 9, '2025-05-14 15:30:00', 3, 34, 1, 31, 12, NULL),
(210, 12, '2025-05-14 15:31:52', 4, 34, 3, 55, 12, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `notas`
--

CREATE TABLE `notas` (
  `id` int(11) NOT NULL,
  `id_matricula` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL,
  `valor` decimal(4,2) NOT NULL,
  `tipo_avaliacao` varchar(50) NOT NULL,
  `data_avaliacao` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `notas`
--

INSERT INTO `notas` (`id`, `id_matricula`, `id_disciplina`, `valor`, `tipo_avaliacao`, `data_avaliacao`) VALUES
(1, 38, 12, '12.00', 'professor', '2015-03-30');

-- --------------------------------------------------------

--
-- Estrutura da tabela `periodo`
--

CREATE TABLE `periodo` (
  `id` int(11) NOT NULL,
  `nome` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `periodo`
--

INSERT INTO `periodo` (`id`, `nome`) VALUES
(1, 'Manhã'),
(2, 'Tarde');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pessoa`
--

CREATE TABLE `pessoa` (
  `id_pessoa` int(11) NOT NULL,
  `nome` varchar(75) NOT NULL,
  `sexo` varchar(10) NOT NULL,
  `num_bi` varchar(20) NOT NULL,
  `data_nasc` date NOT NULL,
  `morada` varchar(100) DEFAULT NULL,
  `nome_pai` varchar(75) DEFAULT NULL,
  `nome_mae` varchar(75) DEFAULT NULL,
  `naturalidade` varchar(50) DEFAULT NULL,
  `tel_1` varchar(20) DEFAULT NULL,
  `tel_2` varchar(20) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `pessoa`
--

INSERT INTO `pessoa` (`id_pessoa`, `nome`, `sexo`, `num_bi`, `data_nasc`, `morada`, `nome_pai`, `nome_mae`, `naturalidade`, `tel_1`, `tel_2`, `email`, `foto`) VALUES
(1, 'Xavier Ngomalo', 'M', '1234567890', '0000-00-00', 'Mundial', 'Domingos', 'Inês ', 'Angola', '941476120', '953975391', 'xavierngomalo@gmail.com', NULL),
(3, 'Enio Damião', 'Masculino', '11111111111', '2025-04-03', 'Mundial', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '9999999', '99999999', 'eniodamiao@gmail.com', NULL),
(9, 'Serafina Marcedes', 'F', '111112222', '0000-00-00', 'Zango 3', 'zango1', 'zango2', 'Angolana', '77777777', '7777779', 'serafina@gmail.com', NULL),
(10, 'Dario Oliveira', 'Masculino', '1234567890098', '0000-00-00', 'Benfica', 'joão', 'Joana', 'Angolana', '941476120', '953975391', 'dario@gamil.com', NULL),
(15, 'Dario Teixeira', 'M', '1212121212', '0000-00-00', 'Mundial', 'Mario', 'Ginga', 'Angolana', '941476120', '941476120', 'dariopreto@gmail.com', NULL),
(17, 'Antonio Ngomalo', 'Masculino', '987654567', '0000-00-00', 'Benfica', 'Xavier', 'Inês', 'Angolana', '941476120', '99999999', 'antonio@gmail.com', NULL),
(19, 'Aderson Cangai', 'Masculino', '121212121212', '0000-00-00', 'Morro Bento', 'Xavier Ngomalo', 'Inês Rebeca', 'Angolana', '941476120', '941476120', 'aderson@gamil.com', NULL),
(20, 'Adilson Gasolina', 'M', '14141414141', '0000-00-00', 'Zango', 'Xavier Ngomalo', 'Inês Ngomalo ', 'Angolana', '9222222222', '933333333', 'adilson@gmail.com', NULL),
(21, 'Edmiro Prof', 'Masculino', '454545454', '0000-00-00', 'Mutamba', 'João Pedro', 'Joana', 'Angolana', '941476120', '953975391', 'admiro@gmail.com', NULL),
(22, 'Fernando Nanai', 'Masculino', '3003030303', '0000-00-00', 'Benfica', 'Lourenço', 'Maria', 'Angolana', '941476120', '99999999', 'fernandonnanai@gmail.com', NULL),
(23, 'Elisio Professor', 'Masculino', '1001000111', '0000-00-00', 'Tendas', 'Afonso', 'São', 'Angolana', '941476120', '953975391', 'elisio@gmail.com', NULL),
(24, 'Cesar', 'Masculino', '50505005', '0000-00-00', 'Tendas', 'joão', 'Maria', 'Angolana', '941476120', '953975391', 'cesar@gmail.com', NULL),
(25, 'Alberto Futi', 'Masculino', '12345678911', '2025-04-20', 'Zango 3', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '941476120', '99999999', 'alberto@gmail.com', NULL),
(29, 'Serafina Gaspar', 'Feminino', '0012300', '2025-04-21', 'Zango 3', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '941476120', '924444444', 'serafinagaspar@gmail.com', 'uploads/foto_680652f86db8d.jpg'),
(30, 'Fernando Ngola', 'Masculino', '000123400', '2025-04-26', 'Mundial', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '941476120', '99999996', 'fernandongola@gmail.com', 'uploads/foto_680661dad4cf3.jpg'),
(31, 'Fernando Bira', 'Masculino', '001234500', '2025-04-22', 'Benfica', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '941476120', '9222220', 'fernando@gmail.com', 'uploads/foto_68066277d1983.jpg'),
(34, 'Anderson Lider', 'Masculino', '007953995LA041', '2025-04-01', 'Patriota', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '941476120', '933260440', 'andersonlider@gmail.com', 'foto_68067287000f3.png'),
(47, 'Marinela Manuel ', 'Feminino', '007953995LA048222', '2025-04-04', 'Patriota', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '941476120', '92228220', 'ffff@gmail.com', 'uploads/foto_68093ccf278ee.jpg'),
(48, 'Anna Paulo Lucas', 'Feminino', '007953995LA041123', '2025-04-01', 'Mundial', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '941476120', '933260440', '', 'uploads/foto_68093f9d91b57.png'),
(53, 'pedro', 'Masculino', '007953995LA041444', '2025-04-24', 'Patriota', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '941476120', '99999999', 'mala@gmail.com', 'foto_680a50ed47e73.JPG'),
(55, 'Xavier Domingos N.Ngomalo', 'Masculino', '00495090LA040', '2004-03-30', 'Onjiva', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Namibiana', '933260449', '941476120', 'xgomalo@gmail.com', 'uploads/foto_6810746ea1d8f.jpg'),
(56, 'Abel Moco', 'Masculino', '00015055', '2025-05-05', 'Benfica', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '941476120', '933260440', 'abel@gmail.com', 'uploads/foto_68184535465d8.png'),
(57, 'Anacleto Antonio', 'Masculino', '00123000123', '2025-05-05', 'Mundial', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '9999998', '66666666666666', 'anacleto@gmail.com', 'uploads/foto_681845a215dbc.png'),
(60, 'Manuela Manuel', 'Feminino', '007953995LA04199', '2006-10-26', 'Mundial', 'Xavier Ngomalo', 'Ines Ngomalo', 'Angolana', '972699506', '953976031', 'manuela@gmail.com', 'uploads/foto_6818f20ed0d3b.jpg'),
(61, 'Jaime Arnaldo', 'Masculino', '00495090LA040122', '2004-10-20', 'Benfica-Mundial', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '933260440', '941476120', 'jaime@gmail.com', 'uploads/foto_6819e87fa9995.jpg'),
(62, 'Dario Oliveira', 'Masculino', '00495090LA04033', '2006-12-02', 'Benfica-Mundial', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '933260440', '941476120', 'dario@gmail.com', 'uploads/foto_6819e99a63b7c.jpg'),
(63, 'Maria Chilemo', 'Masculino', '00495090LA04044', '2003-12-12', 'Benfica-Mundial', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '933260440', '941476120', 'maria@gmail.com', 'uploads/foto_6819ea1df2d0e.jpg'),
(64, 'Margarida Ladislau', 'Feminino', '00495090LA04055', '2004-04-11', 'Benfica-Mundial', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '933260440', '941476120', 'margarida@gmail.com', 'uploads/foto_6819ebacdb94e.jpg'),
(65, 'Raquel Tchinanga', 'Feminino', '00495090LA04066', '2004-01-01', 'Benfica', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '933260440', '941476120', 'raquel@gamil.com', 'uploads/foto_6819ec6f21e5e.jpg'),
(67, 'Serafina Gaspar', 'Feminino', '00495090LA04077', '2006-02-20', 'Benfica', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '933629893', '922132294', 'serafina@gamail', 'uploads/foto_6819ee1c0073f.jpg'),
(68, 'Mariana Alexandre', 'Feminino', '00495090LA04068', '2000-02-14', 'Benfica', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '933260440', '922132294', 'mariana@gamail', 'uploads/foto_6819ef15423a5.jpg'),
(70, 'Maria Sopita', 'Feminino', '00495090LA0406699', '2025-05-06', 'Onjiva', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '933260440', '941476120', 'mariasopita@gmail.com', 'uploads/foto_6819f2deefeaa.jpg'),
(74, 'Doroteia Dala', 'Feminino', '00495090LA0406600', '2025-05-06', 'Benfica', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '933260440', '941476120', 'doroteia@gmail.com', 'uploads/foto_6819f618ae33a.jpg'),
(75, 'Joaquim Pedro', 'Masculino', '00495090LA0404478', '2025-05-06', 'Benfica', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '933260440', '941476120', 'joaquim@gamil.com', 'uploads/foto_6819f68b591b8.jpg'),
(143, 'Melânia Manuel', 'Femenino', '00495090LA04066998', '2003-12-21', 'Benfica', 'Xavier Ngomalo', 'Inês  Rebeca Ngomalo', 'Angolana', '922132294', '933629893', 'melaniamanuel@gmail.com', 'uploads/foto_6824ab06764b3.jpg');

-- --------------------------------------------------------

--
-- Estrutura da tabela `professor`
--

CREATE TABLE `professor` (
  `id_pessoa` int(11) NOT NULL,
  `num_agente` varchar(75) DEFAULT NULL,
  `formacao` varchar(75) DEFAULT NULL,
  `ativo` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `professor`
--

INSERT INTO `professor` (`id_pessoa`, `num_agente`, `formacao`, `ativo`) VALUES
(3, '18810', 'Lincenciado ', 1),
(9, '99998', 'Engenharia Infmática', 1),
(10, '212121', 'Engenharia Infmática', 1),
(19, '404040', 'Lincenciado ', 1),
(20, '30303030', 'Lincenciado ', 1),
(21, '565565', 'Lincenciado ', 1),
(22, '1010101', 'Lincenciado ', 1),
(23, '808080', 'Lincenciado ', 1),
(24, '30300303', 'Lincenciado ', 1),
(34, '5859', 'Licenciado em Desenvolvimento Web ', 1),
(53, '12341234', 'Lincenciado ', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `prova`
--

CREATE TABLE `prova` (
  `id` int(11) NOT NULL,
  `nome_prova` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `prova`
--

INSERT INTO `prova` (`id`, `nome_prova`) VALUES
(1, 'MAC'),
(2, 'Prova do Professor'),
(3, 'Prova do Trimestre'),
(4, 'Prova do Recurso'),
(5, 'Exame Especial');

-- --------------------------------------------------------

--
-- Estrutura da tabela `reclamacoes`
--

CREATE TABLE `reclamacoes` (
  `id` int(11) NOT NULL,
  `id_aluno` int(11) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `data_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `reclamacoes`
--

INSERT INTO `reclamacoes` (`id`, `id_aluno`, `assunto`, `mensagem`, `data_envio`) VALUES
(2, 55, 'Minhas Provas', 'ouvi erro', '2025-05-03 21:49:25'),
(3, 60, 'Para o professor de Estagio Supervisionado', 'Boa tarde Caro Professor Aderson Acho que não foram justos nas minhas notas preciso que o professor veja essa situação não tive negativa', '2025-05-05 18:23:16');

-- --------------------------------------------------------

--
-- Estrutura da tabela `sala`
--

CREATE TABLE `sala` (
  `id` int(11) NOT NULL,
  `num_sala` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `sala`
--

INSERT INTO `sala` (`id`, `num_sala`) VALUES
(1, 1),
(2, 2),
(5, 3),
(6, 4),
(7, 5),
(8, 6),
(9, 7),
(10, 8),
(11, 9);

-- --------------------------------------------------------

--
-- Estrutura da tabela `trimestre`
--

CREATE TABLE `trimestre` (
  `id` int(11) NOT NULL,
  `num_tri` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `trimestre`
--

INSERT INTO `trimestre` (`id`, `num_tri`) VALUES
(1, '1º Trimest'),
(2, '2ªTrimestr'),
(3, '3ªTrimestr');

-- --------------------------------------------------------

--
-- Estrutura da tabela `turma`
--

CREATE TABLE `turma` (
  `id` int(11) NOT NULL,
  `nome_turma` varchar(45) NOT NULL,
  `capacidade` int(11) NOT NULL,
  `id_sala` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `turma`
--

INSERT INTO `turma` (`id`, `nome_turma`, `capacidade`, `id_sala`) VALUES
(1, 'Turma A', 0, 0),
(2, 'CIT13C', 0, 0),
(4, 'CIT10A', 0, 0),
(5, 'CIT10B', 0, 0),
(6, 'CIT11A', 0, 0),
(7, 'CIT11B', 0, 0),
(8, 'CIT13A', 0, 0),
(9, 'CIT13B', 0, 0),
(10, 'CIT12A', 0, 0),
(11, 'CIT12B', 0, 0),
(29, 'CIT12A', 60, 10),
(30, 'CIT12B', 60, 10);

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_pessoa` int(11) NOT NULL,
  `nome_usuario` varchar(45) NOT NULL,
  `senha` varchar(150) NOT NULL,
  `tipo_usuario` enum('Admin','Professor','Aluno') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuario`
--

INSERT INTO `usuario` (`id_pessoa`, `nome_usuario`, `senha`, `tipo_usuario`) VALUES
(1, 'Xavier Ngomalo', '$2y$10$Rj3LyEEASt81p2D96BISruCq7cJHa0yurZ49rdJjApQklYiWJgWrq', 'Admin'),
(3, 'Enio Damião', '$2y$10$SjKj90.TNyxBPb6QTXBvp.NO1VwuqQVxF8bGYHo8aI56aMrJPqcQK', 'Professor'),
(29, 'Serafina', '$2y$10$6aGRWQClvQkSTumiBGh.7.brQPlROZQWEuDKLrILUL8epDVE5RpSK', 'Aluno'),
(30, 'Ngola', '$2y$10$a2Xj52sLVg3yrCBQVShr3.E4tF7NaXNNjHWupWzixChVGiHXKZb4S', 'Aluno'),
(31, 'Bira', '$2y$10$3fx3T3.sIlGwMO04U8f1l.uL8G9X4sYNyW8y456CFyPrNrZym07kO', 'Aluno'),
(34, 'Lider', '$2y$10$1A5PUjrwyjRqDl5dLCtrK.grgU4LRRwJ5RuUUmrCdza0SBe73zaky', 'Professor'),
(47, 'marinela', '$2y$10$hIvR7cAPKcJRAc/gY4aT9u6iIZLgu3G7ijuOawj58p28snYGEmZHa', 'Aluno'),
(48, 'Anna', '$2y$10$.MYZJwTAPh6sNqPHLZNbAeMzQkN1JTt3B/xjWQZDNV3dyXBs0h2f6', 'Aluno'),
(55, 'Ngomalo', '$2y$10$LTM/YG4c.Bgms5HTwSMi0OgfsBgETaDXNYoMBri94riFrM3iJeBDK', 'Aluno'),
(60, 'Manu', '$2y$10$ymn7Bb0ZBwYL.hNBacByy.pZ4a3NvqxcxHwpuE3XHDXjY/oKnmQSi', 'Aluno'),
(143, 'Mel Gostosa', '$2y$10$OFfVF.kK3NEoOAuihQW/Quk6SLwimErRT62VrEK48wXozFNWN/GU6', 'Aluno');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `aluno`
--
ALTER TABLE `aluno`
  ADD PRIMARY KEY (`id_pessoa`),
  ADD KEY `fk_aluno_classe` (`id_classe`),
  ADD KEY `fk_aluno_curso` (`id_curso`),
  ADD KEY `fk_id_turma` (`id_turma`);

--
-- Índices para tabela `anolectivo`
--
ALTER TABLE `anolectivo`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `atribuicao`
--
ALTER TABLE `atribuicao`
  ADD PRIMARY KEY (`id_prof`,`id_turma`,`id_curso`),
  ADD KEY `id_disc` (`id_disc`),
  ADD KEY `id_turma` (`id_turma`),
  ADD KEY `id_anolectivo` (`id_anolectivo`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Índices para tabela `atribuicao_disc`
--
ALTER TABLE `atribuicao_disc`
  ADD UNIQUE KEY `id_prof` (`id_prof`,`id_disc`,`id_anolectivo`,`id_curso`),
  ADD KEY `id_disc` (`id_disc`),
  ADD KEY `id_anolectivo` (`id_anolectivo`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `idx_id_prof` (`id_prof`),
  ADD KEY `fk_classe` (`id_classe`),
  ADD KEY `fk_atribuicao_turma` (`id_turma`);

--
-- Índices para tabela `classe`
--
ALTER TABLE `classe`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `comunicados`
--
ALTER TABLE `comunicados`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `curso`
--
ALTER TABLE `curso`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `curso_disciplina`
--
ALTER TABLE `curso_disciplina`
  ADD PRIMARY KEY (`id_curso`,`id_disc`),
  ADD KEY `id_classe` (`id_classe`),
  ADD KEY `id_disc` (`id_disc`);

--
-- Índices para tabela `disciplina`
--
ALTER TABLE `disciplina`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_curso_id` (`id_curso`);

--
-- Índices para tabela `matricula`
--
ALTER TABLE `matricula`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `id_periodo` (`id_periodo`),
  ADD KEY `id_sala` (`id_sala`),
  ADD KEY `id_turma` (`id_turma`),
  ADD KEY `id_ano` (`id_ano`),
  ADD KEY `fk_id_pessoa` (`id_pessoa`);

--
-- Índices para tabela `minipauta`
--
ALTER TABLE `minipauta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_prova` (`id_prova`),
  ADD KEY `id_aluno` (`id_aluno`),
  ADD KEY `id_disc` (`id_disc`),
  ADD KEY `id_professor` (`id_professor`),
  ADD KEY `id_trimestre` (`id_trimestre`),
  ADD KEY `fk_minipauta_pessoa` (`id_pessoa`);

--
-- Índices para tabela `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_matricula` (`id_matricula`),
  ADD KEY `id_disciplina` (`id_disciplina`);

--
-- Índices para tabela `periodo`
--
ALTER TABLE `periodo`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `pessoa`
--
ALTER TABLE `pessoa`
  ADD PRIMARY KEY (`id_pessoa`),
  ADD UNIQUE KEY `num_bi` (`num_bi`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `professor`
--
ALTER TABLE `professor`
  ADD PRIMARY KEY (`id_pessoa`);

--
-- Índices para tabela `prova`
--
ALTER TABLE `prova`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `reclamacoes`
--
ALTER TABLE `reclamacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_aluno` (`id_aluno`);

--
-- Índices para tabela `sala`
--
ALTER TABLE `sala`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `trimestre`
--
ALTER TABLE `trimestre`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `turma`
--
ALTER TABLE `turma`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_pessoa`),
  ADD UNIQUE KEY `nome_usuario` (`nome_usuario`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `anolectivo`
--
ALTER TABLE `anolectivo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `classe`
--
ALTER TABLE `classe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `comunicados`
--
ALTER TABLE `comunicados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `curso`
--
ALTER TABLE `curso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `disciplina`
--
ALTER TABLE `disciplina`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `matricula`
--
ALTER TABLE `matricula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT de tabela `minipauta`
--
ALTER TABLE `minipauta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT de tabela `notas`
--
ALTER TABLE `notas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `periodo`
--
ALTER TABLE `periodo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pessoa`
--
ALTER TABLE `pessoa`
  MODIFY `id_pessoa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT de tabela `prova`
--
ALTER TABLE `prova`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `reclamacoes`
--
ALTER TABLE `reclamacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `sala`
--
ALTER TABLE `sala`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `trimestre`
--
ALTER TABLE `trimestre`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `turma`
--
ALTER TABLE `turma`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `aluno`
--
ALTER TABLE `aluno`
  ADD CONSTRAINT `aluno_ibfk_1` FOREIGN KEY (`id_pessoa`) REFERENCES `pessoa` (`id_pessoa`),
  ADD CONSTRAINT `fk_aluno_classe` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id`),
  ADD CONSTRAINT `fk_aluno_curso` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_id_turma` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id`);

--
-- Limitadores para a tabela `atribuicao`
--
ALTER TABLE `atribuicao`
  ADD CONSTRAINT `atribuicao_ibfk_1` FOREIGN KEY (`id_prof`) REFERENCES `professor` (`id_pessoa`),
  ADD CONSTRAINT `atribuicao_ibfk_2` FOREIGN KEY (`id_disc`) REFERENCES `disciplina` (`id`),
  ADD CONSTRAINT `atribuicao_ibfk_3` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `atribuicao_ibfk_4` FOREIGN KEY (`id_anolectivo`) REFERENCES `anolectivo` (`id`),
  ADD CONSTRAINT `atribuicao_ibfk_5` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id`);

--
-- Limitadores para a tabela `atribuicao_disc`
--
ALTER TABLE `atribuicao_disc`
  ADD CONSTRAINT `atribuicao_disc_ibfk_2` FOREIGN KEY (`id_disc`) REFERENCES `disciplina` (`id`),
  ADD CONSTRAINT `atribuicao_disc_ibfk_3` FOREIGN KEY (`id_anolectivo`) REFERENCES `anolectivo` (`id`),
  ADD CONSTRAINT `atribuicao_disc_ibfk_4` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id`),
  ADD CONSTRAINT `fk_atribuicao_turma` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_classe` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id`);

--
-- Limitadores para a tabela `curso_disciplina`
--
ALTER TABLE `curso_disciplina`
  ADD CONSTRAINT `curso_disciplina_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id`),
  ADD CONSTRAINT `curso_disciplina_ibfk_2` FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id`),
  ADD CONSTRAINT `curso_disciplina_ibfk_3` FOREIGN KEY (`id_disc`) REFERENCES `disciplina` (`id`);

--
-- Limitadores para a tabela `disciplina`
--
ALTER TABLE `disciplina`
  ADD CONSTRAINT `fk_curso_id` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id`);

--
-- Limitadores para a tabela `matricula`
--
ALTER TABLE `matricula`
  ADD CONSTRAINT `fk_id_pessoa` FOREIGN KEY (`id_pessoa`) REFERENCES `pessoa` (`id_pessoa`),
  ADD CONSTRAINT `matricula_ibfk_2` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id`),
  ADD CONSTRAINT `matricula_ibfk_3` FOREIGN KEY (`id_periodo`) REFERENCES `periodo` (`id`),
  ADD CONSTRAINT `matricula_ibfk_4` FOREIGN KEY (`id_sala`) REFERENCES `sala` (`id`),
  ADD CONSTRAINT `matricula_ibfk_5` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id`),
  ADD CONSTRAINT `matricula_ibfk_6` FOREIGN KEY (`id_ano`) REFERENCES `anolectivo` (`id`);

--
-- Limitadores para a tabela `minipauta`
--
ALTER TABLE `minipauta`
  ADD CONSTRAINT `fk_minipauta_pessoa` FOREIGN KEY (`id_pessoa`) REFERENCES `pessoa` (`id_pessoa`) ON DELETE CASCADE,
  ADD CONSTRAINT `minipauta_ibfk_1` FOREIGN KEY (`id_prova`) REFERENCES `prova` (`id`),
  ADD CONSTRAINT `minipauta_ibfk_2` FOREIGN KEY (`id_aluno`) REFERENCES `aluno` (`id_pessoa`),
  ADD CONSTRAINT `minipauta_ibfk_3` FOREIGN KEY (`id_disc`) REFERENCES `disciplina` (`id`),
  ADD CONSTRAINT `minipauta_ibfk_4` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_pessoa`),
  ADD CONSTRAINT `minipauta_ibfk_5` FOREIGN KEY (`id_trimestre`) REFERENCES `trimestre` (`id`);

--
-- Limitadores para a tabela `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`id_matricula`) REFERENCES `matricula` (`id`),
  ADD CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplina` (`id`);

--
-- Limitadores para a tabela `professor`
--
ALTER TABLE `professor`
  ADD CONSTRAINT `professor_ibfk_1` FOREIGN KEY (`id_pessoa`) REFERENCES `pessoa` (`id_pessoa`);

--
-- Limitadores para a tabela `reclamacoes`
--
ALTER TABLE `reclamacoes`
  ADD CONSTRAINT `reclamacoes_ibfk_1` FOREIGN KEY (`id_aluno`) REFERENCES `aluno` (`id_pessoa`);

--
-- Limitadores para a tabela `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_pessoa`) REFERENCES `pessoa` (`id_pessoa`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
