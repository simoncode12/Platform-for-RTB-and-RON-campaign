<?php
/**
 * Cleanup duplicate categories script
 */

require_once '../config/database.php';

try {
    echo "Starting category cleanup...\n";
    
    $pdo->beginTransaction();
    
    // Get all categories grouped by name and type
    $stmt = $pdo->query("
        SELECT name, type, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
        FROM categories 
        GROUP BY LOWER(name), type
        HAVING count > 1
        ORDER BY name, type
    ");
    
    $duplicates = $stmt->fetchAll();
    
    echo "Found " . count($duplicates) . " duplicate groups\n";
    
    $total_deleted = 0;
    
    foreach ($duplicates as $duplicate) {
        $ids = explode(',', $duplicate['ids']);
        $keep_id = array_shift($ids); // Keep the first (oldest) record
        
        echo "Processing '{$duplicate['name']}' ({$duplicate['type']}): keeping ID {$keep_id}, deleting " . count($ids) . " duplicates\n";
        
        if (!empty($ids)) {
            // Update any references to point to the kept record
            $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
            
            // Update RTB campaigns
            $stmt = $pdo->prepare("UPDATE rtb_campaigns SET category_id = ? WHERE category_id IN ($ids_placeholder)");
            $stmt->execute(array_merge([$keep_id], $ids));
            
            // Update RON campaigns  
            $stmt = $pdo->prepare("UPDATE ron_campaigns SET category_id = ? WHERE category_id IN ($ids_placeholder)");
            $stmt->execute(array_merge([$keep_id], $ids));
            
            // Update websites
            $stmt = $pdo->prepare("UPDATE websites SET category_id = ? WHERE category_id IN ($ids_placeholder)");
            $stmt->execute(array_merge([$keep_id], $ids));
            
            // Delete duplicate records
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id IN ($ids_placeholder)");
            $stmt->execute($ids);
            
            $total_deleted += count($ids);
        }
    }
    
    // Also clean up any truly empty duplicates (same name, no description)
    $stmt = $pdo->query("
        DELETE c1 FROM categories c1
        INNER JOIN categories c2 
        WHERE c1.id > c2.id 
        AND LOWER(c1.name) = LOWER(c2.name) 
        AND c1.type = c2.type
        AND (c1.description IS NULL OR c1.description = '')
        AND (c2.description IS NULL OR c2.description = '')
    ");
    
    $empty_deleted = $stmt->rowCount();
    $total_deleted += $empty_deleted;
    
    echo "Deleted $empty_deleted empty duplicate categories\n";
    
    $pdo->commit();
    
    echo "Cleanup completed! Total deleted: $total_deleted\n";
    
    // Show remaining categories
    $stmt = $pdo->query("
        SELECT type, COUNT(*) as count 
        FROM categories 
        GROUP BY type 
        ORDER BY type
    ");
    
    echo "\nRemaining categories:\n";
    while ($row = $stmt->fetch()) {
        echo "- {$row['type']}: {$row['count']} categories\n";
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>