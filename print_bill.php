<?php
session_start();
require_once 'config/database.php';
require_once 'includes/BillTemplate.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$table = isset($_GET['table']) ? intval($_GET['table']) : 0;

if ($table <= 0) {
    http_response_code(400);
    exit('Invalid table number');
}

// Get all orders for this table
$sql = "SELECT o.*, oi.product_id, oi.quantity, p.name as product_name, p.price as unit_price 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.table_number = ? AND o.status IN ('pending', 'processing')
        ORDER BY o.created_at";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $table);
$stmt->execute();
$result = $stmt->get_result();

// Format data for template
$orderData = null;
$items = [];

while ($row = $result->fetch_assoc()) {
    if (!$orderData) {
        $orderData = [
            'id' => $row['id'],
            'created_at' => $row['created_at'],
            'table_number' => $row['table_number']
        ];
    }
    
    $items[] = [
        'name' => $row['product_name'],
        'quantity' => $row['quantity'],
        'unit_price' => $row['unit_price']
    ];
}

// Template settings
$settings = [
    'restaurant_name' => 'Cubana Ma Pub',
    'contact_phone' => '+250 788 000 000'
];

// Create template instance
$template = new BillTemplate($settings);

// Generate and output bill
if ($orderData && !empty($items)) {
    echo $template->generateBillHTML($orderData, $items, $_SESSION['full_name']);
} else {
    echo "No active orders found for this table.";
} 