USE cajero_web_db;

CREATE TABLE IF NOT EXISTS limites_acceso (
    clave CHAR(64) PRIMARY KEY,
    intentos INT NOT NULL DEFAULT 0,
    inicio_ventana BIGINT NOT NULL,

    CHECK (intentos >= 0),

    INDEX idx_inicio_ventana (inicio_ventana)
) ENGINE=InnoDB;