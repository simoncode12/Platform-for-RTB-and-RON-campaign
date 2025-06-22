<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $standard_formats = [
        [
            'name' => 'Standard Banner',
            'description' => 'Standard banner ad format for display advertising',
            'type' => 'banner',
            'sizes' => ['728x90', '300x250', '160x600', '300x100', '300x50'],
            'supports_html5' => 1,
            'supports_script' => 1,
            'supports_image' => 1,
            'supports_video' => 0,
            'default_cpm' => 1.50,
            'default_cpc' => 0.25
        ],
        [
            'name' => 'Mobile Banner',
            'description' => 'Mobile-optimized banner ads',
            'type' => 'banner',
            'sizes' => ['320x50', '300x50', '300x100', '320x100'],
            'supports_html5' => 1,
            'supports_script' => 1,
            'supports_image' => 1,
            'supports_video' => 0,
            'default_cpm' => 2.00,
            'default_cpc' => 0.30
        ],
        [
            'name' => 'Video Pre-roll',
            'description' => 'Video advertisement played before main content',
            'type' => 'video',
            'sizes' => ['640x360', '480x270', '1280x720'],
            'supports_html5' => 1,
            'supports_script' => 1,
            'supports_image' => 0,
            'supports_video' => 1,
            'default_cpm' => 5.00,
            'default_cpc' => 0.50
        ],
        [
            'name' => 'Native Feed',
            'description' => 'Native ads that blend with content feed',
            'type' => 'native',
            'sizes' => [],
            'supports_html5' => 1,
            'supports_script' => 1,
            'supports_image' => 1,
            'supports_video' => 1,
            'default_cpm' => 3.00,
            'default_cpc' => 0.40
        ],
        [
            'name' => 'Popup',
            'description' => 'Popup advertisement window',
            'type' => 'popup',
            'sizes' => ['800x600', '1024x768', '600x400'],
            'supports_html5' => 1,
            'supports_script' => 1,
            'supports_image' => 1,
            'supports_video' => 1,
            'default_cpm' => 4.00,
            'default_cpc' => 0.35
        ],
        [
            'name' => 'Interstitial',
            'description' => 'Full-screen ad that covers the interface',
            'type' => 'interstitial',
            'sizes' => ['320x480', '768x1024', '414x736'],
            'supports_html5' => 1,
            'supports_script' => 1,
            'supports_image' => 1,
            'supports_video' => 1,
            'default_cpm' => 6.00,
            'default_cpc' => 0.60
        ]
    ];
    
    $created = 0;
    foreach ($standard_formats as $format) {
        // Check if format already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ad_formats WHERE name = ?");
        $stmt->execute([$format['name']]);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO ad_formats (name, description, type, sizes, supports_html5, supports_script, supports_image, supports_video, default_cpm, default_cpc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $format['name'],
                $format['description'],
                $format['type'],
                json_encode($format['sizes']),
                $format['supports_html5'],
                $format['supports_script'],
                $format['supports_image'],
                $format['supports_video'],
                $format['default_cpm'],
                $format['default_cpc']
            ]);
            $created++;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Created $created standard ad formats",
        'created' => $created
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>