-- Migration: Tornar tenant_id nullable na tabela checkout_tokens
-- Criado em: 09/04/2026 13:44
-- Sequencial: 10

-- Em ambientes de desenvolvimento/teste o tenant_id pode não estar disponível na sessão.
-- Ao tornar nullable, o checkout funciona tanto em produção (com tenant) quanto em desenvolvimento.

ALTER TABLE `checkout_tokens`
    MODIFY COLUMN `tenant_id` INT NULL DEFAULT NULL;

-- Remover FK restritiva e recriá-la com ON DELETE SET NULL
ALTER TABLE `checkout_tokens`
    DROP FOREIGN KEY `fk_checkout_tokens_tenant`;

ALTER TABLE `checkout_tokens`
    ADD CONSTRAINT `fk_checkout_tokens_tenant`
    FOREIGN KEY (`tenant_id`) REFERENCES `akti_master`.`tenant_clients`(`id`)
    ON DELETE SET NULL;
