<?php
// ============================================================
// Chandusoft Helper Functions
// ============================================================

// -------------------------
// Sanitize input
// -------------------------
if (!function_exists('sanitize')) {
    function sanitize($str) {
        return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
    }
}

// -------------------------
// Logging
// -------------------------
if (!function_exists('logMessage')) {
    function logMessage($msg) {
        $date = date('Y-m-d H:i:s');
        $logDir = __DIR__ . '/../storage/logs/';
        if (!is_dir($logDir)) mkdir($logDir, 0777, true);
        file_put_contents($logDir . 'app.log', "[$date] $msg" . PHP_EOL, FILE_APPEND);
    }
}

// -------------------------
// Validate image size (max MB)
// -------------------------
if (!function_exists('validateImageSize')) {
    function validateImageSize($file, $maxMB = 2) {
        return isset($file['size']) && $file['size'] <= $maxMB * 1024 * 1024;
    }
}

// -------------------------
// Validate MIME type
// -------------------------
if (!function_exists('validateImageType')) {
    function validateImageType($file) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        return in_array($mime, $allowed);
    }
}

// -------------------------
// Resize + WebP convert
// -------------------------
if (!function_exists('resizeAndConvertToWebp')) {
    function resizeAndConvertToWebp($srcPath, $maxWidth = 1600) {
        if (!extension_loaded('gd')) {
            logMessage("❌ GD not loaded — cannot resize or convert WebP.");
            return false;
        }

        [$width, $height, $type] = @getimagesize($srcPath);
        if (!$width || !$height) {
            logMessage("❌ Invalid image file: $srcPath");
            return false;
        }

        // Determine new size
        if ($width > $maxWidth) {
            $ratio = $height / $width;
            $newWidth = $maxWidth;
            $newHeight = (int)($newWidth * $ratio);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        // Load image
        switch ($type) {
            case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($srcPath); break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($srcPath);
                imagepalettetotruecolor($src);
                imagealphablending($src, true);
                imagesavealpha($src, true);
                break;
            case IMAGETYPE_WEBP: $src = imagecreatefromwebp($srcPath); break;
            default: return false;
        }

        if (!$src) return false;

        // Create destination
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save resized original (overwrite original)
        $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($dst, $srcPath, 85);
                break;
            case 'png':
                imagepng($dst, $srcPath, 6);
                break;
        }

        // Save WebP version
        $newPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $srcPath);
        if (function_exists('imagewebp')) {
            if (imagewebp($dst, $newPath, 85)) {
                logMessage("✅ WebP created: " . basename($newPath));
            } else {
                logMessage("⚠️ WebP conversion failed for $srcPath");
            }
        } else {
            logMessage("⚠️ imagewebp() not available in GD build.");
        }

        imagedestroy($src);
        imagedestroy($dst);
        return $newPath;
    }
}

// -------------------------
// Upload image safely
// -------------------------
if (!function_exists('uploadImage')) {
    function uploadImage($file) {
        if (!validateImageSize($file) || !validateImageType($file)) {
            logMessage("❌ Invalid image upload: size/type check failed.");
            return false;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $subdir = date('Y/m/');
        $uploadDir = __DIR__ . "/../public/uploads/$subdir";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = uniqid('img_') . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $webpPath = resizeAndConvertToWebp($destination);
            if ($webpPath) logMessage("✅ Image uploaded + WebP created: $filename");
            else logMessage("⚠️ Uploaded but WebP not created: $filename");
            return "$subdir$filename";
        }

        logMessage("❌ Failed to move uploaded file: {$file['name']}");
        return false;
    }
}
