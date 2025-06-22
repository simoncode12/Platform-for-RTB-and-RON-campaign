<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Publishers';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = $_POST['company_name'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $revenue_share = $_POST['revenue_share'] ?? 50.00;
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($company_name && $contact_email && $username && $password) {
        try {
            $pdo->beginTransaction();
            
            // Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'publisher')");
            $stmt->execute([$username, $hashed_password, $contact_email]);
            $user_id = $pdo->lastInsertId();
            
            // Create publisher profile
            $stmt = $pdo->prepare("INSERT INTO publishers (user_id, company_name, contact_email, revenue_share) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $company_name, $contact_email, $revenue_share]);
            
            $pdo->commit();
            $message = '<div class="alert alert-success">Publisher created successfully!</div>';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    }
}

// Get existing publishers with website and zone stats
$stmt = $pdo->query("SELECT p.*, u.username, u.status as user_status,
                     (SELECT COUNT(*) FROM websites WHERE publisher_id = p.id) as website_count,
                     (SELECT COUNT(*) FROM zones z JOIN websites w ON z.website_id = w.id WHERE w.publisher_id = p.id) as zone_count
                     FROM publishers p 
                     LEFT JOIN users u ON p.user_id = u.id 
                     ORDER BY p.created_at DESC");
$publishers = $stmt->fetchAll();

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users me-2"></i>Publisher Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPublisherModal">
                <i class="fas fa-plus me-2"></i>Add Publisher
            </button>
        </div>

        <?php echo $message; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h4><?php echo count($publishers); ?></h4>
                        <p class="text-muted mb-0">Total Publishers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h4><?php echo count(array_filter($publishers, fn($p) => $p['status'] == 'active')); ?></h4>
                        <p class="text-muted mb-0">Active</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-globe fa-2x text-info mb-2"></i>
                        <h4><?php echo array_sum(array_column($publishers, 'website_count')); ?></h4>
                        <p class="text-muted mb-0">Websites</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-map-marked-alt fa-2x text-warning mb-2"></i>
                        <h4><?php echo array_sum(array_column($publishers, 'zone_count')); ?></h4>
                        <p class="text-muted mb-0">Ad Zones</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Publishers List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Registered Publishers</h5>
            </div>
            <div class="card-body">
                <?php if ($publishers): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Contact</th>
                                <th>Username</th>
                                <th>Revenue Share</th>
                                <th>Properties</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($publishers as $publisher): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($publisher['company_name']); ?></strong>
                                </td>
                                <td>
                                    <i class="fas fa-envelope me-1"></i>
                                    <?php echo htmlspecialchars($publisher['contact_email']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($publisher['username']); ?></td>
                                <td>
                                    <span class="badge bg-success"><?php echo $publisher['revenue_share']; ?>%</span>
                                </td>
                                <td>
                                    <span class="badge bg-info me-1"><?php echo $publisher['website_count']; ?> Sites</span>
                                    <span class="badge bg-warning"><?php echo $publisher['zone_count']; ?> Zones</span>
                                </td>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?php echo $publisher['status'] == 'active' ? 'checked' : ''; ?> 
                                               onchange="togglePublisher(<?php echo $publisher['id']; ?>, this.checked)">
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="website.php?publisher_id=<?php echo $publisher['id']; ?>">
                                                <i class="fas fa-globe me-2"></i>Manage Websites
                                            </a></li>
                                            <li><a class="dropdown-item" href="zone.php?publisher_id=<?php echo $publisher['id']; ?>">
                                                <i class="fas fa-map-marked-alt me-2"></i>Manage Zones
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="viewEarnings(<?php echo $publisher['id']; ?>)">
                                                <i class="fas fa-dollar-sign me-2"></i>View Earnings
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="editRevShare(<?php echo $publisher['id']; ?>, <?php echo $publisher['revenue_share']; ?>)">
                                                <i class="fas fa-percentage me-2"></i>Edit Revenue Share
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="editPublisher(<?php echo $publisher['id']; ?>)">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deletePublisher(<?php echo $publisher['id']; ?>)">
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
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5>No publishers yet</h5>
                    <p class="text-muted">Add your first publisher to start managing websites and ad zones.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Publisher Modal -->
<div class="modal fade" id="createPublisherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-users me-2"></i>Add Publisher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" class="form-control" name="company_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contact Email *</label>
                        <input type="email" class="form-control" name="contact_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Revenue Share (%) *</label>
                        <input type="number" class="form-control" name="revenue_share" value="50.00" step="0.01" min="0" max="100" required>
                        <div class="form-text">Default: 50%</div>
                    </div>
                    
                    <hr>
                    <h6>Login Credentials</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Publisher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Revenue Share Modal -->
<div class="modal fade" id="editRevShareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-percentage me-2"></i>Edit Revenue Share</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="revShareForm">
                <div class="modal-body">
                    <input type="hidden" id="publisher_id">
                    <div class="mb-3">
                        <label class="form-label">Revenue Share (%)</label>
                        <input type="number" class="form-control" id="revenue_share" step="0.01" min="0" max="100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePublisher(id, status) {
    fetch('ajax/toggle-publisher.php', {
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

function editRevShare(id, currentShare) {
    document.getElementById('publisher_id').value = id;
    document.getElementById('revenue_share').value = currentShare;
    new bootstrap.Modal(document.getElementById('editRevShareModal')).show();
}

document.getElementById('revShareForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const id = document.getElementById('publisher_id').value;
    const share = document.getElementById('revenue_share').value;
    
    fetch('ajax/update-revenue-share.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            revenue_share: share
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating revenue share');
        }
    });
});

function viewEarnings(id) {
    window.open('stats/publisher.php?id=' + id, '_blank');
}

function editPublisher(id) {
    console.log('Edit publisher:', id);
}

function deletePublisher(id) {
    if (confirm('Are you sure you want to delete this publisher? This will also delete all associated websites and zones.')) {
        console.log('Delete publisher:', id);
    }
}
</script>

<?php include 'includes/footer.php'; ?>