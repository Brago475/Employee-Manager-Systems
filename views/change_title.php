<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/Auditlogger.php';
require_once __DIR__ . '/../layout/header.php';

$auditLogger = new AuditLogger($pdo);

// Redirect if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$manager_emp_no = $_SESSION['emp_no'];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emp_no = (int)($_POST['emp_no'] ?? 0);
  $title  = trim($_POST['title'] ?? '');
  if ($emp_no > 0 && $title !== '') {
    try {
      $old_title = $auditLogger->getCurrentTitle($emp_no);
      
      $pdo->beginTransaction();
      
      $pdo->prepare("UPDATE titles SET to_date = CURRENT_DATE WHERE emp_no = ? AND (to_date IS NULL OR to_date > CURRENT_DATE)")
          ->execute([$emp_no]);
      
      $pdo->prepare("INSERT INTO titles(emp_no, title, from_date, to_date) VALUES(?, ?, CURRENT_DATE, NULL)")
          ->execute([$emp_no, $title]);
      
      if ($old_title) {
          $pdo->prepare("
              INSERT INTO titles_history (emp_no, title, from_date, to_date, changed_by)
              SELECT emp_no, title, from_date, CURRENT_DATE, ?
              FROM titles
              WHERE emp_no = ? AND to_date = CURRENT_DATE
          ")->execute([$manager_emp_no, $emp_no]);
      }
      
      $auditLogger->logTitleUpdate($manager_emp_no, $emp_no, $old_title ?: 'None', $title);
      
      $pdo->commit();
      $message = "<p style='color:green;text-align:center;'>Title changed from <strong>{$old_title}</strong> to <strong>{$title}</strong>.</p>";
    } catch(Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $message = "<p style='color:red;text-align:center;'>Error updating title: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
  } else {
    $message = "<p style='color:red;text-align:center;'>emp_no and title required.</p>";
  }
}

$titles = $pdo->query("
    SELECT title, COUNT(*) AS employee_count
    FROM titles
    WHERE to_date IS NULL OR to_date > CURRENT_DATE
    GROUP BY title
    ORDER BY title ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Change Title</h2>
<form method="post" style="max-width: 400px; margin: 20px 0;">
  <label style="display: block; margin-bottom: 15px;">
    Employee # 
    <input name="emp_no" type="number" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;">
  </label>
  
  <label style="display: block; margin-bottom: 15px;">
    Title
    <select name="title" required style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;">
      <option value="">-- choose --</option>
      <?php foreach($titles as $t): ?>
        <option value="<?= htmlspecialchars($t['title']) ?>">
            <?= htmlspecialchars($t['title']) ?> (<?= $t['employee_count'] ?> employees)
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  
  <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
    Change Title
  </button>
</form>
<?= $message ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>