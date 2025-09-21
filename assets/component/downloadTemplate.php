<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="zeblaze_products_template.csv"');

$output = fopen('php://output', 'w');

// Write CSV headers from your table structure
fputcsv($output, [
    'product_name',
    'offer_price',
    'color',
    'warranty',
    'availability',
    'description',
    'photo_1',
    'photo_2'
]);

// Optional: Add an example row
// fputcsv($output, ['Example Watch', 199, 'Black', '1 Year', 'In Stock', 'Premium smartwatch', 'img1.jpg', 'img2.jpg']);

fclose($output);
exit;
?>
