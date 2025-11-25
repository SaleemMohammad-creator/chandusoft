<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php'; // ✅ Added logging support
require_once __DIR__ . '/../utilities/log_action.php'; // ✅ Added DB log support

// Safe user info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Admin';

$id = intval($_GET['id'] ?? 0);
if(!$id) exit('Invalid ID');

$stmt = $pdo->prepare("SELECT * FROM catalog WHERE id=?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$item) exit('Item not found');

$message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $title = sanitize($_POST['title'] ?? '');
    $slug  = sanitize($_POST['slug'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $short_desc = sanitize($_POST['short_desc'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    $imageFile = $_FILES['image'] ?? null;
    $imageName = $item['image'];

    if($imageFile && $imageFile['error'] !== 4){
        try {
            // Delete old images
            if($item['image'] && file_exists(UPLOAD_DIR . $item['image'])){
                unlink(UPLOAD_DIR . $item['image']);
            }

            $extCheck = pathinfo($item['image'], PATHINFO_EXTENSION);
            if (in_array(strtolower($extCheck), ['jpg','jpeg','png'])) {
                $oldWebp = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $item['image']);
                if(file_exists(UPLOAD_DIR . $oldWebp)){
                    unlink(UPLOAD_DIR . $oldWebp);
                }
            }

            $uploaded = uploadOriginalAndWebP($imageFile);
            $imageName = $uploaded['original'];

        } catch (Exception $e) {
            $message = $e->getMessage();
        }
    }

    if(!$message){
        $stmtUpdate = $pdo->prepare("UPDATE catalog SET title=?, slug=?, price=?, image=?, short_desc=?, status=? WHERE id=?");

        try{
            $stmtUpdate->execute([$title, $slug, $price, $imageName, $short_desc, $status, $id]);
            $message = "success: Catalog item updated successfully!";

            // ✅ Mail Log (Mailpit)
            mailLog(
                "Catalog Item Updated: $title",
                "ID: $id | Slug: $slug | Admin ID: " . ($_SESSION['user_id'] ?? 'Unknown'),
                'catalog'
            );

            // ✅ Database Log (Admin Activity)
            log_action(
                $_SESSION['user_id'] ?? 0,
                'Catalog Item Updated',
                "ID: {$id} | Title: {$title} | Slug: {$slug} | Price: {$price}"
            );

            // ✅ Update local copy for instant preview
            $item = array_merge($item, [
                'title'=>$title,
                'slug'=>$slug,
                'price'=>$price,
                'image'=>$imageName,
                'short_desc'=>$short_desc,
                'status'=>$status
            ]);

        } catch(PDOException $e){
            $message = "error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Catalog Item</title>
<style>
 /* ===========================
   Global Styles
=========================== */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f7f8fc;
}

/* ===========================
   Navbar
=========================== */
.navbar {
    background: #2c3e50;
    color: #fff;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar-left {
    font-size: 22px;
    font-weight: bold;
}

.navbar-right {
    display: flex;
    align-items: center;
}

.navbar-right span {
    margin-right: 12px;
    font-weight: bold;
}

.navbar a {
    color: #fff;
    text-decoration: none;
    margin-left: 12px;
    font-weight: bold;
}

.nav-btn {
    padding: 6px 12px;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.nav-btn:hover {
    background: #1C86EE;
}

/* ===========================
   Container
=========================== */
.container {
    max-width: 1000px;
    margin: 100px auto 40px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 30px 28px;
}

h2 {
    text-align: center;
    color: #007BFF;
    margin-bottom: 25px;
}

/* ===========================
   Form Elements
=========================== */
label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
}

input,
textarea,
select {
    width: 100%;
    padding: 12px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
}

img.current-image {
    max-width: 150px;
    margin-top: 10px;
    border-radius: 6px;
}

/* ===========================
   Messages
=========================== */
.message {
    text-align: center;
    font-weight: bold;
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 6px;
}

.message-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* ===========================
   Buttons Layout
=========================== */
.button-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
}

/* Shared button base */
.btn {
    display: inline-block;
    padding: 8px 16px;
    font-weight: 600;
    font-size: 14px;
    text-align: center;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    border: none;
    transition: background 0.3s ease, transform 0.2s ease;
}

/* Primary button */
.btn-primary {
    background: #007BFF;
    color: #fff;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

/* Secondary button */
.btn-secondary {
    background: #007BFF;
    color: #fff;
}

.btn-secondary:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

</style>
</head>
<body>


<div class="navbar">
    <div class="navbar-left">Chandusoft <?= ucfirst(htmlspecialchars($user_role)) ?></div>
    <div class="navbar-right">
        <span>Welcome <?= ucfirst(htmlspecialchars($user_role)) ?>!</span>
        <a href="/admin/dashboard.php">Dashboard</a>
        <!-- Dynamic catalog link based on user role -->
    <?php if ($user_role === 'admin'): ?>
    <a href="/admin/catalog.php">Admin Catalog</a>
    <?php endif; ?>
    <a href="/public/catalog.php">Public Catalog</a>
        <a href="/admin/pages.php">Pages</a>
        <a href="/admin/admin-leads.php">Leads</a>
        <a href="/admin/logout.php">Logout</a>
    </div>
</div>

<div class="container">
<h2>Edit Catalog Item</h2>

<?php if($message): ?>
    <?php if(strpos($message, 'success:') === 0): ?>
        <p class="message message-success"><?= str_replace('success:','',$message) ?></p>
    <?php else: ?>
        <p class="message message-error"><?= str_replace('error:','',$message) ?></p>
    <?php endif; ?>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>Title</label>
    <input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>

    <label>Slug</label>
    <input type="text" name="slug" value="<?= htmlspecialchars($item['slug']) ?>" required>

    <label>Price</label>
    <input type="number" step="0.01" name="price" value="<?= $item['price'] ?>" required>

    <label>Current Image</label>
    <?php if($item['image'] && file_exists(UPLOAD_DIR . $item['image'])): ?>
        <img src="/uploads/<?= htmlspecialchars($item['image']) ?>" class="current-image">
    <?php else: ?>
        <p>No image uploaded</p>
    <?php endif; ?>

    <label>Change Image (Max 2MB)</label>
    <input type="file" name="image" accept="image/*">

    <label>Short Description</label>
    <textarea name="short_desc"><?= htmlspecialchars($item['short_desc']) ?></textarea>

    <label>Status</label>
    <select name="status">
        <option value="draft" <?= $item['status']==='draft'?'selected':'' ?>>Draft</option>
        <option value="published" <?= $item['status']==='published'?'selected':'' ?>>Published</option>
        <option value="archived" <?= $item['status']==='archived'?'selected':'' ?>>Archived</option>
    </select>

    <!-- ✅ Same color + width buttons -->
    <div class="button-row">
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="/admin/catalog.php" class="btn btn-secondary">← Back to Catalog</a>
   </div>

</form>
</div>

</body>
</html>