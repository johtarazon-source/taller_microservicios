CREATE DATABASE IF NOT EXISTS reservas_db;
USE reservas_db;

-- cliente_id y vehiculo_id son referencias lógicas a clientes_ms y vehiculos_ms (bases distintas): sin claves foráneas.
CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    vehiculo_id INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('activa', 'completada', 'cancelada') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reservas_cliente (cliente_id),
    INDEX idx_reservas_vehiculo (vehiculo_id, estado)
);
