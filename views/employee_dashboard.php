<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// redirect to login if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$emp_no = $_SESSION['emp_no'];
$is_manager = $_SESSION['is_manager'] ?? false;

//gets employee details + manager info 
$sql = "
SELECT 
    e.first_name, e.last_name, e.birth_date, e.hire_date,
    d.dept_name, de.from_date AS dept_start, de.to_date AS dept_end,
    t.title, t.from_date AS title_start, t.to_date AS title_end,
    s.salary,
    mgr.first_name AS manager_first,
    mgr.last_name AS manager_last,
    mgr.emp_no AS manager_emp_no
FROM employees e
LEFT JOIN dept_emp de ON e.emp_no = de.emp_no 
    AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
LEFT JOIN departments d ON de.dept_no = d.dept_no
LEFT JOIN titles t ON e.emp_no = t.emp_no 
    AND (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
LEFT JOIN salaries s ON e.emp_no = s.emp_no 
    AND (s.to_date IS NULL OR s.to_date > CURRENT_DATE)
LEFT JOIN dept_manager dmgr ON dmgr.dept_no = de.dept_no
    AND (dmgr.to_date IS NULL OR dmgr.to_date > CURRENT_DATE)
LEFT JOIN employees mgr ON mgr.emp_no = dmgr.emp_no
WHERE e.emp_no = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$emp_no]);
$employee = $stmt->fetch();

// gets employee's performance reviews
$reviews_sql = "
SELECT pr.*, 
       r.first_name as reviewer_first, r.last_name as reviewer_last
FROM performance_reviews pr
JOIN employees r ON pr.reviewer_emp_no = r.emp_no
WHERE pr.emp_no = ?
ORDER BY pr.review_date DESC
LIMIT 10
";

$reviews_stmt = $pdo->prepare($reviews_sql);
$reviews_stmt->execute([$emp_no]);
$reviews = $reviews_stmt->fetchAll();

// calculate employment stats
$hire_date = new DateTime($employee['hire_date']);
$today = new DateTime();
$tenure = $today->diff($hire_date);
$years = $tenure->y;
$months = $tenure->m;
?>

<style>
* {
    box-sizing: border-box;
}

.page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.welcome-header {
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 12px rgba(0, 82, 163, 0.3);
}

.welcome-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 10px 0;
}

.welcome-subtitle {
    font-size: 16px;
    opacity: 0.9;
    margin: 0;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}

.info-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 12px rgba(0, 82, 163, 0.1);
    border: 1px solid #e9ecef;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.card-icon {
    font-size: 24px;
}

.card-title {
    font-size: 18px;
    font-weight: 700;
    color: #0052a3;
    margin: 0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.info-item {
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #0052a3;
}

.info-label {
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}

.info-value {
    font-size: 16px;
    font-weight: 500;
    color: #333;
}

.info-value.highlight {
    color: #0052a3;
    font-weight: 700;
    font-size: 18px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.stat-box {
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 13px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.reviews-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 12px rgba(0, 82, 163, 0.1);
    border: 1px solid #e9ecef;
    margin-bottom: 30px;
}

.review-card {
    background: #f8f9fa;
    border-left: 4px solid #6a1b9a;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.review-card:hover {
    box-shadow: 0 4px 12px rgba(106, 27, 154, 0.2);
    transform: translateX(5px);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.review-date {
    font-size: 14px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 5px;
}

.star {
    color: #ffc107;
    font-size: 18px;
}

.star.empty {
    color: #ddd;
}

.review-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 5px;
}

.badge-excellent {
    background: #d4edda;
    color: #155724;
}

.badge-good {
    background: #d1ecf1;
    color: #0c5460;
}

.badge-average {
    background: #fff3cd;
    color: #856404;
}

.badge-poor {
    background: #f8d7da;
    color: #721c24;
}

.reviewer-info {
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
}

.review-section {
    margin-bottom: 12px;
}

.section-title {
    font-weight: 700;
    color: #6a1b9a;
    font-size: 13px;
    text-transform: uppercase;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.section-content {
    padding-left: 20px;
    border-left: 2px solid #e9ecef;
    font-size: 14px;
    color: #555;
}

.no-reviews {
    text-align: center;
    padding: 40px;
    color: #666;
}


.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #0052a3 0%, #003d7a 100%);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #003d7a 0%, #002855 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 82, 163, 0.3);
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}

@media (max-width: 968px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-container">
    <?php if ($employee): ?>
        
        <div class="welcome-header">
            <h1 class="welcome-title">
                Welcome, <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>!
            </h1>
            <p class="welcome-subtitle">
                <?= htmlspecialchars($employee['title'] ?? 'Employee') ?> • 
                <?= htmlspecialchars($employee['dept_name'] ?? 'No Department') ?>
            </p>
        </div>

        <div class="dashboard-grid">
            <!-- personal information -->
            <div class="info-card">
                <div class="card-header">
                    <h2 class="card-title">Personal Information</h2>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Employee Number</div>
                        <div class="info-value highlight">#<?= htmlspecialchars($emp_no) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Birth Date</div>
                        <div class="info-value"><?= htmlspecialchars($employee['birth_date'] ?? 'N/A') ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Hire Date</div>
                        <div class="info-value"><?= htmlspecialchars($employee['hire_date'] ?? 'N/A') ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value highlight"><?= htmlspecialchars($employee['dept_name'] ?? 'N/A') ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Current Title</div>
                        <div class="info-value highlight"><?= htmlspecialchars($employee['title'] ?? 'N/A') ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Current Salary</div>
                        <div class="info-value highlight">$<?= number_format($employee['salary'] ?? 0, 2) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Your Manager</div>
                        <div class="info-value">
                            <?= htmlspecialchars(trim(($employee['manager_first'] ?? '') . ' ' . ($employee['manager_last'] ?? ''))) ?: 'N/A' ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Years of Service</div>
                        <div class="info-value highlight"><?= $years ?> years, <?= $months ?> months</div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="info-card">
                <div class="card-header">
                    <h2 class="card-title">Quick Stats</h2>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-value"><?= $years ?></div>
                        <div class="stat-label">Years</div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-value"><?= $months ?></div>
                        <div class="stat-label">Months</div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-value"><?= count($reviews) ?></div>
                        <div class="stat-label">Reviews</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Reviews Section -->
        <div class="reviews-section">
            <div class="card-header">
                <span class="card-icon">⭐</span>
                <h2 class="card-title">Your Performance Reviews</h2>
            </div>

            <?php if (empty($reviews)): ?>
                <div class="no-reviews">
                    <h3>No Reviews Yet</h3>
                    <p>You haven't received any performance reviews yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <?php
                        $rating = (int)$review['rating'];
                        $badge_class = 'badge-average';
                        $badge_text = 'Average';
                        
                        if ($rating >= 5) {
                            $badge_class = 'badge-excellent';
                            $badge_text = 'Excellent';
                        } elseif ($rating >= 4) {
                            $badge_class = 'badge-good';
                            $badge_text = 'Good';
                        } elseif ($rating <= 2) {
                            $badge_class = 'badge-poor';
                            $badge_text = 'Needs Improvement';
                        }
                        
                        // parse structured comments
                        $comments = $review['comments'] ?? '';
                        $summary = '';
                        $strengths = '';
                        $improvements = '';
                        $additional = '';
                        
                        if (preg_match('/PERFORMANCE SUMMARY:\s*(.+?)(?=\n\nKEY STRENGTHS:|$)/s', $comments, $match)) {
                            $summary = trim($match[1]);
                        }
                        if (preg_match('/KEY STRENGTHS:\s*(.+?)(?=\n\nAREAS FOR IMPROVEMENT:|$)/s', $comments, $match)) {
                            $strengths = trim($match[1]);
                        }
                        if (preg_match('/AREAS FOR IMPROVEMENT:\s*(.+?)(?=\n\nADDITIONAL COMMENTS:|$)/s', $comments, $match)) {
                            $improvements = trim($match[1]);
                        }
                        if (preg_match('/ADDITIONAL COMMENTS:\s*(.+?)$/s', $comments, $match)) {
                            $additional = trim($match[1]);
                        }
                        
                        // if not structured, use full comments
                        if (empty($summary) && !empty($comments)) {
                            $summary = $comments;
                        }
                    ?>
                    
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <div class="review-date">
                                     <?= date('F j, Y', strtotime($review['review_date'])) ?>
                                </div>
                                <div class="reviewer-info">
                                     Reviewed by <?= htmlspecialchars($review['reviewer_first'] . ' ' . $review['reviewer_last']) ?>
                                </div>
                            </div>
                            
                            <div style="text-align: right;">
                                <div class="rating-display">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?= $i <= $rating ? '' : 'empty' ?>">⭐</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="review-badge <?= $badge_class ?>"><?= $badge_text ?></span>
                            </div>
                        </div>

                        <?php if ($summary): ?>
                            <div class="review-section">
                                <div class="section-title"> Performance Summary</div>
                                <div class="section-content"><?= nl2br(htmlspecialchars($summary)) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($strengths): ?>
                            <div class="review-section">
                                <div class="section-title"> Key Strengths</div>
                                <div class="section-content"><?= nl2br(htmlspecialchars($strengths)) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($improvements): ?>
                            <div class="review-section">
                                <div class="section-title"> Areas for Improvement</div>
                                <div class="section-content"><?= nl2br(htmlspecialchars($improvements)) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($review['goals']): ?>
                            <div class="review-section">
                                <div class="section-title"> Goals & Objectives</div>
                                <div class="section-content"><?= nl2br(htmlspecialchars($review['goals'])) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($additional): ?>
                            <div class="review-section">
                                <div class="section-title"> Additional Comments</div>
                                <div class="section-content"><?= nl2br(htmlspecialchars($additional)) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($is_manager): ?>
            <div class="info-card">
                <div class="card-header">
                    <h2 class="card-title">Manager Actions</h2>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="window.location='change_department.php?emp_no=<?= $emp_no ?>'">
                         Change Department
                    </button>
                    
                    <button class="btn btn-primary" onclick="window.location='change_title.php?emp_no=<?= $emp_no ?>'">
                         Change Title
                    </button>
                    
                    <button class="btn btn-primary" onclick="window.location='update_salary.php?emp_no=<?= $emp_no ?>'">
                         Update Salary
                    </button>
                    
                    <button class="btn btn-danger" 
                            onclick="if(confirm('Are you sure you want to fire this employee?')) window.location='delete_employee.php?emp_no=<?= $emp_no ?>'">
                         Fire Employee
                    </button>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <p style="color:red; font-size:18px;">Employee not found.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>