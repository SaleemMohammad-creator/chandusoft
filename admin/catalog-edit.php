<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

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

            logCatalogAction("Item ID $id updated: '$title' by Admin ID: " . ($_SESSION['user_id'] ?? 'Unknown'));

            $item = array_merge($item, [
                'title'=>$title,
                'slug'=>$slug,
                'price'=>$price,
                'image'=>$imageName,
                'short_desc'=>$short_desc,
                'status'=>$status
            ]);

        } catch(PDOException $e){
            if($e->getCode() === '23000'){
                $message = "error: Slug already exists. Choose another slug.";
            } else {
                $message = "error: Database error: " . $e->getMessage();
            }
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
body{font-family:Arial,sans-serif;background:#f9f9f9;padding:20px;}
.container{max-width:600px;margin:30px auto;background:#fff;padding:30px;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,0.1);}
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
