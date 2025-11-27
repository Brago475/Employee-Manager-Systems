<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// Require login AND manager permissions
if (!isset($_SESSION['emp_no']) || !($_SESSION['is_manager'] ?? false)) {
    header("Location: ../index.php");
    exit;
}

$manager_emp_no = $_SESSION['emp_no'];

// get filter parameters
$filter_dept = $_GET['dept'] ?? '';
$filter_days = $_GET['days'] ?? '90';// by default show employees not reviewed in last 90 days
$filter_search = $_GET['search'] ?? '';
$show_reviewed = isset($_GET['show_reviewed']) ? (bool)$_GET['show_reviewed'] : false;

// fetch employees who need reviews 
$sql = "
    SELECT 
        e.emp_no, e.first_name, e.last_name, e.hire_date,
        d.dept_name, t.title,
        MAX(pr.review_date) as last_review_date,
        DATEDIFF(CURRENT_DATE, MAX(pr.review_date)) as days_since_review,
        DATEDIFF(CURRENT_DATE, e.hire_date) as days_employed,
        COUNT(pr.review_id) as total_reviews
    FROM employees e
    LEFT JOIN dept_emp de ON e.emp_no = de.emp_no 
        AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
    LEFT JOIN departments d ON de.dept_no = d.dept_no
    LEFT JOIN titles t ON e.emp_no = t.emp_no 
        AND (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
    LEFT JOIN performance_reviews pr ON e.emp_no = pr.emp_no
    WHERE e.emp_no != ?
";

$params = [$manager_emp_no];

if ($filter_dept) {
    $sql .= " AND d.dept_no = ?";
    $params[] = $filter_dept;
}

if ($filter_search) {
    $sql .= " AND (e.emp_no = ? OR CONCAT(e.first_name, ' ', e.last_name) LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)";
    $params[] = $filter_search;
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
}

$sql .= " GROUP BY e.emp_no, e.first_name, e.last_name, e.hire_date, d.dept_name, t.title";

// show reviewed or pending based on toggle
if ($show_reviewed) {
    // show employees who have been reviewed recently 
    $sql .= " HAVING last_review_date IS NOT NULL AND days_since_review < ?";
    $params[] = $filter_days;
} else {
    // by defaul shows employees who need reviews 
    $sql .= " HAVING (last_review_date IS NULL OR days_since_review >= ?)";
    $params[] = $filter_days;
}

$sql .= " ORDER BY total_reviews ASC, days_since_review DESC, e.hire_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get department list for filter
$departments = $pdo->query("SELECT dept_no, dept_name FROM departments ORDER BY dept_name")->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$total_pending = count($pending);
if ($show_reviewed) {
    $never_reviewed = 0; // Not applicable for reviewed view
    $overdue = 0; // Not applicable for reviewed view
    $recently_reviewed = count(array_filter($pending, fn($p) => $p['days_since_review'] < 30));
} else {
    $never_reviewed = count(array_filter($pending, fn($p) => $p['last_review_date'] === null));
    $overdue = count(array_filter($pending, fn($p) => $p['days_since_review'] >= 180));
    $recently_reviewed = 0;
}
?>

<style>
* {
    box-sizing: border-box;
}

.twitter-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0;
}

.page-header {
    background: white;
    border-bottom: 1px solid #e1e8ed;
    padding: 15px 20px;
    position: sticky;
    top: 75px;
    z-index: 100;
    margin: 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.back-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.back-btn:hover {
    background-color: rgba(29, 155, 240, 0.1);
}

.back-btn svg {
    width: 20px;
    height: 20px;
    fill: #0f1419;
}

.page-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f1419;
    margin: 0;
}

.subtitle {
    font-size: 13px;
    color: #536471;
}

.search-bar {
    background: white;
    border-bottom: 1px solid #e1e8ed;
    padding: 12px 20px;
}

.search-input-field {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #cfd9de;
    border-radius: 20px;
    font-size: 15px;
    transition: all 0.2s;
}

.search-input-field:focus {
    outline: none;
    border-color: #1d9bf0;
    box-shadow: 0 0 0 3px rgba(29, 155, 240, 0.1);
}

.search-btn {
    background: #1d9bf0;
    color: white;
    border: none;
    border-radius: 20px;
    padding: 10px 24px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.search-btn:hover {
    background: #1a8cd8;
}

.clear-search-btn {
    background: white;
    color: #0f1419;
    border: 1px solid #cfd9de;
    border-radius: 20px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.clear-search-btn:hover {
    background: #f7f9f9;
}

.warning-badge {
    background: #fff3e0;
    color: #e65100;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 13px;
    font-weight: 700;
}

.stats-bar {
    background: white;
    border-bottom: 1px solid #e1e8ed;
    padding: 12px 20px;
    display: flex;
    gap: 20px;
    overflow-x: auto;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #536471;
    white-space: nowrap;
}

.stat-value {
    font-weight: 700;
    color: #0f1419;
}

.stat-value.warning {
    color: #e65100;
}

.stat-value.danger {
    color: #c62828;
}

.filter-bar {
    background: white;
    border-bottom: 1px solid #e1e8ed;
    padding: 15px 20px;
}

.filter-toggle {
    background: none;
    border: none;
    color: #1d9bf0;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    border-radius: 9999px;
    transition: all 0.2s;
}

.filter-toggle:hover {
    background: rgba(29, 155, 240, 0.1);
}

.filter-content {
    display: none;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e1e8ed;
}

.filter-content.show {
    display: block;
}

.filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 15px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-size: 13px;
    font-weight: 600;
    color: #536471;
}

.filter-group select {
    padding: 8px 12px;
    border: 1px solid #cfd9de;
    border-radius: 4px;
    font-size: 14px;
}

.filter-group select:focus {
    outline: none;
    border-color: #1d9bf0;
}

.filter-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.filter-btn {
    padding: 8px 16px;
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-apply {
    background: #1d9bf0;
    color: white;
    border: none;
}

.btn-apply:hover {
    background: #1a8cd8;
}

.btn-clear {
    background: white;
    color: #0f1419;
    border: 1px solid #cfd9de;
}

.btn-clear:hover {
    background: #f7f9f9;
}

.pending-feed {
    background: white;
}

.pending-card {
    border-bottom: 1px solid #e1e8ed;
    padding: 16px 20px;
    transition: all 0.3s;
    position: relative;
}

.pending-card:hover {
    background: #f7f9f9;
}

.pending-card.has-reviews {
    border-left: 4px solid #28a745;
    background: linear-gradient(90deg, #f0f9f4 0%, #ffffff 100%);
}

.pending-card.never-reviewed {
    border-left: 4px solid #c62828;
    background: linear-gradient(90deg, #fff5f5 0%, #ffffff 100%);
}

.review-count-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 2px 6px rgba(40, 167, 69, 0.3);
    display: flex;
    align-items: center;
    gap: 6px;
}

.review-count-badge.zero {
    background: linear-gradient(135deg, #c62828 0%, #d32f2f 100%);
    box-shadow: 0 2px 6px rgba(198, 40, 40, 0.3);
}

.pending-header {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
}

.pending-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
    flex-shrink: 0;
}

.pending-info {
    flex: 1;
    min-width: 0;
}

.pending-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.employee-name {
    font-weight: 700;
    color: #0f1419;
    font-size: 15px;
}

.employee-title {
    color: #536471;
    font-size: 14px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 13px;
    font-weight: 700;
}

.status-never {
    background: #ffebee;
    color: #c62828;
}

.status-overdue {
    background: #fff3e0;
    color: #e65100;
}

.status-due {
    background: #fff9c4;
    color: #f57c00;
}

.status-current {
    background: #e8f5e9;
    color: #2e7d32;
}

.pending-details {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-top: 8px;
    font-size: 13px;
    color: #536471;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pending-actions {
    margin-top: 12px;
    display: flex;
    gap: 10px;
}

.action-btn {
    padding: 8px 16px;
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-review {
    background: #1d9bf0;
    color: white;
    border: none;
}

.btn-review:hover {
    background: #1a8cd8;
}

.btn-view {
    background: white;
    color: #0f1419;
    border: 1px solid #cfd9de;
}

.btn-view:hover {
    background: #f7f9f9;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #536471;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.empty-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f1419;
    margin-bottom: 10px;
}

.empty-text {
    font-size: 15px;
    margin-bottom: 20px;
}

.priority-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 6px;
}

.priority-high {
    background: #c62828;
}

.priority-medium {
    background: #e65100;
}

.priority-low {
    background: #f57c00;
}

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .pending-actions {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
    }
}
</style>

<div class="twitter-container">
    <div class="page-header">
        <div class="header-content">
            <div class="header-left">
                <button class="back-btn" onclick="window.location='manager_dashboard.php'">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                    </svg>
                </button>
                <div>
                    <h1 class="page-title"><?= $show_reviewed ? 'Reviewed Employees' : 'Pending Reviews' ?></h1>
                    <div class="subtitle">
                        <?= number_format($total_pending) ?> 
                        <?= $show_reviewed ? 'employees reviewed' : 'employees need attention' ?>
                    </div>
                </div>
            </div>
            <?php if ($total_pending > 0 && !$show_reviewed): ?>
                <span class="warning-badge"><?= $total_pending ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- search bar -->
    <div class="search-bar">
        <form method="get" style="display: flex; gap: 10px; align-items: center;">
            <div style="flex: 1; position: relative;">
                <input type="text" 
                       name="search" 
                       placeholder=" Search by name or employee number..."
                       value="<?= htmlspecialchars($filter_search) ?>"
                       class="search-input-field">
            </div>
            
            <!-- Preserve other filters -->
            <?php if ($filter_dept): ?>
                <input type="hidden" name="dept" value="<?= htmlspecialchars($filter_dept) ?>">
            <?php endif; ?>
            <?php if ($filter_days != '90'): ?>
                <input type="hidden" name="days" value="<?= htmlspecialchars($filter_days) ?>">
            <?php endif; ?>
            <?php if ($show_reviewed): ?>
                <input type="hidden" name="show_reviewed" value="1">
            <?php endif; ?>
            
            <button type="submit" class="search-btn">Search</button>
            
            <?php if ($filter_search): ?>
                <button type="button" class="clear-search-btn" 
                        onclick="window.location='pending_reviews.php<?= $show_reviewed ? '?show_reviewed=1' : '' ?>'">
                    Clear
                </button>
            <?php endif; ?>
        </form>
    </div>

    <div class="stats-bar">
        <?php if ($show_reviewed): ?>
            <div class="stat-item">
                <span>Total Reviewed:</span>
                <span class="stat-value" style="color: #28a745;"><?= number_format($total_pending) ?></span>
            </div>
            <div class="stat-item">
                <span>Recently (30 days):</span>
                <span class="stat-value"><?= number_format($recently_reviewed) ?></span>
            </div>
            <div class="stat-item">
                <button class="filter-btn btn-apply" onclick="window.location='pending_reviews.php'" 
                        style="margin: 0; padding: 6px 16px; font-size: 13px;">
                    View Pending Reviews
                </button>
            </div>
        <?php else: ?>
            <div class="stat-item">
                <span>Total Pending:</span>
                <span class="stat-value warning"><?= number_format($total_pending) ?></span>
            </div>
            <div class="stat-item">
                <span>Never Reviewed:</span>
                <span class="stat-value danger"><?= number_format($never_reviewed) ?></span>
            </div>
            <div class="stat-item">
                <span>Overdue (6+ months):</span>
                <span class="stat-value danger"><?= number_format($overdue) ?></span>
            </div>
            <div class="stat-item">
                <button class="filter-btn btn-apply" onclick="window.location='pending_reviews.php?show_reviewed=1'" 
                        style="margin: 0; padding: 6px 16px; font-size: 13px;">
                    View Reviewed
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="filter-bar">
        <button class="filter-toggle" onclick="toggleFilters()">
            <span>Filters</span>
        </button>
        
        <form method="get" class="filter-content" id="filterContent">
            <?php if ($show_reviewed): ?>
                <input type="hidden" name="show_reviewed" value="1">
            <?php endif; ?>
            <?php if ($filter_search): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($filter_search) ?>">
            <?php endif; ?>
            
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Department</label>
                    <select name="dept">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['dept_no'] ?>" 
                                    <?= $filter_dept == $dept['dept_no'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['dept_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Review Period</label>
                    <select name="days">
                        <option value="30" <?= $filter_days == '30' ? 'selected' : '' ?>>Last 30 days</option>
                        <option value="60" <?= $filter_days == '60' ? 'selected' : '' ?>>Last 60 days</option>
                        <option value="90" <?= $filter_days == '90' ? 'selected' : '' ?>>Last 90 days</option>
                        <option value="180" <?= $filter_days == '180' ? 'selected' : '' ?>>Last 6 months</option>
                        <option value="365" <?= $filter_days == '365' ? 'selected' : '' ?>>Last year</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="button" class="filter-btn btn-clear" 
                        onclick="window.location='pending_reviews.php'">Clear</button>
                <button type="submit" class="filter-btn btn-apply">Apply Filters</button>
            </div>
        </form>
    </div>

    <div class="pending-feed">
        <?php if (empty($pending)): ?>
            <div class="empty-state">
                <?php if ($show_reviewed): ?>
                    <div class="empty-title">No reviewed employees found</div>
                    <div class="empty-text">
                        <?php if ($filter_search || $filter_dept): ?>
                            Try adjusting your filters or search criteria.
                        <?php else: ?>
                            No employees have been reviewed within this time period.
                        <?php endif; ?>
                    </div>
                    <button class="btn-review" onclick="window.location='pending_reviews.php'">
                        View Pending Reviews
                    </button>
                <?php else: ?>
                    <div class="empty-title">All caught up!</div>
                    <div class="empty-text">
                        <?php if ($filter_search || $filter_dept): ?>
                            No employees match your search criteria.
                        <?php else: ?>
                            No employees need reviews at this time.
                        <?php endif; ?>
                    </div>
                    <button class="btn-review" onclick="window.location='reviews.php'">
                        View All Reviews
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($pending as $emp): ?>
                <?php 
                    $initials = strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1));
                    $total_reviews = (int)$emp['total_reviews'];
                    
                    // add card class based on review status
                    $cardClass = $total_reviews > 0 ? 'has-reviews' : 'never-reviewed';
                    
                    // Determine status and priority
                    if ($show_reviewed) {
                        // For reviewed view, show positive status
                        if ($emp['days_since_review'] < 30) {
                            $statusBadge = 'status-current';
                            $statusText = '✅ Recently Reviewed';
                            $priority = 'priority-low';
                        } elseif ($emp['days_since_review'] < 90) {
                            $statusBadge = 'status-current';
                            $statusText = '✅ Current';
                            $priority = 'priority-low';
                        } else {
                            $statusBadge = 'status-due';
                            $statusText = ' Approaching Due';
                            $priority = 'priority-medium';
                        }
                    } else {
                        // For pending view, show what needs attention
                        if ($emp['last_review_date'] === null) {
                            $statusBadge = 'status-never';
                            $statusText = '⚠️ Never Reviewed';
                            $priority = 'priority-high';
                        } elseif ($emp['days_since_review'] >= 180) {
                            $statusBadge = 'status-overdue';
                            $statusText = '🔴 Overdue';
                            $priority = 'priority-high';
                        } else {
                            $statusBadge = 'status-due';
                            $statusText = '🟡 Due Soon';
                            $priority = 'priority-medium';
                        }
                    }
                    
                    // Format last review info
                    if ($emp['last_review_date']) {
                        $lastReviewText = date('M j, Y', strtotime($emp['last_review_date']));
                        $daysSinceText = $emp['days_since_review'] . ' days ago';
                    } else {
                        $lastReviewText = 'Never';
                        $daysSinceText = 'No reviews yet';
                    }
                    
                    $employedYears = round($emp['days_employed'] / 365.25, 1);
                ?>
                <div class="pending-card <?= $cardClass ?>">
                    <!-- Review Count Badge -->
                    <div class="review-count-badge <?= $total_reviews == 0 ? 'zero' : '' ?>">
                        <span><?= $total_reviews ?> Review<?= $total_reviews != 1 ? 's' : '' ?></span>
                    </div>
                    <div class="pending-header">
                        <div class="pending-avatar"><?= $initials ?></div>
                        <div class="pending-info">
                            <div class="pending-meta">
                                <span class="employee-name">
                                    <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                </span>
                                <span class="employee-title">
                                    <?= htmlspecialchars($emp['title'] ?? 'No Title') ?>
                                </span>
                            </div>
                            <div class="pending-meta" style="margin-top: 4px;">
                                <span class="priority-indicator <?= $priority ?>"></span>
                                <span class="status-badge <?= $statusBadge ?>">
                                    <?= $statusText ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pending-details">
                        <div class="detail-item">
                            <span>Last Review: <strong><?= $lastReviewText ?></strong></span>
                        </div>
                        <?php if ($emp['last_review_date']): ?>
                            <div class="detail-item">
                                <span><?= $daysSinceText ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pending-details">
                        <?php if ($emp['dept_name']): ?>
                            <div class="detail-item">
                                <span><?= htmlspecialchars($emp['dept_name']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <span><?= $employedYears ?> years with company</span>
                        </div>
                    </div>
                    
                    <div class="pending-actions">
                        <button class="action-btn btn-review" 
                                onclick="window.location='create_review.php?emp_no=<?= $emp['emp_no'] ?>'">
                            Create Review
                        </button>
                        <?php if ($emp['last_review_date']): ?>
                            <button class="action-btn btn-view" 
                                    onclick="window.location='reviews.php?employee=<?= $emp['emp_no'] ?>'">
                                View History
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFilters() {
    const content = document.getElementById('filterContent');
    content.classList.toggle('show');
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>