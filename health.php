<?php
/**
 * Health Check Endpoint
 * Returns platform status
 */

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'platform' => 'AdStart RTB & RON',
    'version' => '1.0.0',
    'checks' => []
];

// Database check
try {
    require_once 'config/database.php';
    $stmt = $pdo->query('SELECT 1');
    $health['checks']['database'] = 'ok';
} catch (Exception $e) {
    $health['checks']['database'] = 'error';
    $health['status'] = 'error';
}

// File system check
$health['checks']['filesystem'] = is_writable(__DIR__) ? 'ok' : 'warning';

// Memory check
$memory_limit = ini_get('memory_limit');
$memory_usage = memory_get_usage(true);
$health['checks']['memory'] = [
    'limit' => $memory_limit,
    'usage' => round($memory_usage / 1024 / 1024, 2) . 'MB',
    'status' => 'ok'
];

// Disk space check
$disk_free = disk_free_space(__DIR__);
$disk_total = disk_total_space(__DIR__);
$disk_percent = round(($disk_total - $disk_free) / $disk_total * 100, 2);

$health['checks']['disk'] = [
    'free' => round($disk_free / 1024 / 1024 / 1024, 2) . 'GB',
    'total' => round($disk_total / 1024 / 1024 / 1024, 2) . 'GB',
    'used_percent' => $disk_percent . '%',
    'status' => $disk_percent > 90 ? 'warning' : 'ok'
];

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
?>