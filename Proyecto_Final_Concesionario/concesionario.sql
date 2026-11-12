-- Base de datos del concesionario de autos
-- Importar desde phpMyAdmin (pestaña "Importar") o ejecutar completo.
-- Proyecto en C:\xampp\htdocs\concesionario1\  ->  http://localhost/concesionario1/login.php
-- Si ya tenías una versión anterior de la tabla autos, ejecutá solo esto:
--   ALTER TABLE autos ADD COLUMN color VARCHAR(30) NULL, ADD COLUMN kilometraje INT UNSIGNED NOT NULL DEFAULT 0,
--     ADD COLUMN combustible VARCHAR(20) NOT NULL DEFAULT 'Nafta', ADD COLUMN transmision VARCHAR(20) NOT NULL DEFAULT 'Manual',
--     ADD COLUMN descripcion TEXT NULL;

CREATE DATABASE IF NOT EXISTS concesionario
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE concesionario;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  apellido VARCHAR(60) NOT NULL,
  dni VARCHAR(10) NOT NULL UNIQUE,
  correo VARCHAR(120) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,            -- hash de password_hash()
  rol ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_archivado DATETIME NULL DEFAULT NULL   -- NULL = activo
) ENGINE=InnoDB;

-- Tabla de autos
CREATE TABLE IF NOT EXISTS autos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  marca VARCHAR(60) NOT NULL,
  modelo VARCHAR(60) NOT NULL,
  anio SMALLINT UNSIGNED NOT NULL,
  precio DECIMAL(12,2) NOT NULL,
  patente VARCHAR(10) NOT NULL UNIQUE,
  estado ENUM('disponible','reservado','vendido') NOT NULL DEFAULT 'disponible',
  color VARCHAR(30) NULL DEFAULT NULL,
  kilometraje INT UNSIGNED NOT NULL DEFAULT 0,
  combustible VARCHAR(20) NOT NULL DEFAULT 'Nafta',
  transmision VARCHAR(20) NOT NULL DEFAULT 'Manual',
  descripcion TEXT NULL,
  imagen VARCHAR(255) DEFAULT NULL,            -- ruta relativa de la foto
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_archivado DATETIME NULL DEFAULT NULL   -- NULL = activo
) ENGINE=InnoDB;

-- Compras: cada fila registra qué usuario compró qué auto y a qué precio
CREATE TABLE IF NOT EXISTS compras (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  auto_id INT UNSIGNED NOT NULL,
  precio DECIMAL(12,2) NOT NULL,               -- precio al momento de comprar
  fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  FOREIGN KEY (auto_id) REFERENCES autos(id)
) ENGINE=InnoDB;

-- Administrador inicial: correo admin@concesionario.com | contraseña Admin1234
-- (cambiarla después de la primera prueba)
INSERT INTO usuarios (nombre, apellido, dni, correo, contrasena, rol) VALUES
('Admin', 'Principal', '10000000', 'admin@concesionario.com',
 '$2y$12$ctgtI2z4bvcfpwDk/KU9ouP4jRwRh2z6K3cP0PYkjsgVhga0Flcg2', 'admin');

-- Autos de ejemplo (dos con foto, el resto sin foto)
INSERT INTO autos (marca, modelo, anio, precio, patente, estado, color, kilometraje, combustible, transmision, descripcion, imagen) VALUES
('Ford', 'Ranger Wildtrak', 2023, 52000000.00, 'AF123RN', 'disponible', 'Naranja', 12000, 'Diésel', 'Automática',
 'Pick-up doble cabina 4x4 con motor turbodiésel, pantalla multimedia y asistentes de manejo.', 'uploads/autos/ranger.jpg'),
('Ford', 'F-150 Raptor', 2022, 98000000.00, 'AE456RP', 'disponible', 'Rojo', 18500, 'Nafta', 'Automática',
 'Pick-up de alto rendimiento con suspensión off-road, motor V6 biturbo y tracción 4x4.', 'uploads/autos/raptor.jpg'),
('Toyota', 'Corolla', 2022, 28500000.00, 'AB123CD', 'disponible', 'Blanco', 32000, 'Nafta', 'Automática',
 'Sedán confortable y económico, ideal para uso diario y viajes largos.', NULL),
('Volkswagen', 'Gol Trend', 2019, 14200000.00, 'AA456BC', 'disponible', 'Gris', 68000, 'Nafta', 'Manual',
 'Compacto práctico, bajo consumo y repuestos accesibles.', NULL),
('Ford', 'Ranger', 2021, 41000000.00, 'AC789DE', 'reservado', 'Negro', 45000, 'Diésel', 'Manual',
 'Pick-up doble cabina, motor 2.2 turbodiésel, muy buen estado general.', NULL),
('Chevrolet', 'Cruze', 2020, 24500000.00, 'AD321EF', 'disponible', 'Negro', 52000, 'Nafta', 'Automática',
 'Sedán amplio con buen equipamiento de seguridad y conectividad.', NULL);
