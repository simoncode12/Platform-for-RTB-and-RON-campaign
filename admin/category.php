<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Categories';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? '';
        
        if ($name && $type) {
            try {
                // Check if category already exists (case insensitive)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE LOWER(TRIM(name)) = LOWER(?) AND type = ?");
                $stmt->execute([$name, $type]);
                
                if ($stmt->fetchColumn() > 0) {
                    $message = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Category with this name already exists in ' . ucfirst($type) . ' type!</div>';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, description, type) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $description ?: null, $type]);
                    
                    $message = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Category created successfully!</div>';
                }
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Please fill in all required fields.</div>';
        }
    }
    
    if ($action == 'cleanup') {
        try {
            $pdo->beginTransaction();
            
            // Remove duplicates, keeping the oldest record of each name+type combination
            $stmt = $pdo->exec("
                DELETE c1 FROM categories c1
                INNER JOIN categories c2 
                WHERE c1.id > c2.id 
                AND LOWER(TRIM(c1.name)) = LOWER(TRIM(c2.name))
                AND c1.type = c2.type
            ");
            
            $pdo->commit();
            $message = '<div class="alert alert-success"><i class="fas fa-broom me-2"></i>Removed ' . $stmt . ' duplicate categories!</div>';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error during cleanup: ' . $e->getMessage() . '</div>';
        }
    }
}

// Get existing categories with usage stats - prevent duplicates with DISTINCT
$stmt = $pdo->query("
    SELECT DISTINCT c.id, c.name, c.description, c.type, c.status, c.created_at,
           (SELECT COUNT(*) FROM rtb_campaigns WHERE category_id = c.id) as rtb_usage,
           (SELECT COUNT(*) FROM ron_campaigns WHERE category_id = c.id) as ron_usage,
           (SELECT COUNT(*) FROM websites WHERE category_id = c.id) as website_usage
    FROM categories c 
    ORDER BY c.type, c.name
");
$categories = $stmt->fetchAll();

// Group categories by type
$grouped_categories = [];
foreach ($categories as $category) {
    $grouped_categories[$category['type']][] = $category;
}

// Get duplicate count for warning
$stmt = $pdo->query("
    SELECT COUNT(*) as total_duplicates
    FROM (
        SELECT name, type, COUNT(*) as count
        FROM categories 
        GROUP BY LOWER(TRIM(name)), type
        HAVING count > 1
    ) as duplicates
");
$duplicate_count = $stmt->fetchColumn();

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-tags me-2"></i>Category Management</h2>
            <div>
                <?php if ($duplicate_count > 0): ?>
                <form method="POST" class="d-inline me-2">
                    <input type="hidden" name="action" value="cleanup">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('This will remove duplicate categories. Continue?')">
                        <i class="fas fa-broom me-2"></i>Clean Duplicates (<?php echo $duplicate_count; ?>)
                    </button>
                </form>
                <?php endif; ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                    <i class="fas fa-plus me-2"></i>Add Category
                </button>
            </div>
        </div>

        <?php echo $message; ?>

        <?php if ($duplicate_count > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Warning:</strong> Found <?php echo $duplicate_count; ?> duplicate category groups. Use the "Clean Duplicates" button to remove them.
        </div>
        <?php endif; ?>

        <!-- Statistics Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-tags fa-2x text-primary mb-2"></i>
                        <h4><?php echo count($categories); ?></h4>
                        <p class="text-muted mb-0">Total Categories</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-user-secret fa-2x text-danger mb-2"></i>
                        <h4><?php echo count($grouped_categories['adult'] ?? []); ?></h4>
                        <p class="text-muted mb-0">Adult Categories</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-success mb-2"></i>
                        <h4><?php echo count($grouped_categories['mainstream'] ?? []); ?></h4>
                        <p class="text-muted mb-0">Mainstream Categories</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                        <h4><?php echo array_sum(array_map(function($cat) { return $cat['rtb_usage'] + $cat['ron_usage'] + $cat['website_usage']; }, $categories)); ?></h4>
                        <p class="text-muted mb-0">Total Usage</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories by Type -->
        <div class="row">
            <?php foreach (['adult', 'mainstream'] as $type): 
                $type_categories = $grouped_categories[$type] ?? [];
            ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-<?php echo $type == 'adult' ? 'user-secret' : 'users'; ?> me-2"></i>
                            <?php echo ucfirst($type); ?> Categories
                            <span class="badge bg-secondary ms-2"><?php echo count($type_categories); ?></span>
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if ($type_categories): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($type_categories as $category): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    <small class="text-muted ms-2">#<?php echo $category['id']; ?></small>
                                    <?php if ($category['description']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-info">
                                        <i class="fas fa-bullhorn"></i> RTB: <?php echo $category['rtb_usage']; ?> | 
                                        <i class="fas fa-network-wired"></i> RON: <?php echo $category['ron_usage']; ?> | 
                                        <i class="fas fa-globe"></i> Sites: <?php echo $category['website_usage']; ?>
                                    </small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <label class="toggle-switch me-2">
                                        <input type="checkbox" <?php echo $category['status'] == 'active' ? 'checked' : ''; ?> 
                                               onchange="toggleCategory(<?php echo $category['id']; ?>, this.checked)">
                                        <span class="slider"></span>
                                    </label>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['description']); ?>', '<?php echo $category['type']; ?>')">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a></li>
                                            <?php if ($category['rtb_usage'] == 0 && $category['ron_usage'] == 0 && $category['website_usage'] == 0): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-trash me-2"></i>Delete
                                            </a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-<?php echo $type == 'adult' ? 'user-secret' : 'users'; ?> fa-3x text-muted mb-3"></i>
                            <h6>No <?php echo $type; ?> categories yet</h6>
                            <p class="text-muted">Create your first <?php echo $type; ?> category.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tags me-2"></i>Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="name" required maxlength="100">
                        <div class="form-text">Enter a unique category name</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Optional description for this category" maxlength="500"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category Type *</label>
                        <select class="form-select" name="type" required>
                            <option value="">Select Type</option>
                            <option value="mainstream">Mainstream</option>
                            <option value="adult">Adult</option>
                        </select>
                        <div class="form-text">Choose the appropriate content type for this category</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Category names must be unique within each type (Adult/Mainstream).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleCategory(id, status) {
    fetch('ajax/toggle-category.php', {
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
            showAlert('Category status updated successfully!', 'success');
        } else {
            event.target.checked = !status;
            showAlert('Error updating category status', 'danger');
        }
    });
}

function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        fetch('ajax/delete-category.php', {
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
                showAlert('Error deleting category: ' + data.message, 'danger');
            }
        });
    }
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.main-content');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

<?php include 'includes/footer.php'; ?>