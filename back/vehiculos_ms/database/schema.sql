CREATE DATABASE IF NOT EXISTS vehiculos_db;
USE vehiculos_db;

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
