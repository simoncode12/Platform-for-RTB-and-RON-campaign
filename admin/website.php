<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Websites';
$message = '';

// Get publishers for dropdown
$stmt = $pdo->query("SELECT * FROM publishers WHERE status = 'active' ORDER BY company_name");
$publishers = $stmt->fetchAll();

// Get categories for dropdown
$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $url = $_POST['url'] ?? '';
    $publisher_id = $_POST['publisher_id'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    
    if ($name && $url && $publisher_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO websites (publisher_id, name, url, category_id) VALUES (?, ?, ?, ?)");
            
            $stmt->execute([
                $publisher_id,
                $name,
                $url,
                $category_id ?: null
            ]);
            
            $message = '<div class="alert alert-success">Website added successfully!</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    }
}

// Get existing websites
$stmt = $pdo->query("SELECT w.*, p.company_name as publisher_name, c.name as category_name,
                     (SELECT COUNT(*) FROM zones WHERE website_id = w.id) as zone_count
                     FROM websites w 
                     LEFT JOIN publishers p ON w.publisher_id = p.id 
                     LEFT JOIN categories c ON w.category_id = c.id 
                     ORDER BY w.created_at DESC");
$websites = $stmt->fetchAll();

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-globe me-2"></i>Website Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWebsiteModal">
                <i class="fas fa-plus me-2"></i>Add Website
            </button>
        </div>

        <?php echo $message; ?>

        <!-- Websites List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Registered Websites</h5>
            </div>
            <div class="card-body">
                <?php if ($websites): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Website</th>
                                <th>Publisher</th>
                                <th>Category</th>
                                <th>Zones</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($websites as $website): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($website['name']); ?></strong>
                                    <br>
                                    <a href="<?php echo htmlspecialchars($website['url']); ?>" target="_blank" class="text-muted text-decoration-none">
                                        <i class="fas fa-external-link-alt me-1"></i>
                                        <?php echo htmlspecialchars($website['url']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($website['publisher_name']); ?></td>
                                <td>
                                    <?php if ($website['category_name']): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($website['category_name']); ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $website['zone_count'] > 0 ? 'success' : 'warning'; ?>">
                                        <?php echo $website['zone_count']; ?> Zone<?php echo $website['zone_count'] != 1 ? 's' : ''; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'active' => 'success',
                                        'inactive' => 'secondary',
                                        'pending' => 'warning'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $status_colors[$website['status']]; ?>">
                                        <?php echo ucfirst($website['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="zone.php?website_id=<?php echo $website['id']; ?>">
                                                <i class="fas fa-map-marked-alt me-2"></i>Manage Zones
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="approveWebsite(<?php echo $website['id']; ?>)">
                                                <i class="fas fa-check me-2"></i>Approve
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="rejectWebsite(<?php echo $website['id']; ?>)">
                                                <i class="fas fa-times me-2"></i>Reject
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="editWebsite(<?php echo $website['id']; ?>)">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteWebsite(<?php echo $website['id']; ?>)">
                                                <i class="fas fa-trash me-2"></i>Delete
                                            </a></li>
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
                    <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                    <h5>No websites yet</h5>
                    <p class="text-muted">Add your first website to start managing ad zones and traffic.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Website Modal -->
<div class="modal fade" id="createWebsiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-globe me-2"></i>Add Website</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Website Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Website URL *</label>
                        <input type="url" class="form-control" name="url" placeholder="https://example.com" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Publisher *</label>
                        <select class="form-select" name="publisher_id" required>
                            <option value="">Select Publisher</option>
                            <?php foreach ($publishers as $publisher): ?>
                            <option value="<?php echo $publisher['id']; ?>">
                                <?php echo htmlspecialchars($publisher['company_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
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
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> New websites will be set to "pending" status and require approval before going live.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Website</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveWebsite(id) {
    if (confirm('Approve this website?')) {
        updateWebsiteStatus(id, 'active');
    }
}

function rejectWebsite(id) {
    if (confirm('Reject this website?')) {
        updateWebsiteStatus(id, 'inactive');
    }
}

function updateWebsiteStatus(id, status) {
    fetch('ajax/update-website-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating website status');
        }
    });
}

function editWebsite(id) {
    console.log('Edit website:', id);
}

function deleteWebsite(id) {
    if (confirm('Are you sure you want to delete this website? This will also delete all associated zones.')) {
        console.log('Delete website:', id);
    }
}
</script>

<?php include 'includes/footer.php'; ?>