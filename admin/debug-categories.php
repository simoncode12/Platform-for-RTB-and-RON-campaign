<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

echo "<h2>Category Debug Information</h2>";
echo "<p>Current User: " . ($_SESSION['username'] ?? 'Unknown') . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";

// Check recent category additions
echo "<h3>Recent Category Additions (Last 24 hours)</h3>";
$stmt = $pdo->query("
    SELECT *, 
           TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago
    FROM categories 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY created_at DESC
");

$recent = $stmt->fetchAll();
if ($recent) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Created At</th><th>Minutes Ago</th></tr>";
    foreach ($recent as $cat) {
        echo "<tr>";
        echo "<td>{$cat['id']}</td>";
        echo "<td>{$cat['name']}</td>";
        echo "<td>{$cat['type']}</td>";
        echo "<td>{$cat['created_at']}</td>";
        echo "<td>{$cat['minutes_ago']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No categories created in the last 24 hours.</p>";
}

// Check for duplicates
echo "<h3>Duplicate Categories</h3>";
$stmt = $pdo->query("
    SELECT name, type, COUNT(*) as count, GROUP_CONCAT(id) as ids
    FROM categories 
    GROUP BY LOWER(TRIM(name)), type
    HAVING count > 1
    ORDER BY count DESC, name
");

$duplicates = $stmt->fetchAll();
if ($duplicates) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Name</th><th>Type</th><th>Count</th><th>IDs</th></tr>";
    foreach ($duplicates as $dup) {
        echo "<tr>";
        echo "<td>{$dup['name']}</td>";
        echo "<td>{$dup['type']}</td>";
        echo "<td style='color: red; font-weight: bold;'>{$dup['count']}</td>";
        echo "<td>{$dup['ids']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No duplicate categories found.</p>";
}

// Check total counts
echo "<h3>Category Counts by Type</h3>";
$stmt = $pdo->query("
    SELECT type, COUNT(*) as count
    FROM categories 
    GROUP BY type
    ORDER BY type
");

$counts = $stmt->fetchAll();
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Type</th><th>Count</th></tr>";
foreach ($counts as $count) {
    echo "<tr>";
    echo "<td>{$count['type']}</td>";
    echo "<td>{$count['count']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check for potential causes
echo "<h3>Potential Issues</h3>";

// Check if there are any scheduled tasks or cron jobs
if (file_exists('../database/install.php')) {
    echo "<p style='color: orange;'>⚠️ Database install script still exists - this might be running automatically</p>";
}

// Check if schema.sql is being executed repeatedly
$stmt = $pdo->query("SHOW TABLES LIKE 'migrations'");
if ($stmt->rowCount() == 0) {
    echo "<p style='color: orange;'>⚠️ No migrations table found - database might be re-initializing</p>";
}

// Check for auto-refresh or reload issues
echo "<h3>Recommendations</h3>";
echo "<ul>";
echo "<li>Remove or rename database/install.php to prevent auto-execution</li>";
echo "<li>Check for browser auto-refresh or JavaScript reload loops</li>";
echo "<li>Check server cron jobs that might be running database scripts</li>";
echo "<li>Verify no other admin users are adding categories</li>";
echo "</ul>";

echo "<h3>Quick Actions</h3>";
echo "<a href='?action=cleanup' onclick='return confirm(\"Clean up duplicate categories?\")' style='background: red; color: white; padding: 10px; text-decoration: none;'>Clean Duplicates</a>";

if (isset($_GET['action']) && $_GET['action'] == 'cleanup') {
    echo "<h3>Cleaning Duplicates...</h3>";
    
    try {
        $pdo->beginTransaction();
        
        // Keep only the first occurrence of each name+type combination
        $stmt = $pdo->exec("
            DELETE c1 FROM categories c1
            INNER JOIN categories c2 
            WHERE c1.id > c2.id 
            AND LOWER(TRIM(c1.name)) = LOWER(TRIM(c2.name))
            AND c1.type = c2.type
        ");
        
        $pdo->commit();
        echo "<p style='color: green;'>✅ Removed $stmt duplicate categories</p>";
        echo "<p><a href='debug-categories.php'>Refresh to see results</a></p>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}
?>