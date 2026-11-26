-- =====================================================================
-- CICRO - POPULAÇÃO EXPANDIDA DE DADOS (v2)
-- Inclui: 10 clientes no total, 18 compras completas e 22 avaliações
-- Compatível com MariaDB 10.4 (XAMPP) e banco 'cicro'
-- =====================================================================

USE `cicro`;
SET NAMES utf8mb4;

START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1. NOVOS USUÁRIOS E CLIENTES (10 novos + os já existentes no banco)
-- Senha padrão para testes: 123456
-- Hash bcrypt compatível com password_verify() do PHP:
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ---------------------------------------------------------------------

INSERT INTO `usuario` (`nome`, `email`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `email_verificado`, `data_cadastro`)
VALUES ('Mariana Costa Oliveira', 'mariana.oliveira@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99123-4567', 'cliente', 1, 1, '2026-10-18 10:15:00', 1, '2026-10-18 10:15:00');
SET @u_mariana = LAST_INSERT_ID();
INSERT INTO `cliente` (`id_usuario`, `cpf`) VALUES (@u_mariana, '111.222.333-44');
INSERT INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (@u_mariana, 'Casa', '88801-100', 'Rua Coronel Pedro Benedet', '310', 'Apto 502', 'Centro', 'Criciúma', 'SC', 1);
SET @end_mariana = LAST_INSERT_ID();

INSERT INTO `usuario` (`nome`, `email`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `email_verificado`, `data_cadastro`)
VALUES ('Camila Ferreira Santos', 'camila.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99876-5432', 'cliente', 1, 1, '2026-10-20 14:30:00', 1, '2026-10-20 14:30:00');
SET @u_camila = LAST_INSERT_ID();
INSERT INTO `cliente` (`id_usuario`, `cpf`) VALUES (@u_camila, '222.333.444-55');
INSERT INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (@u_camila, 'Apartamento', '88804-000', 'Avenida Centenário', '1450', 'Bloco B', 'Santa Bárbara', 'Criciúma', 'SC', 1);
SET @end_camila = LAST_INSERT_ID();

INSERT INTO `usuario` (`nome`, `email`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `email_verificado`, `data_cadastro`)
VALUES ('Beatriz Almeida Rocha', 'beatriz.rocha@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99654-3210', 'cliente', 1, 1, '2026-10-22 09:45:00', 1, '2026-10-22 09:45:00');
SET @u_beatriz = LAST_INSERT_ID();
INSERT INTO `cliente` (`id_usuario`, `cpf`) VALUES (@u_beatriz, '333.444.555-66');
INSERT INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (@u_beatriz, 'Casa', '88815-200', 'Rua Henrique Lage', '890', NULL, 'Comerciário', 'Criciúma', 'SC', 1);
SET @end_beatriz = LAST_INSERT_ID();

INSERT INTO `usuario` (`nome`, `email`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `email_verificado`, `data_cadastro`)
VALUES ('Lucas Mendes Pereira', 'lucas.pereira@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99321-7890', 'cliente', 1, 1, '2026-10-25 18:20:00', 1, '2026-10-25 18:20:00');
SET @u_lucas = LAST_INSERT_ID();
INSERT INTO `cliente` (`id_usuario`, `cpf`) VALUES (@u_lucas, '444.555.666-77');
INSERT INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (@u_lucas, 'Trabalho', '88802-300', 'Rua Joaquim Nabuco', '520', 'Sala 04', 'Michel', 'Criciúma', 'SC', 1);
SET @end_lucas = LAST_INSERT_ID();

INSERT INTO `usuario` (`nome`, `email`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `email_verificado`, `data_cadastro`)
VALUES ('Fernanda Lima Cardoso', 'fernanda.cardoso@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99411-2233', 'cliente', 1, 1, '2026-10-26 11:10:00', 1, '2026-10-26 11:10:00');
SET @u_fernanda = LAST_INSERT_ID();
INSERT INTO `cliente` (`id_usuario`, `cpf`) VALUES (@u_fernanda, '555.666.777-88');
INSERT INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (@u_fernanda, 'Casa', '88806-000', 'Rua São José', '712', 'Casa 2', 'Próspera', 'Criciúma', 'SC', 1);
SET @end_fernanda = LAST_INSERT_ID();

INSERT INTO `usuario` (`nome`, `email`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `email_verificado`, `data_cadastro`)
VALUES ('Gabriela Martins Souza', 'gabriela.souza@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99522-3344', 'cliente', 1, 1, '2026-10-28 15:40:00', 1, '2026-10-28 15:40:00');
SET @u_gabriela = LAST_INSERT_ID();
INSERT INTO `cliente` (`id_usuario`, `cpf`) VALUES (@u_gabriela, '666.777.888-99');
INSERT INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (@u_gabriela, 'Casa', '88811-400', 'Rua Anita Garibaldi', '198', NULL, 'Operária Nova', 'Criciúma', 'SC', 1);
SET @end_gabriela = LAST_INSERT_ID();

INSERT INTO `usuario` (`nome`, `email`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `email_verificado`, `data_cadastro`)
VALUES ('Rafael Nogueira Dias', 'rafael.dias@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99633-4455', 'cliente', 1, 1, '2026-10-30 08:50:00', 1, '2026-10-30 08:50:00');
SET @u_rafael = LAST_INSERT_ID();
INSERT INTO `cliente` (`id_usuario`, `cpf`) VALUES (@u_rafael, '777.888.999-00');
INSERT INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (@u_rafael, 'Casa', '88803-210', 'Rua Marechal Deodoro', '405', 'Apto 101', 'Centro', 'Criciúma', 'SC', 1);
SET @end_rafael = LAST_INSERT_ID();

INSERT INTO `usuario` (`nome`, `email`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `email_verificado`, `data_cadastro`)
VALUES ('Larissa Ribeiro Gomes', 'larissa.gomes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99744-5566', 'cliente', 1, 1, '2026-11-01 13:25:00', 1, '2026-11-01 13:25:00');
SET @u_larissa = LAST_INSERT_ID();
INSERT INTO `cliente` (`id_usuario`, `cpf`) VALUES (@u_larissa, '888.999.000-11');
INSERT INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (@u_larissa, 'Casa', '88813-150', 'Rua Santo Antônio', '630', NULL, 'Pinheirinho', 'Criciúma', 'SC', 1);
SET @end_larissa = LAST_INSERT_ID();

INSERT INTO `usuario` (`nome`, `email`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `email_verificado`, `data_cadastro`)
VALUES ('Patrícia Azevedo Barbosa', 'patricia.barbosa@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99855-6677', 'cliente', 1, 1, '2026-11-03 17:05:00', 1, '2026-11-03 17:05:00');
SET @u_patricia = LAST_INSERT_ID();
INSERT INTO `cliente` (`id_usuario`, `cpf`) VALUES (@u_patricia, '999.000.111-22');
INSERT INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (@u_patricia, 'Apartamento', '88807-320', 'Rua Imigrante Zanatta', '95', 'Apto 303', 'Cruzeiro do Sul', 'Criciúma', 'SC', 1);
SET @end_patricia = LAST_INSERT_ID();

INSERT INTO `usuario` (`nome`, `email`, `senha_hash`, `telefone`, `tipo_usuario`, `ativo`, `aceite_termos`, `data_aceite_termos`, `email_verificado`, `data_cadastro`)
VALUES ('Thiago Carvalho Teixeira', 'thiago.teixeira@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(48) 99966-7788', 'cliente', 1, 1, '2026-11-04 19:50:00', 1, '2026-11-04 19:50:00');
SET @u_thiago = LAST_INSERT_ID();
INSERT INTO `cliente` (`id_usuario`, `cpf`) VALUES (@u_thiago, '123.456.789-01');
INSERT INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (@u_thiago, 'Casa', '88809-110', 'Rua Vitor Meireles', '1120', NULL, 'Rio Maina', 'Criciúma', 'SC', 1);
SET @end_thiago = LAST_INSERT_ID();

-- Endereço para a cliente existente Alycia (id_usuario = 1) caso ainda não tenha
INSERT IGNORE INTO `endereco` (`fk_cliente`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`)
VALUES (1, 'Casa', '88805-120', 'Rua Gonçalves Ledo', '215', NULL, 'Pio Corrêa', 'Criciúma', 'SC', 1);
SET @end_alycia = (SELECT id FROM endereco WHERE fk_cliente = 1 LIMIT 1);


-- ---------------------------------------------------------------------
-- 2. RESOLUÇÃO DINÂMICA DOS PRODUTOS E VARIAÇÕES
-- (Funciona com ou sem inserts_produtos.sql executado anteriormente)
-- ---------------------------------------------------------------------

SET @p_life      = 12;
SET @p_casaq     = 13;
SET @p_regata    = 14;
SET @p_dani      = 15;
SET @p_b64       = COALESCE((SELECT id FROM produto WHERE nome = 'Blusa 64' LIMIT 1), 12);
SET @p_tule      = COALESCE((SELECT id FROM produto WHERE nome = 'Blusa By Power Tule' LIMIT 1), 14);
SET @p_crsport   = COALESCE((SELECT id FROM produto WHERE nome = 'Blusa CR Sport' LIMIT 1), 12);
SET @p_gofit     = COALESCE((SELECT id FROM produto WHERE nome = 'Blusa Go Fitness' LIMIT 1), 14);
SET @p_lovecicro = COALESCE((SELECT id FROM produto WHERE nome = 'Blusa Love Cicro' LIMIT 1), 12);
SET @p_premium   = COALESCE((SELECT id FROM produto WHERE nome = 'Blusa Premium' LIMIT 1), 12);
SET @p_calca     = COALESCE((SELECT id FROM produto WHERE nome = 'Calça Simples' LIMIT 1), 13);
SET @p_supergirl = COALESCE((SELECT id FROM produto WHERE nome = 'Camisa Super Girl' LIMIT 1), 14);
SET @p_jacard    = COALESCE((SELECT id FROM produto WHERE nome = 'Casaco Jacard Life' LIMIT 1), 13);
SET @p_ava       = COALESCE((SELECT id FROM produto WHERE nome = 'Conjunto Canelado de Calça Ava' LIMIT 1), 13);
SET @p_yzza      = COALESCE((SELECT id FROM produto WHERE nome = 'Conjunto Canelado de Short Yzza' LIMIT 1), 15);
SET @p_carol     = COALESCE((SELECT id FROM produto WHERE nome = 'Conjunto de Calça Carol' LIMIT 1), 13);
SET @p_confort   = COALESCE((SELECT id FROM produto WHERE nome = 'Conjunto de Short Confort' LIMIT 1), 15);
SET @p_jac_calca = COALESCE((SELECT id FROM produto WHERE nome = 'Conjunto Jacard de Calça Life' LIMIT 1), 13);
SET @p_corrida   = COALESCE((SELECT id FROM produto WHERE nome = 'Conjunto Short Duplo Corrida' LIMIT 1), 15);
SET @p_miami     = COALESCE((SELECT id FROM produto WHERE nome = 'Macaquinho Miami' LIMIT 1), 15);

SET @v_life      = (SELECT id FROM variacao_produto WHERE fk_produto = @p_life LIMIT 1);
SET @v_casaq     = (SELECT id FROM variacao_produto WHERE fk_produto = @p_casaq LIMIT 1);
SET @v_regata    = (SELECT id FROM variacao_produto WHERE fk_produto = @p_regata LIMIT 1);
SET @v_dani      = (SELECT id FROM variacao_produto WHERE fk_produto = @p_dani LIMIT 1);
SET @v_b64       = (SELECT id FROM variacao_produto WHERE fk_produto = @p_b64 LIMIT 1);
SET @v_tule      = (SELECT id FROM variacao_produto WHERE fk_produto = @p_tule LIMIT 1);
SET @v_crsport   = (SELECT id FROM variacao_produto WHERE fk_produto = @p_crsport LIMIT 1);
SET @v_gofit     = (SELECT id FROM variacao_produto WHERE fk_produto = @p_gofit LIMIT 1);
SET @v_lovecicro = (SELECT id FROM variacao_produto WHERE fk_produto = @p_lovecicro LIMIT 1);
SET @v_premium   = (SELECT id FROM variacao_produto WHERE fk_produto = @p_premium LIMIT 1);
SET @v_calca     = (SELECT id FROM variacao_produto WHERE fk_produto = @p_calca LIMIT 1);
SET @v_supergirl = (SELECT id FROM variacao_produto WHERE fk_produto = @p_supergirl LIMIT 1);
SET @v_jacard    = (SELECT id FROM variacao_produto WHERE fk_produto = @p_jacard LIMIT 1);
SET @v_ava       = (SELECT id FROM variacao_produto WHERE fk_produto = @p_ava LIMIT 1);
SET @v_yzza      = (SELECT id FROM variacao_produto WHERE fk_produto = @p_yzza LIMIT 1);
SET @v_carol     = (SELECT id FROM variacao_produto WHERE fk_produto = @p_carol LIMIT 1);
SET @v_confort   = (SELECT id FROM variacao_produto WHERE fk_produto = @p_confort LIMIT 1);
SET @v_jac_calca = (SELECT id FROM variacao_produto WHERE fk_produto = @p_jac_calca LIMIT 1);
SET @v_corrida   = (SELECT id FROM variacao_produto WHERE fk_produto = @p_corrida LIMIT 1);
SET @v_miami     = (SELECT id FROM variacao_produto WHERE fk_produto = @p_miami LIMIT 1);


-- ---------------------------------------------------------------------
-- 3. COMPRAS (18 PEDIDOS COM ITENS, PAGAMENTOS E MOVIMENTAÇÃO DE ESTOQUE)
-- ---------------------------------------------------------------------

-- PEDIDO 1: Mariana (Entregue) - Camiseta LIFE! + Conjunto Dani Short
SET @tot1 = (SELECT preco_base FROM produto WHERE id = @p_life) + (SELECT preco_base FROM produto WHERE id = @p_dani);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_mariana, @end_mariana, 'Rua Coronel Pedro Benedet, nº 310 - Apto 502 · Centro · Criciúma/SC · CEP 88801-100', 'pix', '2026-10-19 14:10:00', 'Entregue', @tot1);
SET @ped1 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped1, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id IN (@v_life, @v_dani);
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped1, 'pix', 'pago', @tot1, CONCAT('CICRO', LPAD(@ped1, 6, '0')), 1, '2026-10-19 14:10:00', '2026-10-19 14:12:00');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_life, 'saida', 1, 'venda', @ped1, '2026-10-19 14:12:00'), (@v_dani, 'saida', 1, 'venda', @ped1, '2026-10-19 14:12:00');

-- PEDIDO 2: Camila (Entregue) - Conjunto casaquinho + Conjunto regata face double
SET @tot2 = (SELECT preco_base FROM produto WHERE id = @p_casaq) + (SELECT preco_base FROM produto WHERE id = @p_regata);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_camila, @end_camila, 'Avenida Centenário, nº 1450 - Bloco B · Santa Bárbara · Criciúma/SC · CEP 88804-000', 'pix', '2026-10-21 16:40:00', 'Entregue', @tot2);
SET @ped2 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped2, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id IN (@v_casaq, @v_regata);
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped2, 'pix', 'pago', @tot2, CONCAT('CICRO', LPAD(@ped2, 6, '0')), 1, '2026-10-21 16:40:00', '2026-10-21 16:40:30');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_casaq, 'saida', 1, 'venda', @ped2, '2026-10-21 16:40:30'), (@v_regata, 'saida', 1, 'venda', @ped2, '2026-10-21 16:40:30');

-- PEDIDO 3: Beatriz (Entregue) - Conjunto Canelado de Calça Ava + Blusa 64
SET @tot3 = (SELECT preco_base FROM produto WHERE id = @p_ava) + (SELECT preco_base FROM produto WHERE id = @p_b64);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_beatriz, @end_beatriz, 'Rua Henrique Lage, nº 890 · Comerciário · Criciúma/SC · CEP 88815-200', 'pix', '2026-10-23 11:05:00', 'Entregue', @tot3);
SET @ped3 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped3, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id IN (@v_ava, @v_b64);
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped3, 'pix', 'pago', @tot3, CONCAT('CICRO', LPAD(@ped3, 6, '0')), 1, '2026-10-23 11:05:00', '2026-10-23 11:07:10');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_ava, 'saida', 1, 'venda', @ped3, '2026-10-23 11:07:10'), (@v_b64, 'saida', 1, 'venda', @ped3, '2026-10-23 11:07:10');

-- PEDIDO 4: Lucas (Entregue) - Casaco Jacard Life
SET @tot4 = (SELECT preco_base FROM produto WHERE id = @p_jacard);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_lucas, @end_lucas, 'Rua Joaquim Nabuco, nº 520 - Sala 04 · Michel · Criciúma/SC · CEP 88802-300', 'pix', '2026-10-26 19:15:00', 'Entregue', @tot4);
SET @ped4 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped4, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id = @v_jacard;
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped4, 'pix', 'pago', @tot4, CONCAT('CICRO', LPAD(@ped4, 6, '0')), 1, '2026-10-26 19:15:00', '2026-10-26 19:16:40');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_jacard, 'saida', 1, 'venda', @ped4, '2026-10-26 19:16:40');

-- PEDIDO 5: Fernanda (Entregue) - Conjunto Canelado de Short Yzza + Blusa By Power Tule
SET @tot5 = (SELECT preco_base FROM produto WHERE id = @p_yzza) + (SELECT preco_base FROM produto WHERE id = @p_tule);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_fernanda, @end_fernanda, 'Rua São José, nº 712 - Casa 2 · Próspera · Criciúma/SC · CEP 88806-000', 'pix', '2026-10-27 13:20:00', 'Entregue', @tot5);
SET @ped5 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped5, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id IN (@v_yzza, @v_tule);
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped5, 'pix', 'pago', @tot5, CONCAT('CICRO', LPAD(@ped5, 6, '0')), 1, '2026-10-27 13:20:00', '2026-10-27 13:20:45');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_yzza, 'saida', 1, 'venda', @ped5, '2026-10-27 13:20:45'), (@v_tule, 'saida', 1, 'venda', @ped5, '2026-10-27 13:20:45');

-- PEDIDO 6: Gabriela (Entregue) - Conjunto de Calça Carol + Blusa CR Sport
SET @tot6 = (SELECT preco_base FROM produto WHERE id = @p_carol) + (SELECT preco_base FROM produto WHERE id = @p_crsport);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_gabriela, @end_gabriela, 'Rua Anita Garibaldi, nº 198 · Operária Nova · Criciúma/SC · CEP 88811-400', 'pix', '2026-10-29 10:10:00', 'Entregue', @tot6);
SET @ped6 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped6, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id IN (@v_carol, @v_crsport);
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped6, 'pix', 'pago', @tot6, CONCAT('CICRO', LPAD(@ped6, 6, '0')), 1, '2026-10-29 10:10:00', '2026-10-29 10:11:30');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_carol, 'saida', 1, 'venda', @ped6, '2026-10-29 10:11:30'), (@v_crsport, 'saida', 1, 'venda', @ped6, '2026-10-29 10:11:30');

-- PEDIDO 7: Rafael (Entregue) - Blusa Go Fitness + Calça Simples
SET @tot7 = (SELECT preco_base FROM produto WHERE id = @p_gofit) + (SELECT preco_base FROM produto WHERE id = @p_calca);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_rafael, @end_rafael, 'Rua Marechal Deodoro, nº 405 - Apto 101 · Centro · Criciúma/SC · CEP 88803-210', 'pix', '2026-10-31 15:00:00', 'Entregue', @tot7);
SET @ped7 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped7, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id IN (@v_gofit, @v_calca);
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped7, 'pix', 'pago', @tot7, CONCAT('CICRO', LPAD(@ped7, 6, '0')), 1, '2026-10-31 15:00:00', '2026-10-31 15:00:25');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_gofit, 'saida', 1, 'venda', @ped7, '2026-10-31 15:00:25'), (@v_calca, 'saida', 1, 'venda', @ped7, '2026-10-31 15:00:25');

-- PEDIDO 8: Larissa (Entregue) - Conjunto de Short Confort + Camisa Super Girl
SET @tot8 = (SELECT preco_base FROM produto WHERE id = @p_confort) + (SELECT preco_base FROM produto WHERE id = @p_supergirl);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_larissa, @end_larissa, 'Rua Santo Antônio, nº 630 · Pinheirinho · Criciúma/SC · CEP 88813-150', 'pix', '2026-11-02 12:30:00', 'Entregue', @tot8);
SET @ped8 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped8, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id IN (@v_confort, @v_supergirl);
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped8, 'pix', 'pago', @tot8, CONCAT('CICRO', LPAD(@ped8, 6, '0')), 1, '2026-11-02 12:30:00', '2026-11-02 12:31:55');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_confort, 'saida', 1, 'venda', @ped8, '2026-11-02 12:31:55'), (@v_supergirl, 'saida', 1, 'venda', @ped8, '2026-11-02 12:31:55');

-- PEDIDO 9: Patrícia (Entregue) - Conjunto Jacard de Calça Life + Blusa Premium
SET @tot9 = (SELECT preco_base FROM produto WHERE id = @p_jac_calca) + (SELECT preco_base FROM produto WHERE id = @p_premium);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_patricia, @end_patricia, 'Rua Imigrante Zanatta, nº 95 - Apto 303 · Cruzeiro do Sul · Criciúma/SC · CEP 88807-320', 'pix', '2026-11-04 09:40:00', 'Entregue', @tot9);
SET @ped9 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped9, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id IN (@v_jac_calca, @v_premium);
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped9, 'pix', 'pago', @tot9, CONCAT('CICRO', LPAD(@ped9, 6, '0')), 1, '2026-11-04 09:40:00', '2026-11-04 09:42:10');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_jac_calca, 'saida', 1, 'venda', @ped9, '2026-11-04 09:42:10'), (@v_premium, 'saida', 1, 'venda', @ped9, '2026-11-04 09:42:10');

-- PEDIDO 10: Thiago (Entregue) - Macaquinho Miami
SET @tot10 = (SELECT preco_base FROM produto WHERE id = @p_miami);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_thiago, @end_thiago, 'Rua Vitor Meireles, nº 1120 · Rio Maina · Criciúma/SC · CEP 88809-110', 'pix', '2026-11-05 20:15:00', 'Entregue', @tot10);
SET @ped10 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped10, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id = @v_miami;
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped10, 'pix', 'pago', @tot10, CONCAT('CICRO', LPAD(@ped10, 6, '0')), 1, '2026-11-05 20:15:00', '2026-11-05 20:15:20');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_miami, 'saida', 1, 'venda', @ped10, '2026-11-05 20:15:20');

-- PEDIDO 11: Alycia Vitória (id = 1) (Entregue) - Conjunto Short Duplo Corrida
SET @tot11 = (SELECT preco_base FROM produto WHERE id = @p_corrida);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (1, @end_alycia, 'Rua Gonçalves Ledo, nº 215 · Pio Corrêa · Criciúma/SC · CEP 88805-120', 'pix', '2026-11-07 15:20:00', 'Entregue', @tot11);
SET @ped11 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped11, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id = @v_corrida;
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped11, 'pix', 'pago', @tot11, CONCAT('CICRO', LPAD(@ped11, 6, '0')), 1, '2026-11-07 15:20:00', '2026-11-07 15:20:45');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_corrida, 'saida', 1, 'venda', @ped11, '2026-11-07 15:20:45');

-- PEDIDO 12: Juliana Fabri (id = 7) (Entregue) - Blusa Love Cicro + Conjunto Dani Short
SET @tot12 = (SELECT preco_base FROM produto WHERE id = @p_lovecicro) + (SELECT preco_base FROM produto WHERE id = @p_dani);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (7, 2, 'Rua Afonso Milanese, nº 464 - Bloco 18 Apto 204 · São Defende · Criciúma/SC · CEP 88808-052', 'pix', '2026-11-09 10:00:00', 'Entregue', @tot12);
SET @ped12 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped12, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id IN (@v_lovecicro, @v_dani);
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped12, 'pix', 'pago', @tot12, CONCAT('CICRO', LPAD(@ped12, 6, '0')), 1, '2026-11-09 10:00:00', '2026-11-09 10:01:50');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_lovecicro, 'saida', 1, 'venda', @ped12, '2026-11-09 10:01:50'), (@v_dani, 'saida', 1, 'venda', @ped12, '2026-11-09 10:01:50');

-- PEDIDO 13: Mariana - 2ª compra (Entregue) - Macaquinho Miami
SET @tot13 = (SELECT preco_base FROM produto WHERE id = @p_miami);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_mariana, @end_mariana, 'Rua Coronel Pedro Benedet, nº 310 - Apto 502 · Centro · Criciúma/SC · CEP 88801-100', 'pix', '2026-11-11 17:45:00', 'Entregue', @tot13);
SET @ped13 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped13, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id = @v_miami;
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped13, 'pix', 'pago', @tot13, CONCAT('CICRO', LPAD(@ped13, 6, '0')), 1, '2026-11-11 17:45:00', '2026-11-11 17:46:12');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_miami, 'saida', 1, 'venda', @ped13, '2026-11-11 17:46:12');

-- PEDIDO 14: Camila - 2ª compra (Entregue) - Conjunto Canelado de Calça Ava
SET @tot14 = (SELECT preco_base FROM produto WHERE id = @p_ava);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_camila, @end_camila, 'Avenida Centenário, nº 1450 - Bloco B · Santa Bárbara · Criciúma/SC · CEP 88804-000', 'pix', '2026-11-13 14:15:00', 'Entregue', @tot14);
SET @ped14 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped14, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id = @v_ava;
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped14, 'pix', 'pago', @tot14, CONCAT('CICRO', LPAD(@ped14, 6, '0')), 1, '2026-11-13 14:15:00', '2026-11-13 14:16:05');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_ava, 'saida', 1, 'venda', @ped14, '2026-11-13 14:16:05');

-- PEDIDO 15: Fernanda - 2ª compra (Enviado) - Conjunto Short Duplo Corrida
SET @tot15 = (SELECT preco_base FROM produto WHERE id = @p_corrida);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_fernanda, @end_fernanda, 'Rua São José, nº 712 - Casa 2 · Próspera · Criciúma/SC · CEP 88806-000', 'pix', '2026-11-19 16:00:00', 'Enviado', @tot15);
SET @ped15 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped15, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id = @v_corrida;
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped15, 'pix', 'pago', @tot15, CONCAT('CICRO', LPAD(@ped15, 6, '0')), 1, '2026-11-19 16:00:00', '2026-11-19 16:01:22');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_corrida, 'saida', 1, 'venda', @ped15, '2026-11-19 16:01:22');

-- PEDIDO 16: Gabriela - 2ª compra (Enviado) - Camiseta LIFE! fitness
SET @tot16 = (SELECT preco_base FROM produto WHERE id = @p_life);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_gabriela, @end_gabriela, 'Rua Anita Garibaldi, nº 198 · Operária Nova · Criciúma/SC · CEP 88811-400', 'pix', '2026-11-21 11:30:00', 'Enviado', @tot16);
SET @ped16 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped16, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id = @v_life;
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped16, 'pix', 'pago', @tot16, CONCAT('CICRO', LPAD(@ped16, 6, '0')), 1, '2026-11-21 11:30:00', '2026-11-21 11:30:40');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_life, 'saida', 1, 'venda', @ped16, '2026-11-21 11:30:40');

-- PEDIDO 17: Larissa - 2ª compra (Pago) - Conjunto casaquinho fitness
SET @tot17 = (SELECT preco_base FROM produto WHERE id = @p_casaq);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_larissa, @end_larissa, 'Rua Santo Antônio, nº 630 · Pinheirinho · Criciúma/SC · CEP 88813-150', 'pix', '2026-11-22 18:10:00', 'Pago', @tot17);
SET @ped17 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped17, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id = @v_casaq;
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped17, 'pix', 'pago', @tot17, CONCAT('CICRO', LPAD(@ped17, 6, '0')), 1, '2026-11-22 18:10:00', '2026-11-22 18:12:00');
INSERT INTO `movimentacao_estoque` (`fk_variacao`, `tipo`, `quantidade`, `motivo`, `fk_pedido`, `data_movimento`) VALUES
(@v_casaq, 'saida', 1, 'venda', @ped17, '2026-11-22 18:12:00');

-- PEDIDO 18: Patrícia - 2ª compra (Pendente - aguardando pagamento) - Blusa Love Cicro
SET @tot18 = (SELECT preco_base FROM produto WHERE id = @p_lovecicro);
INSERT INTO `pedido` (`fk_cliente`, `fk_endereco`, `endereco_entrega`, `forma_pagamento`, `data_criacao`, `status`, `valor_total`)
VALUES (@u_patricia, @end_patricia, 'Rua Imigrante Zanatta, nº 95 - Apto 303 · Cruzeiro do Sul · Criciúma/SC · CEP 88807-320', 'pix', '2026-11-23 20:00:00', 'Pendente', @tot18);
SET @ped18 = LAST_INSERT_ID();
INSERT INTO `item_pedido` (`fk_pedido`, `fk_variacao`, `nome_produto`, `tamanho`, `cor`, `quantidade`, `preco_unitario`)
SELECT @ped18, v.id, p.nome, v.tamanho, v.cor, 1, p.preco_base FROM variacao_produto v JOIN produto p ON p.id = v.fk_produto WHERE v.id = @v_lovecicro;
INSERT INTO `pagamento` (`fk_pedido`, `metodo`, `status`, `valor`, `txid`, `simulado`, `data_criacao`, `data_pagamento`)
VALUES (@ped18, 'pix', 'aguardando', @tot18, CONCAT('CICRO', LPAD(@ped18, 6, '0')), 1, '2026-11-23 20:00:00', NULL);


-- ---------------------------------------------------------------------
-- 4. AVALIAÇÕES DE PRODUTOS (22 avaliações com IGNORE para segurança)
-- Respeita uq_avaliacao_cliente_produto (1 avaliação por cliente/produto)
-- ---------------------------------------------------------------------

INSERT IGNORE INTO `avaliacao` (`fk_produto`, `fk_cliente`, `fk_pedido`, `nota`, `comentario`, `data_avaliacao`) VALUES
(@p_life,      @u_mariana,  @ped1,  5, 'Tecido maravilhoso! A dupla camada não marca nada e o algodão é super confortável para treinar.', '2026-10-24 18:30:00'),
(@p_dani,      @u_mariana,  @ped1,  5, 'O Conjunto Dani Short veste super bem no corpo e realmente não fica transparente no agachamento.', '2026-10-24 18:32:10'),
(@p_miami,     @u_mariana,  @ped13, 5, 'Segunda compra na loja e continuo apaixonada! O Macaquinho Miami modela demais.', '2026-11-15 10:20:00'),
(@p_casaq,     @u_camila,   @ped2,  5, 'Conjunto perfeito para dias mais frescos! Acabamento impecável e entrega super rápida em Criciúma.', '2026-10-26 11:15:00'),
(@p_regata,    @u_camila,   @ped2,  4, 'Gostei bastante do caimento e da sustentação. A cor é linda pessoalmente, recomendo!', '2026-10-26 11:18:45'),
(@p_ava,       @u_camila,   @ped14, 5, 'Comprei o conjunto canelado e virou meu favorito da academia. Zero transparência!', '2026-11-17 14:05:00'),
(@p_ava,       @u_beatriz,  @ped3,  5, 'O tecido canelado tem uma compressão excelente e modela muito bem. Já quero de outras cores!', '2026-10-28 20:05:00'),
(@p_b64,       @u_beatriz,  @ped3,  4, 'Blusa muito estilosa e fresquinha, seca super rápido durante o treino de corrida.', '2026-10-28 20:08:30'),
(@p_jacard,    @u_lucas,    @ped4,  4, 'Comprei para dar de presente e a qualidade surpreendeu bastante. Costuras muito bem feitas.', '2026-10-30 14:40:00'),
(@p_yzza,      @u_fernanda, @ped5,  5, 'O short canelado não fica subindo na perna durante o exercício. Perfeito!', '2026-11-01 09:50:00'),
(@p_tule,      @u_fernanda, @ped5,  5, 'Os detalhes em tule dão um charme incrível na peça. Super leve e respirável.', '2026-11-01 09:53:15'),
(@p_carol,     @u_gabriela, @ped6,  5, 'Calça com cintura alta maravilhosa, não enrola no abdômen. Amei demais!', '2026-11-03 16:12:00'),
(@p_crsport,   @u_gabriela, @ped6,  4, 'Ótimo custo-benefício. Uso tanto na musculação quanto no dia a dia.', '2026-11-03 16:15:00'),
(@p_gofit,     @u_rafael,   @ped7,  5, 'Presenteei minha esposa e ela adorou o tecido e o corte da blusa.', '2026-11-04 19:30:00'),
(@p_calca,     @u_rafael,   @ped7,  4, 'Peça básica indispensável, tecido encorpado e muito resistente.', '2026-11-04 19:32:00'),
(@p_confort,   @u_larissa,  @ped8,  5, 'Faz jus ao nome! Extremamente confortável, parece que não está usando nada.', '2026-11-06 11:45:00'),
(@p_supergirl, @u_larissa,  @ped8,  5, 'Estampa linda que não desbota na lavagem. O tamanho M ficou perfeito em mim.', '2026-11-06 11:48:20'),
(@p_jac_calca, @u_patricia, @ped9,  5, 'O relevo do tecido jacard disfarça super bem e deixa o visual muito elegante.', '2026-11-08 17:22:00'),
(@p_premium,   @u_patricia, @ped9,  4, 'Acabamento de primeira linha, chegou super bem embalado.', '2026-11-08 17:25:00'),
(@p_miami,     @u_thiago,   @ped10, 5, 'Entrega super rápida e produto fiel às fotos do site. Recomendo a CICRO!', '2026-11-09 21:10:00'),
(@p_corrida,   1,           @ped11, 5, 'Amei o short duplo, dá muita segurança para correr e fazer exercícios funcionais. Nota 10!', '2026-11-11 16:50:00'),
(@p_lovecicro, 7,           @ped12, 5, 'Blusa super levinha, fresca e linda! O tamanho veio certinho conforme a tabela.', '2026-11-13 09:25:00');

COMMIT;
