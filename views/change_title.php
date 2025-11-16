<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Redirect if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emp_no = (int)($_POST['emp_no'] ?? 0);
  $title  = trim($_POST['title'] ?? '');
  if ($emp_no > 0 && $title !== '') {
    try {
      $pdo->beginTransaction();
      $pdo->prepare("UPDATE titles SET to_date = CURRENT_DATE WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)")
          ->execute([$emp_no]);
      $pdo->prepare("INSERT INTO titles(emp_no, title, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")
          ->execute([$emp_no, $title]);
      $pdo->commit();
      $message = "<p style='color:green;text-align:center;'>Title changed.</p>";
    } catch(Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $message = "<p style='color:red;text-align:center;'>Error updating title.</p>";
    }
  } else {
    $message = "<p style='color:red;text-align:center;'>emp_no and title required.</p>";
  }
}

/* -----------------------------
    : TITLE COUNTS ADDED
   ----------------------------- */
$titles = $pdo->query("
    SELECT title, COUNT(*) AS employee_count
    FROM titles
    WHERE to_date IS NULL OR to_date > CURRENT_DATE
    GROUP BY title
    ORDER BY title ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Change Title</h2>
<form method="post">
  <label>Employee # <input name="emp_no" required></label>
  <label>Title
    <select name="title" required>
      <option value="">-- choose --</option>

      <!-- UPDATED DROPDOWN -->
      <?php foreach($titles as $t): ?>
        <option value="<?= htmlspecialchars($t['title']) ?>">
            <?= htmlspecialchars($t['title']) ?> (<?= $t['employee_count'] ?> employees)
        </option>
      <?php endforeach; ?>

    </select>
  </label>
  <button>Change</button>
</form>
<?= $message ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
