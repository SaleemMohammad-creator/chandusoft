<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../utilities/log_action.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===============================
// 🔹 Pagination Setup
// ===============================
$limit = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// ===============================
// 🔹 Optional Filters
// ===============================
$search = trim($_GET['search'] ?? '');
$whereSql = '';
$params = [];

if ($search !== '') {
    $whereSql = "WHERE (action LIKE ? OR details LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

// ===============================
// 🔹 Fetch Logs (use ? placeholders for MySQL)
// ===============================
$query = "SELECT * FROM admin_logs $whereSql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// 🔹 Count Total Logs
// ===============================
$countQuery = "SELECT COUNT(*) FROM admin_logs $whereSql";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$total_logs = $countStmt->fetchColumn();

$total_pages = ceil($total_logs / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Logs</title>
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 30px;
            background: #f5f5f5;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            text-align: center;
            margin-bottom: 20px;
        }
        input[type="text"] {
            width: 350px;
            padding: 14px;
            font-size: 17px;
            border: 1px solid #aaa;
            border-radius: 8px;
        }
        button {
            padding: 14px 24px;
            font-size: 17px;
            background-color: #0078d7;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-left: 5px;
        }
        button:hover {
            background-color: #005fa3;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            box-shadow: 0 0 6px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px 12px;
            font-size: 14px;
        }
        th {
            background: #222;
            color: #fff;
            text-align: left;
        }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f1f1f1; }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            display: inline-block;
            padding: 8px 14px;
            margin: 0 3px;
            background: #0078d7;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
        }
        .pagination a.active {
            background: #222;
        }
        .pagination a:hover {
            background: #005fa3;
        }
    </style>
</head>
<body>
    <h2>📜 Admin Activity Logs</h2>

    <form method="get">
        <input type="text" name="search" placeholder="Search actions or details..." 
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Admin ID</th>
            <th>Action</th>
            <th>Details</th>
            <th>IP</th>
            <th>User Agent</th>
            <th>Time</th>
        </tr>
        <?php if (empty($logs)): ?>
        <tr><td colspan="7" style="text-align:center;">No logs found.</td></tr>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= $log['id'] ?></td>
            <td><?= $log['admin_id'] ?: '-' ?></td>
            <td><?= htmlspecialchars($log['action']) ?></td>
            <td><?= htmlspecialchars($log['details']) ?></td>
            <td><?= htmlspecialchars($log['ip_address']) ?></td>
            <td><?= htmlspecialchars(substr($log['user_agent'], 0, 50)) ?>...</td>
            <td><?= $log['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($total_pages > 1): ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                   class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</body>
</html>
