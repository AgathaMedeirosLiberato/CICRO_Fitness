-- =====================================================================
-- CICRO - MIGRAÇÃO v9 -> v12 (junta as migrações v10, v11 e v12)
--   v10: cor de cada foto do produto
--   v11: CPF criptografado
--   v12: novo status de pedido "Em Preparação"
--        (fluxo: Pendente > Pago > Em Preparação > Enviado > Entregue; Cancelado à parte)
--
-- 1) Faça um backup do banco.
-- 2) phpMyAdmin > banco cicro > aba SQL > rode este arquivo.
-- 3) Depois, rode no terminal (necessário por causa da v11):
--      C:\xampp\php\php.exe ferramentas\cifrar_cpf.php
-- Compatível com MariaDB 10.4 (XAMPP). Não apaga dados. Pode ser rodada mais de uma vez.
-- =====================================================================
SET NAMES utf8mb4;
USE `cicro`;

-- ---------------------------------------------------------------------
-- v10: cor de cada foto do produto
-- ---------------------------------------------------------------------
ALTER TABLE `imagem_produto`
  ADD COLUMN IF NOT EXISTS `cor` varchar(30) DEFAULT NULL;

-- ---------------------------------------------------------------------
-- v11: CPF criptografado
-- ---------------------------------------------------------------------
ALTER TABLE `cliente`
  MODIFY `cpf` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cpf_hash` char(64) DEFAULT NULL;

-- o "um CPF = uma conta" passa a ser garantido pela coluna cpf_hash
ALTER TABLE `cliente` DROP INDEX IF EXISTS `cpf`;
ALTER TABLE `cliente` ADD UNIQUE KEY IF NOT EXISTS `cpf_hash` (`cpf_hash`);

ALTER TABLE `solicitacao_cpf` MODIFY `cpf_atual` varchar(255) DEFAULT NULL;

-- ---------------------------------------------------------------------
-- v12: status de pedido "Em Preparação"
-- ---------------------------------------------------------------------
ALTER TABLE `pedido` DROP CONSTRAINT IF EXISTS `chk_pedido_status`;
ALTER TABLE `pedido`
  ADD CONSTRAINT `chk_pedido_status`
  CHECK (`status` in ('Pendente','Pago','Em Preparação','Enviado','Entregue','Cancelado'));
