-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 26/11/2026 às 00:52
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `cicro`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacao`
--

CREATE TABLE `avaliacao` (
  `id` int(11) NOT NULL,
  `fk_produto` int(11) NOT NULL,
  `fk_cliente` int(11) NOT NULL,
  `fk_pedido` int(11) NOT NULL,
  `nota` int(11) NOT NULL,
  `comentario` text DEFAULT NULL,
  `data_avaliacao` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Despejando dados para a tabela `avaliacao`
--

INSERT INTO `avaliacao` (`id`, `fk_produto`, `fk_cliente`, `fk_pedido`, `nota`, `comentario`, `data_avaliacao`) VALUES
(1, 12, 8, 2, 5, 'Tecido maravilhoso! A dupla camada não marca nada e o algodão é super confortável para treinar.', '2026-10-24 21:30:00'),
(2, 15, 8, 2, 5, 'O Conjunto Dani Short veste super bem no corpo e realmente não fica transparente no agachamento.', '2026-10-24 21:32:10'),
(3, 13, 9, 3, 5, 'Conjunto perfeito para dias mais frescos! Acabamento impecável e entrega super rápida em Criciúma.', '2026-10-26 14:15:00'),
(4, 14, 9, 3, 4, 'Gostei bastante do caimento e da sustentação. A cor é linda pessoalmente, recomendo!', '2026-10-26 14:18:45'),
(5, 13, 10, 4, 5, 'O tecido canelado tem uma compressão excelente e modela muito bem. Já quero de outras cores!', '2026-10-28 23:05:00'),
(6, 12, 10, 4, 4, 'Blusa muito estilosa e fresquinha, seca super rápido durante o treino de corrida.', '2026-10-28 23:08:30'),
(7, 13, 11, 5, 4, 'Comprei para dar de presente e a qualidade surpreendeu bastante. Costuras muito bem feitas.', '2026-10-30 17:40:00'),
(8, 15, 12, 6, 5, 'O short canelado não fica subindo na perna durante o exercício. Perfeito!', '2026-11-01 12:50:00'),
(9, 14, 12, 6, 5, 'Os detalhes em tule dão um charme incrível na peça. Super leve e respirável.', '2026-11-01 12:53:15'),
(10, 13, 13, 7, 5, 'Calça com cintura alta maravilhosa, não enrola no abdômen. Amei demais!', '2026-11-03 19:12:00'),
(11, 12, 13, 7, 4, 'Ótimo custo-benefício. Uso tanto na musculação quanto no dia a dia.', '2026-11-03 19:15:00'),
(12, 14, 14, 8, 5, 'Presenteei minha esposa e ela adorou o tecido e o corte da blusa.', '2026-11-04 22:30:00'),
(13, 13, 14, 8, 4, 'Peça básica indispensável, tecido encorpado e muito resistente.', '2026-11-04 22:32:00'),
(14, 15, 15, 9, 5, 'Faz jus ao nome! Extremamente confortável, parece que não está usando nada.', '2026-11-06 14:45:00'),
(15, 14, 15, 9, 5, 'Estampa linda que não desbota na lavagem. O tamanho M ficou perfeito em mim.', '2026-11-06 14:48:20'),
(16, 13, 16, 10, 5, 'O relevo do tecido jacard disfarça super bem e deixa o visual muito elegante.', '2026-11-08 20:22:00'),
(17, 12, 16, 10, 4, 'Acabamento de primeira linha, chegou super bem embalado.', '2026-11-08 20:25:00'),
(18, 15, 17, 11, 5, 'Entrega super rápida e produto fiel às fotos do site. Recomendo a CICRO!', '2026-11-10 00:10:00'),
(19, 15, 1, 12, 5, 'Amei o short duplo, dá muita segurança para correr e fazer exercícios funcionais. Nota 10!', '2026-11-11 19:50:00'),
(20, 12, 7, 13, 5, 'Blusa super levinha, fresca e linda! O tamanho veio certinho conforme a tabela.', '2026-11-13 12:25:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `carrinho`
--

CREATE TABLE `carrinho` (
  `id` int(11) NOT NULL,
  `fk_cliente` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `carrinho`
--

INSERT INTO `carrinho` (`id`, `fk_cliente`, `data_criacao`, `data_atualizacao`) VALUES
(1, 7, '2026-10-15 22:57:30', '2026-10-15 22:57:30'),
(2, 6, '2026-11-25 20:39:03', '2026-11-25 20:39:03');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categoria`
--

CREATE TABLE `categoria` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `ordem_exibicao` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categoria`
--

INSERT INTO `categoria` (`id`, `nome`, `slug`, `ordem_exibicao`, `status`) VALUES
(1, 'Camisas', 'camisas', 3, 1),
(2, 'Conjuntos', 'conjuntos', 1, 1),
(3, 'Regatas', 'regatas', 5, 1),
(4, 'Shorts', 'shorts', 2, 1),
(5, 'Cropped', 'cropped', 4, 1),
(6, 'Calças', 'calcas', 6, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `categoria_carrinho`
--

CREATE TABLE `categoria_carrinho` (
  `id` int(11) NOT NULL,
  `fk_carrinho` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categoria_carrinho`
--

INSERT INTO `categoria_carrinho` (`id`, `fk_carrinho`, `nome`) VALUES
(1, 1, 'CONJUNTOS'),
(2, 2, 'CONJUNTOS'),
(3, 2, 'REGATAS');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cliente`
--

CREATE TABLE `cliente` (
  `id_usuario` int(11) NOT NULL,
  `cpf` varchar(255) DEFAULT NULL,
  `cpf_hash` char(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cliente`
--

INSERT INTO `cliente` (`id_usuario`, `cpf`, `cpf_hash`) VALUES
(1, NULL, NULL),
(6, 'v1:OSLDF5lCiUgmj6jgGhnxipJ83Aq9MHavTn54OkY7sU7JloDueR9dTo6a', '0d70c73e608d142ac0678c6510e4a785843ac4fc534d1fe07d343a73fa1d9609'),
(7, NULL, NULL),
(8, 'v1:gIIQPx7wlNsRYi9UkO0+0i9ApRlkQF1NnsPmVuJiNGK9MWswtfrkmVVk', '3217e319f8bf677138c64a523c3ea5582010f2ee9336b66226fe22fee439b181'),
(9, 'v1:Sn/zV91Kje7AQ9Lkv7HVAzPQ/gMbB2qLgSSoOiKulCs6Qu/KWmPMfEn4', '0ae6e6eb8cb60b0b0e32eff2991a5eb062445a80451e5354ea9f0d52035d3ab9'),
(10, 'v1:yIQH8hl48q33zs4p+sDQJrmE1lFXPsp8siAFf63VI184N2Sz49NZopXc', 'e67faa54bc09b02f3fc6b39a116cfe92586e00405d190df9003be4600f0b20cc'),
(11, 'v1:WUDeqArIRziMBAIT0mA2NwfSPcKRxazyfoat88qSAoYVlelH8KdcQruw', 'b3308eff2cd8e8bc344b90ad599bc9b3d68a2261db60a037031564e3a3899c5e'),
(12, 'v1:4g3zPohFxyu1QIFcZ3/jZsXOJMsNCONiFliJWwnffwPgwBUtDJyKjn7F', '69edd1dd815b43c570dd5bde803809cbbf0fd9d5d8b27c322a2f70c334a56c11'),
(13, 'v1:EtIJC0DAlUEy32JnDInnboAPTWnKtBDojRChVxrXE2NiKddfWmqCOY4x', 'e2889748df848ede23514a1a1f609bd1531140d4c69e9739d52a7aafbe0ca66d'),
(14, 'v1:GcP36KCIUpgSgkPjxxfnIXoY+dKUaN8Vho/FChI6cV8EuJF8izeyyTLF', 'd6d5ba749566756d4a321cfd4b9035c6a6ee817b82ffb1d721076bc540ebd8e6'),
(15, 'v1:gZfIM93LYF7fXcWL8VB4lVCiuUl+5djJcCdU2GvpPEn3H/OayAZURQS7', '7737718700ff8deaa035a11300e5f6028a1d392d2b6e7301d06bae82e1b31914'),
(16, 'v1:cdVkBSpE3HVTa92TeyaJ4EncSX4eFr/YrPPdLoJpG187WgRf2ltBvbQR', '352b64127950fd2b8e51542f45d04977d09d7c8cfabadf015b42abf3ffe34cac'),
(17, 'v1:NC3CCoI8L3E8IgUugGRGlNx4oK+HnRPnuXlu8fhLvKmm0ZOPmi45R+MQ', '746f60c55d80a2e004de024e2921f378828d983dfbcef396f6331a98bf87dcec');

-- --------------------------------------------------------

--
-- Estrutura para tabela `conversa`
--

CREATE TABLE `conversa` (
  `id` int(11) NOT NULL,
  `fk_cliente` int(11) NOT NULL,
  `fk_atendente` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Aberta',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Despejando dados para a tabela `conversa`
--

INSERT INTO `conversa` (`id`, `fk_cliente`, `fk_atendente`, `status`, `data_criacao`) VALUES
(1, 7, 5, 'Encerrada', '2026-10-12 21:39:24'),
(2, 7, 5, 'Encerrada', '2026-10-12 21:42:05'),
(3, 6, 5, 'Encerrada', '2026-11-25 21:23:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `endereco`
--

CREATE TABLE `endereco` (
  `id` int(11) NOT NULL,
  `fk_cliente` int(11) NOT NULL,
  `apelido` varchar(50) DEFAULT NULL,
  `cep` varchar(9) NOT NULL,
  `rua` varchar(150) NOT NULL,
  `numero` varchar(10) NOT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` char(2) NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `endereco`
--

INSERT INTO `endereco` (`id`, `fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`) VALUES
(2, 7, 'Casa', '88808-052', 'Rua Afonso Milanese', '464', 'Bloco 18 Apto 204', 'São Defende', 'Criciúma', 'SC', 1),
(3, 8, 'Casa', '88801-100', 'Rua Coronel Pedro Benedet', '310', 'Apto 502', 'Centro', 'Criciúma', 'SC', 1),
(4, 9, 'Apartamento', '88804-000', 'Avenida Centenário', '1450', 'Bloco B', 'Santa Bárbara', 'Criciúma', 'SC', 1),
(5, 10, 'Casa', '88815-200', 'Rua Henrique Lage', '890', NULL, 'Comerciário', 'Criciúma', 'SC', 1),
(6, 11, 'Trabalho', '88802-300', 'Rua Joaquim Nabuco', '520', 'Sala 04', 'Michel', 'Criciúma', 'SC', 1),
(7, 12, 'Casa', '88806-000', 'Rua São José', '712', 'Casa 2', 'Próspera', 'Criciúma', 'SC', 1),
(8, 13, 'Casa', '88811-400', 'Rua Anita Garibaldi', '198', NULL, 'Operária Nova', 'Criciúma', 'SC', 1),
(9, 14, 'Casa', '88803-210', 'Rua Marechal Deodoro', '405', 'Apto 101', 'Centro', 'Criciúma', 'SC', 1),
(10, 15, 'Casa', '88813-150', 'Rua Santo Antônio', '630', NULL, 'Pinheirinho', 'Criciúma', 'SC', 1),
(11, 16, 'Apartamento', '88807-320', 'Rua Imigrante Zanatta', '95', 'Apto 303', 'Cruzeiro do Sul', 'Criciúma', 'SC', 1),
(12, 17, 'Casa', '88809-110', 'Rua Vitor Meireles', '1120', NULL, 'Rio Maina', 'Criciúma', 'SC', 1),
(13, 1, 'Casa', '88805-120', 'Rua Gonçalves Ledo', '215', NULL, 'Pio Corrêa', 'Criciúma', 'SC', 1),
(14, 6, 'Casa', '88807-090', 'Rua Bento Antônio Neto', '10', 'Última casa da rua, lado direito', 'São Sebastião', 'Criciúma', 'SC', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionario`
--

CREATE TABLE `funcionario` (
  `id_usuario` int(11) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `data_admissao` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcionario`
--

INSERT INTO `funcionario` (`id_usuario`, `cargo`, `data_admissao`) VALUES
(5, 'Administrador', '2026-10-04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `imagem_produto`
--

CREATE TABLE `imagem_produto` (
  `id` int(11) NOT NULL,
  `fk_produto` int(11) NOT NULL,
  `url_imagem` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `capa` tinyint(1) NOT NULL DEFAULT 0,
  `cor` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `imagem_produto`
--

INSERT INTO `imagem_produto` (`id`, `fk_produto`, `url_imagem`, `ordem`, `capa`, `cor`) VALUES
(1, 12, 'admin-pages/uploads/prod_12_6ad22726e5501.png', 0, 1, 'Azul-marinho'),
(2, 12, 'admin-pages/uploads/prod_12_6ad22726e5953.png', 1, 0, 'Preto'),
(3, 12, 'admin-pages/uploads/prod_12_6ad22726e5e2d.png', 2, 0, 'Rosa'),
(4, 13, 'admin-pages/uploads/prod_13_6ad22789bf9f4.png', 0, 1, 'Terracota'),
(5, 13, 'admin-pages/uploads/prod_13_6ad22789bfd0f.png', 1, 0, 'Amarelo'),
(6, 14, 'admin-pages/uploads/prod_14_6ad227c62b0e7.png', 0, 1, 'Bege'),
(7, 15, 'admin-pages/uploads/prod_15_6ad2280e204c2.png', 0, 1, NULL),
(8, 15, 'admin-pages/uploads/prod_15_6ad2280e20785.png', 1, 0, NULL),
(9, 15, 'admin-pages/uploads/prod_15_6ad2280e2119f.png', 2, 0, NULL),
(10, 15, 'admin-pages/uploads/prod_15_6ad2280e214c1.png', 3, 0, NULL),
(11, 16, 'admin-pages/uploads/prod_blusa-64_01.jpg', 0, 1, 'Preto-branco'),
(12, 16, 'admin-pages/uploads/prod_blusa-64_02.jpg', 1, 0, 'Preto-rosa'),
(13, 16, 'admin-pages/uploads/prod_blusa-64_03.jpg', 2, 0, 'Preto-dourado'),
(14, 16, 'admin-pages/uploads/prod_blusa-64_04.jpg', 3, 0, 'Vermelho'),
(15, 16, 'admin-pages/uploads/prod_blusa-64_05.jpg', 4, 0, 'Rosa'),
(16, 16, 'admin-pages/uploads/prod_blusa-64_06.jpg', 5, 0, 'Algodão-doce'),
(17, 16, 'admin-pages/uploads/prod_blusa-64_07.jpg', 6, 0, 'Verde'),
(18, 16, 'admin-pages/uploads/prod_blusa-64_08.jpg', 7, 0, 'Magenta'),
(19, 16, 'admin-pages/uploads/prod_blusa-64_09.jpg', 8, 0, 'Lilás'),
(20, 16, 'admin-pages/uploads/prod_blusa-64_10.jpg', 9, 0, 'Bege'),
(21, 16, 'admin-pages/uploads/prod_blusa-64_11.jpg', 10, 0, 'Terracota'),
(22, 17, 'admin-pages/uploads/prod_blusa-by-power-tule_01.jpg', 0, 1, 'Magenta'),
(23, 17, 'admin-pages/uploads/prod_blusa-by-power-tule_02.jpg', 1, 0, 'Azul-claro'),
(24, 17, 'admin-pages/uploads/prod_blusa-by-power-tule_03.jpg', 2, 0, 'Verde'),
(25, 17, 'admin-pages/uploads/prod_blusa-by-power-tule_04.jpg', 3, 0, 'Laranja'),
(26, 17, 'admin-pages/uploads/prod_blusa-by-power-tule_05.jpg', 4, 0, 'Rosa'),
(27, 17, 'admin-pages/uploads/prod_blusa-by-power-tule_06.jpg', 5, 0, 'Azul'),
(28, 18, 'admin-pages/uploads/prod_blusa-cr-sport_01.jpg', 0, 1, 'Azul-claro'),
(29, 18, 'admin-pages/uploads/prod_blusa-cr-sport_02.jpg', 1, 0, 'Preto'),
(30, 18, 'admin-pages/uploads/prod_blusa-cr-sport_03.jpg', 2, 0, 'Bege'),
(31, 18, 'admin-pages/uploads/prod_blusa-cr-sport_04.jpg', 3, 0, 'Verde'),
(32, 18, 'admin-pages/uploads/prod_blusa-cr-sport_05.jpg', 4, 0, 'Vermelho'),
(33, 18, 'admin-pages/uploads/prod_blusa-cr-sport_06.jpg', 5, 0, 'Lilás'),
(34, 18, 'admin-pages/uploads/prod_blusa-cr-sport_07.jpg', 6, 0, 'Azul'),
(35, 19, 'admin-pages/uploads/prod_blusa-go-fitness_01.jpg', 0, 1, 'Marrom'),
(36, 20, 'admin-pages/uploads/prod_blusa-love-cicro_01.jpg', 0, 1, 'Preto-rosa'),
(37, 20, 'admin-pages/uploads/prod_blusa-love-cicro_02.jpg', 1, 0, 'Preto-branco'),
(38, 20, 'admin-pages/uploads/prod_blusa-love-cicro_03.jpg', 2, 0, 'Branco'),
(39, 20, 'admin-pages/uploads/prod_blusa-love-cicro_04.jpg', 3, 0, 'Cinza'),
(40, 20, 'admin-pages/uploads/prod_blusa-love-cicro_05.jpg', 4, 0, 'Rosa'),
(41, 20, 'admin-pages/uploads/prod_blusa-love-cicro_06.jpg', 5, 0, 'Lilás'),
(42, 20, 'admin-pages/uploads/prod_blusa-love-cicro_07.jpg', 6, 0, 'Laranja'),
(43, 20, 'admin-pages/uploads/prod_blusa-love-cicro_08.jpg', 7, 0, 'Marrom'),
(44, 20, 'admin-pages/uploads/prod_blusa-love-cicro_09.jpg', 8, 0, 'Cacau'),
(45, 20, 'admin-pages/uploads/prod_blusa-love-cicro_10.jpg', 9, 0, 'Lavanda'),
(46, 20, 'admin-pages/uploads/prod_blusa-love-cicro_11.jpg', 10, 0, 'Azul'),
(47, 20, 'admin-pages/uploads/prod_blusa-love-cicro_12.jpg', 11, 0, 'Preto'),
(48, 21, 'admin-pages/uploads/prod_blusa-premium_01.jpg', 0, 1, 'Branco'),
(49, 21, 'admin-pages/uploads/prod_blusa-premium_02.jpg', 1, 0, 'Preto'),
(50, 21, 'admin-pages/uploads/prod_blusa-premium_03.jpg', 2, 0, 'Azul-claro'),
(51, 21, 'admin-pages/uploads/prod_blusa-premium_04.jpg', 3, 0, 'Verde'),
(52, 21, 'admin-pages/uploads/prod_blusa-premium_05.jpg', 4, 0, 'Rosa'),
(53, 21, 'admin-pages/uploads/prod_blusa-premium_06.jpg', 5, 0, 'Laranja'),
(54, 21, 'admin-pages/uploads/prod_blusa-premium_07.jpg', 6, 0, 'Vinho'),
(55, 21, 'admin-pages/uploads/prod_blusa-premium_08.jpg', 7, 0, 'Azul'),
(56, 21, 'admin-pages/uploads/prod_blusa-premium_09.jpg', 8, 0, 'Lilás'),
(57, 22, 'admin-pages/uploads/prod_calca-simples_01.jpg', 0, 1, 'Azul-marinho'),
(58, 22, 'admin-pages/uploads/prod_calca-simples_02.jpg', 1, 0, 'Azul'),
(59, 22, 'admin-pages/uploads/prod_calca-simples_03.jpg', 2, 0, 'Rosa'),
(60, 22, 'admin-pages/uploads/prod_calca-simples_04.jpg', 3, 0, 'Roxo'),
(61, 23, 'admin-pages/uploads/prod_camisa-super-girl_01.jpg', 0, 1, 'Rosa'),
(62, 23, 'admin-pages/uploads/prod_camisa-super-girl_02.jpg', 1, 0, 'preto-amarelo'),
(63, 23, 'admin-pages/uploads/prod_camisa-super-girl_03.jpg', 2, 0, 'preto-branco'),
(64, 23, 'admin-pages/uploads/prod_camisa-super-girl_04.jpg', 3, 0, 'preto-rosa'),
(65, 23, 'admin-pages/uploads/prod_camisa-super-girl_05.jpg', 4, 0, 'branco-preto'),
(66, 23, 'admin-pages/uploads/prod_camisa-super-girl_06.jpg', 5, 0, 'branco-rosa'),
(67, 23, 'admin-pages/uploads/prod_camisa-super-girl_07.jpg', 6, 0, 'Vermelho'),
(68, 23, 'admin-pages/uploads/prod_camisa-super-girl_08.jpg', 7, 0, 'Magenta'),
(69, 23, 'admin-pages/uploads/prod_camisa-super-girl_09.jpg', 8, 0, 'Verde'),
(70, 23, 'admin-pages/uploads/prod_camisa-super-girl_10.jpg', 9, 0, 'Vinho'),
(71, 23, 'admin-pages/uploads/prod_camisa-super-girl_11.jpg', 10, 0, 'Azul'),
(72, 24, 'admin-pages/uploads/prod_casaco-jacard-life_01.jpg', 0, 1, 'Amarelo'),
(73, 24, 'admin-pages/uploads/prod_casaco-jacard-life_02.jpg', 1, 0, 'Preto'),
(74, 24, 'admin-pages/uploads/prod_casaco-jacard-life_03.jpg', 2, 0, 'Terracota'),
(75, 24, 'admin-pages/uploads/prod_casaco-jacard-life_04.jpg', 3, 0, 'Marrom'),
(76, 24, 'admin-pages/uploads/prod_casaco-jacard-life_05.jpg', 4, 0, 'Azul'),
(77, 24, 'admin-pages/uploads/prod_casaco-jacard-life_06.jpg', 5, 0, 'musgo'),
(78, 25, 'admin-pages/uploads/prod_conjunto-canelado-de-calca-ava_01.jpg', 0, 1, 'Lilás'),
(79, 25, 'admin-pages/uploads/prod_conjunto-canelado-de-calca-ava_02.jpg', 1, 0, 'Preto'),
(80, 25, 'admin-pages/uploads/prod_conjunto-canelado-de-calca-ava_03.jpg', 2, 0, 'Pink'),
(81, 25, 'admin-pages/uploads/prod_conjunto-canelado-de-calca-ava_04.jpg', 3, 0, 'Verde'),
(82, 25, 'admin-pages/uploads/prod_conjunto-canelado-de-calca-ava_05.jpg', 4, 0, 'Marrom'),
(83, 26, 'admin-pages/uploads/prod_conjunto-canelado-de-short-yzza_01.jpg', 0, 1, 'Marrom'),
(84, 26, 'admin-pages/uploads/prod_conjunto-canelado-de-short-yzza_02.jpg', 1, 0, 'Preto'),
(85, 26, 'admin-pages/uploads/prod_conjunto-canelado-de-short-yzza_03.jpg', 2, 0, 'Verde'),
(86, 26, 'admin-pages/uploads/prod_conjunto-canelado-de-short-yzza_04.jpg', 3, 0, 'Pink'),
(87, 26, 'admin-pages/uploads/prod_conjunto-canelado-de-short-yzza_05.jpg', 4, 0, 'Lilás'),
(88, 27, 'admin-pages/uploads/prod_conjunto-de-calca-carol_01.jpg', 0, 1, 'Branco'),
(89, 27, 'admin-pages/uploads/prod_conjunto-de-calca-carol_02.jpg', 1, 0, 'Preto'),
(90, 28, 'admin-pages/uploads/prod_conjunto-de-short-confort_01.jpg', 0, 1, 'Azul-marinho'),
(91, 28, 'admin-pages/uploads/prod_conjunto-de-short-confort_02.jpg', 1, 0, 'Branco'),
(92, 28, 'admin-pages/uploads/prod_conjunto-de-short-confort_03.jpg', 2, 0, 'Coral'),
(93, 29, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_01.jpg', 0, 1, 'Marrom'),
(94, 29, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_02.jpg', 1, 0, 'Preto'),
(95, 29, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_03.jpg', 2, 0, 'Bege'),
(96, 29, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_04.jpg', 3, 0, 'Azul'),
(97, 29, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_05.jpg', 4, 0, 'Verde'),
(98, 29, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_06.jpg', 5, 0, 'Rosa'),
(99, 29, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_07.jpg', 6, 0, 'Terracota'),
(100, 30, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_01.jpg', 0, 1, 'Preto'),
(101, 30, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_02.jpg', 1, 0, 'verde neon'),
(102, 30, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_03.jpg', 2, 0, 'Pink'),
(103, 30, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_04.jpg', 3, 0, 'Azul-marinho'),
(104, 30, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_05.jpg', 4, 0, 'Azul'),
(106, 30, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_07.jpg', 6, 0, 'Cinza'),
(107, 30, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_08.jpg', 7, 0, 'Branco'),
(108, 31, 'admin-pages/uploads/prod_macaquinho-miami_01.jpg', 0, 1, 'Rosa'),
(109, 10, 'admin-pages/uploads/prod_10_6b07708068682.jpg', 0, 1, 'Salmão');

-- --------------------------------------------------------

--
-- Estrutura para tabela `item_carrinho`
--

CREATE TABLE `item_carrinho` (
  `id` int(11) NOT NULL,
  `fk_carrinho` int(11) NOT NULL,
  `fk_variacao` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `fk_categoria_carrinho` int(11) DEFAULT NULL,
  `fk_imagem` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `item_pedido`
--

CREATE TABLE `item_pedido` (
  `id` int(11) NOT NULL,
  `fk_pedido` int(11) NOT NULL,
  `fk_variacao` int(11) NOT NULL,
  `nome_produto` varchar(150) NOT NULL,
  `tamanho` varchar(10) NOT NULL,
  `cor` varchar(30) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `item_pedido`
--

INSERT INTO `item_pedido` (`id`, `fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`) VALUES
(1, 1, 22, 'asdasd', 'M', 'Padrão', 1, 68.00),
(2, 2, 29, 'Camiseta LIFE! fitness', 'G', 'Padrão', 1, 40.00),
(3, 2, 37, 'Conjunto Dani Short', 'G', 'Padrão', 1, 50.00),
(5, 3, 32, 'Conjunto casaquinho fitness', 'GG', 'Padrão', 1, 65.00),
(6, 3, 35, 'Conjunto regata face double', 'M', 'Padrão', 1, 46.00),
(8, 4, 29, 'Camiseta LIFE! fitness', 'G', 'Padrão', 1, 40.00),
(9, 4, 32, 'Conjunto casaquinho fitness', 'GG', 'Padrão', 1, 65.00),
(11, 5, 32, 'Conjunto casaquinho fitness', 'GG', 'Padrão', 1, 65.00),
(12, 6, 35, 'Conjunto regata face double', 'M', 'Padrão', 1, 46.00),
(13, 6, 37, 'Conjunto Dani Short', 'G', 'Padrão', 1, 50.00),
(15, 7, 29, 'Camiseta LIFE! fitness', 'G', 'Padrão', 1, 40.00),
(16, 7, 32, 'Conjunto casaquinho fitness', 'GG', 'Padrão', 1, 65.00),
(18, 8, 32, 'Conjunto casaquinho fitness', 'GG', 'Padrão', 1, 65.00),
(19, 8, 35, 'Conjunto regata face double', 'M', 'Padrão', 1, 46.00),
(21, 9, 35, 'Conjunto regata face double', 'M', 'Padrão', 1, 46.00),
(22, 9, 37, 'Conjunto Dani Short', 'G', 'Padrão', 1, 50.00),
(24, 10, 29, 'Camiseta LIFE! fitness', 'G', 'Padrão', 1, 40.00),
(25, 10, 32, 'Conjunto casaquinho fitness', 'GG', 'Padrão', 1, 65.00),
(27, 11, 37, 'Conjunto Dani Short', 'G', 'Padrão', 1, 50.00),
(28, 12, 37, 'Conjunto Dani Short', 'G', 'Padrão', 1, 50.00),
(29, 13, 29, 'Camiseta LIFE! fitness', 'G', 'Padrão', 1, 40.00),
(30, 13, 37, 'Conjunto Dani Short', 'G', 'Padrão', 1, 50.00),
(32, 14, 37, 'Conjunto Dani Short', 'G', 'Padrão', 1, 50.00),
(33, 15, 32, 'Conjunto casaquinho fitness', 'GG', 'Padrão', 1, 65.00),
(34, 16, 37, 'Conjunto Dani Short', 'G', 'Padrão', 1, 50.00),
(35, 17, 29, 'Camiseta LIFE! fitness', 'G', 'Padrão', 1, 40.00),
(36, 18, 32, 'Conjunto casaquinho fitness', 'GG', 'Padrão', 1, 65.00),
(37, 19, 29, 'Camiseta LIFE! fitness', 'G', 'Padrão', 1, 40.00),
(38, 20, 84, 'Macaquinho Miami', 'M', 'Padrão', 1, 79.90),
(39, 21, 73, 'Conjunto de Short Confort', 'PP', 'Padrão', 1, 79.90),
(40, 22, 79, 'Conjunto Short Duplo Corrida', 'P', 'Padrão', 1, 79.90);

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagem`
--

CREATE TABLE `mensagem` (
  `id` int(11) NOT NULL,
  `fk_conversa` int(11) NOT NULL,
  `fk_usuario_remetente` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `mensagem`
--

INSERT INTO `mensagem` (`id`, `fk_conversa`, `fk_usuario_remetente`, `conteudo`, `lida`, `data_envio`) VALUES
(1, 1, 7, 'Oii', 1, '2026-10-12 21:39:24'),
(2, 1, 5, 'Olá, Juliana, o que podemos ajudar?', 1, '2026-10-12 21:40:34'),
(3, 2, 7, 'jdtutuduc', 1, '2026-10-12 21:42:05'),
(4, 2, 7, 'bjbljlkjçbçj', 1, '2026-10-12 21:42:11'),
(5, 2, 5, 'hccjfyifp', 0, '2026-10-12 21:42:47'),
(6, 3, 6, 'Olá', 1, '2026-11-25 21:23:42'),
(7, 3, 5, 'Olá', 0, '2026-11-25 21:29:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacao_estoque`
--

CREATE TABLE `movimentacao_estoque` (
  `id` int(11) NOT NULL,
  `fk_variacao` int(11) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `motivo` varchar(50) DEFAULT NULL,
  `fk_pedido` int(11) DEFAULT NULL,
  `data_movimento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `movimentacao_estoque`
--

INSERT INTO `movimentacao_estoque` (`id`, `fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(1, 22, 'saida', 1, 'venda', 1, '2026-10-15 22:57:40'),
(2, 22, 'entrada', 1, 'cancelamento', 1, '2026-10-16 12:44:30'),
(3, 29, 'saida', 1, 'venda', 2, '2026-10-19 17:12:00'),
(4, 37, 'saida', 1, 'venda', 2, '2026-10-19 17:12:00'),
(5, 32, 'saida', 1, 'venda', 3, '2026-10-21 19:40:30'),
(6, 35, 'saida', 1, 'venda', 3, '2026-10-21 19:40:30'),
(7, 32, 'saida', 1, 'venda', 4, '2026-10-23 14:07:10'),
(8, 29, 'saida', 1, 'venda', 4, '2026-10-23 14:07:10'),
(9, 32, 'saida', 1, 'venda', 5, '2026-10-26 22:16:40'),
(10, 37, 'saida', 1, 'venda', 6, '2026-10-27 16:20:45'),
(11, 35, 'saida', 1, 'venda', 6, '2026-10-27 16:20:45'),
(12, 32, 'saida', 1, 'venda', 7, '2026-10-29 13:11:30'),
(13, 29, 'saida', 1, 'venda', 7, '2026-10-29 13:11:30'),
(14, 35, 'saida', 1, 'venda', 8, '2026-10-31 18:00:25'),
(15, 32, 'saida', 1, 'venda', 8, '2026-10-31 18:00:25'),
(16, 37, 'saida', 1, 'venda', 9, '2026-11-02 15:31:55'),
(17, 35, 'saida', 1, 'venda', 9, '2026-11-02 15:31:55'),
(18, 32, 'saida', 1, 'venda', 10, '2026-11-04 12:42:10'),
(19, 29, 'saida', 1, 'venda', 10, '2026-11-04 12:42:10'),
(20, 37, 'saida', 1, 'venda', 11, '2026-11-05 23:15:20'),
(21, 37, 'saida', 1, 'venda', 12, '2026-11-07 18:20:45'),
(22, 29, 'saida', 1, 'venda', 13, '2026-11-09 13:01:50'),
(23, 37, 'saida', 1, 'venda', 13, '2026-11-09 13:01:50'),
(24, 37, 'saida', 1, 'venda', 14, '2026-11-11 20:46:12'),
(25, 32, 'saida', 1, 'venda', 15, '2026-11-13 17:16:05'),
(26, 37, 'saida', 1, 'venda', 16, '2026-11-19 19:01:22'),
(27, 29, 'saida', 1, 'venda', 17, '2026-11-21 14:30:40'),
(28, 32, 'saida', 1, 'venda', 18, '2026-11-22 21:12:00'),
(29, 29, 'entrada', 1, 'cancelamento', 19, '2026-11-25 20:46:50'),
(30, 84, 'saida', 1, 'venda', 20, '2026-11-25 20:46:57'),
(31, 84, 'entrada', 1, 'cancelamento', 20, '2026-11-25 21:05:14'),
(32, 73, 'saida', 1, 'venda', 21, '2026-11-25 21:05:52'),
(33, 79, 'saida', 1, 'venda', 22, '2026-11-25 21:23:33'),
(34, 23, 'saida', 575, 'ajuste manual', NULL, '2026-11-25 23:34:24'),
(35, 22, 'saida', 555, 'ajuste manual', NULL, '2026-11-25 23:34:24'),
(36, 21, 'saida', 555, 'ajuste manual', NULL, '2026-11-25 23:34:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamento`
--

CREATE TABLE `pagamento` (
  `id` int(11) NOT NULL,
  `fk_pedido` int(11) NOT NULL,
  `metodo` varchar(20) NOT NULL DEFAULT 'pix',
  `status` varchar(20) NOT NULL DEFAULT 'aguardando',
  `valor` decimal(10,2) NOT NULL,
  `txid` varchar(35) NOT NULL,
  `codigo` varchar(512) DEFAULT NULL,
  `simulado` tinyint(1) NOT NULL DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_pagamento` timestamp NULL DEFAULT NULL
) ;

--
-- Despejando dados para a tabela `pagamento`
--

INSERT INTO `pagamento` (`id`, `fk_pedido`, `metodo`, `status`, `valor`, `txid`, `codigo`, `simulado`, `data_criacao`, `data_pagamento`) VALUES
(1, 1, 'pix', 'cancelado', 68.00, 'CICRO000001', NULL, 0, '2026-10-15 22:57:40', NULL),
(2, 2, 'pix', 'pago', 90.00, 'CICRO000002', NULL, 1, '2026-10-19 17:10:00', '2026-10-19 17:12:00'),
(3, 3, 'pix', 'pago', 111.00, 'CICRO000003', NULL, 1, '2026-10-21 19:40:00', '2026-10-21 19:40:30'),
(4, 4, 'pix', 'pago', 105.00, 'CICRO000004', NULL, 1, '2026-10-23 14:05:00', '2026-10-23 14:07:10'),
(5, 5, 'pix', 'pago', 65.00, 'CICRO000005', NULL, 1, '2026-10-26 22:15:00', '2026-10-26 22:16:40'),
(6, 6, 'pix', 'pago', 96.00, 'CICRO000006', NULL, 1, '2026-10-27 16:20:00', '2026-10-27 16:20:45'),
(7, 7, 'pix', 'pago', 105.00, 'CICRO000007', NULL, 1, '2026-10-29 13:10:00', '2026-10-29 13:11:30'),
(8, 8, 'pix', 'pago', 111.00, 'CICRO000008', NULL, 1, '2026-10-31 18:00:00', '2026-10-31 18:00:25'),
(9, 9, 'pix', 'pago', 96.00, 'CICRO000009', NULL, 1, '2026-11-02 15:30:00', '2026-11-02 15:31:55'),
(10, 10, 'pix', 'pago', 105.00, 'CICRO000010', NULL, 1, '2026-11-04 12:40:00', '2026-11-04 12:42:10'),
(11, 11, 'pix', 'pago', 50.00, 'CICRO000011', NULL, 1, '2026-11-05 23:15:00', '2026-11-05 23:15:20'),
(12, 12, 'pix', 'pago', 50.00, 'CICRO000012', NULL, 1, '2026-11-07 18:20:00', '2026-11-07 18:20:45'),
(13, 13, 'pix', 'pago', 90.00, 'CICRO000013', NULL, 1, '2026-11-09 13:00:00', '2026-11-09 13:01:50'),
(14, 14, 'pix', 'pago', 50.00, 'CICRO000014', NULL, 1, '2026-11-11 20:45:00', '2026-11-11 20:46:12'),
(15, 15, 'pix', 'pago', 65.00, 'CICRO000015', NULL, 1, '2026-11-13 17:15:00', '2026-11-13 17:16:05'),
(16, 16, 'pix', 'pago', 50.00, 'CICRO000016', NULL, 1, '2026-11-19 19:00:00', '2026-11-19 19:01:22'),
(17, 17, 'pix', 'pago', 40.00, 'CICRO000017', NULL, 1, '2026-11-21 14:30:00', '2026-11-21 14:30:40'),
(18, 18, 'pix', 'pago', 65.00, 'CICRO000018', NULL, 1, '2026-11-22 21:10:00', '2026-11-22 21:12:00'),
(19, 19, 'pix', 'expirado', 40.00, 'CICRO000019', NULL, 1, '2026-11-23 23:00:00', NULL),
(20, 20, 'pix', 'estornado', 79.90, 'CICRO000020', '00020101021126580014br.gov.bcb.pix013600000000-0000-4000-8000-000000000000520400005303986540579.905802BR5905CICRO6008CRICIUMA62150511CICRO0000206304E9BE', 1, '2026-11-25 20:46:58', '2026-11-25 20:47:02'),
(21, 21, 'pix', 'pago', 79.90, 'CICRO000021', '00020101021126580014br.gov.bcb.pix013600000000-0000-4000-8000-000000000000520400005303986540579.905802BR5905CICRO6008CRICIUMA62150511CICRO000021630443EF', 1, '2026-11-25 21:05:52', '2026-11-25 21:05:57'),
(22, 22, 'pix', 'pago', 79.90, 'CICRO000022', '00020101021126580014br.gov.bcb.pix013600000000-0000-4000-8000-000000000000520400005303986540579.905802BR5905CICRO6008CRICIUMA62150511CICRO0000226304AD3D', 1, '2026-11-25 21:23:33', '2026-11-25 21:23:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido`
--

CREATE TABLE `pedido` (
  `id` int(11) NOT NULL,
  `fk_cliente` int(11) NOT NULL,
  `fk_endereco` int(11) DEFAULT NULL,
  `endereco_entrega` varchar(500) DEFAULT NULL,
  `forma_pagamento` varchar(20) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'Pendente',
  `valor_total` decimal(10,2) NOT NULL
) ;

--
-- Despejando dados para a tabela `pedido`
--

INSERT INTO `pedido` (`id`, `fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`) VALUES
(1, 7, 2, 'Rua Afonso Milanese, nº 464 - Bloco 18 Apto 204 · São Defende · Criciúma/SC · CEP 88808-052', 'pix', '2026-10-15 22:57:40', 'Cancelado', 68.00),
(2, 8, 3, 'Rua Coronel Pedro Benedet, nº 310 - Apto 502 · Centro · Criciúma/SC · CEP 88801-100', 'pix', '2026-10-19 17:10:00', 'Entregue', 90.00),
(3, 9, 4, 'Avenida Centenário, nº 1450 - Bloco B · Santa Bárbara · Criciúma/SC · CEP 88804-000', 'pix', '2026-10-21 19:40:00', 'Entregue', 111.00),
(4, 10, 5, 'Rua Henrique Lage, nº 890 · Comerciário · Criciúma/SC · CEP 88815-200', 'pix', '2026-10-23 14:05:00', 'Entregue', 105.00),
(5, 11, 6, 'Rua Joaquim Nabuco, nº 520 - Sala 04 · Michel · Criciúma/SC · CEP 88802-300', 'pix', '2026-10-26 22:15:00', 'Entregue', 65.00),
(6, 12, 7, 'Rua São José, nº 712 - Casa 2 · Próspera · Criciúma/SC · CEP 88806-000', 'pix', '2026-10-27 16:20:00', 'Entregue', 96.00),
(7, 13, 8, 'Rua Anita Garibaldi, nº 198 · Operária Nova · Criciúma/SC · CEP 88811-400', 'pix', '2026-10-29 13:10:00', 'Entregue', 105.00),
(8, 14, 9, 'Rua Marechal Deodoro, nº 405 - Apto 101 · Centro · Criciúma/SC · CEP 88803-210', 'pix', '2026-10-31 18:00:00', 'Entregue', 111.00),
(9, 15, 10, 'Rua Santo Antônio, nº 630 · Pinheirinho · Criciúma/SC · CEP 88813-150', 'pix', '2026-11-02 15:30:00', 'Entregue', 96.00),
(10, 16, 11, 'Rua Imigrante Zanatta, nº 95 - Apto 303 · Cruzeiro do Sul · Criciúma/SC · CEP 88807-320', 'pix', '2026-11-04 12:40:00', 'Entregue', 105.00),
(11, 17, 12, 'Rua Vitor Meireles, nº 1120 · Rio Maina · Criciúma/SC · CEP 88809-110', 'pix', '2026-11-05 23:15:00', 'Entregue', 50.00),
(12, 1, 13, 'Rua Gonçalves Ledo, nº 215 · Pio Corrêa · Criciúma/SC · CEP 88805-120', 'pix', '2026-11-07 18:20:00', 'Entregue', 50.00),
(13, 7, 2, 'Rua Afonso Milanese, nº 464 - Bloco 18 Apto 204 · São Defende · Criciúma/SC · CEP 88808-052', 'pix', '2026-11-09 13:00:00', 'Entregue', 90.00),
(14, 8, 3, 'Rua Coronel Pedro Benedet, nº 310 - Apto 502 · Centro · Criciúma/SC · CEP 88801-100', 'pix', '2026-11-11 20:45:00', 'Entregue', 50.00),
(15, 9, 4, 'Avenida Centenário, nº 1450 - Bloco B · Santa Bárbara · Criciúma/SC · CEP 88804-000', 'pix', '2026-11-13 17:15:00', 'Entregue', 65.00),
(16, 12, 7, 'Rua São José, nº 712 - Casa 2 · Próspera · Criciúma/SC · CEP 88806-000', 'pix', '2026-11-19 19:00:00', 'Enviado', 50.00),
(17, 13, 8, 'Rua Anita Garibaldi, nº 198 · Operária Nova · Criciúma/SC · CEP 88811-400', 'pix', '2026-11-21 14:30:00', 'Enviado', 40.00),
(18, 15, 10, 'Rua Santo Antônio, nº 630 · Pinheirinho · Criciúma/SC · CEP 88813-150', 'pix', '2026-11-22 21:10:00', 'Em Preparação', 65.00),
(19, 16, 11, 'Rua Imigrante Zanatta, nº 95 - Apto 303 · Cruzeiro do Sul · Criciúma/SC · CEP 88807-320', 'pix', '2026-11-23 23:00:00', 'Cancelado', 40.00),
(20, 6, 14, 'Rua Bento Antônio Neto, nº 10 - Última casa da rua, lado direito · São Sebastião · Criciúma/SC · CEP 88807-090', 'pix', '2026-11-25 20:46:57', 'Cancelado', 79.90),
(21, 6, 14, 'Rua Bento Antônio Neto, nº 10 - Última casa da rua, lado direito · São Sebastião · Criciúma/SC · CEP 88807-090', 'pix', '2026-11-25 21:05:52', 'Em Preparação', 79.90),
(22, 6, 14, 'Rua Bento Antônio Neto, nº 10 - Última casa da rua, lado direito · São Sebastião · Criciúma/SC · CEP 88807-090', 'pix', '2026-11-25 21:23:33', 'Em Preparação', 79.90);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto`
--

CREATE TABLE `produto` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco_base` decimal(10,2) NOT NULL,
  `fk_categoria` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produto`
--

INSERT INTO `produto` (`id`, `nome`, `descricao`, `preco_base`, `fk_categoria`, `status`, `data_cadastro`) VALUES
(10, 'Calça Taylor', NULL, 68.00, 1, 0, '2026-10-02 13:57:35'),
(12, 'Camiseta LIFE! fitness', 'dupla camada de tecido, 70% algodão', 40.00, 1, 1, '2026-10-16 13:31:18'),
(13, 'Conjunto casaquinho fitness', 'Produto 60% algodão', 65.00, 2, 1, '2026-10-16 13:32:57'),
(14, 'Conjunto regata face double', 'Conjunto para melhor desempenho', 46.00, 3, 1, '2026-10-16 13:33:58'),
(15, 'Conjunto Dani Short', 'anti-transpirante, poliéster', 50.00, 2, 1, '2026-10-16 13:35:10'),
(16, 'Blusa 64', 'Blusa 64 - peça da coleção CICRO.', 49.90, 1, 1, '2026-11-25 20:37:58'),
(17, 'Blusa By Power Tule', 'Blusa By Power Tule - peça da coleção CICRO.', 49.90, 1, 1, '2026-11-25 20:37:58'),
(18, 'Blusa CR Sport', 'Blusa CR Sport - peça da coleção CICRO.', 49.90, 1, 1, '2026-11-25 20:37:58'),
(19, 'Blusa Go Fitness', 'Blusa Go Fitness - peça da coleção CICRO.', 49.90, 1, 1, '2026-11-25 20:37:59'),
(20, 'Blusa Love Cicro', 'Blusa Love Cicro - peça da coleção CICRO.', 49.90, 1, 1, '2026-11-25 20:37:59'),
(21, 'Blusa Premium', 'Blusa Premium - peça da coleção CICRO.', 49.90, 1, 1, '2026-11-25 20:37:59'),
(22, 'Calça Simples', 'Calça Simples - peça da coleção CICRO.', 69.90, 6, 1, '2026-11-25 20:37:59'),
(23, 'Camisa Super Girl', 'Camisa Super Girl - peça da coleção CICRO.', 49.90, 1, 1, '2026-11-25 20:37:59'),
(24, 'Casaco Jacard Life', 'Casaco Jacard Life - peça da coleção CICRO.', 89.90, 1, 1, '2026-11-25 20:37:59'),
(25, 'Conjunto Canelado de Calça Ava', 'Conjunto Canelado de Calça Ava - peça da coleção CICRO.', 79.90, 2, 1, '2026-11-25 20:37:59'),
(26, 'Conjunto Canelado de Short Yzza', 'Conjunto Canelado de Short Yzza - peça da coleção CICRO.', 79.90, 2, 1, '2026-11-25 20:37:59'),
(27, 'Conjunto de Calça Carol', 'Conjunto de Calça Carol - peça da coleção CICRO.', 79.90, 2, 1, '2026-11-25 20:37:59'),
(28, 'Conjunto de Short Comfort', 'Conjunto de Short Confort - peça da coleção CICRO.', 79.90, 2, 1, '2026-11-25 20:37:59'),
(29, 'Conjunto Jacard de Calça Life', 'Conjunto Jacard de Calça Life - peça da coleção CICRO.', 79.90, 2, 1, '2026-11-25 20:37:59'),
(30, 'Conjunto Short Duplo Corrida', 'Conjunto Short Duplo Corrida - peça da coleção CICRO.', 79.90, 2, 1, '2026-11-25 20:37:59'),
(31, 'Macaquinho Miami', 'Macaquinho Miami - peça da coleção CICRO.', 79.90, 2, 1, '2026-11-25 20:37:59');

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacao_cpf`
--

CREATE TABLE `solicitacao_cpf` (
  `id` int(11) NOT NULL,
  `fk_cliente` int(11) NOT NULL,
  `cpf_atual` varchar(255) DEFAULT NULL,
  `motivo` varchar(255) NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'pendente',
  `resposta` varchar(255) DEFAULT NULL,
  `fk_admin` int(11) DEFAULT NULL,
  `data_solicitacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_resposta` datetime DEFAULT NULL,
  `data_conclusao` datetime DEFAULT NULL
) ;

--
-- Despejando dados para a tabela `solicitacao_cpf`
--

INSERT INTO `solicitacao_cpf` (`id`, `fk_cliente`, `cpf_atual`, `motivo`, `status`, `resposta`, `fk_admin`, `data_solicitacao`, `data_resposta`, `data_conclusao`) VALUES
(1, 6, 'v1:NNjNh1dJT+BXGOnw7tRdCl2+kd5drGidqt144LZY78m135FHPDWWKrzY', 'errei o cpf', 'aprovada', NULL, 5, '2026-11-25 21:23:53', '2026-11-25 18:29:10', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `tipo_usuario` enum('cliente','vendedor','admin') NOT NULL DEFAULT 'cliente',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `aceite_termos` tinyint(1) NOT NULL DEFAULT 0,
  `data_aceite_termos` timestamp NULL DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_exclusao` timestamp NULL DEFAULT NULL,
  `email_verificado` tinyint(1) NOT NULL DEFAULT 0,
  `token_verificacao` char(64) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `verificacao_enviada_em` datetime DEFAULT NULL,
  `google_id` varchar(40) DEFAULT NULL,
  `token_recuperacao` char(64) DEFAULT NULL,
  `recuperacao_expira` datetime DEFAULT NULL,
  `recuperacao_enviada_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id`, `nome`, `email`, `foto_perfil`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `data_cadastro`, `data_exclusao`, `email_verificado`, `token_verificacao`, `token_expira`, `verificacao_enviada_em`, `google_id`, `token_recuperacao`, `recuperacao_expira`, `recuperacao_enviada_em`) VALUES
(1, 'Alycia Vitória Benício da Silva', 'Alycia@gmail.com', NULL, '$2y$10$sPuvOAcMcaORDBtH3b9YLu2Ao5mVQ0u4e9WSh0x8qSToFoBBO3kaG', NULL, 'cliente', 1, 0, NULL, '2026-09-01 02:17:15', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Administrador', 'admin@cicro.com', NULL, '$2y$10$g4uukUzXvf8NEVJudBJVNO.tXAxpZoQsb3WV.A9dJFAxYHQ7o2zhO', NULL, 'admin', 1, 1, NULL, '2026-09-20 21:18:37', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'teste', '4553368403@estudante.sed.sc.gov.br', NULL, '$2y$10$27g52ZS5dcHAWep9JIwaR.xlPmjACdxe5QTHAEGHwDi8kPt52UeHq', '(48) 99655-3246', 'cliente', 1, 0, NULL, '2026-09-25 13:50:32', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Juliana Fabri', '4553477814@estudante.sed.sc.gov.br', NULL, '$2y$10$Iy2DcFayMPX7P2JN5YIQYeiacroXR5/hgyWu3hhCnd1TUMrNm2yGS', NULL, 'cliente', 1, 0, NULL, '2026-10-04 20:42:04', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Mariana Costa Oliveira', 'mariana.oliveira@email.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99123-4567', 'cliente', 1, 1, '2026-10-18 13:15:00', '2026-10-18 13:15:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'Camila Ferreira Santos', 'camila.santos@email.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99876-5432', 'cliente', 1, 1, '2026-10-20 17:30:00', '2026-10-20 17:30:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'Beatriz Almeida Rocha', 'beatriz.rocha@email.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99654-3210', 'cliente', 1, 1, '2026-10-22 12:45:00', '2026-10-22 12:45:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Lucas Mendes Pereira', 'lucas.pereira@email.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99321-7890', 'cliente', 1, 1, '2026-10-25 21:20:00', '2026-10-25 21:20:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'Fernanda Lima Cardoso', 'fernanda.cardoso@email.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99411-2233', 'cliente', 1, 1, '2026-10-26 14:10:00', '2026-10-26 14:10:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'Gabriela Martins Souza', 'gabriela.souza@email.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99522-3344', 'cliente', 1, 1, '2026-10-28 18:40:00', '2026-10-28 18:40:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'Rafael Nogueira Dias', 'rafael.dias@email.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99633-4455', 'cliente', 1, 1, '2026-10-30 11:50:00', '2026-10-30 11:50:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'Larissa Ribeiro Gomes', 'larissa.gomes@email.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99744-5566', 'cliente', 1, 1, '2026-11-01 16:25:00', '2026-11-01 16:25:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'Patrícia Azevedo Barbosa', 'patricia.barbosa@email.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99855-6677', 'cliente', 1, 1, '2026-11-03 20:05:00', '2026-11-03 20:05:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'Thiago Carvalho Teixeira', 'thiago.teixeira@email.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99966-7788', 'cliente', 1, 1, '2026-11-04 22:50:00', '2026-11-04 22:50:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `variacao_produto`
--

CREATE TABLE `variacao_produto` (
  `id` int(11) NOT NULL,
  `fk_produto` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `tamanho` varchar(10) NOT NULL,
  `cor` varchar(30) NOT NULL,
  `quantidade_estoque` int(11) NOT NULL DEFAULT 0,
  `preco_adicional` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `variacao_produto`
--

INSERT INTO `variacao_produto` (`id`, `fk_produto`, `sku`, `tamanho`, `cor`, `quantidade_estoque`, `preco_adicional`) VALUES
(21, 10, 'PROD-10-PP-9cdb', 'PP', 'Padrão', 20, 0.00),
(22, 10, 'PROD-10-M-9d79', 'M', 'Padrão', 20, 0.00),
(23, 10, 'PROD-10-GG-a8b9', 'GG', 'Padrão', 0, 0.00),
(27, 12, 'PROD-12-PP-4699', 'PP', 'Padrão', 25, 0.00),
(28, 12, 'PROD-12-M-4718', 'M', 'Padrão', 25, 0.00),
(29, 12, 'PROD-12-G-4772', 'G', 'Padrão', 26, 0.00),
(30, 13, 'PROD-13-PP-e81b', 'PP', 'Padrão', 30, 0.00),
(31, 13, 'PROD-13-M-e8f7', 'M', 'Padrão', 30, 0.00),
(32, 13, 'PROD-13-GG-f7d3', 'GG', 'Padrão', 30, 0.00),
(33, 14, 'PROD-14-PP-a5b1', 'PP', 'Padrão', 60, 0.00),
(34, 14, 'PROD-14-P-a620', 'P', 'Padrão', 60, 0.00),
(35, 14, 'PROD-14-M-a65d', 'M', 'Padrão', 60, 0.00),
(36, 15, 'PROD-15-P-0184', 'P', 'Padrão', 20, 0.00),
(37, 15, 'PROD-15-G-0252', 'G', 'Padrão', 20, 0.00),
(38, 16, 'PROD-16-PP-84a6', 'PP', 'Padrão', 20, 0.00),
(39, 16, 'PROD-16-M-83f3', 'M', 'Padrão', 20, 0.00),
(40, 17, 'PROD-17-G-3dec', 'G', 'Padrão', 20, 0.00),
(41, 17, 'PROD-17-GG-9dcd', 'GG', 'Padrão', 20, 0.00),
(42, 18, 'PROD-18-P-7527', 'P', 'Padrão', 20, 0.00),
(43, 18, 'PROD-18-M-a815', 'M', 'Padrão', 20, 0.00),
(44, 18, 'PROD-18-G-9f41', 'G', 'Padrão', 20, 0.00),
(45, 18, 'PROD-18-GG-d41f', 'GG', 'Padrão', 20, 0.00),
(46, 19, 'PROD-19-PP-8db5', 'PP', 'Padrão', 20, 0.00),
(47, 19, 'PROD-19-GG-00cf', 'GG', 'Padrão', 20, 0.00),
(48, 20, 'PROD-20-PP-48f7', 'PP', 'Padrão', 20, 0.00),
(49, 20, 'PROD-20-M-417f', 'M', 'Padrão', 20, 0.00),
(50, 21, 'PROD-21-PP-edf2', 'PP', 'Padrão', 20, 0.00),
(51, 21, 'PROD-21-M-7bed', 'M', 'Padrão', 20, 0.00),
(52, 21, 'PROD-21-G-591a', 'G', 'Padrão', 20, 0.00),
(53, 22, 'PROD-22-P-4876', 'P', 'Padrão', 20, 0.00),
(54, 22, 'PROD-22-G-e6bd', 'G', 'Padrão', 20, 0.00),
(55, 22, 'PROD-22-GG-6891', 'GG', 'Padrão', 20, 0.00),
(56, 23, 'PROD-23-M-80f8', 'M', 'Padrão', 20, 0.00),
(57, 23, 'PROD-23-G-28ae', 'G', 'Padrão', 20, 0.00),
(58, 23, 'PROD-23-GG-9a9d', 'GG', 'Padrão', 20, 0.00),
(59, 24, 'PROD-24-PP-573c', 'PP', 'Padrão', 20, 0.00),
(60, 24, 'PROD-24-P-c18f', 'P', 'Padrão', 20, 0.00),
(61, 24, 'PROD-24-M-e391', 'M', 'Padrão', 20, 0.00),
(62, 24, 'PROD-24-GG-0420', 'GG', 'Padrão', 20, 0.00),
(63, 25, 'PROD-25-P-c120', 'P', 'Padrão', 20, 0.00),
(64, 25, 'PROD-25-M-4600', 'M', 'Padrão', 20, 0.00),
(65, 25, 'PROD-25-G-da2b', 'G', 'Padrão', 20, 0.00),
(66, 26, 'PROD-26-PP-3e5c', 'PP', 'Padrão', 20, 0.00),
(67, 26, 'PROD-26-P-0b7b', 'P', 'Padrão', 20, 0.00),
(68, 26, 'PROD-26-M-4f62', 'M', 'Padrão', 20, 0.00),
(69, 26, 'PROD-26-GG-74fe', 'GG', 'Padrão', 20, 0.00),
(70, 27, 'PROD-27-PP-b1f5', 'PP', 'Padrão', 20, 0.00),
(71, 27, 'PROD-27-G-d340', 'G', 'Padrão', 20, 0.00),
(72, 27, 'PROD-27-GG-d767', 'GG', 'Padrão', 20, 0.00),
(73, 28, 'PROD-28-PP-143c', 'PP', 'Padrão', 19, 0.00),
(74, 28, 'PROD-28-G-1f65', 'G', 'Padrão', 20, 0.00),
(75, 28, 'PROD-28-GG-858b', 'GG', 'Padrão', 20, 0.00),
(76, 29, 'PROD-29-P-311c', 'P', 'Padrão', 20, 0.00),
(77, 29, 'PROD-29-G-3e25', 'G', 'Padrão', 20, 0.00),
(78, 29, 'PROD-29-GG-1569', 'GG', 'Padrão', 20, 0.00),
(79, 30, 'PROD-30-P-667b', 'P', 'Padrão', 19, 0.00),
(80, 30, 'PROD-30-M-4fb2', 'M', 'Padrão', 20, 0.00),
(81, 30, 'PROD-30-G-3cd1', 'G', 'Padrão', 20, 0.00),
(82, 30, 'PROD-30-GG-dd43', 'GG', 'Padrão', 20, 0.00),
(83, 31, 'PROD-31-PP-8842', 'PP', 'Padrão', 20, 0.00),
(84, 31, 'PROD-31-M-7237', 'M', 'Padrão', 20, 0.00),
(85, 31, 'PROD-31-G-bcc5', 'G', 'Padrão', 20, 0.00),
(86, 31, 'PROD-31-GG-b7f1', 'GG', 'Padrão', 20, 0.00);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_avaliacao_cliente_produto` (`fk_cliente`,`fk_produto`),
  ADD KEY `fk_avaliacao_produto` (`fk_produto`),
  ADD KEY `fk_avaliacao_cliente` (`fk_cliente`),
  ADD KEY `fk_avaliacao_pedido` (`fk_pedido`);

--
-- Índices de tabela `carrinho`
--
ALTER TABLE `carrinho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_carrinho_cliente` (`fk_cliente`);

--
-- Índices de tabela `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Índices de tabela `categoria_carrinho`
--
ALTER TABLE `categoria_carrinho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_catcarrinho_carrinho` (`fk_carrinho`);

--
-- Índices de tabela `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `cpf_hash` (`cpf_hash`);

--
-- Índices de tabela `conversa`
--
ALTER TABLE `conversa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_conversa_cliente` (`fk_cliente`),
  ADD KEY `fk_conversa_atendente` (`fk_atendente`);

--
-- Índices de tabela `endereco`
--
ALTER TABLE `endereco`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_endereco_cliente_cep` (`fk_cliente`,`cep`),
  ADD KEY `fk_endereco_cliente` (`fk_cliente`);

--
-- Índices de tabela `funcionario`
--
ALTER TABLE `funcionario`
  ADD PRIMARY KEY (`id_usuario`);

--
-- Índices de tabela `imagem_produto`
--
ALTER TABLE `imagem_produto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_imagem_produto_produto` (`fk_produto`);

--
-- Índices de tabela `item_carrinho`
--
ALTER TABLE `item_carrinho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_itemcarrinho_variacao` (`fk_variacao`),
  ADD KEY `fk_itemcarrinho_catcarrinho` (`fk_categoria_carrinho`),
  ADD KEY `idx_item_carrinho` (`fk_carrinho`,`fk_variacao`),
  ADD KEY `fk_item_imagem` (`fk_imagem`);

--
-- Índices de tabela `item_pedido`
--
ALTER TABLE `item_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_itempedido_pedido` (`fk_pedido`),
  ADD KEY `fk_itempedido_variacao` (`fk_variacao`);

--
-- Índices de tabela `mensagem`
--
ALTER TABLE `mensagem`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mensagem_conversa` (`fk_conversa`),
  ADD KEY `fk_mensagem_usuario` (`fk_usuario_remetente`);

--
-- Índices de tabela `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_movimentacao_variacao` (`fk_variacao`),
  ADD KEY `fk_movimentacao_pedido` (`fk_pedido`);

--
-- Índices de tabela `pagamento`
--
ALTER TABLE `pagamento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pagamento_pedido` (`fk_pedido`),
  ADD UNIQUE KEY `uq_pagamento_txid` (`txid`),
  ADD KEY `idx_pagamento_status` (`status`);

--
-- Índices de tabela `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pedido_cliente` (`fk_cliente`),
  ADD KEY `idx_pedido_data` (`data_criacao`),
  ADD KEY `idx_pedido_status` (`status`),
  ADD KEY `idx_pedido_endereco` (`fk_endereco`);

--
-- Índices de tabela `produto`
--
ALTER TABLE `produto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_produto_categoria` (`fk_categoria`);

--
-- Índices de tabela `solicitacao_cpf`
--
ALTER TABLE `solicitacao_cpf`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solcpf_cliente` (`fk_cliente`),
  ADD KEY `idx_solcpf_status` (`status`),
  ADD KEY `fk_solcpf_admin` (`fk_admin`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_usuario_google` (`google_id`),
  ADD KEY `idx_usuario_token` (`token_verificacao`),
  ADD KEY `idx_usuario_recuperacao` (`token_recuperacao`);

--
-- Índices de tabela `variacao_produto`
--
ALTER TABLE `variacao_produto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `uq_prod_tam_cor` (`fk_produto`,`tamanho`,`cor`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `avaliacao`
--
ALTER TABLE `avaliacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `carrinho`
--
ALTER TABLE `carrinho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `categoria_carrinho`
--
ALTER TABLE `categoria_carrinho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `conversa`
--
ALTER TABLE `conversa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `endereco`
--
ALTER TABLE `endereco`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `imagem_produto`
--
ALTER TABLE `imagem_produto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT de tabela `item_carrinho`
--
ALTER TABLE `item_carrinho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `item_pedido`
--
ALTER TABLE `item_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de tabela `mensagem`
--
ALTER TABLE `mensagem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `pagamento`
--
ALTER TABLE `pagamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedido`
--
ALTER TABLE `pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produto`
--
ALTER TABLE `produto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `solicitacao_cpf`
--
ALTER TABLE `solicitacao_cpf`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `variacao_produto`
--
ALTER TABLE `variacao_produto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `avaliacao`
--
ALTER TABLE `avaliacao`
  ADD CONSTRAINT `fk_avaliacao_cliente` FOREIGN KEY (`fk_cliente`) REFERENCES `cliente` (`id_usuario`),
  ADD CONSTRAINT `fk_avaliacao_pedido` FOREIGN KEY (`fk_pedido`) REFERENCES `pedido` (`id`),
  ADD CONSTRAINT `fk_avaliacao_produto` FOREIGN KEY (`fk_produto`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `carrinho`
--
ALTER TABLE `carrinho`
  ADD CONSTRAINT `fk_carrinho_cliente` FOREIGN KEY (`fk_cliente`) REFERENCES `cliente` (`id_usuario`);

--
-- Restrições para tabelas `categoria_carrinho`
--
ALTER TABLE `categoria_carrinho`
  ADD CONSTRAINT `fk_catcarrinho_carrinho` FOREIGN KEY (`fk_carrinho`) REFERENCES `carrinho` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cliente`
--
ALTER TABLE `cliente`
  ADD CONSTRAINT `fk_cliente_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`);

--
-- Restrições para tabelas `conversa`
--
ALTER TABLE `conversa`
  ADD CONSTRAINT `fk_conversa_atendente` FOREIGN KEY (`fk_atendente`) REFERENCES `funcionario` (`id_usuario`),
  ADD CONSTRAINT `fk_conversa_cliente` FOREIGN KEY (`fk_cliente`) REFERENCES `cliente` (`id_usuario`);

--
-- Restrições para tabelas `endereco`
--
ALTER TABLE `endereco`
  ADD CONSTRAINT `fk_endereco_cliente` FOREIGN KEY (`fk_cliente`) REFERENCES `cliente` (`id_usuario`);

--
-- Restrições para tabelas `funcionario`
--
ALTER TABLE `funcionario`
  ADD CONSTRAINT `fk_funcionario_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`);

--
-- Restrições para tabelas `imagem_produto`
--
ALTER TABLE `imagem_produto`
  ADD CONSTRAINT `fk_imagem_produto_produto` FOREIGN KEY (`fk_produto`) REFERENCES `produto` (`id`);

--
-- Restrições para tabelas `item_carrinho`
--
ALTER TABLE `item_carrinho`
  ADD CONSTRAINT `fk_item_imagem` FOREIGN KEY (`fk_imagem`) REFERENCES `imagem_produto` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_itemcarrinho_carrinho` FOREIGN KEY (`fk_carrinho`) REFERENCES `carrinho` (`id`),
  ADD CONSTRAINT `fk_itemcarrinho_catcarrinho` FOREIGN KEY (`fk_categoria_carrinho`) REFERENCES `categoria_carrinho` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_itemcarrinho_variacao` FOREIGN KEY (`fk_variacao`) REFERENCES `variacao_produto` (`id`);

--
-- Restrições para tabelas `item_pedido`
--
ALTER TABLE `item_pedido`
  ADD CONSTRAINT `fk_itempedido_pedido` FOREIGN KEY (`fk_pedido`) REFERENCES `pedido` (`id`),
  ADD CONSTRAINT `fk_itempedido_variacao` FOREIGN KEY (`fk_variacao`) REFERENCES `variacao_produto` (`id`);

--
-- Restrições para tabelas `mensagem`
--
ALTER TABLE `mensagem`
  ADD CONSTRAINT `fk_mensagem_conversa` FOREIGN KEY (`fk_conversa`) REFERENCES `conversa` (`id`),
  ADD CONSTRAINT `fk_mensagem_usuario` FOREIGN KEY (`fk_usuario_remetente`) REFERENCES `usuario` (`id`);

--
-- Restrições para tabelas `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  ADD CONSTRAINT `fk_movimentacao_pedido` FOREIGN KEY (`fk_pedido`) REFERENCES `pedido` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_movimentacao_variacao` FOREIGN KEY (`fk_variacao`) REFERENCES `variacao_produto` (`id`);

--
-- Restrições para tabelas `pagamento`
--
ALTER TABLE `pagamento`
  ADD CONSTRAINT `fk_pagamento_pedido` FOREIGN KEY (`fk_pedido`) REFERENCES `pedido` (`id`);

--
-- Restrições para tabelas `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`fk_cliente`) REFERENCES `cliente` (`id_usuario`),
  ADD CONSTRAINT `fk_pedido_endereco` FOREIGN KEY (`fk_endereco`) REFERENCES `endereco` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `produto`
--
ALTER TABLE `produto`
  ADD CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`fk_categoria`) REFERENCES `categoria` (`id`);

--
-- Restrições para tabelas `solicitacao_cpf`
--
ALTER TABLE `solicitacao_cpf`
  ADD CONSTRAINT `fk_solcpf_admin` FOREIGN KEY (`fk_admin`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_solcpf_cliente` FOREIGN KEY (`fk_cliente`) REFERENCES `cliente` (`id_usuario`) ON DELETE CASCADE;

--
-- Restrições para tabelas `variacao_produto`
--
ALTER TABLE `variacao_produto`
  ADD CONSTRAINT `fk_variacao_produto_produto` FOREIGN KEY (`fk_produto`) REFERENCES `produto` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
