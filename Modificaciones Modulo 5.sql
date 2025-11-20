-- Modulo 5

ALTER TABLE `citas`
ADD COLUMN `fecha_notificacion` TIMESTAMP NULL DEFAULT NULL AFTER `motivo_cancelacion`;

-- 1. Añade la columna para el "opt-out" del paciente
ALTER TABLE `usuarios`
ADD COLUMN `recibir_recordatorios` TINYINT(1) NOT NULL DEFAULT 1 AFTER `fecha_registro`;

-- 2. Añade la columna para marcar el recordatorio como enviado
ALTER TABLE `citas`
ADD COLUMN `recordatorio_enviado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fecha_notificacion`;