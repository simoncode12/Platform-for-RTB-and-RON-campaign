<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'RTB Campaigns';
$message = '';

// Get advertisers for dropdown
$stmt = $pdo->query("SELECT * FROM advertisers WHERE status = 'active' ORDER BY company_name");
$advertisers = $stmt->fetchAll();

// Get categories for dropdown
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$categories = $stmt->fetchAll();

// Banner sizes
$banner_sizes = ['300x250', '300x100', '300x50', '300x500', '900x250', '728x90', '160x600'];

// Countries, browsers, devices, OS for targeting
$countries = ['US', 'UK', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'BR', 'IN', 'ID', 'MY', 'TH', 'VN', 'PH'];
$browsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];
$devices = ['Desktop', 'Mobile', 'Tablet'];
$os_options = ['Windows', 'macOS', 'Linux', 'iOS', 'Android'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $endpoint_url = $_POST['endpoint_url'] ?? '';
    $bid_type = $_POST['bid_type'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $advertiser_id = $_POST['advertiser_id'] ?? '';
    $format = $_POST['format'] ?? '';
    $sizes = $_POST['sizes'] ?? [];
    $target_countries = $_POST['target_countries'] ?? [];
    $target_browsers = $_POST['target_browsers'] ?? [];
    $target_devices = $_POST['target_devices'] ?? [];
    $target_os = $_POST['target_os'] ?? [];
    $bid_amount = $_POST['bid_amount'] ?? 0;
    $daily_budget = $_POST['daily_budget'] ?? null;
    
    if ($name && $endpoint_url && $bid_type && $advertiser_id && $format && $sizes) {
        try {
            $stmt = $pdo->prepare("INSERT INTO rtb_campaigns (advertiser_id, name, endpoint_url, bid_type, category_id, format, sizes, target_countries, target_browsers, target_devices, target_os, bid_amount, daily_budget) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $advertiser_id,
                $name,
                $endpoint_url,
                $bid_type,
                $category_id ?: null,
                $format,
                json_encode($sizes),
                json_encode($target_countries),
                json_encode($target_browsers),
                json_encode($target_devices),
                json_encode($target_os),
                $bid_amount,
                $daily_budget ?: null
            ]);
            
            $message = '<div class="alert alert-success">RTB Campaign created successfully!</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    }
}

// Get existing campaigns
$stmt = $pdo->query("SELECT r.*, a.company_name, c.name as category_name FROM rtb_campaigns r 
                     LEFT JOIN advertisers a ON r.advertiser_id = a.id 
                     LEFT JOIN categories c ON r.category_id = c.id 
                     ORDER BY r.created_at DESC");
$campaigns = $stmt->fetchAll();

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-bullhorn me-2"></i>RTB Campaigns</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCampaignModal">
                <i class="fas fa-plus me-2"></i>Create RTB Campaign
            </button>
        </div>

        <?php echo $message; ?>

        <!-- Campaigns List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Active RTB Campaigns</h5>
            </div>
            <div class="card-body">
                <?php if ($campaigns): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Campaign Name</th>
                                <th>Advertiser</th>
                                <th>Bid Type</th>
                                <th>Bid Amount</th>
                                <th>Format</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($campaign['name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($campaign['category_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($campaign['company_name']); ?></td>
                                <td><span class="badge bg-info"><?php echo strtoupper($campaign['bid_type']); ?></span></td>
                                <td>$<?php echo number_format($campaign['bid_amount'], 4); ?></td>
                                <td><?php echo htmlspecialchars($campaign['format']); ?></td>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?php echo $campaign['status'] == 'active' ? 'checked' : ''; ?> 
                                               onchange="toggleCampaign(<?php echo $campaign['id']; ?>, this.checked)">
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editCampaign(<?php echo $campaign['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCampaign(<?php echo $campaign['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                    <h5>No RTB campaigns yet</h5>
                    <p class="text-muted">Create your first RTB campaign to start bidding on external traffic.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Campaign Modal -->
<div class="modal fade" id="createCampaignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Create RTB Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Campaign Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Advertiser *</label>
                                <select class="form-select" name="advertiser_id" required>
                                    <option value="">Select Advertiser</option>
                                    <?php foreach ($advertisers as $advertiser): ?>
                                    <option value="<?php echo $advertiser['id']; ?>">
                                        <?php echo htmlspecialchars($advertiser['company_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Endpoint URL *</label>
                        <input type="url" class="form-control" name="endpoint_url" 
                               placeholder="http://rtb.exoclick.com/rtb.php?idzone=5128252&fid=e573a1c2a656509b0112f7213359757be76929c7" required>
                        <div class="form-text">Enter the Exoclick RTB endpoint URL</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Bid Type *</label>
                                <select class="form-select" name="bid_type" required>
                                    <option value="">Select Type</option>
                                    <option value="cpm">CPM</option>
                                    <option value="cpc">CPC</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Bid Amount *</label>
                                <input type="number" class="form-control" name="bid_amount" step="0.0001" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Daily Budget</label>
                                <input type="number" class="form-control" name="daily_budget" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ad Format *</label>
                                <select class="form-select" name="format" required>
                                    <option value="">Select Format</option>
                                    <option value="banner">Banner</option>
                                    <option value="native">Native</option>
                                    <option value="video">Video</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Banner Sizes *</label>
                        <div class="row">
                            <?php foreach ($banner_sizes as $size): ?>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sizes[]" value="<?php echo $size; ?>" id="size_<?php echo str_replace('x', '_', $size); ?>">
                                    <label class="form-check-label" for="size_<?php echo str_replace('x', '_', $size); ?>">
                                        <?php echo $size; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="selectAllSizes" onchange="toggleAllSizes(this)">
                            <label class="form-check-label" for="selectAllSizes">
                                Select All Sizes
                            </label>
                        </div>
                    </div>
                    
                    <!-- Targeting Options -->
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="fas fa-crosshairs me-2"></i>Targeting Options</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Target Countries</label>
                                        <select class="form-select" name="target_countries[]" multiple>
                                            <?php foreach ($countries as $country): ?>
                                            <option value="<?php echo $country; ?>"><?php echo $country; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Target Browsers</label>
                                        <select class="form-select" name="target_browsers[]" multiple>
                                            <?php foreach ($browsers as $browser): ?>
                                            <option value="<?php echo $browser; ?>"><?php echo $browser; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Target Devices</label>
                                        <select class="form-select" name="target_devices[]" multiple>
                                            <?php foreach ($devices as $device): ?>
                                            <option value="<?php echo $device; ?>"><?php echo $device; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Target OS</label>
                                        <select class="form-select" name="target_os[]" multiple>
                                            <?php foreach ($os_options as $os): ?>
                                            <option value="<?php echo $os; ?>"><?php echo $os; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAllSizes(checkbox) {
    const sizeCheckboxes = document.querySelectorAll('input[name="sizes[]"]');
    sizeCheckboxes.forEach(cb => cb.checked = checkbox.checked);
}

function toggleCampaign(id, status) {
    fetch('ajax/toggle-campaign.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            status: status ? 'active' : 'paused',
            type: 'rtb'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
        } else {
            // Revert toggle and show error
            event.target.checked = !status;
        }
    });
}

function editCampaign(id) {
    // Implement edit functionality
    console.log('Edit campaign:', id);
}

function deleteCampaign(id) {
    if (confirm('Are you sure you want to delete this campaign?')) {
        // Implement delete functionality
        console.log('Delete campaign:', id);
    }
}
</script>

<?php include 'includes/footer.php'; ?>