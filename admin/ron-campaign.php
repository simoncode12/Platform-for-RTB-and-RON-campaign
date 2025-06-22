<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'RON Campaigns';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create') {
        $name = trim($_POST['name'] ?? '');
        $advertiser_id = intval($_POST['advertiser_id'] ?? 0) ?: null;
        $category_id = intval($_POST['category_id'] ?? 0) ?: null;
        $bid_type = $_POST['bid_type'] ?? 'cpm'; // cpm or cpc
        $bid_amount = floatval($_POST['bid_amount'] ?? 0);
        $format = $_POST['format'] ?? 'banner';
        $target_countries = $_POST['target_countries'] ?? '';
        $target_browsers = $_POST['target_browsers'] ?? '';
        $target_devices = $_POST['target_devices'] ?? '';
        $target_os = $_POST['target_os'] ?? '';
        $target_languages = $_POST['target_languages'] ?? '';
        $target_age = $_POST['target_age'] ?? '';
        $target_gender = $_POST['target_gender'] ?? '';
        $daily_budget = floatval($_POST['daily_budget'] ?? 0);
        $total_budget = floatval($_POST['total_budget'] ?? 0);
        $start_date = $_POST['start_date'] ?? date('Y-m-d');
        $end_date = $_POST['end_date'] ?? '';
        $frequency_cap = intval($_POST['frequency_cap'] ?? 0);
        $landing_url = trim($_POST['landing_url'] ?? '');
        
        if ($name && $bid_amount > 0 && $landing_url) {
            try {
                $stmt = $pdo->prepare("INSERT INTO ron_campaigns (
                    name, advertiser_id, category_id, bid_type, bid_amount, format,
                    target_countries, target_browsers, target_devices, target_os,
                    target_languages, target_age, target_gender,
                    daily_budget, total_budget, budget_spent, start_date, end_date,
                    frequency_cap, landing_url, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                $stmt->execute([
                    $name, $advertiser_id, $category_id, $bid_type, $bid_amount, $format,
                    $target_countries, $target_browsers, $target_devices, $target_os,
                    $target_languages, $target_age, $target_gender,
                    $daily_budget, $total_budget, 0.00, $start_date, $end_date,
                    $frequency_cap, $landing_url, 'active'
                ]);
                
                $campaign_id = $pdo->lastInsertId();
                
                // Redirect to creative creation
                header("Location: creative.php?campaign_id={$campaign_id}&campaign_type=ron&new=1");
                exit;
                
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Please fill in campaign name, bid amount, and landing URL.</div>';
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

// Get existing campaigns
try {
    $stmt = $pdo->query("SELECT c.*, a.name as advertiser_name, cat.name as category_name,
                         COUNT(cr.id) as creatives_count
                         FROM ron_campaigns c
                         LEFT JOIN advertisers a ON c.advertiser_id = a.id
                         LEFT JOIN categories cat ON c.category_id = cat.id
                         LEFT JOIN creatives cr ON c.id = cr.campaign_id AND cr.campaign_type = 'ron'
                         GROUP BY c.id
                         ORDER BY c.created_at DESC");
    $campaigns = $stmt->fetchAll();
} catch (Exception $e) {
    $campaigns = [];
}

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-network-wired me-2"></i>RON Campaigns</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCampaignModal">
                <i class="fas fa-plus me-2"></i>Create RON Campaign
            </button>
        </div>

        <?php echo $message; ?>

        <!-- Campaigns List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>RON Campaigns (<?php echo count($campaigns); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if ($campaigns): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Advertiser</th>
                                <th>Bidding</th>
                                <th>Budget</th>
                                <th>Targeting</th>
                                <th>Creatives</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($campaign['name']); ?></strong>
                                        <br><small class="text-muted">ID: <?php echo $campaign['id']; ?></small>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($campaign['category_name'] ?? 'All Categories'); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($campaign['advertiser_name'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo strtoupper($campaign['bid_type']); ?>:</strong> $<?php echo number_format($campaign['bid_amount'], 2); ?>
                                        <br><small class="text-muted">Format: <?php echo ucfirst($campaign['format']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <small>Daily:</small> $<?php echo number_format($campaign['daily_budget'], 2); ?>
                                        <br><small>Total:</small> $<?php echo number_format($campaign['total_budget'], 2); ?>
                                        <br><small>Spent:</small> $<?php echo number_format($campaign['budget_spent'], 2); ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if ($campaign['target_countries']): ?>
                                        <small><i class="fas fa-globe"></i> <?php echo htmlspecialchars($campaign['target_countries']); ?></small><br>
                                        <?php endif; ?>
                                        <?php if ($campaign['target_devices']): ?>
                                        <small><i class="fas fa-mobile"></i> <?php echo htmlspecialchars($campaign['target_devices']); ?></small><br>
                                        <?php endif; ?>
                                        <?php if ($campaign['target_os']): ?>
                                        <small><i class="fas fa-desktop"></i> <?php echo htmlspecialchars($campaign['target_os']); ?></small>
                                        <?php endif; ?>
                                        <?php if (!$campaign['target_countries'] && !$campaign['target_devices'] && !$campaign['target_os']): ?>
                                        <small class="text-muted">No targeting</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $campaign['creatives_count']; ?> creatives</span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $campaign['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($campaign['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="creative.php?campaign_id=<?php echo $campaign['id']; ?>&campaign_type=ron" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-images"></i> Creatives
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="editCampaign(<?php echo $campaign['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-network-wired fa-4x text-muted mb-4"></i>
                    <h4>No RON Campaigns Yet</h4>
                    <p class="text-muted mb-4">Create your first RON campaign to start advertising across our network.</p>
                    <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#createCampaignModal">
                        <i class="fas fa-plus me-2"></i>Create RON Campaign
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Campaign Modal -->
<div class="modal fade" id="createCampaignModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-network-wired me-2"></i>Create RON Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <!-- Basic Information -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Campaign Name *</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Advertiser</label>
                                                <select class="form-select" name="advertiser_id">
                                                    <option value="">Select Advertiser</option>
                                                    <?php foreach ($advertisers as $advertiser): ?>
                                                    <option value="<?php echo $advertiser['id']; ?>"><?php echo htmlspecialchars($advertiser['name']); ?></option>
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
                                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Landing URL *</label>
                                        <input type="url" class="form-control" name="landing_url" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Bidding -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6><i class="fas fa-dollar-sign me-2"></i>Bidding & Budget</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Bid Type *</label>
                                                <select class="form-select" name="bid_type" required>
                                                    <option value="cpm">CPM (Cost per 1000 Impressions)</option>
                                                    <option value="cpc">CPC (Cost per Click)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Bid Amount ($) *</label>
                                                <input type="number" class="form-control" name="bid_amount" step="0.01" min="0.01" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Format</label>
                                        <select class="form-select" name="format">
                                            <option value="banner">Banner</option>
                                            <option value="native">Native</option>
                                            <option value="video">Video</option>
                                            <option value="popup">Popup</option>
                                            <option value="popunder">Popunder</option>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Daily Budget ($)</label>
                                                <input type="number" class="form-control" name="daily_budget" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Total Budget ($)</label>
                                                <input type="number" class="form-control" name="total_budget" step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Start Date</label>
                                                <input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">End Date</label>
                                                <input type="date" class="form-control" name="end_date">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Frequency Cap (per user/day)</label>
                                        <input type="number" class="form-control" name="frequency_cap" min="0" placeholder="0 = unlimited">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <!-- Targeting Options -->
                            <div class="card">
                                <div class="card-header">
                                    <h6><i class="fas fa-crosshairs me-2"></i>Targeting Options</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Target Countries</label>
                                        <select class="form-select" name="target_countries" multiple>
                                            <option value="US">United States</option>
                                            <option value="CA">Canada</option>
                                            <option value="UK">United Kingdom</option>
                                            <option value="AU">Australia</option>
                                            <option value="DE">Germany</option>
                                            <option value="FR">France</option>
                                            <option value="IT">Italy</option>
                                            <option value="ES">Spain</option>
                                            <option value="BR">Brazil</option>
                                            <option value="MX">Mexico</option>
                                            <option value="IN">India</option>
                                            <option value="JP">Japan</option>
                                            <option value="CN">China</option>
                                            <option value="RU">Russia</option>
                                            <option value="ID">Indonesia</option>
                                        </select>
                                        <div class="form-text">Hold Ctrl/Cmd to select multiple. Leave empty for all countries.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Target Browsers</label>
                                        <select class="form-select" name="target_browsers" multiple>
                                            <option value="chrome">Chrome</option>
                                            <option value="firefox">Firefox</option>
                                            <option value="safari">Safari</option>
                                            <option value="edge">Edge</option>
                                            <option value="opera">Opera</option>
                                            <option value="ie">Internet Explorer</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Target Devices</label>
                                        <select class="form-select" name="target_devices" multiple>
                                            <option value="desktop">Desktop</option>
                                            <option value="mobile">Mobile</option>
                                            <option value="tablet">Tablet</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Target OS</label>
                                        <select class="form-select" name="target_os" multiple>
                                            <option value="windows">Windows</option>
                                            <option value="macos">macOS</option>
                                            <option value="linux">Linux</option>
                                            <option value="ios">iOS</option>
                                            <option value="android">Android</option>
                                            <option value="chromeos">Chrome OS</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Target Languages</label>
                                        <select class="form-select" name="target_languages" multiple>
                                            <option value="en">English</option>
                                            <option value="es">Spanish</option>
                                            <option value="fr">French</option>
                                            <option value="de">German</option>
                                            <option value="it">Italian</option>
                                            <option value="pt">Portuguese</option>
                                            <option value="ru">Russian</option>
                                            <option value="zh">Chinese</option>
                                            <option value="ja">Japanese</option>
                                            <option value="ko">Korean</option>
                                            <option value="ar">Arabic</option>
                                            <option value="hi">Hindi</option>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Target Age</label>
                                                <select class="form-select" name="target_age">
                                                    <option value="">All Ages</option>
                                                    <option value="18-24">18-24</option>
                                                    <option value="25-34">25-34</option>
                                                    <option value="35-44">35-44</option>
                                                    <option value="45-54">45-54</option>
                                                    <option value="55-64">55-64</option>
                                                    <option value="65+">65+</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Target Gender</label>
                                                <select class="form-select" name="target_gender">
                                                    <option value="">All Genders</option>
                                                    <option value="male">Male</option>
                                                    <option value="female">Female</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-arrow-right me-2"></i>Create Campaign & Add Creatives
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});

function editCampaign(id) {
    // Redirect to edit page or show edit modal
    window.location.href = `edit-ron-campaign.php?id=${id}`;
}
</script>

<?php include 'includes/footer.php'; ?>