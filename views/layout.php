<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page['title'] ?? 'Home') ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="index.php?page=home">Home</a></li>
            <?php if (!empty($navPages) && is_array($navPages)): ?>
                <?php foreach ($navPages as $p): ?>
                    <li><a href="index.php?page=<?= htmlspecialchars($p['slug']) ?>"><?= htmlspecialchars($p['title']) ?></a></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </nav>
 
    <main>
        <?= $content ?>
    </main>
</body>
</html>
 