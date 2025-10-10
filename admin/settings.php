<?php
require_once 'config.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = $_POST['site_name'] ?? '';
    $stmt = $pdo->prepare("REPLACE INTO settings (`key`, `value`) VALUES ('site_name', :name)");
    $stmt->execute(['name' => $siteName]);
    $message = "✅ Site name updated!";
}

// Fetch current site name
$stmt = $pdo->prepare("SELECT value FROM settings WHERE `key`='site_name'");
$stmt->execute();
$currentName = $stmt->fetchColumn() ?: "Chandusoft";
?>
<h2>Site Settings</h2>
<form method="POST">
    <label>Site Name:</label>
    <input type="text" name="site_name" value="<?= htmlspecialchars($currentName) ?>" required>
    <button type="submit">Save</button>
</form>
<?php if ($message) echo "<p>$message</p>"; ?>
