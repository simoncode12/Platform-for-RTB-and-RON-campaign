<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

// Check and create tables if they don't exist
try {
    // Create RTB Campaigns table
    $pdo->exec("CREATE TABLE IF NOT EXISTS rtb_campaigns (
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
        status ENUM('active', 'inactive', 'paused') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create RON Campaigns table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ron_campaigns (
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
        status ENUM('active', 'inactive', 'paused') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create Creatives table
    $pdo->exec("CREATE TABLE IF NOT EXISTS creatives (
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
        status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
} catch (Exception $e) {
    $message = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Table creation warning: ' . $e->getMessage() . '</div>';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create_samples') {
        try {
            $pdo->beginTransaction();
            
            // Create RTB Campaign
            $stmt = $pdo->prepare("INSERT INTO rtb_campaigns (
                name, description, landing_url, cpm_bid, cpc_bid, 
                daily_budget, total_budget, budget_spent, 
                category_id, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                'Sample RTB Campaign',
                'This is a sample RTB campaign for testing ad serving',
                'https://example.com/?utm_source=adstart&utm_medium=rtb',
                2.50, // cpm_bid
                0.25, // cpc_bid
                100.00, // daily_budget
                1000.00, // total_budget
                0.00, // budget_spent
                null, // category_id (any category)
                'active'
            ]);
            
            $rtb_campaign_id = $pdo->lastInsertId();
            
            // Create RON Campaign
            $stmt = $pdo->prepare("INSERT INTO ron_campaigns (
                name, description, landing_url, cmp_bid, cpc_bid, 
                daily_budget, total_budget, budget_spent, 
                category_id, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                'Sample RON Campaign',
                'This is a sample RON campaign for testing ad serving',
                'https://example.org/?utm_source=adstart&utm_medium=ron',
                1.75, // cpm_bid
                0.15, // cpc_bid
                50.00, // daily_budget
                500.00, // total_budget
                0.00, // budget_spent
                null, // category_id (any category)
                'active'
            ]);
            
            $ron_campaign_id = $pdo->lastInsertId();
            
            // Create creatives for different sizes
            $sizes = [
                '300x250' => [300, 250, 'Medium Rectangle'],
                '728x90' => [728, 90, 'Leaderboard'],
                '320x50' => [320, 50, 'Mobile Banner'],
                '300x600' => [300, 600, 'Half Page'],
                '970x250' => [970, 250, 'Billboard']
            ];
            
            foreach ($sizes as $size => $data) {
                list($width, $height, $size_name) = $data;
                
                // RTB Creative
                $rtb_html = "<div style='background:linear-gradient(135deg, #007bff, #0056b3);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;'>
                                <div style='font-size:18px;margin-bottom:8px;'>🚀</div>
                                <h4 style='margin:0 0 8px 0;font-size:16px;font-weight:bold;'>RTB Test Campaign</h4>
                                <p style='margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;'>Premium offers and exclusive deals await! Click to discover amazing opportunities.</p>
                                <div style='margin-top:auto;'>
                                    <a href='https://example.com/?utm_source=adstart&utm_medium=rtb' target='_blank' rel='noopener' style='display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);'>Learn More →</a>
                                </div>
                             </div>";
                
                $stmt = $pdo->prepare("INSERT INTO creatives (
                    campaign_id, campaign_type, name, creative_type, 
                    width, height, html_content, title, description, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                $stmt->execute([
                    $rtb_campaign_id,
                    'rtb',
                    "RTB Creative {$size} ({$size_name})",
                    'banner',
                    $width,
                    $height,
                    $rtb_html,
                    'RTB Test Campaign',
                    'Premium offers and exclusive deals',
                    'active'
                ]);
                
                // RON Creative
                $ron_html = "<div style='background:linear-gradient(135deg, #28a745, #1e7e34);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;'>
                                <div style='font-size:18px;margin-bottom:8px;'>💎</div>
                                <h4 style='margin:0 0 8px 0;font-size:16px;font-weight:bold;'>RON Test Campaign</h4>
                                <p style='margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;'>Special network offers available now! Don't miss out on these limited-time deals.</p>
                                <div style='margin-top:auto;'>
                                    <a href='https://example.org/?utm_source=adstart&utm_medium=ron' target='_blank' rel='noopener' style='display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);'>View Offers →</a>
                                </div>
                             </div>";
                
                $stmt->execute([
                    $ron_campaign_id,
                    'ron',
                    "RON Creative {$size} ({$size_name})",
                    'banner',
                    $width,
                    $height,
                    $ron_html,
                    'RON Test Campaign',
                    'Special network offers available',
                    'active'
                ]);
            }
            
            $pdo->commit();
            $message = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Sample campaigns created successfully! Created ' . count($sizes) . ' creatives for each campaign type.</div>';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
        }
    }
    
    if ($action == 'clear_samples') {
        try {
            $pdo->beginTransaction();
            
            // Get sample campaign IDs
            $rtb_ids = $pdo->query("SELECT GROUP_CONCAT(id) as ids FROM rtb_campaigns WHERE name LIKE 'Sample%'")->fetchColumn();
            $ron_ids = $pdo->query("SELECT GROUP_CONCAT(id) as ids FROM ron_campaigns WHERE name LIKE 'Sample%'")->fetchColumn();
            
            // Delete creatives first
            if ($rtb_ids) {
                $pdo->exec("DELETE FROM creatives WHERE campaign_type = 'rtb' AND campaign_id IN ($rtb_ids)");
            }
            if ($ron_ids) {
                $pdo->exec("DELETE FROM creatives WHERE campaign_type = 'ron' AND campaign_id IN ($ron_ids)");
            }
            
            // Delete campaigns
            $pdo->exec("DELETE FROM rtb_campaigns WHERE name LIKE 'Sample%'");
            $pdo->exec("DELETE FROM ron_campaigns WHERE name LIKE 'Sample%'");
            
            $pdo->commit();
            $message = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Sample campaigns cleared successfully!</div>';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// Get current campaigns count with error handling
try {
    $rtb_count = $pdo->query("SELECT COUNT(*) FROM rtb_campaigns WHERE status = 'active'")->fetchColumn();
} catch (Exception $e) {
    $rtb_count = 0;
}

try {
    $ron_count = $pdo->query("SELECT COUNT(*) FROM ron_campaigns WHERE status = 'active'")->fetchColumn();
} catch (Exception $e) {
    $ron_count = 0;
}

try {
    $creative_count = $pdo->query("SELECT COUNT(*) FROM creatives WHERE status = 'active'")->fetchColumn();
} catch (Exception $e) {
    $creative_count = 0;
}

try {
    $zone_count = $pdo->query("SELECT COUNT(*) FROM zones WHERE status = 'active'")->fetchColumn();
} catch (Exception $e) {
    $zone_count = 0;
}

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tools me-2"></i>Campaign Testing Tools</h2>
            <a href="../zone.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Zones
            </a>
        </div>

        <?php echo $message; ?>

        <!-- Current Status -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <h4><?php echo $rtb_count; ?></h4>
                        <p class="mb-0">Active RTB Campaigns</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <h4><?php echo $ron_count; ?></h4>
                        <p class="mb-0">Active RON Campaigns</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-warning text-white">
                    <div class="card-body">
                        <h4><?php echo $creative_count; ?></h4>
                        <p class="mb-0">Active Creatives</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-primary text-white">
                    <div class="card-body">
                        <h4><?php echo $zone_count; ?></h4>
                        <p class="mb-0">Active Zones</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sample Campaign Tools -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-flask me-2"></i>Sample Campaign Generator</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Create Sample Campaigns</h6>
                        <p class="text-muted">This will create sample RTB and RON campaigns with creatives for multiple ad sizes (300x250, 728x90, 320x50, 300x600, 970x250).</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="create_samples">
                            <button type="submit" class="btn btn-success" onclick="return confirm('Create sample campaigns with creatives for testing?')">
                                <i class="fas fa-plus me-2"></i>Create Sample Campaigns
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h6>Clear Sample Campaigns</h6>
                        <p class="text-muted">Remove all sample campaigns and their creatives from the database.</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="clear_samples">
                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Clear all sample campaigns? This will remove test data.')">
                                <i class="fas fa-trash me-2"></i>Clear Sample Campaigns
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Testing -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-bug me-2"></i>API Testing & Debug</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h6>Test Zone API</h6>
                        <p class="text-muted">Test your zone API with different parameters to see what ads are served.</p>
                        
                        <div class="input-group mb-3">
                            <span class="input-group-text">Zone Token</span>
                            <input type="text" class="form-control" id="testToken" placeholder="Enter zone token">
                            <select class="form-select" id="testSize" style="max-width: 150px;">
                                <option value="300x250">300×250</option>
                                <option value="728x90">728×90</option>
                                <option value="320x50">320×50</option>
                                <option value="300x600">300×600</option>
                                <option value="970x250">970×250</option>
                            </select>
                            <button class="btn btn-primary" onclick="testZoneAPI()">
                                <i class="fas fa-play me-2"></i>Test API
                            </button>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Quick Tests:</label><br>
                            <button class="btn btn-sm btn-outline-info me-2" onclick="testAPI('test')">Test Mode</button>
                            <button class="btn btn-sm btn-outline-warning me-2" onclick="testAPI('debug')">Debug Mode</button>
                            <button class="btn btn-sm btn-outline-success me-2" onclick="testAPI('preview')">Preview Mode</button>
                            <button class="btn btn-sm btn-outline-primary me-2" onclick="testAPI('normal')">Normal Mode</button>
                        </div>
                        
                        <div id="apiResponse" class="mt-3" style="display:none;">
                            <h6>API Response:</h6>
                            <pre id="responseContent" class="bg-light p-3 border rounded" style="max-height: 400px; overflow-y: auto; font-size: 12px;"></pre>
                        </div>
                        
                        <div id="adPreview" class="mt-3" style="display:none;">
                            <h6>Ad Preview:</h6>
                            <div id="previewContainer" class="border rounded p-3 bg-light" style="display: inline-block;">
                                <!-- Ad will be rendered here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Query Tool -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-database me-2"></i>Database Query Results</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>RTB Campaigns</h6>
                        <div class="table-responsive">
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT id, name, status, cpm_bid, (total_budget - budget_spent) as budget_remaining FROM rtb_campaigns ORDER BY created_at DESC LIMIT 10");
                                $rtb_campaigns = $stmt->fetchAll();
                            } catch (Exception $e) {
                                $rtb_campaigns = [];
                            }
                            ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>ID</th><th>Name</th><th>Status</th><th>CPM</th><th>Budget</th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($rtb_campaigns): ?>
                                        <?php foreach ($rtb_campaigns as $campaign): ?>
                                        <tr>
                                            <td><?php echo $campaign['id']; ?></td>
                                            <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                            <td><span class="badge bg-<?php echo $campaign['status'] == 'active' ? 'success' : 'secondary'; ?>"><?php echo $campaign['status']; ?></span></td>
                                            <td>$<?php echo number_format($campaign['cpm_bid'], 2); ?></td>
                                            <td>$<?php echo number_format($campaign['budget_remaining'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center text-muted">No RTB campaigns found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>RON Campaigns</h6>
                        <div class="table-responsive">
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT id, name, status, cpm_bid, (total_budget - budget_spent) as budget_remaining FROM ron_campaigns ORDER BY created_at DESC LIMIT 10");
                                $ron_campaigns = $stmt->fetchAll();
                            } catch (Exception $e) {
                                $ron_campaigns = [];
                            }
                            ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>ID</th><th>Name</th><th>Status</th><th>CPM</th><th>Budget</th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($ron_campaigns): ?>
                                        <?php foreach ($ron_campaigns as $campaign): ?>
                                        <tr>
                                            <td><?php echo $campaign['id']; ?></td>
                                            <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                            <td><span class="badge bg-<?php echo $campaign['status'] == 'active' ? 'success' : 'secondary'; ?>"><?php echo $campaign['status']; ?></span></td>
                                            <td>$<?php echo number_format($campaign['cmp_bid'], 2); ?></td>
                                            <td>$<?php echo number_format($campaign['budget_remaining'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center text-muted">No RON campaigns found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6>Active Creatives by Size</h6>
                        <div class="table-responsive">
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT campaign_type, width, height, COUNT(*) as count FROM creatives WHERE status = 'active' GROUP BY campaign_type, width, height ORDER BY campaign_type, width, height");
                                $creatives = $stmt->fetchAll();
                            } catch (Exception $e) {
                                $creatives = [];
                            }
                            ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>Type</th><th>Size</th><th>Count</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($creatives): ?>
                                        <?php foreach ($creatives as $creative): ?>
                                        <tr>
                                            <td><span class="badge bg-<?php echo $creative['campaign_type'] == 'rtb' ? 'info' : 'success'; ?>"><?php echo strtoupper($creative['campaign_type']); ?></span></td>
                                            <td><?php echo $creative['width']; ?>×<?php echo $creative['height']; ?></td>
                                            <td><?php echo $creative['count']; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="quickTestSize('<?php echo $creative['width']; ?>x<?php echo $creative['height']; ?>')">
                                                    <i class="fas fa-play"></i> Test
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center text-muted">No creatives found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testZoneAPI() {
    const token = document.getElementById('testToken').value;
    const size = document.getElementById('testSize').value;
    if (!token) {
        alert('Please enter a zone token');
        return;
    }
    
    testAPIWithToken(token, 'debug', size);
}

function testAPI(mode) {
    const token = document.getElementById('testToken').value;
    const size = document.getElementById('testSize').value;
    if (!token) {
        alert('Please enter a zone token');
        return;
    }
    
    testAPIWithToken(token, mode, size);
}

function quickTestSize(size) {
    const token = document.getElementById('testToken').value;
    if (!token) {
        alert('Please enter a zone token first');
        return;
    }
    
    document.getElementById('testSize').value = size;
    testAPIWithToken(token, 'debug', size);
}

function testAPIWithToken(token, mode, size = '300x250') {
    let url = `https://up.adstart.click/api/rtb/request.php?token=${token}&format=banner&size=${size}`;
    
    if (mode !== 'normal') {
        url += `&${mode}=1`;
    }
    
    document.getElementById('apiResponse').style.display = 'block';
    document.getElementById('responseContent').textContent = 'Loading...';
    document.getElementById('adPreview').style.display = 'none';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById('responseContent').textContent = JSON.stringify(data, null, 2);
            
            // Show ad preview if content is available
            if (data.success && data.content) {
                const previewContainer = document.getElementById('previewContainer');
                const [width, height] = size.split('x');
                previewContainer.style.width = width + 'px';
                previewContainer.style.height = height + 'px';
                previewContainer.innerHTML = data.content;
                document.getElementById('adPreview').style.display = 'block';
            }
        })
        .catch(error => {
            document.getElementById('responseContent').textContent = 'Error: ' + error.message;
        });
}

// Auto-fill first zone token if available
document.addEventListener('DOMContentLoaded', function() {
    <?php
    try {
        $stmt = $pdo->query("SELECT zone_token FROM zones WHERE status = 'active' LIMIT 1");
        $first_zone = $stmt->fetch();
        if ($first_zone) {
            echo "document.getElementById('testToken').value = '{$first_zone['zone_token']}';";
        }
    } catch (Exception $e) {
        // Ignore error
    }
    ?>
});
</script>

<?php include '../includes/footer.php'; ?>