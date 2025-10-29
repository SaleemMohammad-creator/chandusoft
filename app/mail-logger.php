<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer (composer)
if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Central logger that writes to a local file (backup) and sends to Mailpit.
 * All function definitions are guarded with function_exists to avoid redeclare.
 */

if (!function_exists('log_message')) {
    function log_message($type, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $cleanMessage = trim($message);

        // ✅ Fix broken UTF-8 characters in Mailpit logs
        $cleanMessage = mb_convert_encoding($cleanMessage, 'UTF-8', 'UTF-8');
        $cleanMessage = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '', $cleanMessage);

        $logLine = "[$timestamp][$type] $cleanMessage";

        // local backup (optional)
        @file_put_contents(__DIR__ . '/../storage/logs/system.log', $logLine . PHP_EOL, FILE_APPEND);

        // send to Mailpit
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = '127.0.0.1';
            $mail->Port = 1025;           // Mailpit SMTP default
            $mail->SMTPAuth = false;

            // ✅ Ensure proper encoding for Mailpit inbox
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);

            $mail->setFrom('logger@chandusoft.test', 'Chandusoft Logger');
            $mail->addAddress('admin@chandusoft.test');

            $mail->Subject = "LOG: $type";
            $mail->Body = nl2br($logLine);

            $mail->send();
        } catch (Exception $e) {
            error_log("Mailpit Logging Failed: " . ($mail->ErrorInfo ?? $e->getMessage()));
        }
    }
}

if (!function_exists('mailLog')) {
    function mailLog($subject, $message) {
        log_message($subject, $message);
    }
}

if (!function_exists('log_info')) {
    function log_info($msg) {
        log_message('INFO', $msg);
    }
}

if (!function_exists('log_error')) {
    function log_error($msg) {
        log_message('ERROR', $msg);
    }
}

if (!function_exists('send_mailpit_notification')) {
    function send_mailpit_notification($subject, $body) {
        log_message("NOTICE: $subject", $body);
    }
}
