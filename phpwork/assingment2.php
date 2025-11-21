<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$products = [
    ["id" => 1, "name" => "Laptop", "price" => 1200],
    ["id" => 2, "name" => "Smartphone", "price" => 800],
    ["id" => 3, "name" => "Headphones", "price" => 150]
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($products);
} else {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed"]);
}
?>