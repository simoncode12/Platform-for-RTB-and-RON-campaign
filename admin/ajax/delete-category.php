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

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
    exit;
}

try {
    // Check if category is in use
    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM rtb_campaigns WHERE category_id = ?) +
        (SELECT COUNT(*) FROM ron_campaigns WHERE category_id = ?) +
        (SELECT COUNT(*) FROM websites WHERE category_id = ?) as usage_count");
    $stmt->execute([$id, $id, $id]);
    $usage = $stmt->fetchColumn();
    
    if ($usage > 0) {
        echo json_encode(['success' => false, 'message' => 'Category is currently in use and cannot be deleted']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>