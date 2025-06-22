<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Creative Management';
$message = '';

// Get campaign details
$campaign_id = intval($_GET['campaign_id'] ?? 0);
$campaign_type = $_GET['campaign_type'] ?? 'ron';
$is_new = isset($_GET['new']);

$campaign = null;
if ($campaign_id) {
    try {
        $table = $campaign_type == 'ron' ? 'ron_campaigns' : 'rtb_campaigns';
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$campaign_id]);
        $campaign = $stmt->fetch();
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Campaign not found.</div>';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create_creative') {
        $name = trim($_POST['name'] ?? '');
        $bid_amount = floatval($_POST['bid_amount'] ?? 0);
        $size = $_POST['size'] ?? '';
        $method = $_POST['method'] ?? 'html5';
        $html_content = $_POST['html_content'] ?? '';
        $script_content = $_POST['script_content'] ?? '';
        $image_url = $_POST['image_url'] ?? '';
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $call_to_action = $_POST['call_to_action'] ?? '';
        
        if ($name && $campaign_id && $size && $bid_amount > 0) {
            // Parse size
            $size_parts = explode('x', $size);
            $width = intval($size_parts[0] ?? 0);
            $height = intval($size_parts[1] ?? 0);
            
            if ($width > 0 && $height > 0) {
                try {
                    $content = '';
                    if ($method == 'html5') {
                        $content = $html_content;
                    } else {
                        $content = $script_content;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO creatives (
                        campaign_id, campaign_type, name, creative_type, method,
                        width, height, html_content, image_url, title, description,
                        call_to_action, bid_amount, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    
                    $stmt->execute([
                        $campaign_id, $campaign_type, $name, 'banner', $method,
                        $width, $height, $content, $image_url, $title, $description,
                        $call_to_action, $bid_amount, 'active'
                    ]);
                    
                    $creative_id = $pdo->lastInsertId();
                    $message = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Creative created successfully! ID: ' . $creative_id . '</div>';
                    
                } catch (Exception $e) {
                    $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">Invalid size format.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
        }
    }
}

// Get existing creatives for this campaign
$creatives = [];
if ($campaign_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM creatives WHERE campaign_id = ? AND campaign_type = ? ORDER BY created_at DESC");
        $stmt->execute([$campaign_id, $campaign_type]);
        $creatives = $stmt->fetchAll();
    } catch (Exception $e) {
        $creatives = [];
    }
}

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-images me-2"></i>Creative Management</h2>
                <?php if ($campaign): ?>
                <p class="text-muted mb-0">Campaign: <strong><?php echo htmlspecialchars($campaign['name']); ?></strong> (<?php echo strtoupper($campaign_type); ?>)</p>
                <?php endif; ?>
            </div>
            <div>
                <a href="ron-campaign.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Campaigns
                </a>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCreativeModal">
                    <i class="fas fa-plus me-2"></i>Create Creative
                </button>
            </div>
        </div>

        <?php echo $message; ?>

        <?php if ($is_new): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Campaign Created!</strong> Now create your first creative for this campaign.
        </div>
        <?php endif; ?>

        <!-- Campaign Summary -->
        <?php if ($campaign): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Campaign Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6>Bid Type</h6>
                            <span class="badge bg-primary fs-6"><?php echo strtoupper($campaign['bid_type'] ?? 'CPM'); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6>Bid Amount</h6>
                            <span class="fs-5">$<?php echo number_format($campaign['bid_amount'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6>Daily Budget</h6>
                            <span class="fs-5">$<?php echo number_format($campaign['daily_budget'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h6>Creatives</h6>
                            <span class="fs-5"><?php echo count($creatives); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Creatives List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Creatives (<?php echo count($creatives); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if ($creatives): ?>
                <div class="row">
                    <?php foreach ($creatives as $creative): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo htmlspecialchars($creative['name']); ?></h6>
                                <span class="badge bg-<?php echo $creative['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($creative['status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Size:</strong> <?php echo $creative['width']; ?>×<?php echo $creative['height']; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Method:</strong> <?php echo ucfirst($creative['method']); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Bid:</strong> $<?php echo number_format($creative['bid_amount'], 2); ?>
                                </div>
                                
                                <!-- Preview -->
                                <div class="mt-3">
                                    <strong>Preview:</strong>
                                    <div class="border rounded p-2 bg-light mt-2" style="width: <?php echo min($creative['width'], 300); ?>px; height: <?php echo min($creative['height'], 200); ?>px; overflow: hidden;">
                                        <?php if ($creative['html_content']): ?>
                                            <div style="transform: scale(<?php echo min(300/$creative['width'], 200/$creative['height'], 1); ?>); transform-origin: top left;">
                                                <?php echo $creative['html_content']; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                                <i class="fas fa-image fa-2x"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editCreative(<?php echo $creative['id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="previewCreative(<?php echo $creative['id']; ?>)">
                                        <i class="fas fa-eye"></i> Preview
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCreative(<?php echo $creative['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-images fa-4x text-muted mb-4"></i>
                    <h4>No Creatives Yet</h4>
                    <p class="text-muted mb-4">Create your first creative for this campaign.</p>
                    <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#createCreativeModal">
                        <i class="fas fa-plus me-2"></i>Create First Creative
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Creative Modal -->
<div class="modal fade" id="createCreativeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create New Creative</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="create_creative">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Basic Info -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Creative Name *</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Bid Amount ($) *</label>
                                        <input type="number" class="form-control" name="bid_amount" step="0.01" min="0.01" required 
                                               value="<?php echo $campaign['bid_amount'] ?? ''; ?>">
                                        <div class="form-text">Individual creative bid (can override campaign bid)</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Size *</label>
                                        <select class="form-select" name="size" required>
                                            <option value="">Select Size</option>
                                            <option value="300x250">300×250 (Medium Rectangle)</option>
                                            <option value="300x100">300×100 (Mobile Banner)</option>
                                            <option value="300x50">300×50 (Mobile Banner Small)</option>
                                            <option value="300x500">300×500 (Half Page)</option>
                                            <option value="900x250">900×250 (Billboard)</option>
                                            <option value="728x90">728×90 (Leaderboard)</option>
                                            <option value="160x600">160×600 (Wide Skyscraper)</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Method *</label>
                                        <select class="form-select" name="method" id="creativeMethod" required onchange="toggleMethod()">
                                            <option value="html5">HTML5 Code</option>
                                            <option value="script">Third-party Script</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Creative Content -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6><i class="fas fa-code me-2"></i>Creative Content</h6>
                                </div>
                                <div class="card-body">
                                    <!-- HTML5 Method -->
                                    <div id="html5Method">
                                        <div class="mb-3">
                                            <label class="form-label">HTML5 Code</label>
                                            <textarea class="form-control" name="html_content" rows="8" placeholder="<div>Your HTML5 creative code here...</div>"></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Image URL (optional)</label>
                                            <input type="url" class="form-control" name="image_url" placeholder="https://example.com/image.jpg">
                                        </div>
                                    </div>

                                    <!-- Script Method -->
                                    <div id="scriptMethod" style="display:none;">
                                        <div class="mb-3">
                                            <label class="form-label">Third-party Script</label>
                                            <textarea class="form-control" name="script_content" rows="8" placeholder="<script>...</script> or iframe code"></textarea>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Title (optional)</label>
                                        <input type="text" class="form-control" name="title" placeholder="Creative title">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description (optional)</label>
                                        <textarea class="form-control" name="description" rows="2" placeholder="Creative description"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Call to Action (optional)</label>
                                        <input type="text" class="form-control" name="call_to_action" placeholder="Click Here, Learn More, etc.">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Create Creative
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleMethod() {
    const method = document.getElementById('creativeMethod').value;
    const html5Method = document.getElementById('html5Method');
    const scriptMethod = document.getElementById('scriptMethod');
    
    if (method === 'html5') {
        html5Method.style.display = 'block';
        scriptMethod.style.display = 'none';
    } else {
        html5Method.style.display = 'none';
        scriptMethod.style.display = 'block';
    }
}

function editCreative(id) {
    // Implementation for edit creative
    window.location.href = `edit-creative.php?id=${id}`;
}

function previewCreative(id) {
    // Open preview in new window
    window.open(`preview-creative.php?id=${id}`, '_blank', 'width=800,height=600');
}

function deleteCreative(id) {
    if (confirm('Are you sure you want to delete this creative?')) {
        // Implementation for delete creative
        fetch('ajax/delete-creative.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({id: id})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting creative: ' + data.message);
            }
        });
    }
}

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
</script>

<?php include 'includes/footer.php'; ?>