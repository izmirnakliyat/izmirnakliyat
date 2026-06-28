<?php
require_once __DIR__ . '/require_admin_ajax.php';
mynak_admin_ajax_require_json();

// Response headers
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Menü ID gereklidir.'
    ]);
    exit;
}

$id = (int)$_GET['id'];

// Get menu item data from database
$stmt = $conn->prepare("SELECT * FROM mobile_bottom_menu WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Menü öğesi bulunamadı.'
    ]);
    exit;
}

$item = $result->fetch_assoc();

// Return menu item data
echo json_encode([
    'success' => true,
    'item' => $item
]);
?>
