<?php
/**
 * Click Tracking API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

try {
    $zone_id = $_POST['zone_id'] ?? $_GET['zone_id'] ?? '';
    $campaign_id = $_POST['campaign_id'] ?? $_GET['campaign_id'] ?? '';
    $type = $_POST['type'] ?? $_GET['type'] ?? 'unknown';
    $timestamp = $_POST['timestamp'] ?? $_GET['timestamp'] ?? time();
    
    if (empty($zone_id)) {
        throw new Exception('Zone ID is required');
    }
    
    // Get zone by token
    $stmt = $pdo->prepare("SELECT id FROM zones WHERE zone_token = ?");
    $stmt->execute([$zone_id]);
    $zone = $stmt->fetch();
    
    if (!$zone) {
        throw new Exception('Zone not found');
    }
    
    // Log click
    $stmt = $pdo->prepare("INSERT INTO clicks (zone_id, campaign_id, campaign_type, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $zone['id'],
        $campaign_id,
        $type,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Update zone stats
    updateZoneStats($pdo, $zone['id'], 'click');
    
    echo json_encode(['success' => true, 'tracked' => 'click']);
    
} catch (Exception $e) {
    error_log("Click Tracking Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function updateZoneStats($pdo, $zone_id, $type) {
    try {
        if ($type === 'click') {
            $stmt = $pdo->prepare("UPDATE zones SET 
                                   clicks_today = clicks_today + 1,
                                   clicks_total = clicks_total + 1
                                   WHERE id = ?");
            $stmt->execute([$zone_id]);
        }
    } catch (Exception $e) {
        error_log("Update Zone Stats Error: " . $e->getMessage());
    }
}

// Create clicks table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS clicks (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        zone_id INT NOT NULL,
        campaign_id VARCHAR(100),
        campaign_type VARCHAR(20),
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_zone_date (zone_id, created_at),
        INDEX idx_campaign (campaign_id)
    )");
} catch (Exception $e) {
    error_log("Create clicks table error: " . $e->getMessage());
}
?>