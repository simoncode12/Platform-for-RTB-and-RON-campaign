<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Advertisers';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = $_POST['company_name'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($company_name && $contact_email && $username && $password) {
        try {
            $pdo->beginTransaction();
            
            // Create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'advertiser')");
            $stmt->execute([$username, $hashed_password, $contact_email]);
            $user_id = $pdo->lastInsertId();
            
            // Create advertiser profile
            $stmt = $pdo->prepare("INSERT INTO advertisers (user_id, company_name, contact_email, phone) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $company_name, $contact_email, $phone]);
            
            $pdo->commit();
            $message = '<div class="alert alert-success">Advertiser created successfully!</div>';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    }
}

// Get existing advertisers with campaign stats
$stmt = $pdo->query("SELECT a.*, u.username, u.status as user_status,
                     (SELECT COUNT(*) FROM rtb_campaigns WHERE advertiser_id = a.id) as rtb_campaigns,
                     (SELECT COUNT(*) FROM ron_campaigns WHERE advertiser_id = a.id) as ron_campaigns
                     FROM advertisers a 
                     LEFT JOIN users u ON a.user_id = u.id 
                     ORDER BY a.created_at DESC");
$advertisers = $stmt->fetchAll();

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user-tie me-2"></i>Advertiser Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdvertiserModal">
                <i class="fas fa-plus me-2"></i>Add Advertiser
            </button>
        </div>

        <?php echo $message; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-user-tie fa-2x text-primary mb-2"></i>
                        <h4><?php echo count($advertisers); ?></h4>
                        <p class="text-muted mb-0">Total Advertisers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h4><?php echo count(array_filter($advertisers, fn($a) => $a['status'] == 'active')); ?></h4>
                        <p class="text-muted mb-0">Active</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-bullhorn fa-2x text-info mb-2"></i>
                        <h4><?php echo array_sum(array_column($advertisers, 'rtb_campaigns')); ?></h4>
                        <p class="text-muted mb-0">RTB Campaigns</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-network-wired fa-2x text-warning mb-2"></i>
                        <h4><?php echo array_sum(array_column($advertisers, 'ron_campaigns')); ?></h4>
                        <p class="text-muted mb-0">RON Campaigns</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advertisers List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Registered Advertisers</h5>
            </div>
            <div class="card-body">
                <?php if ($advertisers): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Contact Info</th>
                                <th>Username</th>
                                <th>Campaigns</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($advertisers as $advertiser): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($advertiser['company_name']); ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($advertiser['contact_email']); ?>
                                    </div>
                                    <?php if ($advertiser['phone']): ?>
                                    <div>
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($advertiser['phone']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($advertiser['username']); ?></td>
                                <td>
                                    <span class="badge bg-info me-1">RTB: <?php echo $advertiser['rtb_campaigns']; ?></span>
                                    <span class="badge bg-warning">RON: <?php echo $advertiser['ron_campaigns']; ?></span>
                                </td>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?php echo $advertiser['status'] == 'active' ? 'checked' : ''; ?> 
                                               onchange="toggleAdvertiser(<?php echo $advertiser['id']; ?>, this.checked)">
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($advertiser['created_at'])); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="rtb-sell.php?advertiser_id=<?php echo $advertiser['id']; ?>">
                                                <i class="fas fa-bullhorn me-2"></i>RTB Campaigns
                                            </a></li>
                                            <li><a class="dropdown-item" href="ron-campaign.php?advertiser_id=<?php echo $advertiser['id']; ?>">
                                                <i class="fas fa-network-wired me-2"></i>RON Campaigns
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="viewStats(<?php echo $advertiser['id']; ?>)">
                                                <i class="fas fa-chart-bar me-2"></i>View Stats
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="editAdvertiser(<?php echo $advertiser['id']; ?>)">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteAdvertiser(<?php echo $advertiser['id']; ?>)">
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
                    <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                    <h5>No advertisers yet</h5>
                    <p class="text-muted">Add your first advertiser to start managing campaigns.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Advertiser Modal -->
<div class="modal fade" id="createAdvertiserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-tie me-2"></i>Add Advertiser</h5>
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
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone">
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
                    <button type="submit" class="btn btn-primary">Create Advertiser</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAdvertiser(id, status) {
    fetch('ajax/toggle-advertiser.php', {
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

function viewStats(id) {
    window.open('stats/advertiser.php?id=' + id, '_blank');
}

function editAdvertiser(id) {
    console.log('Edit advertiser:', id);
}

function deleteAdvertiser(id) {
    if (confirm('Are you sure you want to delete this advertiser? This will also delete all associated campaigns.')) {
        console.log('Delete advertiser:', id);
    }
}
</script>

<?php include 'includes/footer.php'; ?>