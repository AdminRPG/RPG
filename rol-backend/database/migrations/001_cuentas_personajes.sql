-- =========================================================
-- Migration 001: Cuentas y Personajes (arquitectura character-driven)
-- El foro se rige por personajes. Una cuenta (MyBB user) tiene
-- N slots de personaje. Los posts se asocian a un personaje.
-- =========================================================

-- Cuentas de rol (una por MyBB user, datos a nivel de cuenta)
CREATE TABLE IF NOT EXISTS rol_cuentas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mybb_user_id INT NOT NULL UNIQUE,
    max_slots TINYINT NOT NULL DEFAULT 3,
    es_narrador BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mybb_user (mybb_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Personajes (pertenecen a una cuenta, ocupan un slot)
CREATE TABLE IF NOT EXISTS rol_personajes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cuenta_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    raza VARCHAR(100),
    clase VARCHAR(100),
    edad INT,
    historia TEXT,
    avatar_url VARCHAR(255),
    estado ENUM('borrador','pendiente','aprobado','rechazado','retirado') DEFAULT 'borrador',
    activo BOOLEAN NOT NULL DEFAULT FALSE,
    slot_index TINYINT NOT NULL DEFAULT 0,
    aprobado_por INT NULL,
    fecha_aprobacion DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cuenta_id) REFERENCES rol_cuentas(id) ON DELETE CASCADE,
    INDEX idx_cuenta (cuenta_id),
    INDEX idx_cuenta_activo (cuenta_id, activo),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Atributos de personaje (EAV: flexible, cada personaje tiene sus stats)
CREATE TABLE IF NOT EXISTS rol_ficha_atributos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    clave VARCHAR(50) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE,
    INDEX idx_personaje (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mapeo post → personaje (cada post de MyBB pertenece a un personaje)
CREATE TABLE IF NOT EXISTS rol_post_personaje (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL UNIQUE,
    personaje_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE,
    INDEX idx_post (post_id),
    INDEX idx_personaje (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Tablas existentes (sin cambios estructurales, solo FK references)
-- =========================================================

CREATE TABLE IF NOT EXISTS rol_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tipo VARCHAR(50),
    valor_economico DECIMAL(10,2) DEFAULT 0,
    metadata JSON NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rol_inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    item_id INT NOT NULL,
    cantidad INT DEFAULT 1,
    adquirido_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES rol_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rol_economia_saldo (
    personaje_id INT PRIMARY KEY,
    saldo DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (personaje_id) REFERENCES rol_personajes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rol_economia_transacciones (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    origen_personaje_id INT NULL,
    destino_personaje_id INT NULL,
    monto DECIMAL(10,2) NOT NULL,
    tipo ENUM('transferencia','recompensa','compra','ajuste_admin') NOT NULL,
    referencia VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_origen (origen_personaje_id),
    INDEX idx_destino (destino_personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rol_tiradas (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    personaje_id INT NOT NULL,
    formula VARCHAR(50) NOT NULL,
    resultado INT NOT NULL,
    detalle JSON NOT NULL,
    hilo_mybb_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_personaje (personaje_id),
    INDEX idx_hilo (hilo_mybb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
