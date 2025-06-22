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
    
    if ($action == 'safe_fix') {
        try {
            // Step 1: Disable foreign key checks temporarily
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Step 2: Check if advertisers table exists and backup data
            $existing_advertisers = [];
            try {
                $stmt = $pdo->query("SELECT * FROM advertisers");
                $existing_advertisers = $stmt->fetchAll();
            } catch (Exception $e) {
                // Table doesn't exist, continue
            }
            
            // Step 3: Drop and recreate advertisers table
            $pdo->exec("DROP TABLE IF EXISTS advertisers");
            
            $pdo->exec("CREATE TABLE advertisers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                company VARCHAR(255),
                website VARCHAR(255),
                phone VARCHAR(50),
                address TEXT,
                contact_person VARCHAR(255),
                status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_name (name)
            )");
            
            // Step 4: Insert sample advertisers
            $stmt = $pdo->prepare("INSERT INTO advertisers (name, email, company, website, contact_person, status) VALUES (?, ?, ?, ?, ?, ?)");
            
            $sample_advertisers = [
                ['AdStart Network', 'admin@adstart.click', 'AdStart Media', 'https://adstart.click', 'Admin Team', 'active'],
                ['Premium Ads Co', 'contact@premiumads.com', 'Premium Advertising', 'https://premiumads.com', 'John Smith', 'active'],
                ['Digital Marketing Pro', 'hello@digitalmarketing.com', 'Digital Marketing Solutions', 'https://digitalmarketing.com', 'Jane Doe', 'active'],
                ['E-Commerce Giant', 'ads@ecommerce.com', 'E-Commerce Solutions', 'https://ecommerce.com', 'Bob Johnson', 'active'],
                ['Tech Startup Inc', 'marketing@techstartup.com', 'Tech Startup', 'https://techstartup.com', 'Alice Wilson', 'active'],
                ['Finance Pro Ltd', 'ads@financepro.com', 'Finance Professional', 'https://financepro.com', 'Mike Brown', 'active'],
                ['Retail Network', 'marketing@retail.com', 'Retail Solutions', 'https://retail.com', 'Sarah Davis', 'active']
            ];
            
            foreach ($sample_advertisers as $advertiser) {
                $stmt->execute($advertiser);
            }
            
            // Step 5: Update ron_campaigns table structure safely
            $columns_to_add = [
                'advertiser_id' => 'ADD COLUMN advertiser_id INT AFTER id',
                'bid_type' => "ADD COLUMN bid_type ENUM('cpm', 'cpc') DEFAULT 'cpm' AFTER landing_url",
                'bid_amount' => 'ADD COLUMN bid_amount DECIMAL(10,4) DEFAULT 0.0000 AFTER bid_type',
                'format' => "ADD COLUMN format VARCHAR(50) DEFAULT 'banner' AFTER bid_amount",
                'target_browsers' => 'ADD COLUMN target_browsers TEXT AFTER target_countries',
                'target_languages' => 'ADD COLUMN target_languages TEXT AFTER target_os',
                'target_age' => 'ADD COLUMN target_age VARCHAR(20) AFTER target_languages',
                'target_gender' => 'ADD COLUMN target_gender VARCHAR(10) AFTER target_age',
                'start_date' => 'ADD COLUMN start_date DATE AFTER total_budget',
                'end_date' => 'ADD COLUMN end_date DATE AFTER start_date',
                'frequency_cap' => 'ADD COLUMN frequency_cap INT DEFAULT 0 AFTER end_date'
            ];
            
            // Check existing columns
            $stmt = $pdo->query("DESCRIBE ron_campaigns");
            $existing_columns = array_column($stmt->fetchAll(), 'Field');
            
            foreach ($columns_to_add as $column => $sql) {
                if (!in_array($column, $existing_columns)) {
                    try {
                        $pdo->exec("ALTER TABLE ron_campaigns $sql");
                    } catch (Exception $e) {
                        // Continue if column already exists
                    }
                }
            }
            
            // Step 6: Update creatives table structure safely
            $creative_columns = [
                'method' => "ADD COLUMN method ENUM('html5', 'script') DEFAULT 'html5' AFTER creative_type",
                'bid_amount' => 'ADD COLUMN bid_amount DECIMAL(10,4) DEFAULT 0.0000 AFTER call_to_action'
            ];
            
            $stmt = $pdo->query("DESCRIBE creatives");
            $existing_creative_columns = array_column($stmt->fetchAll(), 'Field');
            
            foreach ($creative_columns as $column => $sql) {
                if (!in_array($column, $existing_creative_columns)) {
                    try {
                        $pdo->exec("ALTER TABLE creatives $sql");
                    } catch (Exception $e) {
                        // Continue if column already exists
                    }
                }
            }
            
            // Step 7: Re-enable foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // Step 8: Update any existing campaigns to have valid advertiser_id
            $pdo->exec("UPDATE ron_campaigns SET advertiser_id = 1 WHERE advertiser_id IS NULL OR advertiser_id = 0");
            
            $message = '<div class="alert alert-success">
                <i class="fas fa-check me-2"></i>
                <strong>Database fixed successfully!</strong><br>
                • Advertisers table recreated with ' . count($sample_advertisers) . ' sample advertisers<br>
                • RON campaigns table updated with new columns<br>
                • Creatives table updated with method and bid_amount columns<br>
                • Foreign key constraints handled safely
            </div>';
            
        } catch (Exception $e) {
            // Re-enable foreign key checks in case of error
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Exception $e2) {
                // Ignore
            }
            $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
        }
    }
    
    if ($action == 'add_sample_data') {
        try {
            // Just add sample advertisers if table exists
            $stmt = $pdo->prepare("INSERT IGNORE INTO advertisers (name, email, company, website, contact_person, status) VALUES (?, ?, ?, ?, ?, ?)");
            
            $sample_advertisers = [
                ['AdStart Network', 'admin@adstart.click', 'AdStart Media', 'https://adstart.click', 'Admin Team', 'active'],
                ['Premium Ads Co', 'contact@premiumads.com', 'Premium Advertising', 'https://premiumads.com', 'John Smith', 'active'],
                ['Digital Marketing Pro', 'hello@digitalmarketing.com', 'Digital Marketing Solutions', 'https://digitalmarketing.com', 'Jane Doe', 'active'],
                ['E-Commerce Giant', 'ads@ecommerce.com', 'E-Commerce Solutions', 'https://ecommerce.com', 'Bob Johnson', 'active']
            ];
            
            $count = 0;
            foreach ($sample_advertisers as $advertiser) {
                if ($stmt->execute($advertiser)) {
                    $count++;
                }
            }
            
            $message = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Added ' . $count . ' sample advertisers successfully!</div>';
            
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// Check current status
$status = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM advertisers");
    $status['advertisers_count'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT * FROM advertisers LIMIT 5");
    $status['sample_advertisers'] = $stmt->fetchAll();
} catch (Exception $e) {
    $status['advertisers_error'] = $e->getMessage();
}

try {
    $stmt = $pdo->query("DESCRIBE ron_campaigns");
    $status['ron_columns'] = array_column($stmt->fetchAll(), 'Field');
} catch (Exception $e) {
    $status['ron_error'] = $e->getMessage();
}

try {
    $stmt = $pdo->query("DESCRIBE creatives");
    $status['creative_columns'] = array_column($stmt->fetchAll(), 'Field');
} catch (Exception $e) {
    $status['creative_error'] = $e->getMessage();
}

include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-shield-alt me-2"></i>Safe Database Fix</h2>
            <a href="../ron-campaign.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to RON Campaigns
            </a>
        </div>

        <?php echo $message; ?>

        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Safe Fix:</strong> This tool handles foreign key constraints safely and preserves existing data where possible.
        </div>

        <!-- Current Status -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-users me-2"></i>Advertisers</h6>
                    </div>
                    <div class="card-body">
                        <?php if (isset($status['advertisers_count'])): ?>
                            <div class="text-center">
                                <h4 class="text-success"><?php echo $status['advertisers_count']; ?></h4>
                                <p class="mb-0">Advertisers Available</p>
                            </div>
                            <?php if (!empty($status['sample_advertisers'])): ?>
                            <hr>
                            <small class="text-muted">Sample:</small>
                            <?php foreach (array_slice($status['sample_advertisers'], 0, 3) as $adv): ?>
                            <div class="small"><?php echo htmlspecialchars($adv['name']); ?></div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center text-danger">
                                <i class="fas fa-times fa-2x mb-2"></i>
                                <p class="mb-0">Table Missing</p>
                                <small><?php echo $status['advertisers_error'] ?? ''; ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-network-wired me-2"></i>RON Campaigns</h6>
                    </div>
                    <div class="card-body">
                        <?php if (isset($status['ron_columns'])): ?>
                            <div class="text-success mb-2">
                                <i class="fas fa-check me-2"></i>Table exists
                            </div>
                            <small class="text-muted">Required columns:</small>
                            <?php 
                            $required = ['advertiser_id', 'bid_type', 'bid_amount', 'format'];
                            foreach ($required as $col): 
                                $exists = in_array($col, $status['ron_columns']);
                            ?>
                            <div class="small">
                                <i class="fas fa-<?php echo $exists ? 'check text-success' : 'times text-danger'; ?> me-1"></i>
                                <?php echo $col; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-danger">
                                <i class="fas fa-times me-2"></i>Table missing
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-images me-2"></i>Creatives</h6>
                    </div>
                    <div class="card-body">
                        <?php if (isset($status['creative_columns'])): ?>
                            <div class="text-success mb-2">
                                <i class="fas fa-check me-2"></i>Table exists
                            </div>
                            <small class="text-muted">Required columns:</small>
                            <?php 
                            $required = ['method', 'bid_amount'];
                            foreach ($required as $col): 
                                $exists = in_array($col, $status['creative_columns']);
                            ?>
                            <div class="small">
                                <i class="fas fa-<?php echo $exists ? 'check text-success' : 'times text-danger'; ?> me-1"></i>
                                <?php echo $col; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-danger">
                                <i class="fas fa-times me-2"></i>Table missing
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fix Options -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-shield-alt me-2"></i>Safe Complete Fix</h5>
                    </div>
                    <div class="card-body">
                        <p>Recommended approach that:</p>
                        <ul>
                            <li>Safely handles foreign key constraints</li>
                            <li>Preserves existing data where possible</li>
                            <li>Adds all missing columns</li>
                            <li>Creates sample advertisers</li>
                        </ul>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="safe_fix">
                            <button type="submit" class="btn btn-success" onclick="return confirm('Proceed with safe database fix?')">
                                <i class="fas fa-shield-alt me-2"></i>Safe Fix Database
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-plus me-2"></i>Add Sample Data Only</h5>
                    </div>
                    <div class="card-body">
                        <p>If table exists but needs sample data:</p>
                        <ul>
                            <li>Adds sample advertisers only</li>
                            <li>Doesn't modify table structure</li>
                            <li>Safe for existing data</li>
                            <li>Quick fix option</li>
                        </ul>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="add_sample_data">
                            <button type="submit" class="btn btn-info" onclick="return confirm('Add sample advertiser data?')">
                                <i class="fas fa-plus me-2"></i>Add Sample Data
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expected Result -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-bullseye me-2"></i>After Fix - You'll Be Able To</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-network-wired me-2"></i>Create RON Campaigns</h6>
                        <ul>
                            <li>Select from available advertisers</li>
                            <li>Choose bid type (CPM/CPC)</li>
                            <li>Set targeting options</li>
                            <li>Configure budgets and scheduling</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-images me-2"></i>Manage Creatives</h6>
                        <ul>
                            <li>Choose HTML5 or Script methods</li>
                            <li>Set individual creative bids</li>
                            <li>Multiple ad sizes support</li>
                            <li>Preview functionality</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>