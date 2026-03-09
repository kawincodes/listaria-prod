<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=listaria_bulk_template.csv');

$output = fopen('php://output', 'w');

// Headers - Updated with Predefined Categories
fputcsv($output, ['Title', 'Brand', 'Category (Tops|Bottoms|Jackets|Shoes|Bags|Accessories|Others)', 'Condition (Brand New|Lightly Used|Regularly Used)', 'Location', 'Description', 'Price', 'Image Filenames (Comma separated)'], ',', '"', "\\");

fclose($output);
