<?php
/**
 * Database Configuration
 * AdStart RTB & RON Platform
 */

$DB_HOST = 'localhost';
$DB_USER = 'user_up';
$DB_PASS = 'Puputchen12$';
$DB_NAME = 'user_up';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // REMOVED: Auto-insert of categories and users
    // This was causing duplicate entries on every page load
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Utility function to check if setup is needed (use only once)
function isSetupNeeded($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        return $stmt->fetchColumn() == 0;
    } catch (Exception $e) {
        return true;
    }
}

// Only for debugging - remove in production
if (isset($_GET['debug_db']) && $_GET['debug_db'] === 'true') {
    echo "Database connected successfully<br>";
    echo "Server: $DB_HOST<br>";
    echo "Database: $DB_NAME<br>";
    echo "Time: " . date('Y-m-d H:i:s') . "<br>";
}
?>