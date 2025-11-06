-- Estructura de base de datos para el panel de viajes
DROP TABLE IF EXISTS viajes;
DROP TABLE IF EXISTS transportes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS destinos;
DROP TABLE IF EXISTS marcas;
DROP TABLE IF EXISTS niveles;

CREATE TABLE niveles (
  id INT NOT NULL AUTO_INCREMENT,
  denominacion VARCHAR(50) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO niveles (id, denominacion) VALUES
(1, 'Admin'),
(2, 'Operador'),
(3, 'Chofer');

CREATE TABLE marcas (
  id INT NOT NULL AUTO_INCREMENT,
  denominacion VARCHAR(80) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_marcas_denominacion (denominacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO marcas (id, denominacion) VALUES
(1, 'Iveco'),
(2, 'Volvo'),
(3, 'Scania'),
(4, 'Volkswagen');

CREATE TABLE destinos (
  id INT NOT NULL AUTO_INCREMENT,
  denominacion VARCHAR(120) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_destinos_denominacion (denominacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO destinos (id, denominacion) VALUES
(1, 'Capilla del Monte'),
(2, 'Morteros'),
(3, 'Toledo'),
(4, 'Río Cuarto'),
(5, 'Villa María');

CREATE TABLE usuarios (
  id INT NOT NULL AUTO_INCREMENT,
  apellido VARCHAR(80) NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  dni VARCHAR(10) NOT NULL,
  usuario VARCHAR(50) NOT NULL,
  clave VARCHAR(255) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  id_nivel INT NOT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  imagen VARCHAR(150) DEFAULT 'profile-img.jpg',
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_dni (dni),
  UNIQUE KEY uq_usuarios_usuario (usuario),
  KEY idx_usuarios_nivel (id_nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO usuarios (id, apellido, nombre, dni, usuario, clave, activo, id_nivel, fecha_creacion, imagen) VALUES
(1, 'Palacios', 'Sue', '20123456', 'sue', '$2y$12$o7VBA13ujQ4Hb9DDl/Fo8eQ92C1OLxDrqWdHcsWSurBEVWkQMjBQ.', 1, 1, '2023-10-01 10:00:00', 'sue.jpg'),
(2, 'Ramirez', 'Carlos', '22123456', 'carlos', '$2y$12$o7VBA13ujQ4Hb9DDl/Fo8eQ92C1OLxDrqWdHcsWSurBEVWkQMjBQ.', 1, 2, '2023-10-10 11:30:00', 'profile-img.jpg'),
(3, 'Alvarez', 'Marcos', '30111222', 'marcos', '$2y$12$o7VBA13ujQ4Hb9DDl/Fo8eQ92C1OLxDrqWdHcsWSurBEVWkQMjBQ.', 1, 3, '2023-11-05 09:15:00', 'marcos.jpg'),
(4, 'Perez', 'Juan', '31222333', 'juan', '$2y$12$o7VBA13ujQ4Hb9DDl/Fo8eQ92C1OLxDrqWdHcsWSurBEVWkQMjBQ.', 1, 3, '2023-11-06 09:15:00', 'juan.jpg');

CREATE TABLE transportes (
  id INT NOT NULL AUTO_INCREMENT,
  marca_id INT NOT NULL,
  modelo VARCHAR(60) NOT NULL,
  patente VARCHAR(10) NOT NULL,
  anio SMALLINT DEFAULT NULL,
  disponible TINYINT(1) NOT NULL DEFAULT 1,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_transportes_patente (patente),
  KEY idx_transportes_marca (marca_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO transportes (id, marca_id, modelo, patente, anio, disponible, fecha_creacion) VALUES
(1, 1, 'Daily Furgón', 'AC020K', 2023, 1, '2024-01-10 08:00:00'),
(2, 3, 'Serie P', 'AA322CX', 2022, 1, '2024-01-12 08:00:00'),
(3, 1, 'Daily Chasis', 'AD698HA', 2021, 1, '2024-01-15 08:00:00'),
(4, 4, 'Delivery', 'AE345BC', 2020, 0, '2024-01-20 08:00:00');

CREATE TABLE viajes (
  id INT NOT NULL AUTO_INCREMENT,
  chofer_id INT NOT NULL,
  transporte_id INT NOT NULL,
  destino_id INT NOT NULL,
  fecha_programada DATE NOT NULL,
  costo DECIMAL(12,2) NOT NULL,
  porcentaje_chofer INT NOT NULL,
  creado_por INT DEFAULT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_viajes_fecha (fecha_programada),
  KEY idx_viajes_destino (destino_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO viajes (chofer_id, transporte_id, destino_id, fecha_programada, costo, porcentaje_chofer, creado_por, fecha_creacion) VALUES
(3, 1, 1, '2025-11-02', 300000.00, 10, 1, '2025-10-20 09:00:00'),
(3, 2, 2, '2025-11-03', 100000.00, 15, 1, '2025-10-21 09:15:00'),
(4, 3, 3, '2025-11-05', 250000.00, 10, 2, '2025-10-23 09:30:00'),
(4, 1, 1, '2025-11-04', 150000.00, 10, 2, '2025-10-24 09:45:00');
