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
$name = $input['name'] ?? '';
$description = $input['description'] ?? '';
$type = $input['type'] ?? '';

if (!$id || !$name || !$type) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Check if another category with same name exists (excluding current)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE LOWER(name) = LOWER(?) AND id != ?");
    $stmt->execute([$name, $id]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Category with this name already exists']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, type = ? WHERE id = ?");
    $stmt->execute([$name, $description, $type, $id]);
    
    echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>