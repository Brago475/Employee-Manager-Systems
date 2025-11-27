<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/Auditlogger.php';
require_once __DIR__ . '/../layout/header.php';

$auditLogger = new AuditLogger($pdo);

if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$manager_emp_no = $_SESSION['emp_no'];

$message = '';
$messageType = '';

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
      $message = "Title changed from {$old_title} to {$title}";
      $messageType = 'success';
    } catch(Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $message = "Error updating title: " . htmlspecialchars($e->getMessage());
      $messageType = 'error';
    }
  } else {
    $message = "Employee number and title are required";
    $messageType = 'error';
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

<style>
.page-header {
    margin-bottom: 35px;
}

.page-title {
    color: #0052a3;
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 10px;
}

.page-subtitle {
    color: #666;
    font-size: 15px;
}

.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 500;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: 1px solid #c3e6cb;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);
}

.alert-error {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border: 1px solid #f5c6cb;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.15);
}

.alert-icon {
    font-size: 20px;
}

.form-container {
    display: grid;
    grid-template-columns: 500px 1fr;
    gap: 30px;
    align-items: start;
}

.form-card {
    background: white;
    padding: 35px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 82, 163, 0.1);
    border: 1px solid #e9ecef;
}

.form-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.title-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    font-size: 14px;
    color: #333;
    margin-bottom: 8px;
}

.form-group input,
.form-group select {
    padding: 12px 14px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #0052a3;
    box-shadow: 0 0 0 4px rgba(0, 82, 163, 0.1);
}

.form-group select {
    cursor: pointer;
}

.helper-text {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.btn-submit {
    padding: 14px 28px;
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0, 82, 163, 0.3);
    margin-top: 10px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 82, 163, 0.4);
}

.btn-submit:active {
    transform: translateY(0);
}

.info-sidebar {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.info-section {
    margin-bottom: 25px;
}

.info-section:last-child {
    margin-bottom: 0;
}

.info-title {
    font-size: 16px;
    font-weight: 700;
    color: #0052a3;
    margin-bottom: 12px;
}

.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-list li {
    padding: 8px 0;
    font-size: 13px;
    color: #555;
    display: flex;
    align-items: start;
    gap: 8px;
}

.info-list li::before {
    content: "•";
    color: #0052a3;
    font-weight: bold;
    font-size: 16px;
}

.title-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-top: 15px;
}

.title-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e0e0e0;
    transition: all 0.2s;
}

.title-card:hover {
    border-color: #0052a3;
    box-shadow: 0 2px 8px rgba(0, 82, 163, 0.15);
}

.title-name {
    font-size: 12px;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.title-count {
    font-size: 20px;
    font-weight: 700;
    color: #0052a3;
}

.title-label {
    font-size: 10px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 1024px) {
    .form-container {
        grid-template-columns: 1fr;
    }
    
    .info-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 24px;
    }
    
    .form-card {
        padding: 25px 20px;
    }
    
    .title-grid {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    }
}
</style>

<div class="page-header">
    <h1 class="page-title">Change Title</h1>
    <p class="page-subtitle">Update employee job title</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <span class="alert-icon"><?= $messageType === 'success' ? '✓' : '✕' ?></span>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<div class="form-container">
    <div class="form-card">
        <h3 class="form-title">Title Update</h3>
        
        <form method="post" class="title-form">
            <div class="form-group">
                <label>Employee Number</label>
                <input name="emp_no" 
                       type="number" 
                       placeholder="Enter employee number"
                       required>
                <span class="helper-text">Enter the employee number to update</span>
            </div>
            
            <div class="form-group">
                <label>New Job Title</label>
                <select name="title" required>
                    <option value="">Select Title</option>
                    <?php foreach($titles as $t): ?>
                        <option value="<?= htmlspecialchars($t['title']) ?>">
                            <?= htmlspecialchars($t['title']) ?> (<?= $t['employee_count'] ?> employees)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="helper-text">Choose the new job title</span>
            </div>
            
            <button type="submit" class="btn-submit">
                Change Title
            </button>
        </form>
    </div>

    <div class="info-sidebar">
        <div class="info-section">
            <div class="info-title">How It Works</div>
            <ul class="info-list">
                <li>Enter the employee number to update</li>
                <li>Select the new job title from the list</li>
                <li>Current title assignment is closed</li>
                <li>New title assignment is created</li>
                <li>Change is logged in audit trail</li>
            </ul>
        </div>

        <div class="info-section">
            <div class="info-title">Available Titles</div>
            <div class="title-grid">
                <?php foreach($titles as $t): ?>
                    <div class="title-card">
                        <div class="title-name" title="<?= htmlspecialchars($t['title']) ?>">
                            <?= htmlspecialchars($t['title']) ?>
                        </div>
                        <div class="title-count"><?= $t['employee_count'] ?></div>
                        <div class="title-label">Employees</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="info-section">
            <div class="info-title">Important Notes</div>
            <ul class="info-list">
                <li>Title changes are effective immediately</li>
                <li>Previous title history is preserved</li>
                <li>All changes are audited and tracked</li>
                <li>Manager authorization is required</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>