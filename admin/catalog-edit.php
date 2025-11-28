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
    font-family: "Inter", Arial, sans-serif;
    margin: 0;
    background: #f3f4f6;
    color: #111827;
}

/* ===========================
   Navbar
=========================== */
.navbar {
    background: #1f2937;
    color: #fff;
    padding: 16px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}

.navbar-left {
    font-size: 22px;
    font-weight: 700;
}

.navbar-right {
    display: flex;
    align-items: center;
}

.navbar-right span {
    margin-right: 14px;
    font-weight: 600;
}

.navbar a {
    padding: 8px 14px;
    margin-left: 10px;
    border-radius: 6px;
    font-weight: 600;
    color: #e5e7eb;
    text-decoration: none;
    transition: 0.25s ease-in-out;
}

.navbar a:hover {
    background: #374151;
}

.navbar a.active {
    background: #2563eb !important;
    color: #fff;
}

/* ===========================
   Container / Card
=========================== */
.container {
    max-width: 900px;
    margin: 90px auto 40px;
    padding: 0 20px;
}

.card {
    background: #fff;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}

h2 {
    text-align: center;
    font-size: 26px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 25px;
}

/* ===========================
   Messages (success / error)
=========================== */
.message {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-weight: 600;
    text-align: center;
}

.message-success {
    background: #dcfce7;
    color: #14532d;
    border: 1px solid #86efac;
}

.message-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* ===========================
   Form Layout
=========================== */
.form-row {
    display: flex;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 18px;
}

.form-row label {
    width: 200px;
    font-size: 15px;
    font-weight: 600;
    color: #374151;
    padding-top: 8px;
}

.form-row input,
.form-row textarea,
.form-row select {
    flex: 1;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    outline: none;
    background: #fff;
    transition: border 0.25s, box-shadow 0.25s;
}

.form-row input:focus,
.form-row textarea:focus,
.form-row select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
}

textarea {
    height: 90px;
    resize: vertical;
}

/* ===========================
   Current Image Preview
=========================== */
.current-image {
    max-width: 160px;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}

/* ===========================
   Buttons
=========================== */
.button-row {
    margin-left: 200px;
    margin-top: 10px;
    display: flex;
    gap: 12px;
}

.btn {
    padding: 12px 20px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: 0.2s;
}

/* Primary – Save */
.btn-primary {
    background: #2563eb;
    color: #fff;
}
.btn-primary:hover {
    background: #1e4fd4;
    transform: translateY(-1px);
}

/* Secondary – Back */
.btn-secondary {
    background: #6b7280;
    color: #fff;
}
.btn-secondary:hover {
    background: #4b5563;
    transform: translateY(-1px);
}

</style>
</head>
<body>

<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<div class="navbar">
    <div class="navbar-left">Chandusoft <?= ucfirst(htmlspecialchars($user_role)) ?></div>
    <div class="navbar-right">
        <span>Welcome <?= ucfirst(htmlspecialchars($user_role)) ?>!</span>
        <a href="/admin/dashboard.php">Dashboard</a>

        <?php if ($user_role === 'admin'): ?>
        <a href="/admin/catalog.php"
           style="<?= ($currentPage === 'catalog.php' || 
                       $currentPage === 'catalog-new.php' || 
                       $currentPage === 'catalog-edit.php') 
                   ? 'background:#1E90FF; padding:6px 12px; border-radius:4px;' 
                   : '' ?>">
            Admin Catalog
        </a>
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

    <div class="form-row">
        <label>Title:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>
    </div>

    <div class="form-row">
        <label>Slug:</label>
        <input type="text" name="slug" value="<?= htmlspecialchars($item['slug']) ?>" required>
    </div>

    <div class="form-row">
        <label>Price:</label>
        <input type="number" step="0.01" name="price" value="<?= $item['price'] ?>" required>
    </div>

    <div class="form-row">
        <label>Current Image:</label>

        <?php if($item['image'] && file_exists(UPLOAD_DIR . $item['image'])): ?>
            <img src="/uploads/<?= htmlspecialchars($item['image']) ?>" class="current-image">
        <?php else: ?>
            <span>No image uploaded</span>
        <?php endif; ?>
    </div>

    <div class="form-row">
        <label>Change Image (Max 2MB):</label>
        <input type="file" name="image" accept="image/*">
    </div>

    <div class="form-row">
        <label>Short Description:</label>
        <textarea name="short_desc"><?= htmlspecialchars($item['short_desc']) ?></textarea>
    </div>

    <div class="form-row">
        <label>Status:</label>
        <select name="status">
            <option value="draft"     <?= $item['status']==='draft'?'selected':'' ?>>Draft</option>
            <option value="published" <?= $item['status']==='published'?'selected':'' ?>>Published</option>
            <option value="archived"  <?= $item['status']==='archived'?'selected':'' ?>>Archived</option>
        </select>
    </div>

    <div class="button-row">
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="/admin/catalog.php" class="btn btn-secondary">← Back to Catalog</a>
    </div>

</form>

</div>

</body>
</html>