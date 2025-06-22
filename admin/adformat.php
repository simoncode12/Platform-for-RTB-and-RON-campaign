<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Ad Formats';
$message = '';

// Create ad_formats table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ad_formats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        type ENUM('banner', 'native', 'video', 'popup', 'interstitial', 'rewarded') NOT NULL,
        sizes JSON,
        min_width INT,
        max_width INT,
        min_height INT,
        max_height INT,
        supports_html5 BOOLEAN DEFAULT TRUE,
        supports_script BOOLEAN DEFAULT TRUE,
        supports_image BOOLEAN DEFAULT TRUE,
        supports_video BOOLEAN DEFAULT FALSE,
        default_cpm DECIMAL(10,4) DEFAULT 0.0000,
        default_cpc DECIMAL(10,4) DEFAULT 0.0000,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
    // Table might already exist
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $type = $_POST['type'] ?? '';
        $sizes = $_POST['sizes'] ?? [];
        $min_width = $_POST['min_width'] ?? null;
        $max_width = $_POST['max_width'] ?? null;
        $min_height = $_POST['min_height'] ?? null;
        $max_height = $_POST['max_height'] ?? null;
        $supports_html5 = isset($_POST['supports_html5']) ? 1 : 0;
        $supports_script = isset($_POST['supports_script']) ? 1 : 0;
        $supports_image = isset($_POST['supports_image']) ? 1 : 0;
        $supports_video = isset($_POST['supports_video']) ? 1 : 0;
        $default_cpm = $_POST['default_cpm'] ?? 0.0000;
        $default_cpc = $_POST['default_cpc'] ?? 0.0000;
        
        if ($name && $type) {
            try {
                $stmt = $pdo->prepare("INSERT INTO ad_formats (name, description, type, sizes, min_width, max_width, min_height, max_height, supports_html5, supports_script, supports_image, supports_video, default_cpm, default_cpc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $name,
                    $description,
                    $type,
                    json_encode($sizes),
                    $min_width ?: null,
                    $max_width ?: null,
                    $min_height ?: null,
                    $max_height ?: null,
                    $supports_html5,
                    $supports_script,
                    $supports_image,
                    $supports_video,
                    $default_cpm,
                    $default_cpc
                ]);
                
                $message = '<div class="alert alert-success">Ad format created successfully!</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
        }
    }
}

// Predefined banner sizes
$standard_sizes = [
    '300x250' => 'Medium Rectangle',
    '728x90' => 'Leaderboard',
    '300x100' => 'Mobile Banner',
    '300x50' => 'Mobile Banner Small',
    '300x500' => 'Half Page',
    '900x250' => 'Billboard',
    '160x600' => 'Wide Skyscraper',
    '120x600' => 'Skyscraper',
    '468x60' => 'Banner',
    '234x60' => 'Half Banner',
    '88x31' => 'Micro Bar',
    '320x50' => 'Mobile Banner',
    '320x100' => 'Large Mobile Banner',
    '970x90' => 'Large Leaderboard',
    '970x250' => 'Billboard',
    '336x280' => 'Large Rectangle',
    '250x250' => 'Square',
    '200x200' => 'Small Square',
    '180x150' => 'Rectangle',
    '125x125' => 'Button'
];

// Get existing ad formats with usage statistics
$stmt = $pdo->query("SELECT af.*,
                     (SELECT COUNT(*) FROM rtb_campaigns WHERE JSON_CONTAINS(LOWER(format), LOWER(CONCAT('\"', af.name, '\"')))) as rtb_usage,
                     (SELECT COUNT(*) FROM ron_campaigns WHERE LOWER(format) = LOWER(af.name)) as ron_usage,
                     (SELECT COUNT(*) FROM creatives WHERE JSON_CONTAINS(LOWER(af.sizes), LOWER(CONCAT('\"', creatives.size, '\"')))) as creative_usage
                     FROM ad_formats af 
                     ORDER BY af.type, af.name");
$formats = $stmt->fetchAll();

// Group formats by type
$grouped_formats = [];
foreach ($formats as $format) {
    $grouped_formats[$format['type']][] = $format;
}

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-th-large me-2"></i>Ad Format Management</h2>
            <div>
                <button class="btn btn-success me-2" onclick="initializeStandardFormats()">
                    <i class="fas fa-magic me-2"></i>Initialize Standard Formats
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFormatModal">
                    <i class="fas fa-plus me-2"></i>Create Ad Format
                </button>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Overview Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-th-large fa-2x text-primary mb-2"></i>
                        <h4><?php echo count($formats); ?></h4>
                        <p class="text-muted mb-0">Total Formats</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-square fa-2x text-info mb-2"></i>
                        <h4><?php echo count($grouped_formats['banner'] ?? []); ?></h4>
                        <p class="text-muted mb-0">Banner Formats</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-play fa-2x text-warning mb-2"></i>
                        <h4><?php echo count($grouped_formats['video'] ?? []); ?></h4>
                        <p class="text-muted mb-0">Video Formats</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-list fa-2x text-success mb-2"></i>
                        <h4><?php echo count($grouped_formats['native'] ?? []); ?></h4>
                        <p class="text-muted mb-0">Native Formats</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ad Formats by Type -->
        <?php foreach ($grouped_formats as $type => $type_formats): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5>
                    <i class="fas fa-<?php 
                        switch($type) {
                            case 'banner': echo 'square'; break;
                            case 'video': echo 'play'; break;
                            case 'native': echo 'list'; break;
                            case 'popup': echo 'window-maximize'; break;
                            case 'interstitial': echo 'expand'; break;
                            case 'rewarded': echo 'gift'; break;
                            default: echo 'th-large';
                        }
                    ?> me-2"></i>
                    <?php echo ucfirst($type); ?> Ad Formats
                    <span class="badge bg-secondary ms-2"><?php echo count($type_formats); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($type_formats): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Format Name</th>
                                <th>Description</th>
                                <th>Sizes</th>
                                <th>Supported Types</th>
                                <th>Default Rates</th>
                                <th>Usage</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($type_formats as $format): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($format['name']); ?></strong>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($format['description'] ?: 'No description'); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php 
                                    $sizes = json_decode($format['sizes'], true) ?: [];
                                    if ($sizes): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($sizes as $size): ?>
                                            <span class="badge bg-light text-dark"><?php echo htmlspecialchars($size); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Flexible</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php if ($format['supports_html5']): ?>
                                        <span class="badge bg-primary">HTML5</span>
                                        <?php endif; ?>
                                        <?php if ($format['supports_script']): ?>
                                        <span class="badge bg-info">Script</span>
                                        <?php endif; ?>
                                        <?php if ($format['supports_image']): ?>
                                        <span class="badge bg-success">Image</span>
                                        <?php endif; ?>
                                        <?php if ($format['supports_video']): ?>
                                        <span class="badge bg-warning">Video</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <small>
                                        CPM: $<?php echo number_format($format['default_cpm'], 4); ?><br>
                                        CPC: $<?php echo number_format($format['default_cpc'], 4); ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        RTB: <?php echo $format['rtb_usage']; ?><br>
                                        RON: <?php echo $format['ron_usage']; ?><br>
                                        Creatives: <?php echo $format['creative_usage']; ?>
                                    </small>
                                </td>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?php echo $format['status'] == 'active' ? 'checked' : ''; ?> 
                                               onchange="toggleFormat(<?php echo $format['id']; ?>, this.checked)">
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="viewFormatDetails(<?php echo $format['id']; ?>)">
                                                <i class="fas fa-eye me-2"></i>View Details
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="editFormat(<?php echo $format['id']; ?>)">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="duplicateFormat(<?php echo $format['id']; ?>)">
                                                <i class="fas fa-copy me-2"></i>Duplicate
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php if ($format['rtb_usage'] == 0 && $format['ron_usage'] == 0 && $format['creative_usage'] == 0): ?>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteFormat(<?php echo $format['id']; ?>)">
                                                <i class="fas fa-trash me-2"></i>Delete
                                            </a></li>
                                            <?php else: ?>
                                            <li><a class="dropdown-item text-muted disabled" href="#">
                                                <i class="fas fa-info-circle me-2"></i>Cannot delete (in use)
                                            </a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-th-large fa-3x text-muted mb-3"></i>
                    <h5>No <?php echo $type; ?> formats yet</h5>
                    <p class="text-muted">Create your first <?php echo $type; ?> ad format.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($grouped_formats)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-th-large fa-4x text-muted mb-4"></i>
                <h4>No Ad Formats Yet</h4>
                <p class="text-muted mb-4">Start by creating ad formats or initialize with standard industry formats.</p>
                <button class="btn btn-primary btn-lg me-3" data-bs-toggle="modal" data-bs-target="#createFormatModal">
                    <i class="fas fa-plus me-2"></i>Create Custom Format
                </button>
                <button class="btn btn-success btn-lg" onclick="initializeStandardFormats()">
                    <i class="fas fa-magic me-2"></i>Initialize Standard Formats
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Format Modal -->
<div class="modal fade" id="createFormatModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-th-large me-2"></i>Create Ad Format</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Format Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Format Type *</label>
                                <select class="form-select" name="type" required onchange="updateFormatOptions(this.value)">
                                    <option value="">Select Type</option>
                                    <option value="banner">Banner</option>
                                    <option value="native">Native</option>
                                    <option value="video">Video</option>
                                    <option value="popup">Popup</option>
                                    <option value="interstitial">Interstitial</option>
                                    <option value="rewarded">Rewarded</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    
                    <!-- Size Configuration -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6><i class="fas fa-ruler-combined me-2"></i>Size Configuration</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Supported Sizes</label>
                                <div class="row" id="sizeCheckboxes">
                                    <?php foreach ($standard_sizes as $size => $name): ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="sizes[]" value="<?php echo $size; ?>" id="size_<?php echo str_replace('x', '_', $size); ?>">
                                            <label class="form-check-label" for="size_<?php echo str_replace('x', '_', $size); ?>">
                                                <small><?php echo $size; ?><br><span class="text-muted"><?php echo $name; ?></span></small>
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
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Min Width (px)</label>
                                        <input type="number" class="form-control" name="min_width" min="1">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Max Width (px)</label>
                                        <input type="number" class="form-control" name="max_width" min="1">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Min Height (px)</label>
                                        <input type="number" class="form-control" name="min_height" min="1">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Max Height (px)</label>
                                        <input type="number" class="form-control" name="max_height" min="1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Supported Content Types -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6><i class="fas fa-file-code me-2"></i>Supported Content Types</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="supports_html5" id="supports_html5" checked>
                                        <label class="form-check-label" for="supports_html5">
                                            HTML5
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="supports_script" id="supports_script" checked>
                                        <label class="form-check-label" for="supports_script">
                                            JavaScript/Script
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="supports_image" id="supports_image" checked>
                                        <label class="form-check-label" for="supports_image">
                                            Image
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="supports_video" id="supports_video">
                                        <label class="form-check-label" for="supports_video">
                                            Video
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Default Rates -->
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="fas fa-dollar-sign me-2"></i>Default Bid Rates</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Default CPM Rate ($)</label>
                                        <input type="number" class="form-control" name="default_cpm" step="0.0001" min="0" value="0.0000">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Default CPC Rate ($)</label>
                                        <input type="number" class="form-control" name="default_cpc" step="0.0001" min="0" value="0.0000">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Format</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateFormatOptions(type) {
    const videoSupport = document.getElementById('supports_video');
    
    // Auto-enable video support for video formats
    if (type === 'video') {
        videoSupport.checked = true;
    }
    
    // Suggest sizes based on format type
    const sizeCheckboxes = document.querySelectorAll('input[name="sizes[]"]');
    sizeCheckboxes.forEach(cb => cb.checked = false);
    
    if (type === 'banner') {
        // Select common banner sizes
        ['300x250', '728x90', '300x100', '900x250', '160x600'].forEach(size => {
            const checkbox = document.getElementById('size_' + size.replace('x', '_'));
            if (checkbox) checkbox.checked = true;
        });
    } else if (type === 'video') {
        // Select video-friendly sizes
        ['300x250', '640x360', '480x270'].forEach(size => {
            const checkbox = document.getElementById('size_' + size.replace('x', '_'));
            if (checkbox) checkbox.checked = true;
        });
    }
}

function toggleAllSizes(checkbox) {
    const sizeCheckboxes = document.querySelectorAll('input[name="sizes[]"]');
    sizeCheckboxes.forEach(cb => cb.checked = checkbox.checked);
}

function toggleFormat(id, status) {
    fetch('ajax/toggle-format.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            status: status ? 'active' : 'inactive'
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

function initializeStandardFormats() {
    if (confirm('This will create standard industry ad formats. Continue?')) {
        fetch('ajax/initialize-standard-formats.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error initializing formats: ' + data.message);
            }
        });
    }
}

function viewFormatDetails(id) {
    window.open('format-details.php?id=' + id, '_blank', 'width=800,height=600');
}

function editFormat(id) {
    console.log('Edit format:', id);
}

function duplicateFormat(id) {
    if (confirm('Create a copy of this ad format?')) {
        fetch('ajax/duplicate-format.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error duplicating format: ' + data.message);
            }
        });
    }
}

function deleteFormat(id) {
    if (confirm('Are you sure you want to delete this ad format? This action cannot be undone.')) {
        fetch('ajax/delete-format.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting format: ' + data.message);
            }
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>