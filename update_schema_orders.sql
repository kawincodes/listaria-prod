
USE listaria_db;

-- Add status column to products if not exists
-- We use a stored procedure-like check or just ALTER IGNORE if feasible, 
-- but straightforward ALTER works if we assume column might not exist or we handle error.
-- Better: Check if column exists, or just try to add it. 
-- Since I can't do complex IF EXISTS in simple SQL without procedure, I'll just run it. 
-- If it fails because it exists, that's fine (or I'll check error). 
-- 'status' ENUM('available', 'sold') DEFAULT 'available'

SET @dbname = DATABASE();
SET @tablename = "products";
SET @columnname = "status";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE products ADD COLUMN status ENUM('available', 'sold') DEFAULT 'available';"
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;


-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
