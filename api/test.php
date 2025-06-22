<?php
/**
 * API Test Endpoint
 */

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'AdStart API is working',
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoints' => [
        'rtb_request' => '/api/rtb/request.php',
        'track_impression' => '/api/track/impression.php',
        'track_click' => '/api/track/click.php'
    ]
]);
?>