CREATE DATABASE IF NOT EXISTS listaria_db;
USE listaria_db;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    brand VARCHAR(100) NOT NULL,
    condition_tag ENUM('Brand New', 'Lightly Used', 'Regularly Used') NOT NULL,
    price_min DECIMAL(10, 2) NOT NULL,
    price_max DECIMAL(10, 2) NOT NULL,
    image_paths TEXT NOT NULL COMMENT 'JSON array of image paths',
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
