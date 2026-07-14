-- =========================================================
-- Migration 002: Sistema de creación de personaje
-- Depende de: 001_cuentas_personajes.sql (tablas base)
-- 
-- Añade tablas estructuradas para stats, virtudes, defectos,
-- equipo e historia, reemplazando el enfoque EAV genérico
-- para los datos del núcleo de la ficha.
--
-- La tabla rol_ficha_atributos se mantiene para datos
-- adicionales no cubiertos por las tablas específicas.
-- =========================================================

-- Extensión de columnas en rol_personajes (datos de identidad)
-- MySQL compatible column additions using INFORMATION_SCHEMA check
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rol_personajes' AND COLUMN_NAME = 'alias');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE rol_personajes ADD COLUMN alias VARCHAR(100) AFTER nombre', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rol_personajes' AND COLUMN_NAME = 'concept');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE rol_personajes ADD COLUMN concept VARCHAR(255) AFTER alias', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rol_personajes' AND COLUMN_NAME = 'apariencia');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE rol_personajes ADD COLUMN apariencia TEXT AFTER historia', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rol_personajes' AND COLUMN_NAME = 'personalidad');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE rol_personajes ADD COLUMN personalidad TEXT AFTER apariencia', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rol_personajes' AND COLUMN_NAME = 'voz');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE rol_personajes ADD COLUMN voz JSON AFTER personalidad', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rol_personajes' AND COLUMN_NAME = 'motivaciones');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE rol_personajes ADD COLUMN motivaciones JSON AFTER voz', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rol_personajes' AND COLUMN_NAME = 'arco_narrativo');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE rol_personajes ADD COLUMN arco_narrativo JSON AFTER motivaciones', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rol_personajes' AND COLUMN_NAME = 'pv_restantes');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE rol_personajes ADD COLUMN pv_restantes INT NOT NULL DEFAULT 6 AFTER historia', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Stats (12 filas por personaje, una por stat)
-- Sistema numerico: escala 5-100+. Siglas de 3 letras (FUE, DES, ...).
CREATE TABLE IF NOT EXISTS rol_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    pilar ENUM('cuerpo', 'mente', 'espiritu') NOT NULL,
    stat_key VARCHAR(3) NOT NULL,
    valor INT NOT NULL DEFAULT 5,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_personaje_stat (personaje_id, stat_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Virtudes del personaje
CREATE TABLE IF NOT EXISTS rol_virtudes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    coste_pv INT NOT NULL,
    descripcion TEXT,
    catalogo_id VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Defectos del personaje
CREATE TABLE IF NOT EXISTS rol_defectos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    pv_otorgados INT NOT NULL,
    descripcion TEXT,
    catalogo_id VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Equipo inicial del personaje (JSON para flexibilidad)
CREATE TABLE IF NOT EXISTS rol_equipo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL UNIQUE,
    arma_basica JSON,
    objeto_personal JSON,
    ropa_pertenencias JSON,
    moneda JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Relaciones entre personajes
CREATE TABLE IF NOT EXISTS rol_relaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    destino_personaje_id INT NOT NULL,
    tipo ENUM('aliado', 'rival', 'enemigo', 'familiar', 'mentor', 'discipulo', 'amoroso', 'neutro') NOT NULL,
    descripcion TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE,
    FOREIGN KEY (destino_personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE,
    UNIQUE KEY uq_relacion (personaje_id, destino_personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Catalogo de referencia de labels para valores numericos
-- (compartido con el fontend: ope_rol_stat_label en inc/ope_rol_data.php)
-- =========================================================
CREATE TABLE IF NOT EXISTS ref_stat_labels (
    min_val INT NOT NULL,
    label VARCHAR(20) NOT NULL,
    PRIMARY KEY (min_val)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ref_stat_labels (min_val, label) VALUES
    (100, 'Trascendente'),
    (80,  'Legendario'),
    (60,  'Excepcional'),
    (40,  'Notable'),
    (25,  'Bueno'),
    (15,  'Normal'),
    (10,  'Bajo'),
    (5,   'Minimo');
