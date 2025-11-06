-- Estructura de base de datos para el panel de viajes
DROP TABLE IF EXISTS viajes;
DROP TABLE IF EXISTS transportes;
DROP TABLE IF EXISTS usuarios;
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
  CONSTRAINT fk_usuarios_niveles FOREIGN KEY (id_nivel) REFERENCES niveles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO usuarios (id, apellido, nombre, dni, usuario, clave, activo, id_nivel, fecha_creacion, imagen) VALUES
(1, 'Palacios', 'Sue', '20123456', 'sue', '$2y$12$YliEVG0SzpOS3HfmDxTY3eqDlH2DW5j9Bq1mO/iuVL8frlqVCTL.K', 1, 1, '2023-10-01 10:00:00', 'sue.jpg'),
(2, 'Ramirez', 'Carlos', '22123456', 'carlos', '$2y$12$NTgq63R.f4bRsavWTLUgf.VeupoL4Wvo5gHLc8ETA0FXO2rF1BhLa', 1, 2, '2023-10-10 11:30:00', 'profile-img.jpg'),
(3, 'Alvarez', 'Marcos', '30111222', 'marcos', '$2y$12$hggUnqyakviUg6aKs/uzeeBxR7eg9fYeYQqFNhMiiJ8QnKQ.p76tq', 1, 3, '2023-11-05 09:15:00', 'marcos.jpg'),
(4, 'Perez', 'Juan', '31222333', 'juan', '$2y$12$Q4PyXViHvSdTRtmfTyh7BO/8Hv1uBCvbesahtmWQ0o3jvNTC8uuPi', 1, 3, '2023-11-06 09:15:00', 'juan.jpg');

CREATE TABLE transportes (
  id INT NOT NULL AUTO_INCREMENT,
  marca VARCHAR(60) NOT NULL,
  modelo VARCHAR(60) NOT NULL,
  patente VARCHAR(10) NOT NULL,
  anio SMALLINT DEFAULT NULL,
  disponible TINYINT(1) NOT NULL DEFAULT 1,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_transportes_patente (patente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO transportes (id, marca, modelo, patente, anio, disponible, fecha_creacion) VALUES
(1, 'Iveco', 'Daily Furgón', 'AC020K', 2023, 1, '2024-01-10 08:00:00'),
(2, 'Scania', 'Serie P', 'AA322CX', 2022, 1, '2024-01-12 08:00:00'),
(3, 'Iveco', 'Daily Chasis', 'AD698HA', 2021, 1, '2024-01-15 08:00:00');

CREATE TABLE viajes (
  id INT NOT NULL AUTO_INCREMENT,
  chofer_id INT NOT NULL,
  transporte_id INT NOT NULL,
  fecha_programada DATE NOT NULL,
  destino VARCHAR(120) NOT NULL,
  costo DECIMAL(12,2) NOT NULL,
  porcentaje_chofer INT NOT NULL,
  creado_por INT DEFAULT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_viajes_fecha (fecha_programada),
  CONSTRAINT fk_viajes_chofer FOREIGN KEY (chofer_id) REFERENCES usuarios (id),
  CONSTRAINT fk_viajes_transporte FOREIGN KEY (transporte_id) REFERENCES transportes (id),
  CONSTRAINT fk_viajes_creador FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO viajes (chofer_id, transporte_id, fecha_programada, destino, costo, porcentaje_chofer, creado_por, fecha_creacion) VALUES
(3, 1, '2025-11-02', 'Capilla del Monte', 300000.00, 10, 1, '2025-10-20 09:00:00'),
(3, 2, '2025-11-03', 'Morteros', 100000.00, 15, 1, '2025-10-21 09:15:00'),
(4, 3, '2025-11-05', 'Toledo', 250000.00, 10, 2, '2025-10-23 09:30:00'),
(4, 1, '2025-11-04', 'Capilla del Monte', 150000.00, 10, 2, '2025-10-24 09:45:00');
