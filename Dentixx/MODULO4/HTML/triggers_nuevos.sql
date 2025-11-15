USE novasoft;

DELIMITER $$

CREATE TRIGGER actualizar_faltas_consecutivas
AFTER UPDATE ON Citas
FOR EACH ROW
BEGIN
    -- Si la cita cambió a "No Asistio"
    IF NEW.estado_cita = 'No Asistio' AND OLD.estado_cita != 'No Asistio' THEN
        UPDATE usuarios 
        SET faltas_consecutivas = faltas_consecutivas + 1 
        WHERE id_usuario = NEW.id_paciente;
    END IF;
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER actualizar_ultima_cita
AFTER UPDATE ON Citas
FOR EACH ROW
BEGIN
    -- Si la cita cambió a "Asistida"
    IF NEW.estado_cita = 'Asistida' AND OLD.estado_cita != 'Asistida' THEN
        UPDATE usuarios 
        SET 
            ultima_cita_fecha = (SELECT fecha FROM franjasdisponibles WHERE id_franja = NEW.id_franja),
            ultima_cita_motivo = NEW.tipo_servicio
        WHERE id_usuario = NEW.id_paciente;
    END IF;
END $$

DELIMITER ;