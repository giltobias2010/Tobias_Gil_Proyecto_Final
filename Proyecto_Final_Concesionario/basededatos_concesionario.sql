-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-11-2026 a las 16:22:01
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `concesionario`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autos`
--

CREATE TABLE `autos` (
  `id` int(10) UNSIGNED NOT NULL,
  `marca` varchar(60) NOT NULL,
  `modelo` varchar(60) NOT NULL,
  `anio` smallint(5) UNSIGNED NOT NULL,
  `precio` decimal(12,2) NOT NULL,
  `patente` varchar(10) NOT NULL,
  `estado` enum('disponible','reservado','vendido') NOT NULL DEFAULT 'disponible',
  `color` varchar(30) DEFAULT NULL,
  `kilometraje` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `combustible` varchar(20) NOT NULL DEFAULT 'Nafta',
  `transmision` varchar(20) NOT NULL DEFAULT 'Manual',
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_archivado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `autos`
--

INSERT INTO `autos` (`id`, `marca`, `modelo`, `anio`, `precio`, `patente`, `estado`, `color`, `kilometraje`, `combustible`, `transmision`, `descripcion`, `imagen`, `creado_en`, `fecha_archivado`) VALUES
(1, 'Ford', 'Ranger Wildtrak', 2023, 52000000.00, 'AF123RN', 'disponible', 'Naranja', 12000, 'Diésel', 'Automática', 'Pick-up doble cabina 4x4 con motor turbodiésel, pantalla multimedia y asistentes de manejo.', 'uploads/autos/ranger.jpg', '2026-11-12 15:17:03', NULL),
(2, 'Ford', 'F-150 Raptor', 2022, 98000000.00, 'AE456RP', 'disponible', 'Rojo', 18500, 'Nafta', 'Automática', 'Pick-up de alto rendimiento con suspensión off-road, motor V6 biturbo y tracción 4x4.', 'uploads/autos/raptor.jpg', '2026-11-12 15:17:03', NULL),
(3, 'Toyota', 'Corolla', 2022, 28500000.00, 'AB123CD', 'disponible', 'Blanco', 32000, 'Nafta', 'Automática', 'Sedán confortable y económico, ideal para uso diario y viajes largos.', 'uploads/autos/94d40c81f55aecc109e6987dc4cf2311.jpg', '2026-11-12 15:17:03', NULL),
(4, 'Volkswagen', 'Gol Trend', 2019, 14200000.00, 'AA456BC', 'disponible', 'Gris', 68000, 'Nafta', 'Manual', 'Compacto práctico, bajo consumo y repuestos accesibles.', 'uploads/autos/d0747f9d1c9b3515f66ab24c88b7658b.jpg', '2026-11-12 15:17:03', NULL),
(5, 'Ford', 'Ranger', 2021, 41000000.00, 'AC789DE', 'reservado', 'Negro', 45000, 'Diésel', 'Manual', 'Pick-up doble cabina, motor 2.2 turbodiésel, muy buen estado general.', 'uploads/autos/adb228837448d574deaec6ef8a9bc193.jpg', '2026-11-12 15:17:03', NULL),
(6, 'Chevrolet', 'Cruze', 2020, 24500000.00, 'AD321EF', 'disponible', 'Negro', 52000, 'Nafta', 'Automática', 'Sedán amplio con buen equipamiento de seguridad y conectividad.', 'uploads/autos/48754e191d4e54454d0466d2a32101dc.jpg', '2026-11-12 15:17:03', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

CREATE TABLE `compras` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `auto_id` int(10) UNSIGNED NOT NULL,
  `precio` decimal(12,2) NOT NULL,
  `fecha_compra` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `apellido` varchar(60) NOT NULL,
  `dni` varchar(10) NOT NULL,
  `correo` varchar(120) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('usuario','admin') NOT NULL DEFAULT 'usuario',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_archivado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `dni`, `correo`, `contrasena`, `rol`, `creado_en`, `fecha_archivado`) VALUES
(1, 'Admin', 'Principal', '10000000', 'admin@concesionario.com', '$2y$12$ctgtI2z4bvcfpwDk/KU9ouP4jRwRh2z6K3cP0PYkjsgVhga0Flcg2', 'admin', '2026-11-12 15:17:01', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `autos`
--
ALTER TABLE `autos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patente` (`patente`);

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `auto_id` (`auto_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `autos`
--
ALTER TABLE `autos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `compras_ibfk_2` FOREIGN KEY (`auto_id`) REFERENCES `autos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
