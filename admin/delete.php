<?php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/mail-logger.php'; // ✅ Added for Mailpit Logging

// Only admins can delete
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    log_error("❌ Unauthorized delete attempt detected. User role: " . ($_SESSION['user_role'] ?? 'unknown'));
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Unknown Admin';

// Check delete_id
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        $_SESSION['success_message'] = "Page ID {$id} deleted successfully by {$adminName} ✅";
        
        // ✅ Log success to Mailpit with admin name
        log_info("🗑️ Page ID {$id} deleted successfully by Admin: {$adminName}");
    } else {
        $_SESSION['error_message'] = "Failed to delete page by {$adminName}!";
        
        // ✅ Log failure to Mailpit
        log_error("⚠️ Page ID {$id} deletion failed by Admin: {$adminName}");
    }
} else {
    $_SESSION['error_message'] = "Invalid request!";
    
    // ✅ Log invalid attempt to Mailpit
    log_error("🚫 Invalid delete request received — No delete_id parameter. User: {$adminName}");
}

header("Location: pages.php");
exit;
