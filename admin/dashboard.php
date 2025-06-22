<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Dashboard';

// Get statistics
$stats = [];

// RTB Campaigns
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM rtb_campaigns");
$stats['rtb'] = $stmt->fetch();

// RON Campaigns
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM ron_campaigns");
$stats['ron'] = $stmt->fetch();

// Advertisers
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM advertisers");
$stats['advertisers'] = $stmt->fetch();

// Publishers
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM publishers");
$stats['publishers'] = $stmt->fetch();

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
            <div class="text-muted">
                Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-bullhorn fa-2x text-primary mb-2"></i>
                        <h4><?php echo $stats['rtb']['total']; ?></h4>
                        <p class="text-muted mb-0">RTB Campaigns</p>
                        <small class="text-success"><?php echo $stats['rtb']['active']; ?> Active</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-network-wired fa-2x text-info mb-2"></i>
                        <h4><?php echo $stats['ron']['total']; ?></h4>
                        <p class="text-muted mb-0">RON Campaigns</p>
                        <small class="text-success"><?php echo $stats['ron']['active']; ?> Active</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-user-tie fa-2x text-warning mb-2"></i>
                        <h4><?php echo $stats['advertisers']['total']; ?></h4>
                        <p class="text-muted mb-0">Advertisers</p>
                        <small class="text-success"><?php echo $stats['advertisers']['active']; ?> Active</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-success mb-2"></i>
                        <h4><?php echo $stats['publishers']['total']; ?></h4>
                        <p class="text-muted mb-0">Publishers</p>
                        <small class="text-success"><?php echo $stats['publishers']['active']; ?> Active</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="rtb-sell.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Create RTB Campaign
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="ron-campaign.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-plus me-2"></i>Create RON Campaign
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="rtb-buy.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-link me-2"></i>Generate Endpoint
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent RTB Campaigns</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->query("SELECT r.*, a.company_name FROM rtb_campaigns r 
                                           LEFT JOIN advertisers a ON r.advertiser_id = a.id 
                                           ORDER BY r.created_at DESC LIMIT 5");
                        $campaigns = $stmt->fetchAll();
                        
                        if ($campaigns): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($campaigns as $campaign): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($campaign['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($campaign['company_name']); ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $campaign['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($campaign['status']); ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">No RTB campaigns yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent RON Campaigns</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->query("SELECT r.*, a.company_name FROM ron_campaigns r 
                                           LEFT JOIN advertisers a ON r.advertiser_id = a.id 
                                           ORDER BY r.created_at DESC LIMIT 5");
                        $campaigns = $stmt->fetchAll();
                        
                        if ($campaigns): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($campaigns as $campaign): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($campaign['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($campaign['company_name']); ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $campaign['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($campaign['status']); ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">No RON campaigns yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>