-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: novasoft
-- ------------------------------------------------------
-- Server version	9.5.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;



--
-- Table structure for table `citas`
--

DROP TABLE IF EXISTS `citas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `citas` (
  `id_cita` int NOT NULL AUTO_INCREMENT,
  `id_paciente` int NOT NULL,
  `id_franja` int NOT NULL,
  `tipo_servicio` varchar(100) NOT NULL,
  `estado_cita` enum('Pendiente','Confirmada','Rechazada','Cancelada','Asistida','No Asistio') NOT NULL DEFAULT 'Pendiente',
  `motivo_cancelacion` text,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cita`),
  KEY `id_paciente` (`id_paciente`),
  KEY `id_franja` (`id_franja`),
  CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`id_franja`) REFERENCES `franjasdisponibles` (`id_franja`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `citas`
--

LOCK TABLES `citas` WRITE;
/*!40000 ALTER TABLE `citas` DISABLE KEYS */;
/*!40000 ALTER TABLE `citas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `franjasdisponibles`
--

DROP TABLE IF EXISTS `franjasdisponibles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `franjasdisponibles` (
  `id_franja` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `estado` enum('Disponible','NoDisponible','Reservada') NOT NULL DEFAULT 'Disponible',
  PRIMARY KEY (`id_franja`),
  UNIQUE KEY `uq_fecha_hora` (`fecha`,`hora_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `franjasdisponibles`
--

LOCK TABLES `franjasdisponibles` WRITE;
/*!40000 ALTER TABLE `franjasdisponibles` DISABLE KEYS */;
/*!40000 ALTER TABLE `franjasdisponibles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tokensvalidacion`
--

DROP TABLE IF EXISTS `tokensvalidacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tokensvalidacion` (
  `id_token` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `tipo` enum('ValidacionCorreo','RecuperacionPass') NOT NULL,
  `fecha_expiracion` datetime NOT NULL,
  PRIMARY KEY (`id_token`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `tokensvalidacion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tokensvalidacion`
--

LOCK TABLES `tokensvalidacion` WRITE;
/*!40000 ALTER TABLE `tokensvalidacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `tokensvalidacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `codigo_paciente` varchar(20) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `telefono` varchar(10) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `rol` enum('Paciente','Odontologo') NOT NULL DEFAULT 'Paciente',
  `estado_cuenta` enum('Activo','Bloqueado','Pendiente') NOT NULL DEFAULT 'Pendiente',
  `faltas_consecutivas` tinyint NOT NULL DEFAULT '0',
  `ultima_cita_fecha` date DEFAULT NULL,
  `ultima_cita_motivo` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  UNIQUE KEY `codigo_paciente` (`codigo_paciente`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (6,'CLTE001','Jose Alfredo','Portillo Lopez','joseportillo.pez@gmail.com','$2y$10$tFFiqhDBLbWAsIvlkiNGyu2/XOE/dhG4Z.eX4moEHrFSEk7ME7Te2','7541044732','uploads/perfiles/perfil_6_1762558054.jpg','Paciente','Activo',0,NULL,NULL,'2025-11-07 22:13:57'),(7,'CLTE002','Sheyla','Varela Arcos','sheyvar@gmail.com','$2y$10$2fgZ.fOymSlV5VzDQD5Kq.PSAvPdPt7RnqeZSe0t39fh4KwRUedMy','7541041020','uploads/perfiles/perfil_7_1762558494.png','Odontologo','Activo',0,NULL,NULL,'2025-11-07 23:31:18');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

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

-- Modulo 5

ALTER TABLE `citas`
ADD COLUMN `fecha_notificacion` TIMESTAMP NULL DEFAULT NULL AFTER `motivo_cancelacion`;

-- 1. Añade la columna para el "opt-out" del paciente
ALTER TABLE `usuarios`
ADD COLUMN `recibir_recordatorios` TINYINT(1) NOT NULL DEFAULT 1 AFTER `fecha_registro`;

-- 2. Añade la columna para marcar el recordatorio como enviado
ALTER TABLE `citas`
ADD COLUMN `recordatorio_enviado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fecha_notificacion`;