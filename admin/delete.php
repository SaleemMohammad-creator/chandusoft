<?php
session_start();
require_once __DIR__ . '/../app/config.php';

// Only admins can delete
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check delete_id
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success_message'] = "Page deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete page!";
    }
} else {
    $_SESSION['error_message'] = "Invalid request!";
}

header("Location: pages.php");
exit;
