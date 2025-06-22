<?php
/**
 * RTB Request API - Fixed for correct column names
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
    $token = $_GET['token'] ?? $_POST['token'] ?? '';
    $format = $_GET['format'] ?? $_POST['format'] ?? 'banner';
    $size = $_GET['size'] ?? $_POST['size'] ?? '300x250';
    $test = isset($_GET['test']) || isset($_POST['test']);
    $preview = isset($_GET['preview']) || isset($_POST['preview']);
    $debug = isset($_GET['debug']) || isset($_POST['debug']);
    
    if (empty($token)) {
        throw new Exception('Zone token is required');
    }
    
    // Get zone information
    $stmt = $pdo->prepare("SELECT z.*, 
                           w.name as website_name, 
                           w.url as website_url, 
                           w.category_id,
                           c.name as category_name
                           FROM zones z 
                           LEFT JOIN websites w ON z.website_id = w.id 
                           LEFT JOIN categories c ON w.category_id = c.id
                           WHERE z.zone_token = ?");
    $stmt->execute([$token]);
    $zone = $stmt->fetch();
    
    if (!$zone) {
        throw new Exception('Zone not found with token: ' . $token);
    }
    
    if ($zone['status'] !== 'active') {
        throw new Exception('Zone is not active. Status: ' . $zone['status']);
    }
    
    $dimensions = explode('x', $size);
    $width = intval($dimensions[0] ?? 300);
    $height = intval($dimensions[1] ?? 250);
    
    if ($test || $preview) {
        $sample_ad = generateSampleAd($zone, $width, $height, $test);
        echo json_encode([
            'success' => true,
            'content' => $sample_ad,
            'campaign_id' => 'test_campaign',
            'type' => 'test',
            'zone_token' => $token,
            'cpm' => 1.50,
            'cpc' => 0.25
        ]);
        exit;
    }
    
    $ad_content = findMatchingAd($pdo, $zone, $width, $height, $debug);
    
    if ($ad_content) {
        updateZoneStats($pdo, $zone['id'], 'impression');
        
        $response = [
            'success' => true,
            'content' => $ad_content['content'],
            'campaign_id' => $ad_content['campaign_id'],
            'type' => $ad_content['type'],
            'zone_token' => $token,
            'cpm' => $ad_content['cpm'],
            'cpc' => $ad_content['cpc'],
            'click_url' => $ad_content['click_url'] ?? null
        ];
        
        if ($debug) {
            $response['debug_info'] = [
                'zone' => $zone,
                'search_criteria' => [
                    'category_id' => $zone['category_id'],
                    'width' => $width,
                    'height' => $height
                ],
                'found_campaign' => $ad_content['debug_info'] ?? null
            ];
        }
        
        echo json_encode($response);
    } else {
        $debug_info = [];
        if ($debug) {
            $debug_info = getCampaignDebugInfo($pdo, $zone, $width, $height);
        }
        
        $fallback_ad = generateFallbackAd($zone, $width, $height);
        echo json_encode([
            'success' => true,
            'content' => $fallback_ad,
            'campaign_id' => 'fallback',
            'type' => 'fallback',
            'zone_token' => $token,
            'message' => 'No matching campaigns found',
            'debug_info' => $debug ? $debug_info : null
        ]);
    }
    
} catch (Exception $e) {
    error_log("RTB Request Error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'token' => $token ?? 'missing',
            'format' => $format ?? 'missing',
            'size' => $size ?? 'missing'
        ]
    ]);
}

function findMatchingAd($pdo, $zone, $width, $height, $debug = false) {
    $rtb_ad = findRTBCampaign($pdo, $zone, $width, $height, $debug);
    if ($rtb_ad) return $rtb_ad;
    
    $ron_ad = findRONCampaign($pdo, $zone, $width, $height, $debug);
    if ($ron_ad) return $ron_ad;
    
    return null;
}

function findRTBCampaign($pdo, $zone, $width, $height, $debug = false) {
    try {
        $stmt = $pdo->prepare("SELECT c.*, cr.*, 
                               c.name as campaign_name,
                               c.cpm_bid,
                               c.cpc_bid,
                               c.landing_url,
                               cr.html_content
                               FROM rtb_campaigns c
                               INNER JOIN creatives cr ON c.id = cr.campaign_id 
                               WHERE c.status = 'active' 
                               AND cr.campaign_type = 'rtb'
                               AND cr.status = 'active'
                               AND (c.total_budget - c.budget_spent) > 0
                               AND (c.category_id = ? OR c.category_id IS NULL)
                               AND cr.width = ? AND cr.height = ?
                               ORDER BY c.cpm_bid DESC
                               LIMIT 1");
        
        $stmt->execute([$zone['category_id'], $width, $height]);
        $campaign = $stmt->fetch();
        
        if ($debug) {
            error_log("RTB Query - Category: {$zone['category_id']}, Size: {$width}x{$height}, Found: " . ($campaign ? 'YES' : 'NO'));
        }
        
        if ($campaign) {
            return [
                'content' => $campaign['html_content'] ?: generateDefaultAdContent($campaign, 'rtb'),
                'campaign_id' => $campaign['id'],
                'type' => 'rtb',
                'cpm' => $campaign['cpm_bid'],
                'cpc' => $campaign['cpc_bid'] ?? 0,
                'click_url' => $campaign['landing_url'],
                'debug_info' => $debug ? $campaign : null
            ];
        }
    } catch (Exception $e) {
        if ($debug) error_log("RTB Error: " . $e->getMessage());
    }
    
    return null;
}

function findRONCampaign($pdo, $zone, $width, $height, $debug = false) {
    try {
        $stmt = $pdo->prepare("SELECT c.*, cr.*, 
                               c.name as campaign_name,
                               c.cpm_bid,
                               c.cpc_bid,
                               c.landing_url,
                               cr.html_content
                               FROM ron_campaigns c
                               INNER JOIN creatives cr ON c.id = cr.campaign_id 
                               WHERE c.status = 'active' 
                               AND cr.campaign_type = 'ron'
                               AND cr.status = 'active'
                               AND (c.total_budget - c.budget_spent) > 0
                               AND (c.category_id = ? OR c.category_id IS NULL)
                               AND cr.width = ? AND cr.height = ?
                               ORDER BY c.cpm_bid DESC
                               LIMIT 1");
        
        $stmt->execute([$zone['category_id'], $width, $height]);
        $campaign = $stmt->fetch();
        
        if ($debug) {
            error_log("RON Query - Category: {$zone['category_id']}, Size: {$width}x{$height}, Found: " . ($campaign ? 'YES' : 'NO'));
        }
        
        if ($campaign) {
            return [
                'content' => $campaign['html_content'] ?: generateDefaultAdContent($campaign, 'ron'),
                'campaign_id' => $campaign['id'],
                'type' => 'ron',
                'cpm' => $campaign['cmp_bid'],
                'cpc' => $campaign['cpc_bid'] ?? 0,
                'click_url' => $campaign['landing_url'],
                'debug_info' => $debug ? $campaign : null
            ];
        }
    } catch (Exception $e) {
        if ($debug) error_log("RON Error: " . $e->getMessage());
    }
    
    return null;
}

function generateDefaultAdContent($campaign, $type) {
    $color = $type === 'rtb' ? '#007bff' : '#28a745';
    $title = $campaign['campaign_name'] ?? ($type === 'rtb' ? 'RTB Campaign' : 'RON Campaign');
    $url = $campaign['landing_url'] ?? '#';
    
    return "<div style='background:{$color};color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;'>
                <h4 style='margin:0 0 8px 0;'>{$title}</h4>
                <p style='margin:0 0 12px 0;font-size:12px;'>Click to learn more!</p>
                <a href='{$url}' target='_blank' style='color:yellow;text-decoration:none;'>Learn More →</a>
            </div>";
}

function getCampaignDebugInfo($pdo, $zone, $width, $height) {
    $debug = ['search_criteria' => ['zone_id' => $zone['id'], 'category_id' => $zone['category_id'], 'width' => $width, 'height' => $height]];
    
    try {
        $stmt = $pdo->query("SELECT 'rtb' as type, COUNT(*) as count FROM rtb_campaigns WHERE status = 'active' UNION SELECT 'ron', COUNT(*) FROM ron_campaigns WHERE status = 'active'");
        $debug['campaign_counts'] = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT campaign_type, width, height, COUNT(*) as count FROM creatives WHERE status = 'active' GROUP BY campaign_type, width, height");
        $debug['creative_counts'] = $stmt->fetchAll();
    } catch (Exception $e) {
        $debug['error'] = $e->getMessage();
    }
    
    return $debug;
}

function generateSampleAd($zone, $width, $height, $test = false) {
    $type = $test ? 'TEST MODE' : 'PREVIEW';
    return "<div style='background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:20px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;'>
                <div style='font-size:18px;margin-bottom:10px;'>🚀</div>
                <h4 style='margin:0 0 10px 0;font-weight:bold;'>{$type}</h4>
                <p style='margin:0 0 5px 0;font-size:12px;'>Zone: {$zone['name']}</p>
                <p style='margin:0 0 5px 0;font-size:12px;'>Size: {$width}×{$height}</p>
                <small style='font-size:9px;opacity:0.6;'>{$zone['zone_token']}</small>
            </div>";
}

function generateFallbackAd($zone, $width, $height) {
    return "<div style='background:#f8f9fa;border:2px dashed #dee2e6;color:#6c757d;padding:20px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;'>
                <div style='font-size:24px;margin-bottom:10px;'>📢</div>
                <div style='font-size:14px;margin-bottom:8px;font-weight:bold;'>Your Ad Here</div>
                <div style='font-size:11px;margin-bottom:8px;'>{$width}×{$height}</div>
                <div style='font-size:10px;'>Contact us for advertising</div>
            </div>";
}

function updateZoneStats($pdo, $zone_id, $type) {
    try {
        if ($type === 'impression') {
            $stmt = $pdo->prepare("UPDATE zones SET impressions_today = impressions_today + 1, impressions_total = impressions_total + 1 WHERE id = ?");
            $stmt->execute([$zone_id]);
        }
    } catch (Exception $e) {
        error_log("Update stats error: " . $e->getMessage());
    }
}
?>