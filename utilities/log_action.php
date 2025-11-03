<?php
// ===============================================
// 🧾 Admin Action Logging Helper
// ===============================================
// Purpose: Logs all major admin actions for auditing & security.
// Location: /utilities/log_action.php

function log_action($admin_id, $action, $details = '') {
    require_once __DIR__ . '/../app/config.php'; // Load PDO instance once
    global $pdo; // ✅ Make PDO available inside this function

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    try {
        // Use named parameters for better readability and NULL handling
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
            VALUES (:admin_id, :action, :details, :ip, :agent)
        ");
        $stmt->execute([
            ':admin_id' => $admin_id,
            ':action'   => $action,
            ':details'  => $details,
            ':ip'       => $ip,
            ':agent'    => $agent
        ]);
    } catch (PDOException $e) {
        // fallback if DB logging fails — store in local file
        $log_dir = __DIR__ . '/../storage/logs/';
        if (!file_exists($log_dir)) mkdir($log_dir, 0755, true);
        $logfile = $log_dir . 'admin_fallback.log';
        $msg = date('Y-m-d H:i:s') . " [Log failed] " . $e->getMessage() . PHP_EOL;
        file_put_contents($logfile, $msg, FILE_APPEND | LOCK_EX);
    }
}
?>
