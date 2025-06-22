<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Edit Creative';
$message = '';

// Get creative ID
$creative_id = intval($_GET['id'] ?? 0);

if (!$creative_id) {
    header('Location: creative.php');
    exit;
}

// Get creative details
$creative = null;
$campaign = null;

try {
    $stmt = $pdo->prepare("SELECT c.*, 
                           CASE 
                               WHEN c.campaign_type = 'ron' THEN rc.name
                               WHEN c.campaign_type = 'rtb' THEN rtb.name
                           END as campaign_name,
                           CASE 
                               WHEN c.campaign_type = 'ron' THEN rc.bid_amount
                               WHEN c.campaign_type = 'rtb' THEN rtb.cpm_bid
                           END as campaign_bid
                           FROM creatives c
                           LEFT JOIN ron_campaigns rc ON c.campaign_id = rc.id AND c.campaign_type = 'ron'
                           LEFT JOIN rtb_campaigns rtb ON c.campaign_id = rtb.id AND c.campaign_type = 'rtb'
                           WHERE c.id = ?");
    $stmt->execute([$creative_id]);
    $creative = $stmt->fetch();
    
    if (!$creative) {
        header('Location: creative.php?error=Creative not found');
        exit;
    }
} catch (Exception $e) {
    header('Location: creative.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update') {
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
        $status = $_POST['status'] ?? 'active';
        
        if ($name && $bid_amount > 0 && $size) {
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
                    
                    $stmt = $pdo->prepare("UPDATE creatives SET 
                        name = ?, method = ?, width = ?, height = ?, 
                        html_content = ?, image_url = ?, title = ?, 
                        description = ?, call_to_action = ?, bid_amount = ?, 
                        status = ?, updated_at = NOW()
                        WHERE id = ?");
                    
                    $stmt->execute([
                        $name, $method, $width, $height,
                        $content, $image_url, $title,
                        $description, $call_to_action, $bid_amount,
                        $status, $creative_id
                    ]);
                    
                    $message = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Creative updated successfully!</div>';
                    
                    // Refresh creative data
                    $stmt = $pdo->prepare("SELECT c.*, 
                                           CASE 
                                               WHEN c.campaign_type = 'ron' THEN rc.name
                                               WHEN c.campaign_type = 'rtb' THEN rtb.name
                                           END as campaign_name,
                                           CASE 
                                               WHEN c.campaign_type = 'ron' THEN rc.bid_amount
                                               WHEN c.campaign_type = 'rtb' THEN rtb.cpm_bid
                                           END as campaign_bid
                                           FROM creatives c
                                           LEFT JOIN ron_campaigns rc ON c.campaign_id = rc.id AND c.campaign_type = 'ron'
                                           LEFT JOIN rtb_campaigns rtb ON c.campaign_id = rtb.id AND c.campaign_type = 'rtb'
                                           WHERE c.id = ?");
                    $stmt->execute([$creative_id]);
                    $creative = $stmt->fetch();
                    
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
    
    if ($action == 'duplicate') {
        try {
            $stmt = $pdo->prepare("INSERT INTO creatives (
                campaign_id, campaign_type, name, creative_type, method,
                width, height, html_content, image_url, title, description,
                call_to_action, bid_amount, status, created_at
            ) SELECT 
                campaign_id, campaign_type, CONCAT(name, ' (Copy)'), creative_type, method,
                width, height, html_content, image_url, title, description,
                call_to_action, bid_amount, 'inactive', NOW()
            FROM creatives WHERE id = ?");
            
            $stmt->execute([$creative_id]);
            $new_id = $pdo->lastInsertId();
            
            $message = '<div class="alert alert-success"><i class="fas fa-copy me-2"></i>Creative duplicated successfully! <a href="edit-creative.php?id=' . $new_id . '" class="alert-link">Edit the copy</a></div>';
            
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error duplicating creative: ' . $e->getMessage() . '</div>';
        }
    }
}

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-edit me-2"></i>Edit Creative</h2>
                <p class="text-muted mb-0">
                    Campaign: <strong><?php echo htmlspecialchars($creative['campaign_name']); ?></strong> 
                    (<?php echo strtoupper($creative['campaign_type']); ?>)
                </p>
            </div>
            <div>
                <a href="creative.php?campaign_id=<?php echo $creative['campaign_id']; ?>&campaign_type=<?php echo $creative['campaign_type']; ?>" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Creatives
                </a>
                <button class="btn btn-outline-info me-2" onclick="previewCreative()">
                    <i class="fas fa-eye me-2"></i>Preview
                </button>
                <div class="btn-group">
                    <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-2"></i>Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="duplicateCreative()">
                            <i class="fas fa-copy me-2"></i>Duplicate Creative
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="testCreative()">
                            <i class="fas fa-flask me-2"></i>Test Creative
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteCreative()">
                            <i class="fas fa-trash me-2"></i>Delete Creative
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Creative Info Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Creative Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>ID</h6>
                            <span class="badge bg-secondary fs-6">#<?php echo $creative['id']; ?></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Size</h6>
                            <span class="fs-6"><?php echo $creative['width']; ?>×<?php echo $creative['height']; ?></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Method</h6>
                            <span class="badge bg-info"><?php echo strtoupper($creative['method']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Status</h6>
                            <span class="badge bg-<?php echo $creative['status'] == 'active' ? 'success' : ($creative['status'] == 'pending' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($creative['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Created</h6>
                            <span class="fs-6"><?php echo date('M j, Y', strtotime($creative['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6>Last Update</h6>
                            <span class="fs-6"><?php echo date('M j, H:i', strtotime($creative['updated_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Edit Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-edit me-2"></i>Edit Creative Details</h5>
                    </div>
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update">
                        <div class="card-body">
                            <!-- Basic Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Creative Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($creative['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status *</label>
                                        <select class="form-select" name="status" required>
                                            <option value="active" <?php echo $creative['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $creative['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="pending" <?php echo $creative['status'] == 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                                            <option value="rejected" <?php echo $creative['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Bid Amount ($) *</label>
                                        <input type="number" class="form-control" name="bid_amount" step="0.01" min="0.01" 
                                               value="<?php echo $creative['bid_amount']; ?>" required>
                                        <div class="form-text">Campaign bid: $<?php echo number_format($creative['campaign_bid'], 2); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Size *</label>
                                        <select class="form-select" name="size" required>
                                            <?php 
                                            $current_size = $creative['width'] . 'x' . $creative['height'];
                                            $sizes = [
                                                '300x250' => '300×250 (Medium Rectangle)',
                                                '300x100' => '300×100 (Mobile Banner)',
                                                '300x50' => '300×50 (Mobile Banner Small)',
                                                '300x500' => '300×500 (Half Page)',
                                                '900x250' => '900×250 (Billboard)',
                                                '728x90' => '728×90 (Leaderboard)',
                                                '160x600' => '160×600 (Wide Skyscraper)',
                                                '320x50' => '320×50 (Mobile Banner)',
                                                '468x60' => '468×60 (Banner)',
                                                '970x250' => '970×250 (Billboard Large)'
                                            ];
                                            ?>
                                            <?php foreach ($sizes as $size_value => $size_label): ?>
                                            <option value="<?php echo $size_value; ?>" <?php echo $current_size == $size_value ? 'selected' : ''; ?>>
                                                <?php echo $size_label; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Method *</label>
                                <select class="form-select" name="method" id="creativeMethod" required onchange="toggleMethod()">
                                    <option value="html5" <?php echo $creative['method'] == 'html5' ? 'selected' : ''; ?>>HTML5 Code</option>
                                    <option value="script" <?php echo $creative['method'] == 'script' ? 'selected' : ''; ?>>Third-party Script</option>
                                </select>
                            </div>

                            <!-- Creative Content -->
                            <div id="html5Method" <?php echo $creative['method'] != 'html5' ? 'style="display:none;"' : ''; ?>>
                                <div class="mb-3">
                                    <label class="form-label">HTML5 Code</label>
                                    <textarea class="form-control font-monospace" name="html_content" rows="8" 
                                              placeholder="<div>Your HTML5 creative code here...</div>"><?php echo htmlspecialchars($creative['html_content']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Image URL (optional)</label>
                                    <input type="url" class="form-control" name="image_url" 
                                           value="<?php echo htmlspecialchars($creative['image_url']); ?>"
                                           placeholder="https://example.com/image.jpg">
                                </div>
                            </div>

                            <div id="scriptMethod" <?php echo $creative['method'] != 'script' ? 'style="display:none;"' : ''; ?>>
                                <div class="mb-3">
                                    <label class="form-label">Third-party Script</label>
                                    <textarea class="form-control font-monospace" name="script_content" rows="8" 
                                              placeholder="<script>...</script> or iframe code"><?php echo htmlspecialchars($creative['html_content']); ?></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Title (optional)</label>
                                        <input type="text" class="form-control" name="title" 
                                               value="<?php echo htmlspecialchars($creative['title']); ?>"
                                               placeholder="Creative title">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Call to Action (optional)</label>
                                        <input type="text" class="form-control" name="call_to_action" 
                                               value="<?php echo htmlspecialchars($creative['call_to_action']); ?>"
                                               placeholder="Click Here, Learn More, etc.">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description (optional)</label>
                                <textarea class="form-control" name="description" rows="3" 
                                          placeholder="Creative description"><?php echo htmlspecialchars($creative['description']); ?></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="button" class="btn btn-outline-info me-2" onclick="previewCreative()">
                                        <i class="fas fa-eye me-2"></i>Preview Changes
                                    </button>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Creative
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview & Actions -->
            <div class="col-md-4">
                <!-- Current Preview -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6><i class="fas fa-eye me-2"></i>Current Preview</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="border rounded p-2 bg-light" 
                             style="width: <?php echo min($creative['width'], 250); ?>px; height: <?php echo min($creative['height'], 200); ?>px; overflow: hidden; margin: 0 auto;">
                            <?php if ($creative['html_content']): ?>
                                <div style="transform: scale(<?php echo min(250/$creative['width'], 200/$creative['height'], 1); ?>); transform-origin: top left;">
                                    <?php echo $creative['html_content']; ?>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <i class="fas fa-image fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted"><?php echo $creative['width']; ?>×<?php echo $creative['height']; ?> pixels</small>
                        </div>
                    </div>
                </div>

                <!-- Creative Statistics -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-bar me-2"></i>Performance</h6>
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
                                    <small class="text-muted">Spent</small>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <a href="reports/creative-stats.php?id=<?php echo $creative['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-chart-line me-1"></i>View Detailed Stats
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
                                <button type="submit" class="btn btn-outline-info btn-sm w-100" onclick="return confirm('Duplicate this creative?')">
                                    <i class="fas fa-copy me-2"></i>Duplicate Creative
                                </button>
                            </form>
                            
                            <button class="btn btn-outline-warning btn-sm" onclick="testCreative()">
                                <i class="fas fa-flask me-2"></i>Test in Ad Zone
                            </button>
                            
                            <button class="btn btn-outline-success btn-sm" onclick="exportCreative()">
                                <i class="fas fa-download me-2"></i>Export Code
                            </button>
                            
                            <hr class="my-2">
                            
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteCreative()">
                                <i class="fas fa-trash me-2"></i>Delete Creative
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Creative Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="previewContainer" class="border rounded p-3 bg-light d-inline-block">
                    <!-- Preview will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});

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

function previewCreative() {
    const method = document.getElementById('creativeMethod').value;
    const size = document.querySelector('select[name="size"]').value;
    const [width, height] = size.split('x');
    
    let content = '';
    if (method === 'html5') {
        content = document.querySelector('textarea[name="html_content"]').value;
    } else {
        content = document.querySelector('textarea[name="script_content"]').value;
    }
    
    const previewContainer = document.getElementById('previewContainer');
    previewContainer.style.width = width + 'px';
    previewContainer.style.height = height + 'px';
    previewContainer.innerHTML = content || '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="fas fa-image fa-2x"></i></div>';
    
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function resetForm() {
    if (confirm('Reset form to original values? All unsaved changes will be lost.')) {
        location.reload();
    }
}

function duplicateCreative() {
    if (confirm('Create a copy of this creative?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'duplicate';
        
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function testCreative() {
    // Open test window with creative
    const size = document.querySelector('select[name="size"]').value;
    const method = document.getElementById('creativeMethod').value;
    let content = '';
    
    if (method === 'html5') {
        content = document.querySelector('textarea[name="html_content"]').value;
    } else {
        content = document.querySelector('textarea[name="script_content"]').value;
    }
    
    const testWindow = window.open('', '_blank', 'width=800,height=600');
    testWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Creative Test - ${size}</title>
            <style>
                body { margin: 20px; font-family: Arial, sans-serif; }
                .test-container { border: 2px dashed #ccc; padding: 20px; display: inline-block; }
                .info { margin-bottom: 20px; background: #f8f9fa; padding: 10px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="info">
                <h3>Creative Test</h3>
                <p><strong>Size:</strong> ${size} | <strong>Method:</strong> ${method.toUpperCase()}</p>
            </div>
            <div class="test-container">
                ${content}
            </div>
        </body>
        </html>
    `);
}

function exportCreative() {
    const method = document.getElementById('creativeMethod').value;
    let content = '';
    
    if (method === 'html5') {
        content = document.querySelector('textarea[name="html_content"]').value;
    } else {
        content = document.querySelector('textarea[name="script_content"]').value;
    }
    
    const blob = new Blob([content], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `creative_${<?php echo $creative_id; ?>}_${method}.${method === 'html5' ? 'html' : 'js'}`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function deleteCreative() {
    if (confirm('Are you sure you want to delete this creative?\n\nThis action cannot be undone.')) {
        fetch('ajax/delete-creative.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: <?php echo $creative_id; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Creative deleted successfully!');
                window.location.href = 'creative.php?campaign_id=<?php echo $creative['campaign_id']; ?>&campaign_type=<?php echo $creative['campaign_type']; ?>';
            } else {
                alert('Error deleting creative: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}
</script>

<style>
.font-monospace {
    font-family: 'Courier New', Courier, monospace;
    font-size: 14px;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

#previewContainer {
    max-width: 100%;
    overflow: auto;
}

.btn-group .dropdown-menu {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>

<?php include 'includes/footer.php'; ?>