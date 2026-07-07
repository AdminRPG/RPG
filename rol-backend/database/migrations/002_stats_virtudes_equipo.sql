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
ALTER TABLE rol_personajes
  ADD COLUMN IF NOT EXISTS alias VARCHAR(100) AFTER nombre,
  ADD COLUMN IF NOT EXISTS concept VARCHAR(255) AFTER alias,
  ADD COLUMN IF NOT EXISTS apariencia TEXT AFTER historia,
  ADD COLUMN IF NOT EXISTS personalidad TEXT after apariencia,
  ADD COLUMN IF NOT EXISTS voz JSON after personalidad,
  ADD COLUMN IF NOT EXISTS motivaciones JSON AFTER voz,
  ADD COLUMN IF NOT EXISTS arco_narrativo JSON AFTER motivaciones,
  ADD COLUMN IF NOT EXISTS pv_restantes INT NOT NULL DEFAULT 6 AFTER historia;

-- Stats (12 filas por personaje, una por stat)
CREATE TABLE IF NOT EXISTS rol_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    pilar ENUM('cuerpo', 'mente', 'espiritu') NOT NULL,
    stat_key VARCHAR(20) NOT NULL,
    rango CHAR(3) NOT NULL DEFAULT 'F',
    valor TINYINT NOT NULL DEFAULT 1,
    es_mejorada BOOLEAN NOT NULL DEFAULT FALSE,
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
-- Datos de referencia: catálogo de rangos de stats
-- =========================================================
CREATE TABLE IF NOT EXISTS ref_rangos_stats (
    rango CHAR(3) PRIMARY KEY,
    valor TINYINT NOT NULL UNIQUE,
    label VARCHAR(20) NOT NULL,
    descripcion VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ref_rangos_stats (rango, valor, label, descripcion) VALUES
    ('F',   1,  'Pésimo',        'Significativamente por debajo del promedio'),
    ('E',   2,  'Malo',          'Por debajo del promedio'),
    ('D',   3,  'Mediocre',      'Ligeramente por debajo del promedio'),
    ('C',   4,  'Promedio',      'Humano adulto normal'),
    ('B',   5,  'Bueno',         'Entrenado / por encima del promedio'),
    ('A',   6,  'Notable',       'Experto / Élite'),
    ('S',   7,  'Sobresaliente', 'Maestro / Mejor en su campo'),
    ('SS',  8,  'Legendario',    'Cúspide humana'),
    ('SSS', 9,  'Mítico',        'Trasciende lo humano'),
    ('M+',  10, 'Trascendental', 'Sobrenatural / Divino');
