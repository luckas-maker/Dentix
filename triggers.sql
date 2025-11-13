

use novasoft;

DELIMITER $$

CREATE TRIGGER actualizar_estado_franja
AFTER INSERT ON Citas
FOR EACH ROW
BEGIN
    UPDATE franjasdisponibles
    SET estado = 'NoDisponible'
    WHERE id_franja = NEW.id_franja;
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER liberar_franja
AFTER UPDATE ON Citas
FOR EACH ROW
BEGIN
    -- Solo si la cita estaba Pendiente y ahora fue Rechazada
    IF OLD.estado_cita = 'Pendiente' AND NEW.estado_cita = 'Rechazada' THEN
        UPDATE franjasdisponibles
        SET estado = 'Disponible'
        WHERE id_franja = NEW.id_franja;
    END IF;
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER reservar_franja_pendiente_a_confirmada
AFTER UPDATE ON Citas
FOR EACH ROW
BEGIN
    -- Solo si la cita estaba Pendiente y ahora fue Confirmada
    IF OLD.estado_cita = 'Pendiente' AND NEW.estado_cita = 'Confirmada' THEN
        UPDATE franjasdisponibles
        SET estado = 'Reservada'
        WHERE id_franja = NEW.id_franja;
    END IF;
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER cita_cancelada
AFTER UPDATE ON Citas
FOR EACH ROW
BEGIN
    -- Si la cita fue cancelada desde Pendiente o Confirmada
    IF (OLD.estado_cita IN ('Pendiente', 'Confirmada')) AND NEW.estado_cita = 'Cancelada' THEN
        UPDATE franjasdisponibles
        SET estado = 'Disponible'
        WHERE id_franja = NEW.id_franja;
    END IF;
END $$

DELIMITER ;
