<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Ad Zones';
$message = '';

// Create zones table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS zones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        size VARCHAR(20) NOT NULL,
        ad_format_id INT,
        zone_code TEXT,
        zone_token VARCHAR(100) UNIQUE,
        floor_price_cpm DECIMAL(10,4) DEFAULT 0.0000,
        floor_price_cpc DECIMAL(10,4) DEFAULT 0.0000,
        status ENUM('active', 'inactive', 'paused') DEFAULT 'active',
        impressions_today BIGINT DEFAULT 0,
        clicks_today BIGINT DEFAULT 0,
        revenue_today DECIMAL(15,4) DEFAULT 0.0000,
        impressions_total BIGINT DEFAULT 0,
        clicks_total BIGINT DEFAULT 0,
        revenue_total DECIMAL(15,4) DEFAULT 0.0000,
        last_reset_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
        FOREIGN KEY (ad_format_id) REFERENCES ad_formats(id) ON DELETE SET NULL
    )");
} catch (Exception $e) {
    // Table might already exist
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create') {
        $website_id = intval($_POST['website_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $size = $_POST['size'] ?? '';
        $ad_format_id = intval($_POST['ad_format_id'] ?? 0) ?: null;
        $floor_price_cpm = floatval($_POST['floor_price_cpm'] ?? 0);
        $floor_price_cpc = floatval($_POST['floor_price_cpc'] ?? 0);
        
        if ($website_id && $name && $size) {
            try {
                // Generate unique zone token
                do {
                    $zone_token = 'zone_' . uniqid() . '_' . substr(md5(time()), 0, 8);
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM zones WHERE zone_token = ?");
                    $stmt->execute([$zone_token]);
                } while ($stmt->fetchColumn() > 0);
                
                // Insert zone first
                $stmt = $pdo->prepare("INSERT INTO zones (website_id, name, description, size, ad_format_id, zone_token, floor_price_cpm, floor_price_cpc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $website_id,
                    $name,
                    $description ?: null,
                    $size,
                    $ad_format_id,
                    $zone_token,
                    $floor_price_cpm,
                    $floor_price_cpc
                ]);
                
                $zone_id = $pdo->lastInsertId();
                
                // Generate zone code with the token
                $zone_code = generateZoneCode($zone_id, $zone_token, $name, $size);
                
                // Update zone with generated code
                $stmt = $pdo->prepare("UPDATE zones SET zone_code = ? WHERE id = ?");
                $stmt->execute([$zone_code, $zone_id]);
                
                $message = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Zone created successfully! Token: <code>' . $zone_token . '</code></div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Please fill in all required fields.</div>';
        }
    }
    
    if ($action == 'update') {
        $zone_id = intval($_POST['zone_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $floor_price_cpm = floatval($_POST['floor_price_cpm'] ?? 0);
        $floor_price_cpc = floatval($_POST['floor_price_cpc'] ?? 0);
        
        if ($zone_id && $name) {
            try {
                $stmt = $pdo->prepare("UPDATE zones SET name = ?, description = ?, floor_price_cpm = ?, floor_price_cpc = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $description ?: null, $floor_price_cpm, $floor_price_cpc, $zone_id]);
                
                $message = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Zone updated successfully!</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
    
    if ($action == 'regenerate_token') {
        $zone_id = intval($_POST['zone_id'] ?? 0);
        
        if ($zone_id) {
            try {
                // Get zone details
                $stmt = $pdo->prepare("SELECT * FROM zones WHERE id = ?");
                $stmt->execute([$zone_id]);
                $zone = $stmt->fetch();
                
                if ($zone) {
                    // Generate new unique token
                    do {
                        $new_token = 'zone_' . uniqid() . '_' . substr(md5(time()), 0, 8);
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM zones WHERE zone_token = ?");
                        $stmt->execute([$new_token]);
                    } while ($stmt->fetchColumn() > 0);
                    
                    // Generate new zone code
                    $new_zone_code = generateZoneCode($zone_id, $new_token, $zone['name'], $zone['size']);
                    
                    // Update zone
                    $stmt = $pdo->prepare("UPDATE zones SET zone_token = ?, zone_code = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$new_token, $new_zone_code, $zone_id]);
                    
                    $message = '<div class="alert alert-success"><i class="fas fa-sync-alt me-2"></i>Zone token regenerated successfully! New token: <code>' . $new_token . '</code></div>';
                }
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Function to generate zone code
function generateZoneCode($zone_id, $token, $name, $size) {
    $domain = $_SERVER['HTTP_HOST'] ?? 'up.adstart.click';
    $sizes = explode('x', $size);
    $width = $sizes[0] ?? '300';
    $height = $sizes[1] ?? '250';
    
    // Make sure token is not empty
    if (empty($token)) {
        $token = 'zone_error_' . $zone_id;
    }
    
    $embed_code = "<!-- AdStart Zone: {$name} ({$size}) -->
<div id=\"adzone-{$token}\" style=\"width:{$width}px; height:{$height}px; border:1px solid #ddd; background:#f5f5f5; display:flex; align-items:center; justify-content:center; color:#666; font-family:Arial,sans-serif; font-size:14px; position:relative; overflow:hidden;\">
    <span style=\"color:#999;\">Loading ad...</span>
</div>
<script>
(function() {
    const container = document.getElementById('adzone-{$token}');
    if (!container) {
        console.error('AdZone container not found: adzone-{$token}');
        return;
    }
    
    const domain = 'https://{$domain}';
    const zoneToken = '{$token}';
    const size = '{$size}';
    
    console.log('AdZone Loading:', {domain, zoneToken, size});
    
    // Request ad content
    fetch(domain + '/api/rtb/request.php?token=' + zoneToken + '&format=banner&size=' + size + '&r=' + Math.random())
        .then(response => {
            console.log('AdZone Response Status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('AdZone Response Data:', data);
            
            if (data.success && data.content) {
                container.innerHTML = data.content;
                container.style.border = 'none';
                container.style.background = 'transparent';
                
                // Track impression
                fetch(domain + '/api/track/impression.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'zone_id=' + encodeURIComponent(zoneToken) + 
                          '&campaign_id=' + encodeURIComponent(data.campaign_id || '') + 
                          '&type=' + encodeURIComponent(data.type || 'unknown') +
                          '&timestamp=' + Date.now()
                }).catch(e => console.warn('Impression tracking failed:', e));
                
                // Add click tracking to all links
                setTimeout(() => {
                    const links = container.querySelectorAll('a, [onclick], button');
                    links.forEach(link => {
                        link.addEventListener('click', function(e) {
                            fetch(domain + '/api/track/click.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'zone_id=' + encodeURIComponent(zoneToken) + 
                                      '&campaign_id=' + encodeURIComponent(data.campaign_id || '') + 
                                      '&type=' + encodeURIComponent(data.type || 'unknown') +
                                      '&timestamp=' + Date.now()
                            }).catch(e => console.warn('Click tracking failed:', e));
                        });
                    });
                }, 100);
            } else {
                container.innerHTML = '<div style=\"display:flex;align-items:center;justify-content:center;height:100%;color:#999;font-size:12px;text-align:center;\">No ads available<br><small>Zone: {$token}</small></div>';
            }
        })
        .catch(error => {
            console.error('AdZone error:', error);
            container.innerHTML = '<div style=\"display:flex;align-items:center;justify-content:center;height:100%;color:#cc0000;font-size:11px;text-align:center;\">Ad loading failed<br><small>Zone: {$token}</small><br><small>' + error.message + '</small></div>';
        });
})();
</script>
<!-- End AdStart Zone -->";
    
    return $embed_code;
}

// Get statistics for overview
$stats = [];
try {
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_zones,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_zones,
        COALESCE(SUM(impressions_today), 0) as total_impressions_today,
        COALESCE(SUM(clicks_today), 0) as total_clicks_today,
        COALESCE(SUM(revenue_today), 0) as total_revenue_today,
        COALESCE(SUM(impressions_total), 0) as total_impressions_total,
        COALESCE(SUM(clicks_total), 0) as total_clicks_total,
        COALESCE(SUM(revenue_total), 0) as total_revenue_total
        FROM zones");
    $stats = $stmt->fetch() ?: [];
} catch (Exception $e) {
    $stats = [
        'total_zones' => 0,
        'active_zones' => 0,
        'total_impressions_today' => 0,
        'total_clicks_today' => 0,
        'total_revenue_today' => 0,
        'total_impressions_total' => 0,
        'total_clicks_total' => 0,
        'total_revenue_total' => 0
    ];
}

// Get zones with website and format information
try {
    $stmt = $pdo->query("SELECT z.*, 
                         w.name as website_name, w.url as website_url,
                         af.name as format_name, af.type as format_type,
                         p.company_name as publisher_name,
                         p.id as publisher_id
                         FROM zones z
                         LEFT JOIN websites w ON z.website_id = w.id
                         LEFT JOIN ad_formats af ON z.ad_format_id = af.id
                         LEFT JOIN publishers p ON w.publisher_id = p.id
                         ORDER BY z.created_at DESC");
    $zones = $stmt->fetchAll();
} catch (Exception $e) {
    $zones = [];
}

// Get websites for dropdown (only for current user if publisher)
try {
    if ($_SESSION['role'] === 'publisher') {
        $stmt = $pdo->prepare("SELECT w.*, p.company_name as publisher_name 
                               FROM websites w 
                               LEFT JOIN publishers p ON w.publisher_id = p.id 
                               WHERE w.status = 'active' AND p.user_id = ?
                               ORDER BY w.name");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->query("SELECT w.*, p.company_name as publisher_name 
                             FROM websites w 
                             LEFT JOIN publishers p ON w.publisher_id = p.id 
                             WHERE w.status = 'active' 
                             ORDER BY p.company_name, w.name");
    }
    $websites = $stmt->fetchAll();
} catch (Exception $e) {
    $websites = [];
}

// Get ad formats for dropdown
try {
    $stmt = $pdo->query("SELECT * FROM ad_formats WHERE status = 'active' ORDER BY type, name");
    $ad_formats = $stmt->fetchAll();
} catch (Exception $e) {
    $ad_formats = [];
}

// Standard ad sizes
$standard_sizes = [
    '300x250' => 'Medium Rectangle',
    '728x90' => 'Leaderboard',
    '300x100' => 'Mobile Banner',
    '300x50' => 'Mobile Banner Small',
    '320x50' => 'Mobile Banner',
    '320x100' => 'Large Mobile Banner',
    '160x600' => 'Wide Skyscraper',
    '120x600' => 'Skyscraper',
    '970x90' => 'Large Leaderboard',
    '970x250' => 'Billboard',
    '336x280' => 'Large Rectangle',
    '468x60' => 'Banner',
    '234x60' => 'Half Banner',
    '125x125' => 'Button',
    '250x250' => 'Square',
    '600x90' => 'Super Banner',
    '300x600' => 'Half Page',
    '970x66' => 'Super Leaderboard'
];

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-th-large me-2"></i>Ad Zone Management</h2>
            <div>
                <button class="btn btn-outline-info me-2" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh Stats
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createZoneModal">
                    <i class="fas fa-plus me-2"></i>Create Zone
                </button>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Statistics Overview -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-th-large fa-2x mb-2"></i>
                        <h4><?php echo number_format($stats['total_zones']); ?></h4>
                        <p class="mb-0">Total Zones</p>
                        <small><?php echo number_format($stats['active_zones']); ?> Active</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-eye fa-2x mb-2"></i>
                        <h4><?php echo number_format($stats['total_impressions_today']); ?></h4>
                        <p class="mb-0">Impressions Today</p>
                        <small><?php echo number_format($stats['total_impressions_total']); ?> Total</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                        <h4><?php echo number_format($stats['total_clicks_today']); ?></h4>
                        <p class="mb-0">Clicks Today</p>
                        <small><?php echo number_format($stats['total_clicks_total']); ?> Total</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h4>$<?php echo number_format($stats['total_revenue_today'], 2); ?></h4>
                        <p class="mb-0">Revenue Today</p>
                        <small>$<?php echo number_format($stats['total_revenue_total'], 2); ?> Total</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zones List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-list me-2"></i>Ad Zones (<?php echo count($zones); ?>)</h5>
                <div>
                    <button class="btn btn-sm btn-outline-warning" onclick="checkTokens()">
                        <i class="fas fa-exclamation-triangle me-1"></i>Check Tokens
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if ($zones): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Zone Info</th>
                                <th>Website</th>
                                <th>Size & Format</th>
                                <th>Performance Today</th>
                                <th>Floor Prices</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($zones as $zone): 
                                $ctr_today = $zone['impressions_today'] > 0 ? ($zone['clicks_today'] / $zone['impressions_today']) * 100 : 0;
                                $cpm_today = $zone['impressions_today'] > 0 ? ($zone['revenue_today'] / $zone['impressions_today']) * 1000 : 0;
                                $ctr_total = $zone['impressions_total'] > 0 ? ($zone['clicks_total'] / $zone['impressions_total']) * 100 : 0;
                                $has_token_issue = empty($zone['zone_token']);
                            ?>
                            <tr <?php if ($has_token_issue) echo 'class="table-danger"'; ?>>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($zone['name']); ?></strong>
                                        <?php if ($has_token_issue): ?>
                                        <span class="badge bg-danger ms-2">NO TOKEN</span>
                                        <?php endif; ?>
                                        <br><small class="text-muted">ID: <?php echo $zone['id']; ?></small>
                                        <br><small class="text-muted">Token: 
                                            <?php if ($zone['zone_token']): ?>
                                                <code><?php echo $zone['zone_token']; ?></code>
                                            <?php else: ?>
                                                <span class="text-danger">MISSING</span>
                                            <?php endif; ?>
                                        </small>
                                        <?php if ($zone['description']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($zone['description']); ?></small>
                                        <?php endif; ?>
                                        <br><small class="text-muted">Created: <?php echo date('M j, Y', strtotime($zone['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($zone['website_name'] ?: 'Unknown'); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($zone['publisher_name'] ?: 'No Publisher'); ?></small>
                                        <?php if ($zone['website_url']): ?>
                                        <br><small class="text-muted">
                                            <a href="<?php echo htmlspecialchars($zone['website_url']); ?>" target="_blank" class="text-decoration-none">
                                                <?php echo htmlspecialchars($zone['website_url']); ?>
                                                <i class="fas fa-external-link-alt ms-1"></i>
                                            </a>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-primary fs-6"><?php echo $zone['size']; ?></span>
                                        <?php if ($zone['format_name']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($zone['format_name']); ?></small>
                                        <br><span class="badge bg-secondary"><?php echo ucfirst($zone['format_type']); ?></span>
                                        <?php endif; ?>
                                        <br><small class="text-muted"><?php echo $standard_sizes[$zone['size']] ?? 'Custom Size'; ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <small><i class="fas fa-eye text-info"></i> <?php echo number_format($zone['impressions_today']); ?> imp</small>
                                        <small><i class="fas fa-mouse-pointer text-success"></i> <?php echo number_format($zone['clicks_today']); ?> clicks</small>
                                        <small><i class="fas fa-percentage text-warning"></i> <?php echo number_format($ctr_today, 2); ?>% CTR</small>
                                        <small><i class="fas fa-dollar-sign text-primary"></i> $<?php echo number_format($zone['revenue_today'], 2); ?></small>
                                        <small class="text-muted">eCPM: $<?php echo number_format($cpm_today, 2); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <small>CPM: $<?php echo number_format($zone['floor_price_cpm'], 4); ?></small>
                                        <br><small>CPC: $<?php echo number_format($zone['floor_price_cpc'], 4); ?></small>
                                        <?php if ($zone['floor_price_cpm'] > 0 || $zone['floor_price_cpc'] > 0): ?>
                                        <br><span class="badge bg-info">Floor Set</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" <?php echo $zone['status'] == 'active' ? 'checked' : ''; ?> 
                                                   onchange="toggleZone(<?php echo $zone['id']; ?>, this.checked)"
                                                   <?php if ($has_token_issue) echo 'disabled'; ?>>
                                            <span class="slider"></span>
                                        </label>
                                        <br><small class="badge bg-<?php echo $zone['status'] == 'active' ? 'success' : ($zone['status'] == 'paused' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst($zone['status']); ?>
                                        </small>
                                        <?php if ($has_token_issue): ?>
                                        <br><small class="text-danger">Token Missing</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if (!$has_token_issue): ?>
                                            <li><a class="dropdown-item" href="#" onclick="showZoneCode('<?php echo $zone['zone_token']; ?>', '<?php echo htmlspecialchars($zone['name']); ?>', '<?php echo $zone['size']; ?>')">
                                                <i class="fas fa-code me-2"></i>Get Zone Code
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="previewZone('<?php echo $zone['zone_token']; ?>', '<?php echo $zone['size']; ?>')">
                                                <i class="fas fa-eye me-2"></i>Preview Zone
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="testZone('<?php echo $zone['zone_token']; ?>', '<?php echo $zone['size']; ?>')">
                                                <i class="fas fa-flask me-2"></i>Test Zone
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php else: ?>
                                            <li><a class="dropdown-item text-warning" href="#" onclick="regenerateToken(<?php echo $zone['id']; ?>)">
                                                <i class="fas fa-sync-alt me-2"></i>Fix Missing Token
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php endif; ?>
                                            <li><a class="dropdown-item" href="#" onclick="viewZoneStats(<?php echo $zone['id']; ?>)">
                                                <i class="fas fa-chart-bar me-2"></i>Detailed Stats
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="editZone(<?php echo $zone['id']; ?>, '<?php echo htmlspecialchars($zone['name']); ?>', '<?php echo htmlspecialchars($zone['description']); ?>', <?php echo $zone['floor_price_cpm']; ?>, <?php echo $zone['floor_price_cpc']; ?>)">
                                                <i class="fas fa-edit me-2"></i>Edit Zone
                                            </a></li>
                                            <li><a class="dropdown-item text-warning" href="#" onclick="regenerateToken(<?php echo $zone['id']; ?>)">
                                                <i class="fas fa-sync-alt me-2"></i>Regenerate Token
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteZone(<?php echo $zone['id']; ?>, '<?php echo htmlspecialchars($zone['name']); ?>')">
                                                <i class="fas fa-trash me-2"></i>Delete Zone
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
                <div class="text-center py-5">
                    <i class="fas fa-th-large fa-4x text-muted mb-4"></i>
                    <h4>No Ad Zones Yet</h4>
                    <p class="text-muted mb-4">Create your first ad zone to start serving ads on your websites.</p>
                    <?php if (empty($websites)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        You need to add a website first before creating zones.
                        <a href="websites.php" class="btn btn-sm btn-warning ms-2">
                            <i class="fas fa-globe me-1"></i>Add Website
                        </a>
                    </div>
                    <?php else: ?>
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createZoneModal">
                        <i class="fas fa-plus me-2"></i>Create First Zone
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Zone Modal -->
<div class="modal fade" id="createZoneModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-th-large me-2"></i>Create Ad Zone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Zone Name *</label>
                                <input type="text" class="form-control" name="name" required maxlength="255">
                                <div class="invalid-feedback">Please provide a zone name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Website *</label>
                                <select class="form-select" name="website_id" required>
                                    <option value="">Select Website</option>
                                    <?php foreach ($websites as $website): ?>
                                    <option value="<?php echo $website['id']; ?>">
                                        <?php echo htmlspecialchars($website['name']); ?> 
                                        <?php if ($website['publisher_name']): ?>
                                        (<?php echo htmlspecialchars($website['publisher_name']); ?>)
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a website.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2" maxlength="500" placeholder="Optional description for this zone"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ad Size *</label>
                                <select class="form-select" name="size" required>
                                    <option value="">Select Size</option>
                                    <?php foreach ($standard_sizes as $size => $name): ?>
                                    <option value="<?php echo $size; ?>"><?php echo $size; ?> - <?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select an ad size.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ad Format (Optional)</label>
                                <select class="form-select" name="ad_format_id">
                                    <option value="">Any Format</option>
                                    <?php foreach ($ad_formats as $format): ?>
                                    <option value="<?php echo $format['id']; ?>">
                                        <?php echo htmlspecialchars($format['name']); ?> (<?php echo ucfirst($format['type']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6><i class="fas fa-dollar-sign me-2"></i>Floor Prices (Optional)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Minimum CPM ($)</label>
                                        <input type="number" class="form-control" name="floor_price_cpm" step="0.0001" min="0" value="0.0000">
                                        <div class="form-text">Minimum price per 1000 impressions</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Minimum CPC ($)</label>
                                        <input type="number" class="form-control" name="floor_price_cpc" step="0.0001" min="0" value="0.0000">
                                        <div class="form-text">Minimum price per click</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> After creating the zone, you'll get an embed code to place on your website where you want ads to appear. The zone will automatically serve the best paying ads that match your criteria.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Zone
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Zone Modal -->
<div class="modal fade" id="editZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Zone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editZoneForm" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_zone_id" name="zone_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zone Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Minimum CPM ($)</label>
                                <input type="number" class="form-control" id="edit_floor_price_cpm" name="floor_price_cpm" step="0.0001" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Minimum CPC ($)</label>
                                <input type="number" class="form-control" id="edit_floor_price_cpc" name="floor_price_cpc" step="0.0001" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Zone
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Zone Code Modal -->
<div class="modal fade" id="zoneCodeModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-code me-2"></i>Zone Embed Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="alert alert-info" id="zoneInfo">
                        <!-- Zone info will be populated by JavaScript -->
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Embed Code (Copy and paste this into your website):</label>
                    <textarea id="zoneCodeTextarea" class="form-control font-monospace" rows="15" readonly style="font-size: 11px;"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Direct API URL:</label>
                            <div class="input-group">
                                <input type="text" id="zoneApiUrl" class="form-control" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('zoneApiUrl')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Test URL:</label>
                            <div class="input-group">
                                <input type="text" id="zoneTestUrl" class="form-control" readonly>
                                <button class="btn btn-outline-primary" type="button" onclick="window.open(document.getElementById('zoneTestUrl').value, '_blank')">
                                    <i class="fas fa-external-link-alt"></i> Test
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-success">
                    <h6><i class="fas fa-lightbulb me-2"></i>Implementation Instructions:</h6>
                    <ol class="mb-0">
                        <li><strong>Copy the embed code</strong> from the textarea above</li>
                        <li><strong>Paste it into your website's HTML</strong> where you want the ad to appear</li>
                        <li><strong>The ad will load automatically</strong> when visitors view the page</li>
                        <li><strong>Use the test URL</strong> to verify the zone is working correctly</li>
                        <li><strong>Check your statistics</strong> regularly to monitor performance</li>
                    </ol>
                </div>
                
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Important Notes:</h6>
                    <ul class="mb-0">
                        <li>Each zone has a unique token - do not share or modify it</li>
                        <li>The embed code includes automatic impression and click tracking</li>
                        <li>Zone statistics update in real-time</li>
                        <li>If you disable the zone, ads will stop serving immediately</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="copyToClipboard('zoneCodeTextarea')">
                    <i class="fas fa-copy me-2"></i>Copy Embed Code
                </button>
                <button type="button" class="btn btn-outline-info" onclick="downloadZoneCode()">
                    <i class="fas fa-download me-2"></i>Download Code
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
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
    
    // Check for zones with token issues
    window.checkTokens = function() {
        const tokenIssues = document.querySelectorAll('.table-danger').length;
        if (tokenIssues > 0) {
            showAlert(`Found ${tokenIssues} zones with missing tokens. Use "Fix Missing Token" to resolve.`, 'warning');
        } else {
            showAlert('All zones have valid tokens!', 'success');
        }
    };
    
    // Regenerate token
    window.regenerateToken = function(zoneId) {
        if (confirm('Are you sure you want to regenerate the token for this zone? This will change the embed code and you will need to update your website.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="regenerate_token">
                <input type="hidden" name="zone_id" value="${zoneId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };
    
    // Toggle zone status
    window.toggleZone = function(id, status) {
        const data = {
            id: id,
            status: status ? 'active' : 'inactive'
        };
        
        fetch('ajax/toggle-zone.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Zone status updated successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                event.target.checked = !status;
                showAlert('Error updating zone status: ' + (data.message || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            event.target.checked = !status;
            showAlert('Network error occurred', 'danger');
        });
    };

    // Show zone code
    window.showZoneCode = function(token, name, size) {
        if (!token) {
            showAlert('Zone token is missing! Please regenerate the token first.', 'danger');
            return;
        }
        
        try {
            const domain = window.location.hostname;
            
            // Get the actual embed code from server
            fetch('ajax/get-zone-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ token: token })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const embedCode = data.zone_code;
                    const apiUrl = `https://${domain}/api/rtb/request.php?token=${token}&format=banner&size=${size}`;
                    const testUrl = `https://${domain}/api/rtb/request.php?token=${token}&format=banner&size=${size}&test=1`;
                    
                    // Update modal content
                    const zoneInfoEl = document.getElementById('zoneInfo');
                    if (zoneInfoEl) {
                        zoneInfoEl.innerHTML = `
                            <div class="row">
                                <div class="col-md-3"><strong>Zone:</strong> ${name}</div>
                                <div class="col-md-3"><strong>Token:</strong> <code>${token}</code></div>
                                <div class="col-md-3"><strong>Size:</strong> <span class="badge bg-primary">${size}</span></div>
                                <div class="col-md-3"><strong>Domain:</strong> ${domain}</div>
                            </div>
                        `;
                    }
                    
                    const codeTextarea = document.getElementById('zoneCodeTextarea');
                    if (codeTextarea) {
                        codeTextarea.value = embedCode;
                    }
                    
                    const apiUrlInput = document.getElementById('zoneApiUrl');
                    if (apiUrlInput) {
                        apiUrlInput.value = apiUrl;
                    }
                    
                    const testUrlInput = document.getElementById('zoneTestUrl');
                    if (testUrlInput) {
                        testUrlInput.value = testUrl;
                    }
                    
                    new bootstrap.Modal(document.getElementById('zoneCodeModal')).show();
                } else {
                    showAlert('Error getting zone code: ' + (data.message || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Network error occurred', 'danger');
            });
        } catch (error) {
            console.error('Error in showZoneCode:', error);
            showAlert('Error generating zone code', 'danger');
        }
    };

    // Preview zone
    window.previewZone = function(token, size) {
        if (!token) {
            showAlert('Zone token is missing!', 'danger');
            return;
        }
        const domain = window.location.hostname;
        const previewUrl = `https://${domain}/api/rtb/request.php?token=${token}&format=banner&size=${size}&preview=1`;
        window.open(previewUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
    };

    // Test zone
    window.testZone = function(token, size) {
        if (!token) {
            showAlert('Zone token is missing!', 'danger');
            return;
        }
        const domain = window.location.hostname;
        const testUrl = `https://${domain}/api/rtb/request.php?token=${token}&format=banner&size=${size}&test=1`;
        window.open(testUrl, '_blank', 'width=1000,height=700,scrollbars=yes,resizable=yes');
    };

    // View zone statistics
    window.viewZoneStats = function(id) {
        window.open(`reports/zone-stats.php?id=${id}`, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
    };

    // Edit zone
    window.editZone = function(id, name, description, cpm, cpc) {
        document.getElementById('edit_zone_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_description').value = description || '';
        document.getElementById('edit_floor_price_cpm').value = cpm;
        document.getElementById('edit_floor_price_cpc').value = cpc;
        
        new bootstrap.Modal(document.getElementById('editZoneModal')).show();
    };

    // Delete zone
    window.deleteZone = function(id, name) {
        if (confirm(`Are you sure you want to delete zone "${name}"?\n\nThis action cannot be undone and will break any websites using this zone code.`)) {
            fetch('ajax/delete-zone.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Zone deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('Error deleting zone: ' + (data.message || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Network error occurred', 'danger');
            });
        }
    };

    // Copy to clipboard
    window.copyToClipboard = function(elementId) {
        const element = document.getElementById(elementId);
        if (!element) {
            showAlert('Element not found', 'warning');
            return;
        }
        
        element.select();
        element.setSelectionRange(0, 99999);
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showAlert('Copied to clipboard!', 'success');
            } else {
                throw new Error('Copy command failed');
            }
        } catch (err) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(element.value)
                    .then(() => showAlert('Copied to clipboard!', 'success'))
                    .catch(() => showAlert('Failed to copy to clipboard', 'warning'));
            } else {
                showAlert('Copy not supported by browser', 'warning');
            }
        }
    };

    // Download zone code
    window.downloadZoneCode = function() {
        const codeTextarea = document.getElementById('zoneCodeTextarea');
        if (codeTextarea && codeTextarea.value) {
            const blob = new Blob([codeTextarea.value], { type: 'text/html' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'adzone-embed-code.html';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            showAlert('Zone code downloaded!', 'success');
        }
    };

    // Show alert function
    window.showAlert = function(message, type) {
        document.querySelectorAll('.alert-auto-dismiss').forEach(alert => alert.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-auto-dismiss`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : type === 'danger' ? 'times' : type === 'warning' ? 'exclamation-triangle' : 'info'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.main-content');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    };
});
</script>

<style>
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: .4s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #28a745;
}

input:checked + .slider:before {
  transform: translateX(26px);
}

.font-monospace {
  font-family: 'Courier New', Courier, monospace !important;
}

.table-danger {
  background-color: rgba(220, 53, 69, 0.1);
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border: 1px solid rgba(0, 0, 0, 0.125);
}
</style>

<?php include 'includes/footer.php'; ?>