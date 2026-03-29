-- Adiciona coluna must_change_password para forçar troca de senha temporária
ALTER TABLE `customer_portal_access`
    ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Indica que a senha é temporária e deve ser alterada no próximo login'
    AFTER `locked_until`;
