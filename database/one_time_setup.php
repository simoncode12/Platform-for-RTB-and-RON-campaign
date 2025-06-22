<?php
/**
 * One-time setup script
 * Run this only once after fresh installation
 */

require_once '../config/database.php';

// Check if already setup
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ($stmt->fetchColumn() > 0) {
        die("Setup already completed. Admin user exists.");
    }
} catch (Exception $e) {
    // Continue with setup
}

try {
    $pdo->beginTransaction();
    
    echo "Starting one-time setup...\n";
    
    // Create admin user if not exists
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['admin', $password, 'admin@adstart.click', 'admin']);
    echo "Admin user created.\n";
    
    // Clear any existing categories first
    $pdo->exec("DELETE FROM categories");
    echo "Cleared existing categories.\n";
    
    // Insert default categories
    $categories = [
        ['Adult', 'Adult content and related services', 'adult'],
        ['Mainstream', 'General content suitable for all audiences', 'mainstream'],
        ['Dating', 'Dating and relationship services', 'mainstream'],
        ['Gaming', 'Video games and gaming services', 'mainstream'],
        ['Finance', 'Financial services and products', 'mainstream'],
        ['Health', 'Health and wellness products', 'mainstream'],
        ['Technology', 'Technology and software products', 'mainstream'],
        ['Entertainment', 'Entertainment and media content', 'mainstream']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO categories (name, description, type) VALUES (?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    echo "Created " . count($categories) . " default categories.\n";
    
    // Mark setup as complete
    $pdo->exec("CREATE TABLE IF NOT EXISTS setup_status (completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("INSERT INTO setup_status VALUES ()");
    
    $pdo->commit();
    echo "Setup completed successfully!\n";
    
    // Rename this file to prevent re-execution
    rename(__FILE__, __FILE__ . '.completed');
    echo "Setup script disabled.\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Setup failed: " . $e->getMessage() . "\n";
}
?>