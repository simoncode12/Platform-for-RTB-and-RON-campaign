<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$step_completed = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'setup_campaign_system') {
        try {
            // No transaction needed for individual operations
            $errors = [];
            $success_messages = [];
            
            // Step 1: Drop and recreate tables
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                $pdo->exec("DROP TABLE IF EXISTS creatives");
                $pdo->exec("DROP TABLE IF EXISTS rtb_campaigns");  
                $pdo->exec("DROP TABLE IF EXISTS ron_campaigns");
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                $success_messages[] = "✓ Old tables removed";
            } catch (Exception $e) {
                $errors[] = "Error dropping tables: " . $e->getMessage();
            }
            
            // Step 2: Create RTB Campaigns table
            try {
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
                    status ENUM('active', 'inactive', 'paused') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_status_budget (status, total_budget, budget_spent)
                )");
                $success_messages[] = "✓ RTB campaigns table created";
            } catch (Exception $e) {
                $errors[] = "Error creating RTB table: " . $e->getMessage();
            }
            
            // Step 3: Create RON Campaigns table
            try {
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
                    status ENUM('active', 'inactive', 'paused') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_status_budget (status, total_budget, budget_spent)
                )");
                $success_messages[] = "✓ RON campaigns table created";
            } catch (Exception $e) {
                $errors[] = "Error creating RON table: " . $e->getMessage();
            }
            
            // Step 4: Create Creatives table
            try {
                $pdo->exec("CREATE TABLE creatives (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    campaign_id INT NOT NULL,
                    campaign_type ENUM('rtb', 'ron') NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    creative_type ENUM('banner', 'text', 'video', 'native') DEFAULT 'banner',
                    width INT NOT NULL,
                    height INT NOT NULL,
                    html_content TEXT,
                    title VARCHAR(255),
                    description TEXT,
                    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_campaign_type_size (campaign_type, width, height, status)
                )");
                $success_messages[] = "✓ Creatives table created";
            } catch (Exception $e) {
                $errors[] = "Error creating creatives table: " . $e->getMessage();
            }
            
            // Step 5: Insert sample RTB campaign
            try {
                $stmt = $pdo->prepare("INSERT INTO rtb_campaigns (
                    name, description, landing_url, cpm_bid, cpc_bid, 
                    daily_budget, total_budget, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    'High-Value RTB Campaign',
                    'Premium RTB campaign with high-converting offers',
                    'https://example.com/rtb-offers?source=adstart',
                    5.25, // cpm_bid
                    0.52, // cpc_bid
                    250.00, // daily_budget
                    2500.00, // total_budget
                    'active'
                ]);
                $rtb_id = $pdo->lastInsertId();
                $success_messages[] = "✓ RTB campaign created (ID: $rtb_id)";
            } catch (Exception $e) {
                $errors[] = "Error creating RTB campaign: " . $e->getMessage();
            }
            
            // Step 6: Insert sample RON campaign
            try {
                $stmt = $pdo->prepare("INSERT INTO ron_campaigns (
                    name, description, landing_url, cpm_bid, cpc_bid, 
                    daily_budget, total_budget, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    'Wide-Reach RON Campaign',
                    'Broad network campaign for maximum exposure',
                    'https://example.org/ron-deals?source=adstart',
                    3.75, // cpm_bid
                    0.37, // cpc_bid
                    180.00, // daily_budget
                    1800.00, // total_budget
                    'active'
                ]);
                $ron_id = $pdo->lastInsertId();
                $success_messages[] = "✓ RON campaign created (ID: $ron_id)";
            } catch (Exception $e) {
                $errors[] = "Error creating RON campaign: " . $e->getMessage();
            }
            
            // Step 7: Create creatives for standard ad sizes
            if (isset($rtb_id) && isset($ron_id)) {
                $sizes = [
                    '300x250' => [300, 250],
                    '728x90' => [728, 90], 
                    '320x50' => [320, 50],
                    '300x600' => [300, 600],
                    '970x250' => [970, 250]
                ];
                
                $creative_count = 0;
                $stmt = $pdo->prepare("INSERT INTO creatives (
                    campaign_id, campaign_type, name, creative_type, 
                    width, height, html_content, title, description, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                foreach ($sizes as $size => $dims) {
                    // RTB Creative
                    $rtb_html = "<div style='background:linear-gradient(135deg, #007bff, #0056b3);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:6px;'>
                        <div style='font-size:20px;margin-bottom:8px;'>💎</div>
                        <h4 style='margin:0 0 8px 0;font-weight:bold;'>Premium RTB Deal</h4>
                        <p style='margin:0 0 10px 0;font-size:12px;opacity:0.9;'>Exclusive offers available now!</p>
                        <a href='https://example.com/rtb-offers?source=adstart' target='_blank' style='color:#ffd700;text-decoration:none;font-weight:bold;'>Get Deal →</a>
                        <small style='font-size:9px;opacity:0.7;margin-top:5px;'>{$size}</small>
                    </div>";
                    
                    try {
                        $stmt->execute([
                            $rtb_id, 'rtb', "RTB Creative {$size}", 'banner',
                            $dims[0], $dims[1], $rtb_html,
                            'Premium RTB Deal', 'Exclusive offers available', 'active'
                        ]);
                        $creative_count++;
                    } catch (Exception $e) {
                        $errors[] = "Error creating RTB creative {$size}: " . $e->getMessage();
                    }
                    
                    // RON Creative
                    $ron_html = "<div style='background:linear-gradient(135deg, #28a745, #1e7e34);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:6px;'>
                        <div style='font-size:20px;margin-bottom:8px;'>🚀</div>
                        <h4 style='margin:0 0 8px 0;font-weight:bold;'>Network Special</h4>
                        <p style='margin:0 0 10px 0;font-size:12px;opacity:0.9;'>Amazing deals across our network!</p>
                        <a href='https://example.org/ron-deals?source=adstart' target='_blank' style='color:#90ee90;text-decoration:none;font-weight:bold;'>View Deals →</a>
                        <small style='font-size:9px;opacity:0.7;margin-top:5px;'>{$size}</small>
                    </div>";
                    
                    try {
                        $stmt->execute([
                            $ron_id, 'ron', "RON Creative {$size}", 'banner',
                            $dims[0], $dims[1], $ron_html,
                            'Network Special', 'Amazing network deals', 'active'
                        ]);
                        $creative_count++;
                    } catch (Exception $e) {
                        $errors[] = "Error creating RON creative {$size}: " . $e->getMessage();
                    }
                }
                
                $success_messages[] = "✓ Created {$creative_count} creatives for " . count($sizes) . " ad sizes";
            }
            
            // Build result message
            if (empty($errors)) {
                $message = '<div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>Campaign System Setup Complete!</h5>
                    <ul class="mb-0">' . implode('', array_map(function($msg) { return "<li>$msg</li>"; }, $success_messages)) . '</ul>
                </div>';
                $step_completed = true;
            } else {
                $message = '<div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Setup Completed with Some Issues</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Successful:</strong>
                            <ul>' . implode('', array_map(function($msg) { return "<li>$msg</li>"; }, $success_messages)) . '</ul>
                        </div>
                        <div class="col-md-6">
                            <strong>Errors:</strong>
                            <ul class="text-danger">' . implode('', array_map(function($msg) { return "<li>$msg</li>"; }, $errors)) . '</ul>
                        </div>
                    </div>
                </div>';
            }
            
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Setup failed: ' . $e->getMessage() . '</div>';
        }
    }
}

// Check current status
$current_status = [];
try {
    // Check tables
    $tables = ['rtb_campaigns', 'ron_campaigns', 'creatives'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
            $current_status[$table] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $current_status[$table] = 'missing';
        }
    }
} catch (Exception $e) {
    $current_status['error'] = $e->getMessage();
}

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-rocket me-2"></i>Quick Campaign Setup</h2>
            <div>
                <a href="../zone.php" class="btn btn-outline-primary">
                    <i class="fas fa-th-large me-1"></i>Back to Zones
                </a>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Current Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Current Status</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <?php foreach (['rtb_campaigns', 'ron_campaigns', 'creatives'] as $table): ?>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6><?php echo ucwords(str_replace('_', ' ', $table)); ?></h6>
                                <?php if (isset($current_status[$table]) && $current_status[$table] !== 'missing'): ?>
                                    <span class="badge bg-success fs-6">✓ <?php echo $current_status[$table]; ?> records</span>
                                <?php else: ?>
                                    <span class="badge bg-danger fs-6">✗ Missing</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Setup Action -->
        <?php if (!$step_completed): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-magic me-2"></i>One-Click Campaign Setup</h5>
            </div>
            <div class="card-body">
                <p>This will set up the complete campaign system for serving real ads:</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-database me-1"></i>Database Setup</h6>
                        <ul>
                            <li>Create RTB & RON campaign tables</li>
                            <li>Create creatives table with relationships</li>
                            <li>Add performance indexes</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-ad me-1"></i>Sample Data</h6>
                        <ul>
                            <li>1 RTB Campaign ($5.25 CPM)</li>
                            <li>1 RON Campaign ($3.75 CPM)</li>
                            <li>10 creatives (5 sizes × 2 types)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <form method="POST">
                        <input type="hidden" name="action" value="setup_campaign_system">
                        <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Set up the campaign system? This will create/replace campaign tables.')">
                            <i class="fas fa-magic me-2"></i>Setup Campaign System
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Test Section -->
        <?php if ($step_completed || (isset($current_status['rtb_campaigns']) && $current_status['rtb_campaigns'] > 0)): ?>
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-play me-2"></i>Test Real Ads</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Campaign system is ready!</strong> You should now see real ads instead of placeholders.
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">Zone Token</span>
                            <input type="text" class="form-control" id="testToken" value="zone_685844b55310f_b1aeef91">
                            <select class="form-select" id="testSize" style="max-width: 140px;">
                                <option value="300x250">300×250</option>
                                <option value="728x90">728×90</option>
                                <option value="320x50">320×50</option>
                                <option value="300x600">300×600</option>
                                <option value="970x250">970×250</option>
                            </select>
                            <button class="btn btn-success" onclick="testAPI()">
                                <i class="fas fa-play me-1"></i>Test
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="btn-group w-100" role="group">
                            <button class="btn btn-outline-primary btn-sm" onclick="testMode('normal')">Normal</button>
                            <button class="btn btn-outline-warning btn-sm" onclick="testMode('debug')">Debug</button>
                        </div>
                    </div>
                </div>
                
                <div id="testResults" class="mt-4" style="display:none;">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>API Response</h6>
                            <pre id="apiResponse" class="bg-light p-3 border rounded small" style="max-height: 250px; overflow-y: auto;"></pre>
                        </div>
                        <div class="col-md-6">
                            <h6>Ad Preview</h6>
                            <div id="adPreview" class="border rounded p-2 bg-white text-center" style="min-height: 200px;">
                                <!-- Ad content here -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div id="testResult" class="alert" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function testAPI() {
    testMode('normal');
}

function testMode(mode) {
    const token = document.getElementById('testToken').value;
    const size = document.getElementById('testSize').value;
    
    if (!token) {
        alert('Please enter a zone token');
        return;
    }
    
    let url = `https://up.adstart.click/api/rtb/request.php?token=${token}&format=banner&size=${size}`;
    if (mode !== 'normal') {
        url += `&${mode}=1`;
    }
    
    document.getElementById('testResults').style.display = 'block';
    document.getElementById('apiResponse').textContent = 'Loading...';
    document.getElementById('adPreview').innerHTML = '<div class="p-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById('apiResponse').textContent = JSON.stringify(data, null, 2);
            
            // Show ad preview
            if (data.success && data.content) {
                const [width, height] = size.split('x');
                const preview = document.getElementById('adPreview');
                preview.style.width = width + 'px';
                preview.style.height = height + 'px';
                preview.innerHTML = data.content;
                
                // Show result message
                const resultDiv = document.getElementById('testResult');
                if (data.type === 'fallback') {
                    resultDiv.className = 'alert alert-warning';
                    resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Fallback Ad:</strong> No matching campaigns found. Check if campaigns exist and have budget.';
                } else if (data.type === 'rtb' || data.type === 'ron') {
                    resultDiv.className = 'alert alert-success';
                    resultDiv.innerHTML = `<i class="fas fa-check-circle me-2"></i><strong>Success!</strong> Real ${data.type.toUpperCase()} ad served (CPM: $${data.cpm})`;
                } else {
                    resultDiv.className = 'alert alert-info';
                    resultDiv.innerHTML = `<i class="fas fa-info-circle me-2"></i><strong>Test Mode:</strong> ${data.type} ad displayed`;
                }
                resultDiv.style.display = 'block';
            }
        })
        .catch(error => {
            document.getElementById('apiResponse').textContent = 'Error: ' + error.message;
            document.getElementById('adPreview').innerHTML = '<div class="p-3 text-danger">Error loading ad</div>';
        });
}

// Auto-test after successful setup
<?php if ($step_completed): ?>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        testAPI();
    }, 1500);
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>