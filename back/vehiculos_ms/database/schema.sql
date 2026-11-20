CREATE DATABASE IF NOT EXISTS vehiculos_db;
USE vehiculos_db;

-- El estado guardado solo distingue disponible y mantenimiento. El estado "alquilado" se deriva de las reservas activas (reservas_ms).
-- No usar punto y coma en los comentarios: database_config.php separa las sentencias por ese caracter.
CREATE TABLE IF NOT EXISTS vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(100) NOT NULL,
    modelo VARCHAR(100) NOT NULL,
    anio YEAR NOT NULL,
    categoria VARCHAR(50),
    estado ENUM('disponible', 'mantenimiento') DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
