<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM zones WHERE zone_token = ?");
    $stmt->execute([$token]);
    $zone = $stmt->fetch();
    
    if (!$zone) {
        echo json_encode(['success' => false, 'message' => 'Zone not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'zone_code' => $zone['zone_code'],
        'zone_info' => $zone
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>