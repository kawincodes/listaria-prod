<?php
require 'includes/db.php';
$pdo->exec("UPDATE products SET category = 'Tops' WHERE category = 'Top'");
$pdo->exec("UPDATE products SET category = 'Bottoms' WHERE category = 'Bottom'");
$pdo->exec("UPDATE products SET category = 'Jackets' WHERE category = 'Jacket'");
$pdo->exec("UPDATE products SET category = 'Shoes' WHERE category = 'Shoe'");
$pdo->exec("UPDATE products SET category = 'Bags' WHERE category = 'Bag'");
$pdo->exec("UPDATE products SET category = 'Accessories' WHERE category = 'Accessory'");
echo "Categories updated.";
