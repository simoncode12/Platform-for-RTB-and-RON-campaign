<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;
$revenue_share = $input['revenue_share'] ?? 0;

if (!$id || $revenue_share < 0 || $revenue_share > 100) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE publishers SET revenue_share = ? WHERE id = ?");
    $stmt->execute([$revenue_share, $id]);
    
    echo json_encode(['success' => true, 'message' => 'Revenue share updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>