-- CICRO - cadastro de produtos a partir da pasta "imagens"
-- ATENCAO: precos, estoque (20 por tamanho) e descricoes abaixo sao VALORES PROVISORIOS; os tamanhos de cada produto foram sorteados.
-- Ajuste antes de rodar (ou depois, pelo painel). Pode ser executado no phpMyAdmin.
-- As imagens precisam estar em admin-pages/uploads/ com os nomes usados aqui
-- (rode renomear_imagens.ps1 depois de extrair o .rar).

START TRANSACTION;

-- Categoria nova para a Calca (as demais ja existem)
INSERT INTO categoria (nome, slug, ordem_exibicao, status)
SELECT 'Calças', 'calcas', 6, 1
WHERE NOT EXISTS (SELECT 1 FROM categoria WHERE slug = 'calcas');

-- Blusa 64 (11 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Blusa 64', 'Blusa 64 - peça da coleção CICRO.', 49.90, (SELECT id FROM categoria WHERE slug = 'camisas'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-PP-', SUBSTRING(MD5(RAND()), 1, 4)), 'PP', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-M-', SUBSTRING(MD5(RAND()), 1, 4)), 'M', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_blusa-64_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_blusa-64_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_blusa-64_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_blusa-64_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_blusa-64_05.jpg', 4, 0),
(@p, 'admin-pages/uploads/prod_blusa-64_06.jpg', 5, 0),
(@p, 'admin-pages/uploads/prod_blusa-64_07.jpg', 6, 0),
(@p, 'admin-pages/uploads/prod_blusa-64_08.jpg', 7, 0),
(@p, 'admin-pages/uploads/prod_blusa-64_09.jpg', 8, 0),
(@p, 'admin-pages/uploads/prod_blusa-64_10.jpg', 9, 0),
(@p, 'admin-pages/uploads/prod_blusa-64_11.jpg', 10, 0);

-- Blusa By Power Tule (6 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Blusa By Power Tule', 'Blusa By Power Tule - peça da coleção CICRO.', 49.90, (SELECT id FROM categoria WHERE slug = 'camisas'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_blusa-by-power-tule_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_blusa-by-power-tule_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_blusa-by-power-tule_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_blusa-by-power-tule_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_blusa-by-power-tule_05.jpg', 4, 0),
(@p, 'admin-pages/uploads/prod_blusa-by-power-tule_06.jpg', 5, 0);

-- Blusa CR Sport (7 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Blusa CR Sport', 'Blusa CR Sport - peça da coleção CICRO.', 49.90, (SELECT id FROM categoria WHERE slug = 'camisas'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-P-', SUBSTRING(MD5(RAND()), 1, 4)), 'P', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-M-', SUBSTRING(MD5(RAND()), 1, 4)), 'M', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_blusa-cr-sport_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_blusa-cr-sport_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_blusa-cr-sport_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_blusa-cr-sport_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_blusa-cr-sport_05.jpg', 4, 0),
(@p, 'admin-pages/uploads/prod_blusa-cr-sport_06.jpg', 5, 0),
(@p, 'admin-pages/uploads/prod_blusa-cr-sport_07.jpg', 6, 0);

-- Blusa Go Fitness (1 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Blusa Go Fitness', 'Blusa Go Fitness - peça da coleção CICRO.', 49.90, (SELECT id FROM categoria WHERE slug = 'camisas'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-PP-', SUBSTRING(MD5(RAND()), 1, 4)), 'PP', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_blusa-go-fitness_01.jpg', 0, 1);

-- Blusa Love Cicro (12 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Blusa Love Cicro', 'Blusa Love Cicro - peça da coleção CICRO.', 49.90, (SELECT id FROM categoria WHERE slug = 'camisas'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-PP-', SUBSTRING(MD5(RAND()), 1, 4)), 'PP', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-M-', SUBSTRING(MD5(RAND()), 1, 4)), 'M', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_05.jpg', 4, 0),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_06.jpg', 5, 0),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_07.jpg', 6, 0),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_08.jpg', 7, 0),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_09.jpg', 8, 0),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_10.jpg', 9, 0),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_11.jpg', 10, 0),
(@p, 'admin-pages/uploads/prod_blusa-love-cicro_12.jpg', 11, 0);

-- Blusa Premium (9 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Blusa Premium', 'Blusa Premium - peça da coleção CICRO.', 49.90, (SELECT id FROM categoria WHERE slug = 'camisas'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-PP-', SUBSTRING(MD5(RAND()), 1, 4)), 'PP', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-M-', SUBSTRING(MD5(RAND()), 1, 4)), 'M', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_blusa-premium_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_blusa-premium_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_blusa-premium_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_blusa-premium_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_blusa-premium_05.jpg', 4, 0),
(@p, 'admin-pages/uploads/prod_blusa-premium_06.jpg', 5, 0),
(@p, 'admin-pages/uploads/prod_blusa-premium_07.jpg', 6, 0),
(@p, 'admin-pages/uploads/prod_blusa-premium_08.jpg', 7, 0),
(@p, 'admin-pages/uploads/prod_blusa-premium_09.jpg', 8, 0);

-- Calça Simples (4 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Calça Simples', 'Calça Simples - peça da coleção CICRO.', 69.90, (SELECT id FROM categoria WHERE slug = 'calcas'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-P-', SUBSTRING(MD5(RAND()), 1, 4)), 'P', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_calca-simples_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_calca-simples_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_calca-simples_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_calca-simples_04.jpg', 3, 0);

-- Camisa Super Girl (11 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Camisa Super Girl', 'Camisa Super Girl - peça da coleção CICRO.', 49.90, (SELECT id FROM categoria WHERE slug = 'camisas'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-M-', SUBSTRING(MD5(RAND()), 1, 4)), 'M', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_camisa-super-girl_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_camisa-super-girl_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_camisa-super-girl_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_camisa-super-girl_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_camisa-super-girl_05.jpg', 4, 0),
(@p, 'admin-pages/uploads/prod_camisa-super-girl_06.jpg', 5, 0),
(@p, 'admin-pages/uploads/prod_camisa-super-girl_07.jpg', 6, 0),
(@p, 'admin-pages/uploads/prod_camisa-super-girl_08.jpg', 7, 0),
(@p, 'admin-pages/uploads/prod_camisa-super-girl_09.jpg', 8, 0),
(@p, 'admin-pages/uploads/prod_camisa-super-girl_10.jpg', 9, 0),
(@p, 'admin-pages/uploads/prod_camisa-super-girl_11.jpg', 10, 0);

-- Casaco Jacard Life (6 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Casaco Jacard Life', 'Casaco Jacard Life - peça da coleção CICRO.', 89.90, (SELECT id FROM categoria WHERE slug = 'camisas'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-PP-', SUBSTRING(MD5(RAND()), 1, 4)), 'PP', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-P-', SUBSTRING(MD5(RAND()), 1, 4)), 'P', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-M-', SUBSTRING(MD5(RAND()), 1, 4)), 'M', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_casaco-jacard-life_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_casaco-jacard-life_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_casaco-jacard-life_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_casaco-jacard-life_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_casaco-jacard-life_05.jpg', 4, 0),
(@p, 'admin-pages/uploads/prod_casaco-jacard-life_06.jpg', 5, 0);

-- Conjunto Canelado de Calça Ava (5 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Conjunto Canelado de Calça Ava', 'Conjunto Canelado de Calça Ava - peça da coleção CICRO.', 79.90, (SELECT id FROM categoria WHERE slug = 'conjuntos'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-P-', SUBSTRING(MD5(RAND()), 1, 4)), 'P', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-M-', SUBSTRING(MD5(RAND()), 1, 4)), 'M', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_conjunto-canelado-de-calca-ava_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_conjunto-canelado-de-calca-ava_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_conjunto-canelado-de-calca-ava_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_conjunto-canelado-de-calca-ava_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_conjunto-canelado-de-calca-ava_05.jpg', 4, 0);

-- Conjunto Canelado de Short Yzza (5 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Conjunto Canelado de Short Yzza', 'Conjunto Canelado de Short Yzza - peça da coleção CICRO.', 79.90, (SELECT id FROM categoria WHERE slug = 'conjuntos'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-PP-', SUBSTRING(MD5(RAND()), 1, 4)), 'PP', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-P-', SUBSTRING(MD5(RAND()), 1, 4)), 'P', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-M-', SUBSTRING(MD5(RAND()), 1, 4)), 'M', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_conjunto-canelado-de-short-yzza_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_conjunto-canelado-de-short-yzza_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_conjunto-canelado-de-short-yzza_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_conjunto-canelado-de-short-yzza_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_conjunto-canelado-de-short-yzza_05.jpg', 4, 0);

-- Conjunto de Calça Carol (2 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Conjunto de Calça Carol', 'Conjunto de Calça Carol - peça da coleção CICRO.', 79.90, (SELECT id FROM categoria WHERE slug = 'conjuntos'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-PP-', SUBSTRING(MD5(RAND()), 1, 4)), 'PP', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_conjunto-de-calca-carol_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_conjunto-de-calca-carol_02.jpg', 1, 0);

-- Conjunto de Short Confort (3 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Conjunto de Short Confort', 'Conjunto de Short Confort - peça da coleção CICRO.', 79.90, (SELECT id FROM categoria WHERE slug = 'conjuntos'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-PP-', SUBSTRING(MD5(RAND()), 1, 4)), 'PP', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_conjunto-de-short-confort_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_conjunto-de-short-confort_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_conjunto-de-short-confort_03.jpg', 2, 0);

-- Conjunto Jacard de Calça Life (7 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Conjunto Jacard de Calça Life', 'Conjunto Jacard de Calça Life - peça da coleção CICRO.', 79.90, (SELECT id FROM categoria WHERE slug = 'conjuntos'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-P-', SUBSTRING(MD5(RAND()), 1, 4)), 'P', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_05.jpg', 4, 0),
(@p, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_06.jpg', 5, 0),
(@p, 'admin-pages/uploads/prod_conjunto-jacard-de-calca-life_07.jpg', 6, 0);

-- Conjunto Short Duplo Corrida (8 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Conjunto Short Duplo Corrida', 'Conjunto Short Duplo Corrida - peça da coleção CICRO.', 79.90, (SELECT id FROM categoria WHERE slug = 'conjuntos'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-P-', SUBSTRING(MD5(RAND()), 1, 4)), 'P', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-M-', SUBSTRING(MD5(RAND()), 1, 4)), 'M', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_01.jpg', 0, 1),
(@p, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_02.jpg', 1, 0),
(@p, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_03.jpg', 2, 0),
(@p, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_04.jpg', 3, 0),
(@p, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_05.jpg', 4, 0),
(@p, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_06.jpg', 5, 0),
(@p, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_07.jpg', 6, 0),
(@p, 'admin-pages/uploads/prod_conjunto-short-duplo-corrida_08.jpg', 7, 0);

-- Macaquinho Miami (1 foto(s))
INSERT INTO produto (nome, descricao, preco_base, fk_categoria, status)
VALUES ('Macaquinho Miami', 'Macaquinho Miami - peça da coleção CICRO.', 79.90, (SELECT id FROM categoria WHERE slug = 'conjuntos'), 1);
SET @p = LAST_INSERT_ID();
INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES
(@p, CONCAT('PROD-', @p, '-PP-', SUBSTRING(MD5(RAND()), 1, 4)), 'PP', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-M-', SUBSTRING(MD5(RAND()), 1, 4)), 'M', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-G-', SUBSTRING(MD5(RAND()), 1, 4)), 'G', 'Padrão', 20),
(@p, CONCAT('PROD-', @p, '-GG-', SUBSTRING(MD5(RAND()), 1, 4)), 'GG', 'Padrão', 20);
INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa) VALUES
(@p, 'admin-pages/uploads/prod_macaquinho-miami_01.jpg', 0, 1);

COMMIT;
