-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.3 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para gym_system
CREATE DATABASE IF NOT EXISTS `gym_system` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `gym_system`;

-- Volcando estructura para tabla gym_system.asistencias
CREATE TABLE IF NOT EXISTS `asistencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int NOT NULL,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.asistencias: ~10 rows (aproximadamente)
DELETE FROM `asistencias`;
INSERT INTO `asistencias` (`id`, `socio_id`, `fecha_hora`) VALUES
	(1, 2, '2026-03-11 22:24:00'),
	(2, 4, '2026-03-15 21:58:00'),
	(3, 3, '2026-03-10 12:28:00'),
	(4, 6, '2026-03-12 07:55:00'),
	(5, 2, '2026-03-15 22:25:00'),
	(6, 10, '2026-03-15 07:13:00'),
	(7, 10, '2026-03-13 11:30:00'),
	(8, 5, '2026-03-12 22:09:00'),
	(9, 5, '2026-03-10 15:49:00'),
	(10, 3, '2026-03-15 08:51:00');

-- Volcando estructura para tabla gym_system.cajas
CREATE TABLE IF NOT EXISTS `cajas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `monto_inicial` decimal(10,2) NOT NULL,
  `monto_final` decimal(10,2) DEFAULT '0.00',
  `total_ventas` decimal(10,2) DEFAULT '0.00',
  `total_gastos` decimal(10,2) DEFAULT '0.00',
  `diferencia` decimal(10,2) DEFAULT '0.00',
  `fecha_apertura` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre` datetime DEFAULT NULL,
  `estado` enum('abierta','cerrada') DEFAULT 'abierta',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `cajas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.cajas: ~0 rows (aproximadamente)
DELETE FROM `cajas`;
INSERT INTO `cajas` (`id`, `usuario_id`, `monto_inicial`, `monto_final`, `total_ventas`, `total_gastos`, `diferencia`, `fecha_apertura`, `fecha_cierre`, `estado`) VALUES
	(1, 1, 100.00, 100.00, 49.00, 0.00, 0.00, '2026-03-15 08:41:55', NULL, 'abierta');

-- Volcando estructura para tabla gym_system.categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.categorias: ~3 rows (aproximadamente)
DELETE FROM `categorias`;
INSERT INTO `categorias` (`id`, `nombre`, `estado`) VALUES
	(1, 'Bebidas', 'activo'),
	(2, 'Suplementos', 'activo'),
	(3, 'Accesorios', 'activo');

-- Volcando estructura para tabla gym_system.configuracion
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int NOT NULL,
  `nombre_sistema` varchar(100) DEFAULT NULL,
  `ruc` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `moneda` varchar(10) DEFAULT '$',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.configuracion: ~0 rows (aproximadamente)
DELETE FROM `configuracion`;
INSERT INTO `configuracion` (`id`, `nombre_sistema`, `ruc`, `direccion`, `telefono`, `email`, `logo`, `moneda`) VALUES
	(1, 'Gimnasio Xtremo', '20600000001', 'Av. Principal 123, Lima', '987654321', 'admin@gym.com', 'logo_empresa.png', 'S/');

-- Volcando estructura para tabla gym_system.detalle_ventas
CREATE TABLE IF NOT EXISTS `detalle_ventas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venta_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.detalle_ventas: ~11 rows (aproximadamente)
DELETE FROM `detalle_ventas`;
INSERT INTO `detalle_ventas` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
	(1, 1, 5, 1, 69.00, 69.00),
	(2, 2, 9, 2, 54.00, 108.00),
	(3, 3, 3, 3, 68.00, 204.00),
	(4, 4, 10, 3, 29.00, 87.00),
	(5, 5, 4, 2, 57.00, 114.00),
	(6, 6, 8, 3, 52.00, 156.00),
	(7, 7, 5, 3, 69.00, 207.00),
	(8, 8, 1, 1, 49.00, 49.00),
	(9, 9, 7, 3, 34.00, 102.00),
	(10, 10, 2, 2, 52.00, 104.00),
	(11, 11, 1, 1, 49.00, 49.00);

-- Volcando estructura para tabla gym_system.gastos
CREATE TABLE IF NOT EXISTS `gastos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('creado','anulado') DEFAULT 'creado',
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.gastos: ~10 rows (aproximadamente)
DELETE FROM `gastos`;
INSERT INTO `gastos` (`id`, `descripcion`, `monto`, `fecha`, `estado`, `motivo_anulacion`) VALUES
	(1, 'Gasto de prueba 1', 482.00, '2026-02-25', 'creado', NULL),
	(2, 'Gasto de prueba 2', 186.00, '2026-02-16', 'creado', NULL),
	(3, 'Gasto de prueba 3', 379.00, '2026-02-15', 'creado', NULL),
	(4, 'Gasto de prueba 4', 369.00, '2026-02-24', 'creado', NULL),
	(5, 'Gasto de prueba 5', 357.00, '2026-02-26', 'creado', NULL),
	(6, 'Gasto de prueba 6', 285.00, '2026-02-18', 'creado', NULL),
	(7, 'Gasto de prueba 7', 100.00, '2026-03-11', 'creado', NULL),
	(8, 'Gasto de prueba 8', 500.00, '2026-02-26', 'creado', NULL),
	(9, 'Gasto de prueba 9', 196.00, '2026-03-07', 'anulado', 'prueba 1'),
	(10, 'Gasto de prueba 10', 488.00, '2026-02-21', 'creado', NULL);

-- Volcando estructura para tabla gym_system.medidas
CREATE TABLE IF NOT EXISTS `medidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int NOT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `grasa` decimal(5,2) DEFAULT NULL,
  `cintura` decimal(5,2) DEFAULT NULL,
  `brazo` decimal(5,2) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `medidas_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.medidas: ~0 rows (aproximadamente)
DELETE FROM `medidas`;

-- Volcando estructura para tabla gym_system.planes
CREATE TABLE IF NOT EXISTS `planes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `duracion_dias` int NOT NULL,
  `descripcion` text,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.planes: ~10 rows (aproximadamente)
DELETE FROM `planes`;
INSERT INTO `planes` (`id`, `nombre`, `precio`, `duracion_dias`, `descripcion`, `estado`) VALUES
	(1, 'Plan Dummy 1', 194.00, 311, 'Descripción del plan 1', 'activo'),
	(2, 'Plan Dummy 2', 162.00, 37, 'Descripción del plan 2', 'activo'),
	(3, 'Plan Dummy 3', 109.00, 46, 'Descripción del plan 3', 'activo'),
	(4, 'Plan Dummy 4', 256.00, 195, 'Descripción del plan 4', 'activo'),
	(5, 'Plan Dummy 5', 77.00, 128, 'Descripción del plan 5', 'activo'),
	(6, 'Plan Dummy 6', 148.00, 324, 'Descripción del plan 6', 'activo'),
	(7, 'Plan Dummy 7', 122.00, 44, 'Descripción del plan 7', 'activo'),
	(8, 'Plan Dummy 8', 203.00, 127, 'Descripción del plan 8', 'activo'),
	(9, 'Plan Dummy 9', 224.00, 283, 'Descripción del plan 9', 'activo'),
	(10, 'Plan Dummy 10', 231.00, 219, 'Descripción del plan 10', 'activo');

-- Volcando estructura para tabla gym_system.productos
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria_id` int NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock` int DEFAULT '0',
  `foto` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.productos: ~10 rows (aproximadamente)
DELETE FROM `productos`;
INSERT INTO `productos` (`id`, `categoria_id`, `codigo`, `nombre`, `precio_compra`, `precio_venta`, `stock`, `foto`, `estado`) VALUES
	(1, 1, 'PROD-001', 'Producto Prueba 1', 26.00, 49.00, 20, NULL, 'activo'),
	(2, 1, 'PROD-002', 'Producto Prueba 2', 28.00, 52.00, 37, NULL, 'activo'),
	(3, 1, 'PROD-003', 'Producto Prueba 3', 45.00, 68.00, 35, NULL, 'activo'),
	(4, 2, 'PROD-004', 'Producto Prueba 4', 39.00, 57.00, 86, NULL, 'activo'),
	(5, 1, 'PROD-005', 'Producto Prueba 5', 48.00, 69.00, 28, NULL, 'activo'),
	(6, 2, 'PROD-006', 'Producto Prueba 6', 15.00, 40.00, 96, NULL, 'activo'),
	(7, 3, 'PROD-007', 'Producto Prueba 7', 17.00, 34.00, 15, NULL, 'activo'),
	(8, 1, 'PROD-008', 'Producto Prueba 8', 26.00, 52.00, 44, NULL, 'activo'),
	(9, 3, 'PROD-009', 'Producto Prueba 9', 30.00, 54.00, 84, NULL, 'activo'),
	(10, 1, 'PROD-0010', 'Producto Prueba 10', 12.00, 29.00, 71, NULL, 'activo');

-- Volcando estructura para tabla gym_system.rutinas
CREATE TABLE IF NOT EXISTS `rutinas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int NOT NULL,
  `dia1` text,
  `dia2` text,
  `dia3` text,
  `dia4` text,
  `dia5` text,
  `dia6` text,
  `observaciones` text,
  `fecha_asignacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `rutinas_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.rutinas: ~0 rows (aproximadamente)
DELETE FROM `rutinas`;

-- Volcando estructura para tabla gym_system.socios
CREATE TABLE IF NOT EXISTS `socios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `whatsapp_api_key` varchar(50) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo','pendiente') DEFAULT 'activo',
  `foto` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dni` (`dni`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.socios: ~10 rows (aproximadamente)
DELETE FROM `socios`;
INSERT INTO `socios` (`id`, `nombre`, `dni`, `email`, `telefono`, `whatsapp_api_key`, `fecha_registro`, `estado`, `foto`) VALUES
	(1, 'Socio Prueba 1', '88880001', 'socio1@test.com', '999888771', NULL, '2026-03-15 08:41:54', 'activo', NULL),
	(2, 'Socio Prueba 2', '88880002', 'socio2@test.com', '999888772', NULL, '2026-03-15 08:41:54', 'activo', NULL),
	(3, 'Socio Prueba 3', '88880003', 'socio3@test.com', '999888773', NULL, '2026-03-15 08:41:54', 'activo', NULL),
	(4, 'Socio Prueba 4', '88880004', 'socio4@test.com', '999888774', NULL, '2026-03-15 08:41:54', 'activo', NULL),
	(5, 'Socio Prueba 5', '88880005', 'socio5@test.com', '999888775', NULL, '2026-03-15 08:41:54', 'activo', NULL),
	(6, 'Socio Prueba 6', '88880006', 'socio6@test.com', '999888776', NULL, '2026-03-15 08:41:54', 'activo', NULL),
	(7, 'Socio Prueba 7', '88880007', 'socio7@test.com', '999888777', NULL, '2026-03-15 08:41:54', 'activo', NULL),
	(8, 'Socio Prueba 8', '88880008', 'socio8@test.com', '999888778', NULL, '2026-03-15 08:41:54', 'activo', NULL),
	(9, 'Socio Prueba 9', '88880009', 'socio9@test.com', '999888779', NULL, '2026-03-15 08:41:54', 'activo', NULL),
	(10, 'Socio Prueba 10', '88880010', 'socio10@test.com', '9998887710', NULL, '2026-03-15 08:41:54', 'activo', NULL);

-- Volcando estructura para tabla gym_system.suscripciones
CREATE TABLE IF NOT EXISTS `suscripciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int DEFAULT NULL,
  `plan_id` int DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('activa','vencida') DEFAULT 'activa',
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `suscripciones_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suscripciones_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.suscripciones: ~10 rows (aproximadamente)
DELETE FROM `suscripciones`;
INSERT INTO `suscripciones` (`id`, `socio_id`, `plan_id`, `fecha_inicio`, `fecha_fin`, `estado`) VALUES
	(1, 1, 8, '2026-01-16', '2026-02-15', 'vencida'),
	(2, 2, 4, '2026-02-08', '2026-03-10', 'vencida'),
	(3, 3, 1, '2026-03-05', '2026-04-04', 'activa'),
	(4, 4, 3, '2026-02-15', '2026-03-17', 'activa'),
	(5, 5, 1, '2026-02-17', '2026-03-19', 'activa'),
	(6, 6, 10, '2026-03-09', '2026-04-08', 'activa'),
	(7, 7, 2, '2026-02-10', '2026-03-12', 'vencida'),
	(8, 8, 10, '2026-02-23', '2026-03-25', 'activa'),
	(9, 9, 7, '2026-01-26', '2026-02-25', 'vencida'),
	(10, 10, 1, '2026-02-28', '2026-03-30', 'activa');

-- Volcando estructura para tabla gym_system.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `rol` enum('admin','recepcionista','entrenador') DEFAULT 'recepcionista',
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.usuarios: ~3 rows (aproximadamente)
DELETE FROM `usuarios`;
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `estado`) VALUES
	(1, 'Administrador', 'admin@gym.com', '$2y$10$eeUFYFldSH9vWP3kElE3buIPXvXdEfIryMpYh9J47a6mxsju0q1GC', 'admin', 'activo'),
	(3, 'Vendedor Turno Mañana', 'recepcion@gym.com', '$2y$10$1cHBZUuhxxHZAb7u9.9OeuAkW0V99JOTLWgisEaUaLQpIv98pWBj6', 'recepcionista', 'activo'),
	(4, 'ENTRENADOR 1', 'entrenador@gym.com', '$2y$10$gEronM53aSMRfmsqeTeGE.xm48WYy.17MBaJWAp0TMUz1S1U9NcjC', 'entrenador', 'activo');

-- Volcando estructura para tabla gym_system.ventas
CREATE TABLE IF NOT EXISTS `ventas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `caja_id` int NOT NULL,
  `socio_id` int DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `metodo_pago` enum('efectivo','tarjeta','transferencia') DEFAULT 'efectivo',
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `caja_id` (`caja_id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`),
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla gym_system.ventas: ~11 rows (aproximadamente)
DELETE FROM `ventas`;
INSERT INTO `ventas` (`id`, `caja_id`, `socio_id`, `total`, `descuento`, `metodo_pago`, `fecha`) VALUES
	(1, 1, 7, 69.00, 0.00, 'efectivo', '2026-03-11 13:41:55'),
	(2, 1, 7, 108.00, 0.00, 'tarjeta', '2026-03-14 13:41:55'),
	(3, 1, 5, 204.00, 0.00, 'transferencia', '2026-03-15 13:41:55'),
	(4, 1, 5, 87.00, 0.00, 'transferencia', '2026-03-15 13:41:55'),
	(5, 1, 2, 114.00, 0.00, 'transferencia', '2026-03-14 13:41:55'),
	(6, 1, 3, 156.00, 0.00, 'efectivo', '2026-03-10 13:41:55'),
	(7, 1, 3, 207.00, 0.00, 'tarjeta', '2026-03-13 13:41:55'),
	(8, 1, 1, 49.00, 0.00, 'transferencia', '2026-03-10 13:41:55'),
	(9, 1, 6, 102.00, 0.00, 'efectivo', '2026-03-14 13:41:55'),
	(10, 1, 9, 104.00, 0.00, 'transferencia', '2026-03-11 13:41:55'),
	(11, 1, NULL, 49.00, 0.00, 'efectivo', '2026-03-15 21:16:54');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
