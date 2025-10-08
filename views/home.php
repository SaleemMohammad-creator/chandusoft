<?php
ob_start();
?>
 
<h1>Welcome to Our Site</h1>
<p>This is the home page.</p>
 
<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>