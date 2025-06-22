<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'recreate_all') {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Drop existing tables if they exist (ignore errors)
            try {
                $pdo->exec("DROP TABLE IF EXISTS creatives");
                $pdo->exec("DROP TABLE IF EXISTS rtb_campaigns");  
                $pdo->exec("DROP TABLE IF EXISTS ron_campaigns");
            } catch (Exception $e) {
                // Ignore drop errors
            }
            
            // Create RTB Campaigns table
            $pdo->exec("CREATE TABLE rtb_campaigns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                landing_url VARCHAR(500),
                cpm_bid DECIMAL(10,4) DEFAULT 0.0000,
                cpc_bid DECIMAL(10,4) DEFAULT 0.0000,
                daily_budget DECIMAL(10,2) DEFAULT 0.00,
                total_budget DECIMAL(10,2) DEFAULT 0.00,
                budget_spent DECIMAL(10,2) DEFAULT 0.00,
                category_id INT,
                target_countries TEXT,
                target_devices TEXT,
                target_os TEXT,
                frequency_cap INT DEFAULT 0,
                status ENUM('active', 'inactive', 'paused') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status_budget (status, total_budget, budget_spent),
                INDEX idx_category (category_id)
            )");
            
            // Create RON Campaigns table
            $pdo->exec("CREATE TABLE ron_campaigns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                landing_url VARCHAR(500),
                cpm_bid DECIMAL(10,4) DEFAULT 0.0000,
                cpc_bid DECIMAL(10,4) DEFAULT 0.0000,
                daily_budget DECIMAL(10,2) DEFAULT 0.00,
                total_budget DECIMAL(10,2) DEFAULT 0.00,
                budget_spent DECIMAL(10,2) DEFAULT 0.00,
                category_id INT,
                target_countries TEXT,
                target_devices TEXT,
                target_os TEXT,
                frequency_cap INT DEFAULT 0,
                status ENUM('active', 'inactive', 'paused') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status_budget (status, total_budget, budget_spent),
                INDEX idx_category (category_id)
            )");
            
            // Create Creatives table
            $pdo->exec("CREATE TABLE creatives (
                id INT AUTO_INCREMENT PRIMARY KEY,
                campaign_id INT NOT NULL,
                campaign_type ENUM('rtb', 'ron') NOT NULL,
                name VARCHAR(255) NOT NULL,
                creative_type ENUM('banner', 'text', 'video', 'native') DEFAULT 'banner',
                width INT NOT NULL,
                height INT NOT NULL,
                image_url VARCHAR(500),
                html_content TEXT,
                title VARCHAR(255),
                description TEXT,
                call_to_action VARCHAR(100),
                click_url VARCHAR(500),
                impression_tracking_url VARCHAR(500),
                click_tracking_url VARCHAR(500),
                status ENUM('active', 'inactive', 'pending', 'rejected') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_campaign (campaign_id, campaign_type),
                INDEX idx_size_status (width, height, status),
                INDEX idx_campaign_type_size (campaign_type, width, height, status)
            )");
            
            // Insert sample RTB campaign
            $stmt = $pdo->prepare("INSERT INTO rtb_campaigns (
                name, description, landing_url, cpm_bid, cpc_bid, 
                daily_budget, total_budget, budget_spent, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                'Premium RTB Campaign',
                'High-quality RTB campaign with premium ad placements and targeted audience',
                'https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium',
                4.50, // cpm_bid
                0.45, // cpc_bid
                200.00, // daily_budget
                2000.00, // total_budget
                0.00, // budget_spent
                'active'
            ]);
            $rtb_campaign_id = $pdo->lastInsertId();
            
            // Insert sample RON campaign  
            $stmt = $pdo->prepare("INSERT INTO ron_campaigns (
                name, description, landing_url, cpm_bid, cpc_bid, 
                daily_budget, total_budget, budget_spent, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                'Network RON Campaign',
                'Wide-reach RON campaign for maximum exposure across the advertising network',
                'https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network',
                3.25, // cpm_bid
                0.32, // cpc_bid
                150.00, // daily_budget
                1500.00, // total_budget
                0.00, // budget_spent
                'active'
            ]);
            $ron_campaign_id = $pdo->lastInsertId();
            
            // Create creatives for multiple sizes
            $ad_sizes = [
                '300x250' => [300, 250, 'Medium Rectangle'],
                '728x90' => [728, 90, 'Leaderboard'],
                '320x50' => [320, 50, 'Mobile Banner'],
                '300x600' => [300, 600, 'Half Page'],
                '970x250' => [970, 250, 'Billboard'],
                '160x600' => [160, 600, 'Wide Skyscraper'],
                '336x280' => [336, 280, 'Large Rectangle'],
                '468x60' => [468, 60, 'Banner']
            ];
            
            $creative_stmt = $pdo->prepare("INSERT INTO creatives (
                campaign_id, campaign_type, name, creative_type, 
                width, height, html_content, title, description, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $created_creatives = 0;
            
            foreach ($ad_sizes as $size => $data) {
                list($width, $height, $size_name) = $data;
                
                // RTB Creative with enhanced styling
                $rtb_html = "<div style='background:linear-gradient(135deg, #007bff 0%, #0056b3 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(0,123,255,0.3);position:relative;overflow:hidden;'>
                    <div style='position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #ffd700, #ffed4e);'></div>
                    <div style='font-size:22px;margin-bottom:8px;'>💎</div>
                    <h4 style='margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);'>Premium RTB Deal</h4>
                    <p style='margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;'>Exclusive premium offers! Limited time - Act now!</p>
                    <div style='margin-top:auto;'>
                        <a href='https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium' target='_blank' rel='noopener' style='display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);'>Get Deal →</a>
                    </div>
                    <small style='font-size:9px;opacity:0.6;margin-top:8px;'>{$size} RTB</small>
                </div>";
                
                $creative_stmt->execute([
                    $rtb_campaign_id,
                    'rtb',
                    "RTB Creative {$size} ({$size_name})",
                    'banner',
                    $width,
                    $height,
                    $rtb_html,
                    'Premium RTB Deal',
                    'Exclusive premium offers with limited time opportunity',
                    'active'
                ]);
                $created_creatives++;
                
                // RON Creative with enhanced styling
                $ron_html = "<div style='background:linear-gradient(135deg, #28a745 0%, #1e7e34 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(40,167,69,0.3);position:relative;overflow:hidden;'>
                    <div style='position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #32cd32, #98fb98);'></div>
                    <div style='font-size:22px;margin-bottom:8px;'>🚀</div>
                    <h4 style='margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);'>Network Special</h4>
                    <p style='margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;'>Amazing network deals! Don't miss these offers.</p>
                    <div style='margin-top:auto;'>
                        <a href='https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network' target='_blank' rel='noopener' style='display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);'>View Deals →</a>
                    </div>
                    <small style='font-size:9px;opacity:0.6;margin-top:8px;'>{$size} RON</small>
                </div>";
                
                $creative_stmt->execute([
                    $ron_campaign_id,
                    'ron',
                    "RON Creative {$size} ({$size_name})",
                    'banner',
                    $width,
                    $height,
                    $ron_html,
                    'Network Special',
                    'Amazing network deals with incredible offers',
                    'active'
                ]);
                $created_creatives++;
            }
            
            // Commit transaction
            $pdo->commit();
            
            $message = '<div class="alert alert-success">
                <i class="fas fa-check me-2"></i>
                <strong>Tables recreated successfully!</strong><br>
                Created 2 campaigns with ' . $created_creatives . ' total creatives (' . count($ad_sizes) . ' sizes each).<br>
                <small>RTB Campaign ID: ' . $rtb_campaign_id . ' | RON Campaign ID: ' . $ron_campaign_id . '</small>
            </div>';
            
        } catch (Exception $e) {
            // Only rollback if there's an active transaction
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// Check current table status with corrected queries
$table_status = [];
$tables = ['rtb_campaigns', 'ron_campaigns', 'creatives'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
        $count = $stmt->fetchColumn();
        $table_status[$table] = [
            'exists' => true,
            'count' => $count
        ];
        
        // Get sample data if table exists and has records
        if ($count > 0) {
            if ($table === 'creatives') {
                $stmt = $pdo->query("SELECT campaign_type, width, height, COUNT(*) as creative_count FROM {$table} GROUP BY campaign_type, width, height LIMIT 5");
                $table_status[$table]['samples'] = $stmt->fetchAll();
            } else {
                // Fixed the column name here - use cpm_bid instead of cmp_bid
                $stmt = $pdo->query("SELECT id, name, status, cpm_bid FROM {$table} LIMIT 3");
                $table_status[$table]['samples'] = $stmt->fetchAll();
            }
        }
    } catch (Exception $e) {
        $table_status[$table] = [
            'exists' => false,
            'error' => $e->getMessage()
        ];
    }
}

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-database me-2"></i>Campaign Tables - Fixed</h2>
            <div>
                <a href="sample-campaigns.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-tools me-1"></i>Tools
                </a>
                <a href="../zone.php" class="btn btn-outline-primary">
                    <i class="fas fa-th-large me-1"></i>Zones
                </a>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Success Message for Created Tables -->
        <?php if (strpos($message, 'success') !== false): ?>
        <div class="alert alert-info">
            <i class="fas fa-rocket me-2"></i>
            <strong>Ready to Test!</strong> The campaign tables have been created with proper structure. You can now test the API to see real ads instead of placeholders.
        </div>
        <?php endif; ?>

        <!-- Current Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Database Status</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($table_status as $table => $status): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="text-center"><?php echo ucfirst(str_replace('_', ' ', $table)); ?></h6>
                                <div class="text-center">
                                    <?php if ($status['exists']): ?>
                                        <span class="badge bg-success fs-6">✓ EXISTS</span>
                                        <p class="mb-2 mt-2"><strong><?php echo $status['count']; ?></strong> records</p>
                                        
                                        <?php if (!empty($status['samples'])): ?>
                                        <div class="text-start">
                                            <small class="text-muted">Samples:</small>
                                            <?php foreach (array_slice($status['samples'], 0, 3) as $sample): ?>
                                            <div class="small">
                                                <?php if ($table === 'creatives'): ?>
                                                    • <?php echo strtoupper($sample['campaign_type']); ?> <?php echo $sample['width']; ?>×<?php echo $sample['height']; ?> (<?php echo $sample['creative_count']; ?>)
                                                <?php else: ?>
                                                    • <?php echo htmlspecialchars($sample['name']); ?> ($<?php echo number_format($sample['cpm_bid'], 2); ?>)
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-danger fs-6">✗ MISSING</span>
                                        <p class="mb-0 mt-2 small text-muted"><?php echo $status['error']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recreate Action -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-sync-alt me-2"></i>Create/Recreate Campaign Tables</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <p><strong>This will create:</strong></p>
                        <ul>
                            <li>✓ RTB & RON campaign tables with proper structure</li>
                            <li>✓ Creatives table with campaign relationships</li>
                            <li>✓ Sample campaigns with realistic bidding ($4.50 RTB, $3.25 RON)</li>
                            <li>✓ 16 sample creatives (8 sizes × 2 campaign types)</li>
                            <li>✓ Performance indexes for fast API response</li>
                        </ul>
                    </div>
                    <div class="col-md-4 text-center">
                        <form method="POST">
                            <input type="hidden" name="action" value="recreate_all">
                            <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Create campaign tables with sample data?')">
                                <i class="fas fa-sync-alt me-2"></i>Create Tables
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test API Section -->
        <?php 
        $all_tables_exist = true;
        foreach ($table_status as $status) {
            if (!$status['exists'] || $status['count'] == 0) {
                $all_tables_exist = false;
                break;
            }
        }
        
        if ($all_tables_exist || strpos($message, 'success') !== false): ?>
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-play me-2"></i>Test API - Real Ads Available!</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Campaign data is ready!</strong> The API should now serve real ads instead of placeholders.
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="input-group mb-3">
                            <span class="input-group-text">Zone Token</span>
                            <input type="text" class="form-control" id="testToken" value="zone_685844b55310f_b1aeef91">
                            <select class="form-select" id="testSize" style="max-width: 150px;">
                                <option value="300x250">300×250</option>
                                <option value="728x90">728×90</option>
                                <option value="320x50">320×50</option>
                                <option value="300x600">300×600</option>
                                <option value="970x250">970×250</option>
                            </select>
                            <button class="btn btn-success" onclick="testAPI()">
                                <i class="fas fa-play me-1"></i>Test API
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary btn-sm" onclick="testAPIMode('normal')">Normal</button>
                            <button class="btn btn-outline-warning btn-sm" onclick="testAPIMode('debug')">Debug</button>
                            <button class="btn btn-outline-info btn-sm" onclick="testAPIMode('test')">Test</button>
                        </div>
                    </div>
                </div>
                
                <div id="apiResult" class="mt-3" style="display:none;">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>API Response:</h6>
                            <pre id="apiResponse" class="bg-light p-3 border rounded" style="max-height: 300px; overflow-y: auto; font-size: 11px;"></pre>
                        </div>
                        <div class="col-md-6">
                            <h6>Ad Preview:</h6>
                            <div id="previewContainer" class="border rounded p-3 bg-white" style="display: inline-block; min-height: 100px;">
                                <!-- Ad will be rendered here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Expected result: You should see RTB or RON ads with colored backgrounds instead of gray placeholder ads.
                    </small>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function testAPI() {
    testAPIMode('normal');
}

function testAPIMode(mode) {
    const token = document.getElementById('testToken').value;
    const size = document.getElementById('testSize').value;
    let url = `https://up.adstart.click/api/rtb/request.php?token=${token}&format=banner&size=${size}`;
    
    if (mode !== 'normal') {
        url += `&${mode}=1`;
    }
    
    document.getElementById('apiResult').style.display = 'block';
    document.getElementById('apiResponse').textContent = 'Loading...';
    
    const previewContainer = document.getElementById('previewContainer');
    previewContainer.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById('apiResponse').textContent = JSON.stringify(data, null, 2);
            
            // Show ad preview
            if (data.success && data.content) {
                const [width, height] = size.split('x');
                previewContainer.style.width = width + 'px';
                previewContainer.style.height = height + 'px';
                previewContainer.innerHTML = data.content;
                
                // Show success message if it's a real ad (not fallback)
                if (data.type !== 'fallback' && data.type !== 'test') {
                    setTimeout(() => {
                        alert('Success! Real ' + data.type.toUpperCase() + ' ad is being served (CPM: $' + data.cpm + ')');
                    }, 1000);
                }
            } else {
                previewContainer.innerHTML = '<div class="text-center p-3 text-danger">Ad failed to load</div>';
            }
        })
        .catch(error => {
            document.getElementById('apiResponse').textContent = 'Error: ' + error.message;
            previewContainer.innerHTML = '<div class="text-center p-3 text-danger">Network error</div>';
        });
}

// Auto-test if tables were just created
<?php if (strpos($message, 'success') !== false): ?>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        testAPI();
    }, 1000);
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>