<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$zone_id = $input['zone_id'] ?? 0;

if (!$zone_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid zone ID']);
    exit;
}

try {
    // Get zone details
    $stmt = $pdo->prepare("SELECT z.*, w.url as website_url FROM zones z LEFT JOIN websites w ON z.website_id = w.id WHERE z.id = ?");
    $stmt->execute([$zone_id]);
    $zone = $stmt->fetch();
    
    if (!$zone) {
        echo json_encode(['success' => false, 'message' => 'Zone not found']);
        exit;
    }
    
    // Generate zone token if not exists
    if (!$zone['zone_token']) {
        $token = 'zone_' . uniqid();
        $stmt = $pdo->prepare("UPDATE zones SET zone_token = ? WHERE id = ?");
        $stmt->execute([$token, $zone_id]);
        $zone['zone_token'] = $token;
    }
    
    $domain = 'up.adstart.click';
    $size = $zone['size'];
    $token = $zone['zone_token'];
    
    // Generate the complete zone code
    $zone_code = "<!-- AdStart Zone Code - {$zone['name']} -->
<div id=\"adzone-{$token}\" data-domain=\"https://{$domain}\" style=\"width:{$size}px; height:" . explode('x', $size)[1] . "px; border:1px solid #ddd; background:#f5f5f5; display:flex; align-items:center; justify-content:center; color:#666; font-family:Arial,sans-serif; font-size:14px;\">
    Loading ad...
</div>
<script>
(function() {
    const container = document.getElementById('adzone-{$token}');
    const domain = container.dataset.domain;
    const zoneToken = '{$token}';
    const size = '{$size}';
    
    // Extract width and height
    const [width, height] = size.split('x');
    container.style.width = width + 'px';
    container.style.height = height + 'px';
    
    // Request ad
    fetch(domain + '/api/rtb/request.php?token=' + zoneToken + '&format=banner&size=' + size)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                container.innerHTML = data.content;
                
                // Track impression
                fetch(domain + '/api/track/impression.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'zone_id=' + zoneToken + '&campaign_id=' + data.campaign_id + '&type=' + data.type
                });
                
                // Add click tracking
                if (data.click_url) {
                    const links = container.querySelectorAll('a');
                    links.forEach(link => {
                        link.addEventListener('click', function() {
                            fetch(domain + '/api/track/click.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'zone_id=' + zoneToken + '&campaign_id=' + data.campaign_id + '&type=' + data.type
                            });
                        });
                    });
                }
            } else {
                container.innerHTML = '<div style=\"display:flex;align-items:center;justify-content:center;height:100%;color:#999;font-size:12px;\">No ads available</div>';
            }
        })
        .catch(error => {
            console.error('AdZone error:', error);
            container.innerHTML = '<div style=\"display:flex;align-items:center;justify-content:center;height:100%;color:#cc0000;font-size:12px;\">Ad loading failed</div>';
        });
})();
</script>
<!-- End AdStart Zone Code -->";
    
    // Update zone code in database
    $stmt = $pdo->prepare("UPDATE zones SET zone_code = ? WHERE id = ?");
    $stmt->execute([$zone_code, $zone_id]);
    
    echo json_encode([
        'success' => true,
        'zone_code' => $zone_code,
        'preview_url' => "https://{$domain}/api/rtb/request.php?token={$token}&format=banner&size={$size}&test=1"
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>