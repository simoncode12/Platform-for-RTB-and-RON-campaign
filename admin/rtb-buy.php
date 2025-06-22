<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'RTB Buy';
$message = '';

// Get publishers for dropdown
$stmt = $pdo->query("SELECT * FROM publishers WHERE status = 'active' ORDER BY company_name");
$publishers = $stmt->fetchAll();

// Get websites for dropdown
$stmt = $pdo->query("SELECT w.*, p.company_name as publisher_name FROM websites w 
                     LEFT JOIN publishers p ON w.publisher_id = p.id 
                     WHERE w.status = 'active' ORDER BY w.name");
$websites = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $format = $_POST['format'] ?? '';
    $website_id = $_POST['website_id'] ?? '';
    $publisher_id = $_POST['publisher_id'] ?? '';
    
    if ($name && $format && $website_id && $publisher_id) {
        try {
            // Generate unique endpoint URL
            $endpoint_token = bin2hex(random_bytes(16));
            $endpoint_url = "https://up.adstart.click/api/rtb/request?token=" . $endpoint_token . "&format=" . $format;
            
            $stmt = $pdo->prepare("INSERT INTO rtb_endpoints (publisher_id, name, format, website_id, endpoint_url) VALUES (?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $publisher_id,
                $name,
                $format,
                $website_id,
                $endpoint_url
            ]);
            
            $message = '<div class="alert alert-success">RTB Endpoint created successfully!</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    }
}

// Get existing endpoints
$stmt = $pdo->query("SELECT e.*, p.company_name as publisher_name, w.name as website_name, w.url as website_url 
                     FROM rtb_endpoints e 
                     LEFT JOIN publishers p ON e.publisher_id = p.id 
                     LEFT JOIN websites w ON e.website_id = w.id 
                     ORDER BY e.created_at DESC");
$endpoints = $stmt->fetchAll();

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-shopping-cart me-2"></i>RTB Buy - Endpoint Generator</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEndpointModal">
                <i class="fas fa-plus me-2"></i>Generate Endpoint
            </button>
        </div>

        <?php echo $message; ?>

        <!-- Info Card -->
        <div class="card mb-4">
            <div class="card-body">
                <h5><i class="fas fa-info-circle me-2"></i>RTB Endpoint Generator</h5>
                <p class="mb-0">Generate unique RTB endpoints for publishers to buy traffic. These endpoints will compete with RON campaigns to serve the best ads to users.</p>
            </div>
        </div>

        <!-- Endpoints List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Generated RTB Endpoints</h5>
            </div>
            <div class="card-body">
                <?php if ($endpoints): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Endpoint Name</th>
                                <th>Publisher</th>
                                <th>Website</th>
                                <th>Format</th>
                                <th>Endpoint URL</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($endpoints as $endpoint): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($endpoint['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($endpoint['publisher_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($endpoint['website_name']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($endpoint['website_url']); ?></small>
                                </td>
                                <td><span class="badge bg-info"><?php echo ucfirst($endpoint['format']); ?></span></td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-sm" 
                                               value="<?php echo htmlspecialchars($endpoint['endpoint_url']); ?>" 
                                               id="endpoint_<?php echo $endpoint['id']; ?>" readonly>
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                onclick="copyToClipboard('endpoint_<?php echo $endpoint['id']; ?>')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?php echo $endpoint['status'] == 'active' ? 'checked' : ''; ?> 
                                               onchange="toggleEndpoint(<?php echo $endpoint['id']; ?>, this.checked)">
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-success" onclick="testEndpoint('<?php echo $endpoint['endpoint_url']; ?>')" title="Test Endpoint">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editEndpoint(<?php echo $endpoint['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteEndpoint(<?php echo $endpoint['id']; ?>)">
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
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h5>No RTB endpoints yet</h5>
                    <p class="text-muted">Generate your first RTB endpoint to start buying traffic for publishers.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Endpoint Modal -->
<div class="modal fade" id="createEndpointModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-link me-2"></i>Generate RTB Endpoint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Endpoint Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Publisher *</label>
                        <select class="form-select" name="publisher_id" required onchange="loadPublisherWebsites(this.value)">
                            <option value="">Select Publisher</option>
                            <?php foreach ($publishers as $publisher): ?>
                            <option value="<?php echo $publisher['id']; ?>">
                                <?php echo htmlspecialchars($publisher['company_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Website *</label>
                        <select class="form-select" name="website_id" required id="websiteSelect">
                            <option value="">Select Website</option>
                            <?php foreach ($websites as $website): ?>
                            <option value="<?php echo $website['id']; ?>" data-publisher="<?php echo $website['publisher_id']; ?>">
                                <?php echo htmlspecialchars($website['name']); ?> - <?php echo htmlspecialchars($website['url']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ad Format *</label>
                        <select class="form-select" name="format" required>
                            <option value="">Select Format</option>
                            <option value="banner">Banner</option>
                            <option value="native">Native</option>
                            <option value="video">Video</option>
                            <option value="popup">Popup</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> The system will automatically generate a unique endpoint URL that can be used to buy traffic from external RTB sources.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Endpoint</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadPublisherWebsites(publisherId) {
    const websiteSelect = document.getElementById('websiteSelect');
    const options = websiteSelect.querySelectorAll('option[data-publisher]');
    
    // Hide all website options first
    options.forEach(option => {
        option.style.display = 'none';
    });
    
    // Show only websites for selected publisher
    if (publisherId) {
        options.forEach(option => {
            if (option.dataset.publisher === publisherId) {
                option.style.display = 'block';
            }
        });
    }
    
    // Reset selection
    websiteSelect.value = '';
}

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(element.value);
    
    // Show feedback
    const button = element.nextElementSibling;
    const originalIcon = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check text-success"></i>';
    setTimeout(() => {
        button.innerHTML = originalIcon;
    }, 2000);
}

function testEndpoint(url) {
    // Open test endpoint in new window
    window.open(url + '&test=1', '_blank', 'width=800,height=600');
}

function toggleEndpoint(id, status) {
    fetch('ajax/toggle-endpoint.php', {
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

function editEndpoint(id) {
    console.log('Edit endpoint:', id);
}

function deleteEndpoint(id) {
    if (confirm('Are you sure you want to delete this endpoint?')) {
        console.log('Delete endpoint:', id);
    }
}
</script>

<?php include 'includes/footer.php'; ?>