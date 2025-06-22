<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Edit RON Campaign';
$message = '';

// Get campaign ID
$campaign_id = intval($_GET['id'] ?? 0);

if (!$campaign_id) {
    header('Location: ron-campaign.php');
    exit;
}

// Get campaign details
$campaign = null;
try {
    $stmt = $pdo->prepare("SELECT c.*, a.name as advertiser_name, cat.name as category_name,
                           COUNT(cr.id) as creatives_count
                           FROM ron_campaigns c
                           LEFT JOIN advertisers a ON c.advertiser_id = a.id
                           LEFT JOIN categories cat ON c.category_id = cat.id
                           LEFT JOIN creatives cr ON c.id = cr.campaign_id AND cr.campaign_type = 'ron'
                           WHERE c.id = ?
                           GROUP BY c.id");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch();
    
    if (!$campaign) {
        header('Location: ron-campaign.php?error=Campaign not found');
        exit;
    }
} catch (Exception $e) {
    header('Location: ron-campaign.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update') {
        $name = trim($_POST['name'] ?? '');
        $advertiser_id = intval($_POST['advertiser_id'] ?? 0) ?: null;
        $category_id = intval($_POST['category_id'] ?? 0) ?: null;
        $bid_type = $_POST['bid_type'] ?? 'cpm';
        $bid_amount = floatval($_POST['bid_amount'] ?? 0);
        $format = $_POST['format'] ?? 'banner';
        $target_countries = is_array($_POST['target_countries']) ? implode(',', $_POST['target_countries']) : '';
        $target_browsers = is_array($_POST['target_browsers']) ? implode(',', $_POST['target_browsers']) : '';
        $target_devices = is_array($_POST['target_devices']) ? implode(',', $_POST['target_devices']) : '';
        $target_os = is_array($_POST['target_os']) ? implode(',', $_POST['target_os']) : '';
        $target_languages = is_array($_POST['target_languages']) ? implode(',', $_POST['target_languages']) : '';
        $target_age = $_POST['target_age'] ?? '';
        $target_gender = $_POST['target_gender'] ?? '';
        $daily_budget = floatval($_POST['daily_budget'] ?? 0);
        $total_budget = floatval($_POST['total_budget'] ?? 0);
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $frequency_cap = intval($_POST['frequency_cap'] ?? 0);
        $landing_url = trim($_POST['landing_url'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if ($name && $bid_amount > 0 && $landing_url) {
            try {
                $stmt = $pdo->prepare("UPDATE ron_campaigns SET 
                    name = ?, advertiser_id = ?, category_id = ?, bid_type = ?, bid_amount = ?, format = ?,
                    target_countries = ?, target_browsers = ?, target_devices = ?, target_os = ?,
                    target_languages = ?, target_age = ?, target_gender = ?,
                    daily_budget = ?, total_budget = ?, start_date = ?, end_date = ?,
                    frequency_cap = ?, landing_url = ?, status = ?, updated_at = NOW()
                    WHERE id = ?");
                
                $stmt->execute([
                    $name, $advertiser_id, $category_id, $bid_type, $bid_amount, $format,
                    $target_countries, $target_browsers, $target_devices, $target_os,
                    $target_languages, $target_age, $target_gender,
                    $daily_budget, $total_budget, $start_date ?: null, $end_date ?: null,
                    $frequency_cap, $landing_url, $status, $campaign_id
                ]);
                
                $message = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>RON Campaign updated successfully!</div>';
                
                // Refresh campaign data
                $stmt = $pdo->prepare("SELECT c.*, a.name as advertiser_name, cat.name as category_name,
                                       COUNT(cr.id) as creatives_count
                                       FROM ron_campaigns c
                                       LEFT JOIN advertisers a ON c.advertiser_id = a.id
                                       LEFT JOIN categories cat ON c.category_id = cat.id
                                       LEFT JOIN creatives cr ON c.id = cr.campaign_id AND cr.campaign_type = 'ron'
                                       WHERE c.id = ?
                                       GROUP BY c.id");
                $stmt->execute([$campaign_id]);
                $campaign = $stmt->fetch();
                
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Please fill in campaign name, bid amount, and landing URL.</div>';
        }
    }
    
    if ($action == 'duplicate') {
        try {
            $stmt = $pdo->prepare("INSERT INTO ron_campaigns (
                name, advertiser_id, category_id, bid_type, bid_amount, format,
                target_countries, target_browsers, target_devices, target_os,
                target_languages, target_age, target_gender,
                daily_budget, total_budget, budget_spent, start_date, end_date,
                frequency_cap, landing_url, status, created_at
            ) SELECT 
                CONCAT(name, ' (Copy)'), advertiser_id, category_id, bid_type, bid_amount, format,
                target_countries, target_browsers, target_devices, target_os,
                target_languages, target_age, target_gender,
                daily_budget, total_budget, 0.00, start_date, end_date,
                frequency_cap, landing_url, 'inactive', NOW()
            FROM ron_campaigns WHERE id = ?");
            
            $stmt->execute([$campaign_id]);
            $new_id = $pdo->lastInsertId();
            
            $message = '<div class="alert alert-success"><i class="fas fa-copy me-2"></i>Campaign duplicated successfully! <a href="edit-ron-campaign.php?id=' . $new_id . '" class="alert-link">Edit the copy</a></div>';
            
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error duplicating campaign: ' . $e->getMessage() . '</div>';
        }
    }
}

// Get data for dropdowns
try {
    // Get categories
    $stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Get advertisers
    $stmt = $pdo->query("SELECT * FROM advertisers WHERE status = 'active' ORDER BY name");
    $advertisers = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    $advertisers = [];
}

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-edit me-2"></i>Edit RON Campaign</h2>
                <p class="text-muted mb-0">Campaign ID: <strong>#<?php echo $campaign['id']; ?></strong></p>
            </div>
            <div>
                <a href="ron-campaign.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Campaigns
                </a>
                <a href="creative.php?campaign_id=<?php echo $campaign['id']; ?>&campaign_type=ron" class="btn btn-outline-primary me-2">
                    <i class="fas fa-images me-2"></i>Manage Creatives (<?php echo $campaign['creatives_count']; ?>)
                </a>
                <div class="btn-group">
                    <button class="btn btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-2"></i>Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="duplicateCampaign()">
                            <i class="fas fa-copy me-2"></i>Duplicate Campaign
                        </a></li>
                        <li><a class="dropdown-item" href="reports/campaign-stats.php?id=<?php echo $campaign['id']; ?>&type=ron" target="_blank">
                            <i class="fas fa-chart-bar me-2"></i>View Statistics
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-warning" href="#" onclick="pauseCampaign()">
                            <i class="fas fa-pause me-2"></i>Pause Campaign
                        </a></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteCampaign()">
                            <i class="fas fa-trash me-2"></i>Delete Campaign
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Campaign Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Campaign Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Status</h6>
                            <span class="badge bg-<?php echo $campaign['status'] == 'active' ? 'success' : ($campaign['status'] == 'paused' ? 'warning' : 'secondary'); ?> fs-6">
                                <?php echo ucfirst($campaign['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Bid</h6>
                            <span class="fs-6"><?php echo strtoupper($campaign['bid_type']); ?>: $<?php echo number_format($campaign['bid_amount'], 2); ?></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Daily Budget</h6>
                            <span class="fs-6">$<?php echo number_format($campaign['daily_budget'], 2); ?></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Total Budget</h6>
                            <span class="fs-6">$<?php echo number_format($campaign['total_budget'], 2); ?></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Spent</h6>
                            <span class="fs-6">$<?php echo number_format($campaign['budget_spent'], 2); ?></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Created</h6>
                            <span class="fs-6"><?php echo date('M j, Y', strtotime($campaign['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php if ($campaign['total_budget'] > 0): ?>
                <div class="mt-3">
                    <?php $budget_percent = ($campaign['budget_spent'] / $campaign['total_budget']) * 100; ?>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar <?php echo $budget_percent > 80 ? 'bg-danger' : ($budget_percent > 60 ? 'bg-warning' : 'bg-success'); ?>" 
                             style="width: <?php echo min($budget_percent, 100); ?>%"></div>
                    </div>
                    <small class="text-muted">Budget utilization: <?php echo number_format($budget_percent, 1); ?>%</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Edit Form -->
            <div class="col-md-8">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="update">
                    
                    <!-- Basic Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Campaign Name *</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo htmlspecialchars($campaign['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status *</label>
                                        <select class="form-select" name="status" required>
                                            <option value="active" <?php echo $campaign['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $campaign['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="paused" <?php echo $campaign['status'] == 'paused' ? 'selected' : ''; ?>>Paused</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Advertiser</label>
                                        <select class="form-select" name="advertiser_id">
                                            <option value="">Select Advertiser</option>
                                            <?php foreach ($advertisers as $advertiser): ?>
                                            <option value="<?php echo $advertiser['id']; ?>" 
                                                    <?php echo $campaign['advertiser_id'] == $advertiser['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($advertiser['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-select" name="category_id">
                                            <option value="">All Categories</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"
                                                    <?php echo $campaign['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Landing URL *</label>
                                <input type="url" class="form-control" name="landing_url" 
                                       value="<?php echo htmlspecialchars($campaign['landing_url']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Bidding & Budget -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6><i class="fas fa-dollar-sign me-2"></i>Bidding & Budget</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Bid Type *</label>
                                        <select class="form-select" name="bid_type" required>
                                            <option value="cpm" <?php echo $campaign['bid_type'] == 'cpm' ? 'selected' : ''; ?>>CPM (Cost per 1000 Impressions)</option>
                                            <option value="cpc" <?php echo $campaign['bid_type'] == 'cpc' ? 'selected' : ''; ?>>CPC (Cost per Click)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Bid Amount ($) *</label>
                                        <input type="number" class="form-control" name="bid_amount" step="0.01" min="0.01" 
                                               value="<?php echo $campaign['bid_amount']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Format</label>
                                <select class="form-select" name="format">
                                    <option value="banner" <?php echo $campaign['format'] == 'banner' ? 'selected' : ''; ?>>Banner</option>
                                    <option value="native" <?php echo $campaign['format'] == 'native' ? 'selected' : ''; ?>>Native</option>
                                    <option value="video" <?php echo $campaign['format'] == 'video' ? 'selected' : ''; ?>>Video</option>
                                    <option value="popup" <?php echo $campaign['format'] == 'popup' ? 'selected' : ''; ?>>Popup</option>
                                    <option value="popunder" <?php echo $campaign['format'] == 'popunder' ? 'selected' : ''; ?>>Popunder</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Daily Budget ($)</label>
                                        <input type="number" class="form-control" name="daily_budget" step="0.01" min="0" 
                                               value="<?php echo $campaign['daily_budget']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Total Budget ($)</label>
                                        <input type="number" class="form-control" name="total_budget" step="0.01" min="0" 
                                               value="<?php echo $campaign['total_budget']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" name="start_date" 
                                               value="<?php echo $campaign['start_date']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control" name="end_date" 
                                               value="<?php echo $campaign['end_date']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Frequency Cap (per user/day)</label>
                                <input type="number" class="form-control" name="frequency_cap" min="0" 
                                       value="<?php echo $campaign['frequency_cap']; ?>" 
                                       placeholder="0 = unlimited">
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="ron-campaign.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-warning me-2" onclick="resetForm()">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Campaign
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Targeting Options -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6><i class="fas fa-crosshairs me-2"></i>Targeting Options</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="targetingForm">
                            <input type="hidden" name="action" value="update">
                            
                            <div class="mb-3">
                                <label class="form-label">Target Countries</label>
                                <select class="form-select" name="target_countries[]" multiple size="4">
                                    <?php 
                                    $selected_countries = explode(',', $campaign['target_countries']);
                                    $countries = [
                                        'US' => 'United States', 'CA' => 'Canada', 'UK' => 'United Kingdom',
                                        'AU' => 'Australia', 'DE' => 'Germany', 'FR' => 'France',
                                        'IT' => 'Italy', 'ES' => 'Spain', 'BR' => 'Brazil',
                                        'MX' => 'Mexico', 'IN' => 'India', 'JP' => 'Japan'
                                    ];
                                    foreach ($countries as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" <?php echo in_array($code, $selected_countries) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Target Browsers</label>
                                <select class="form-select" name="target_browsers[]" multiple>
                                    <?php 
                                    $selected_browsers = explode(',', $campaign['target_browsers']);
                                    $browsers = ['chrome' => 'Chrome', 'firefox' => 'Firefox', 'safari' => 'Safari', 'edge' => 'Edge'];
                                    foreach ($browsers as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo in_array($value, $selected_browsers) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Target Devices</label>
                                <select class="form-select" name="target_devices[]" multiple>
                                    <?php 
                                    $selected_devices = explode(',', $campaign['target_devices']);
                                    $devices = ['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet'];
                                    foreach ($devices as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo in_array($value, $selected_devices) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Target OS</label>
                                <select class="form-select" name="target_os[]" multiple>
                                    <?php 
                                    $selected_os = explode(',', $campaign['target_os']);
                                    $os_list = ['windows' => 'Windows', 'macos' => 'macOS', 'linux' => 'Linux', 'ios' => 'iOS', 'android' => 'Android'];
                                    foreach ($os_list as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo in_array($value, $selected_os) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Target Age</label>
                                        <select class="form-select" name="target_age">
                                            <option value="">All Ages</option>
                                            <option value="18-24" <?php echo $campaign['target_age'] == '18-24' ? 'selected' : ''; ?>>18-24</option>
                                            <option value="25-34" <?php echo $campaign['target_age'] == '25-34' ? 'selected' : ''; ?>>25-34</option>
                                            <option value="35-44" <?php echo $campaign['target_age'] == '35-44' ? 'selected' : ''; ?>>35-44</option>
                                            <option value="45+" <?php echo $campaign['target_age'] == '45+' ? 'selected' : ''; ?>>45+</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Target Gender</label>
                                        <select class="form-select" name="target_gender">
                                            <option value="">All Genders</option>
                                            <option value="male" <?php echo $campaign['target_gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo $campaign['target_gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Copy fields to main form -->
                            <script>
                            document.getElementById('targetingForm').addEventListener('change', function() {
                                // Sync targeting fields with main form
                                const mainForm = document.querySelector('form[method="POST"]:not(#targetingForm)');
                                
                                // Copy all targeting fields
                                const targetingFields = ['target_countries[]', 'target_browsers[]', 'target_devices[]', 'target_os[]', 'target_age', 'target_gender'];
                                
                                targetingFields.forEach(fieldName => {
                                    const sourceField = this.querySelector(`[name="${fieldName}"]`);
                                    let targetField = mainForm.querySelector(`[name="${fieldName}"]`);
                                    
                                    if (!targetField) {
                                        targetField = document.createElement('input');
                                        targetField.type = 'hidden';
                                        targetField.name = fieldName;
                                        mainForm.appendChild(targetField);
                                    }
                                    
                                    if (sourceField.multiple) {
                                        const selectedValues = Array.from(sourceField.selectedOptions).map(option => option.value);
                                        targetField.value = selectedValues.join(',');
                                    } else {
                                        targetField.value = sourceField.value;
                                    }
                                });
                            });
                            </script>
                        </form>
                    </div>
                </div>

                <!-- Campaign Performance -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-line me-2"></i>Performance Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="mb-2">
                                    <h6 class="mb-0">0</h6>
                                    <small class="text-muted">Impressions</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-2">
                                    <h6 class="mb-0">0</h6>
                                    <small class="text-muted">Clicks</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-2">
                                    <h6 class="mb-0">0.00%</h6>
                                    <small class="text-muted">CTR</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-2">
                                    <h6 class="mb-0">$0.00</h6>
                                    <small class="text-muted">eCPM</small>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <a href="reports/campaign-stats.php?id=<?php echo $campaign['id']; ?>&type=ron" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-chart-bar me-1"></i>Detailed Report
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="duplicate">
                                <button type="submit" class="btn btn-outline-info btn-sm w-100" onclick="return confirm('Duplicate this campaign?')">
                                    <i class="fas fa-copy me-2"></i>Duplicate Campaign
                                </button>
                            </form>
                            
                            <a href="creative.php?campaign_id=<?php echo $campaign['id']; ?>&campaign_type=ron" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-images me-2"></i>Manage Creatives
                            </a>
                            
                            <button class="btn btn-outline-success btn-sm" onclick="testCampaign()">
                                <i class="fas fa-flask me-2"></i>Test Campaign
                            </button>
                            
                            <hr class="my-2">
                            
                            <button class="btn btn-outline-warning btn-sm" onclick="pauseCampaign()">
                                <i class="fas fa-pause me-2"></i>Pause Campaign
                            </button>
                            
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteCampaign()">
                                <i class="fas fa-trash me-2"></i>Delete Campaign
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            // Copy targeting data before submit
            copyTargetingData();
            
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Initialize targeting data copy
    copyTargetingData();
});

function copyTargetingData() {
    const mainForm = document.querySelector('form[method="POST"]:not(#targetingForm)');
    const targetingForm = document.getElementById('targetingForm');
    
    if (!targetingForm || !mainForm) return;
    
    // Remove existing hidden targeting fields
    mainForm.querySelectorAll('input[name^="target_"]').forEach(field => field.remove());
    
    // Copy targeting fields
    const fieldsToCopy = ['target_countries[]', 'target_browsers[]', 'target_devices[]', 'target_os[]', 'target_age', 'target_gender'];
    
    fieldsToCopy.forEach(fieldName => {
        const sourceField = targetingForm.querySelector(`[name="${fieldName}"]`);
        if (!sourceField) return;
        
        if (sourceField.multiple) {
            const selectedValues = Array.from(sourceField.selectedOptions).map(option => option.value);
            if (selectedValues.length > 0) {
                selectedValues.forEach(value => {
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = fieldName;
                    hiddenField.value = value;
                    mainForm.appendChild(hiddenField);
                });
            }
        } else {
            if (sourceField.value) {
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = fieldName;
                hiddenField.value = sourceField.value;
                mainForm.appendChild(hiddenField);
            }
        }
    });
}

function resetForm() {
    if (confirm('Reset form to original values? All unsaved changes will be lost.')) {
        location.reload();
    }
}

function duplicateCampaign() {
    return confirm('Create a copy of this campaign?');
}

function testCampaign() {
    window.open(`/api/rtb/request.php?campaign_id=<?php echo $campaign['id']; ?>&type=ron&test=1`, '_blank', 'width=800,height=600');
}

function pauseCampaign() {
    if (confirm('Pause this campaign?')) {
        fetch('ajax/toggle-campaign.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: <?php echo $campaign['id']; ?>,
                type: 'ron',
                status: 'paused'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        });
    }
}

function deleteCampaign() {
    if (confirm('Are you sure you want to delete this campaign?\n\nThis will also delete all associated creatives and cannot be undone.')) {
        fetch('ajax/delete-campaign.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: <?php echo $campaign['id']; ?>,
                type: 'ron'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Campaign deleted successfully!');
                window.location.href = 'ron-campaign.php';
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        });
    }
}
</script>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.form-select[multiple] {
    height: auto;
}

.btn-group .dropdown-menu {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>

<?php include 'includes/footer.php'; ?>