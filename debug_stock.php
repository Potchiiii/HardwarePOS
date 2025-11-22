<?php
require 'db.php';

$items = $pdo->query("SELECT id, name, quantity, low_threshold FROM inventory LIMIT 10")->fetchAll();

echo "Inventory Items Check:\n";
echo str_repeat("=", 80) . "\n";

foreach($items as $i) {
    $status = ($i['quantity'] < $i['low_threshold']) ? "LOW STOCK" : "OK";
    echo "ID: {$i['id']}, Name: {$i['name']}, Qty: {$i['quantity']}, Threshold: {$i['low_threshold']}, Status: $status\n";
}
?>
