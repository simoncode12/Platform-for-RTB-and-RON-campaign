<?php
/**
 * Create sample campaigns for testing
 */

require_once '../config/database.php';

try {
    echo "Creating sample campaigns...\n";
    
    // Create RTB Campaign
    $stmt = $pdo->prepare("INSERT INTO rtb_campaigns (
        name, description, landing_url, bid_cpm, bid_cpc, 
        daily_budget, total_budget, budget_remaining, 
        category_id, status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        'Sample RTB Campaign',
        'This is a sample RTB campaign for testing',
        'https://example.com',
        2.50, // bid_cpm
        0.25, // bid_cpc
        100.00, // daily_budget
        1000.00, // total_budget
        1000.00, // budget_remaining
        null, // category_id (any category)
        'active'
    ]);
    
    $rtb_campaign_id = $pdo->lastInsertId();
    echo "Created RTB Campaign ID: $rtb_campaign_id\n";
    
    // Create RON Campaign
    $stmt = $pdo->prepare("INSERT INTO ron_campaigns (
        name, description, landing_url, bid_cpm, bid_cpc, 
        daily_budget, total_budget, budget_remaining, 
        category_id, status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        'Sample RON Campaign',
        'This is a sample RON campaign for testing',
        'https://example.org',
        1.75, // bid_cpm
        0.15, // bid_cpc
        50.00, // daily_budget
        500.00, // total_budget
        500.00, // budget_remaining
        null, // category_id (any category)
        'active'
    ]);
    
    $ron_campaign_id = $pdo->lastInsertId();
    echo "Created RON Campaign ID: $ron_campaign_id\n";
    
    // Create creatives for different sizes
    $sizes = [
        '300x250' => [300, 250],
        '728x90' => [728, 90],
        '320x50' => [320, 50]
    ];
    
    foreach ($sizes as $size => $dimensions) {
        // RTB Creative
        $stmt = $pdo->prepare("INSERT INTO creatives (
            campaign_id, campaign_type, name, creative_type, 
            width, height, html_content, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $rtb_html = "<div style='background:#007bff;color:white;padding:10px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;'>
                        <h4 style='margin:0 0 5px 0;'>RTB Ad Campaign</h4>
                        <p style='margin:0 0 10px 0;font-size:12px;'>Click for amazing deals!</p>
                        <a href='https://example.com' target='_blank' style='color:yellow;'>Learn More →</a>
                     </div>";
        
        $stmt->execute([
            $rtb_campaign_id,
            'rtb',
            "RTB Creative {$size}",
            'banner',
            $dimensions[0],
            $dimensions[1],
            $rtb_html,
            'active'
        ]);
        
        echo "Created RTB Creative for {$size}\n";
        
        // RON Creative
        $ron_html = "<div style='background:#28a745;color:white;padding:10px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;'>
                        <h4 style='margin:0 0 5px 0;'>RON Ad Campaign</h4>
                        <p style='margin:0 0 10px 0;font-size:12px;'>Special offers available!</p>
                        <a href='https://example.org' target='_blank' style='color:yellow;'>View Offers →</a>
                     </div>";
        
        $stmt->execute([
            $ron_campaign_id,
            'ron',
            "RON Creative {$size}",
            'banner',
            $dimensions[0],
            $dimensions[1],
            $ron_html,
            'active'
        ]);
        
        echo "Created RON Creative for {$size}\n";
    }
    
    echo "\nSample campaigns created successfully!\n";
    echo "Now test with: https://up.adstart.click/api/rtb/request.php?token=YOUR_TOKEN&format=banner&size=300x250&debug=1\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>