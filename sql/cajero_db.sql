
-- =========================================
-- BANCO FISEI
-- Cajero Automatico QR - Version Web
-- =========================================

CREATE DATABASE IF NOT EXISTS cajero_web_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE cajero_web_db;

-- =========================================
-- TABLA CLIENTES
-- =========================================

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    cedula VARCHAR(10) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- =========================================
-- TABLA USUARIOS
-- =========================================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNIQUE,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    rol ENUM('ADMIN', 'CLIENTE') NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    intentos_fallidos INT NOT NULL DEFAULT 0,
    bloqueado BOOLEAN NOT NULL DEFAULT FALSE,

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id),

    CHECK (intentos_fallidos >= 0),

    CHECK (
        (rol = 'ADMIN' AND id_cliente IS NULL)
        OR
        (rol = 'CLIENTE' AND id_cliente IS NOT NULL)
    )
) ENGINE=InnoDB;

-- =========================================
-- TABLA CUENTAS
-- =========================================

CREATE TABLE cuentas (
    numero VARCHAR(10) PRIMARY KEY,
    id_cliente INT NOT NULL,
    tipo ENUM('AHORROS', 'CORRIENTE') NOT NULL,
    saldo DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado BOOLEAN NOT NULL DEFAULT TRUE,

    FOREIGN KEY (id_cliente)
        REFERENCES clientes(id),

    CHECK (saldo >= 0)
) ENGINE=InnoDB;

-- =========================================
-- TABLA TARJETAS QR
-- =========================================

CREATE TABLE tarjetas (
    codigo_qr VARCHAR(20) PRIMARY KEY,
    numero_cuenta VARCHAR(10) NOT NULL,
    activa BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_emision DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,
    fecha_anulacion DATETIME NULL,

    cuenta_activa VARCHAR(10)
        GENERATED ALWAYS AS (
            CASE
                WHEN activa = TRUE THEN numero_cuenta
                ELSE NULL
            END
        ) STORED,

    UNIQUE (cuenta_activa),

    FOREIGN KEY (numero_cuenta)
        REFERENCES cuentas(numero)
) ENGINE=InnoDB;

-- =========================================
-- TABLA MOVIMIENTOS
-- =========================================

CREATE TABLE movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_cuenta VARCHAR(10) NOT NULL,

    tipo ENUM(
        'RETIRO',
        'DEPOSITO',
        'TRANSFERENCIA_ENVIADA',
        'TRANSFERENCIA_RECIBIDA'
    ) NOT NULL,

    monto DECIMAL(12,2) NOT NULL,
    fecha DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,
    cuenta_relacionada VARCHAR(10) NULL,
    saldo_resultante DECIMAL(12,2) NOT NULL,
    referencia VARCHAR(40) NOT NULL,

    FOREIGN KEY (numero_cuenta)
        REFERENCES cuentas(numero),

    FOREIGN KEY (cuenta_relacionada)
        REFERENCES cuentas(numero),

    CHECK (monto > 0),
    CHECK (saldo_resultante >= 0),

    INDEX idx_cuenta_fecha (numero_cuenta, fecha),
    INDEX idx_referencia (referencia)
) ENGINE=InnoDB;
