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
$status = $input['status'] ?? '';

if (!$id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Update publisher status
    $stmt = $pdo->prepare("UPDATE publishers SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    // Update user status
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = (SELECT user_id FROM publishers WHERE id = ?)");
    $stmt->execute([$status, $id]);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Publisher status updated']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>