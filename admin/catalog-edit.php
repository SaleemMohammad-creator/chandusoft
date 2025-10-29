<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/mail-logger.php'; // ✅ Added logging support


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

            // ✅ Log update
            mailLog("Catalog Item Updated: $title",
                "ID: $id | Slug: $slug | Admin ID: " . ($_SESSION['user_id'] ?? 'Unknown'),
                'catalog'
            );

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
body { font-family: Arial; margin:0; background:#f7f8fc; }

.navbar {
    background:#2c3e50;
    color:#fff;
    padding:15px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;

    /* ✅ new lines */
    position: fixed;
    top: 0;
    left: 0;
    width:100%;
    z-index:1000;
    box-sizing: border-box;
}

.navbar a { color:#fff; text-decoration:none; margin-left:15px; font-weight:bold; }
.navbar .navbar-left { font-weight:bold; font-size:22px; }
.navbar .navbar-right { display:flex; align-items:center; }
.navbar .navbar-right span { margin-right:10px; font-weight:bold; }
.navbar a.nav-btn { color:#fff; text-decoration:none; margin-left:5px; font-weight:bold; padding:6px 12px; border-radius:4px; transition:background 0.3s; }
.navbar a.nav-btn:hover { background:#1C86EE; }

/* ✅ Prevent overlap by pushing content down */
.container {
    max-width:1000px;
    margin:100px auto 40px auto; /* Keep your original spacing */
    background:#fff;
    border-radius:10px;
    box-shadow:0 4px 12px #0001;
    padding:30px 28px;
}
h2{text-align:center;color:#007BFF;margin-bottom:25px;}
label{display:block;margin-top:15px;font-weight:bold;}
input,textarea,select{width:100%;padding:12px;border-radius:6px;border:1px solid #ccc;margin-top:5px;box-sizing:border-box;}
img.current-image{max-width:150px;margin-top:10px;border-radius:6px;}

/* ✅ Success + Error messages */
.message{
    text-align:center;
    font-weight:bold;
    margin-bottom:10px;
    padding:12px;
    border-radius:6px;
}
.message-success{
    background:#d4edda;
    color:#155724;
    border:1px solid #c3e6cb;
}
.message-error{
    background:#f8d7da;
    color:#721c24;
    border:1px solid #f5c6cb;
}

/* ✅ Buttons same style, side by side */
.button-row{
    display:flex;
    gap:10px;
    margin-top:20px;
}
.btn{
    flex:1;
    display:inline-block;
    padding:15px;
    font-weight:bold;
    text-align:center;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
    border:none;
    color:#fff;
    background:#007BFF;
    transition:0.3s;
}
.btn:hover{
    background:#0056b3;
}
</style>
</head>
<body>


<div class="navbar">
    <div class="navbar-left">Chandusoft Admin</div>
    <div class="navbar-right">
        <span>Welcome <?= htmlspecialchars($user_role)?>!</span>
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
        <button type="submit" class="btn">Update</button>
        <a href="/admin/catalog" class="btn">Back to Catalog</a>
    </div>

</form>
</div>

</body>
</html>
