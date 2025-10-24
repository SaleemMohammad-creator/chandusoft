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
        if($imageFile['error'] === UPLOAD_ERR_INI_SIZE || $imageFile['error'] === UPLOAD_ERR_FORM_SIZE){
            $message = "File too large! Maximum 2MB allowed.";
        } elseif(!validateImageSize($imageFile)) {
            $message = "File too large! Maximum 2MB allowed.";
        } elseif(!validateImageType($imageFile)) {
            $message = "Invalid file type! Only JPG, PNG, WebP allowed.";
        } else {
            if($item['image'] && file_exists(UPLOAD_DIR . $item['image'])){
                unlink(UPLOAD_DIR . $item['image']);
            }
            $newImage = uploadImage($imageFile);
            if(!$newImage){
                $message = "Image upload failed!";
            } else {
                $imageName = $newImage;
            }
        }
    }

    if(!$message){
        $stmtUpdate = $pdo->prepare("UPDATE catalog SET title=?, slug=?, price=?, image=?, short_desc=?, status=? WHERE id=?");
        try{
            $stmtUpdate->execute([$title, $slug, $price, $imageName, $short_desc, $status, $id]);
            $message = "Catalog item updated successfully!";
            logMessage("Catalog item updated: $title (ID: $id)");
            $item = array_merge($item, [
                'title'=>$title,'slug'=>$slug,'price'=>$price,'image'=>$imageName,'short_desc'=>$short_desc,'status'=>$status
            ]);
        } catch(PDOException $e){
            if($e->getCode() === '23000'){ // duplicate slug
                $message = "Slug already exists. Choose another slug.";
            } else {
                $message = "Database error: " . $e->getMessage();
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
button{margin-top:20px;width:100%;padding:15px;background:#007BFF;color:#fff;border:none;border-radius:8px;font-weight:bold;cursor:pointer;}
button:hover{background:#0056b3;}
p.message{text-align:center;font-weight:bold;color:red;}
img.current-image{max-width:150px;margin-top:10px;border-radius:6px;}
</style>
</head>
<body>

<div class="container">
<h2>Edit Catalog Item</h2>
<?php if($message): ?><p class="message"><?= $message ?></p><?php endif; ?>

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

    <button type="submit">Update</button>
</form>
</div>

</body>
</html>