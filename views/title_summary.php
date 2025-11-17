<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Restrict access: only logged-in users
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$sql = "
SELECT t.title, COUNT(*) AS title_count
FROM titles t
WHERE (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
GROUP BY t.title
ORDER BY title_count DESC, t.title ASC
";
$rows = $pdo->query($sql)->fetchAll();
?>
<h2>Title Summary</h2>
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr><th>Title</th><th>Employee Count</th></tr>
  </thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td><?= htmlspecialchars($r['title_count']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>

