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
$message = '';
$messageType = '';

// check if employee number is passed in URL 
$preselected_emp_no = isset($_GET['emp_no']) ? (int)$_GET['emp_no'] : 0;

// fetch all employees for selection
$stmt = $pdo->prepare("
    SELECT e.emp_no, e.first_name, e.last_name, 
           d.dept_name, t.title
    FROM employees e
    LEFT JOIN dept_emp de ON e.emp_no = de.emp_no 
        AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
    LEFT JOIN departments d ON de.dept_no = d.dept_no
    LEFT JOIN titles t ON e.emp_no = t.emp_no 
        AND (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
    WHERE e.emp_no != ?
    ORDER BY e.last_name, e.first_name
");
$stmt->execute([$manager_emp_no]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST REQUEST RECEIVED");
    error_log("POST Data: " . print_r($_POST, true));
    
    $emp_no = (int)($_POST['emp_no'] ?? 0);
    $review_date = trim($_POST['review_date'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $performance_summary = trim($_POST['performance_summary'] ?? '');
    $strengths = trim($_POST['strengths'] ?? '');
    $areas_for_improvement = trim($_POST['areas_for_improvement'] ?? '');
    $goals = trim($_POST['goals'] ?? '');
    $comments = trim($_POST['comments'] ?? '');
    
    error_log("Parsed - Emp: $emp_no, Rating: $rating, Date: $review_date, Summary length: " . strlen($performance_summary));
    
    if ($emp_no > 0 && $review_date && $rating > 0 && $performance_summary) {
        try {
            // DEBUG: log the data being inserted
            error_log("Creating review - Emp: $emp_no, Reviewer: $manager_emp_no, Date: $review_date, Rating: $rating");
            
            // combine all text fields into comments and goals
            $full_comments = "PERFORMANCE SUMMARY:\n" . $performance_summary;
            
            if ($strengths) {
                $full_comments .= "\n\nKEY STRENGTHS:\n" . $strengths;
            }
            
            if ($areas_for_improvement) {
                $full_comments .= "\n\nAREAS FOR IMPROVEMENT:\n" . $areas_for_improvement;
            }
            
            if ($comments) {
                $full_comments .= "\n\nADDITIONAL COMMENTS:\n" . $comments;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO performance_reviews 
                (emp_no, reviewer_emp_no, review_date, rating, goals, comments)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $emp_no, 
                $manager_emp_no, 
                $review_date, 
                $rating, 
                $goals,
                $full_comments
            ]);
            
            // gets the review ID
            $review_id = $pdo->lastInsertId();
            
            error_log("Review created successfully! ID: $review_id");
            
            // show success message then redirect
            $message = "Review created successfully! Redirecting...";
            $messageType = 'success';
            
            // use meta refresh instead of immediate JavaScript redirect
            echo '<meta http-equiv="refresh" content="2;url=reviews.php?success=1">';
            
            $_POST = [];
        } catch (Exception $e) {
            $message = "Error creating review: " . htmlspecialchars($e->getMessage());
            $messageType = 'error';
            
            // log detailed error
            error_log("Review insert error: " . $e->getMessage());
            error_log("SQL State: " . print_r($stmt->errorInfo(), true));
        }
    } else {
        $message = "Please fill in all required fields";
        $messageType = 'error';
    }
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
    margin: 0 0 0 0;
}

.header-content {
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

.alert {
    padding: 16px 20px;
    border-radius: 0;
    margin: 0;
    border-bottom: 1px solid #e1e8ed;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 15px;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
}

.alert-error {
    background: #ffebee;
    color: #c62828;
}

.compose-box {
    background: white;
    border-bottom: 1px solid #e1e8ed;
    padding: 20px;
}

.employee-selector {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e1e8ed;
}

.employee-selector label {
    display: block;
    font-size: 15px;
    font-weight: 600;
    color: #0f1419;
    margin-bottom: 10px;
}

.employee-selector select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #cfd9de;
    border-radius: 4px;
    font-size: 15px;
    color: #0f1419;
    background: white;
    transition: all 0.2s;
    max-height: 300px;
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.employee-selector select:focus {
    outline: none;
    border-color: #1d9bf0;
    box-shadow: 0 0 0 3px rgba(29, 155, 240, 0.1);
}

.search-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #cfd9de;
    border-radius: 4px;
    font-size: 15px;
    color: #0f1419;
    background: white;
    transition: all 0.2s;
    margin-bottom: 10px;
}

.search-input:focus {
    outline: none;
    border-color: #1d9bf0;
    box-shadow: 0 0 0 3px rgba(29, 155, 240, 0.1);
}

.search-input::placeholder {
    color: #8b98a5;
}

.search-stats {
    font-size: 13px;
    color: #536471;
    margin-top: 8px;
    padding: 8px;
    background: #f7f9f9;
    border-radius: 4px;
}

.search-stats span {
    font-weight: 600;
    color: #1d9bf0;
}

.selected-employee {
    display: none;
    margin-top: 12px;
    padding: 12px;
    background: #f7f9f9;
    border-radius: 8px;
}

.selected-employee.show {
    display: block;
}

.employee-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.employee-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #1d9bf0 0%, #0d8bd9 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
}

.employee-details {
    flex: 1;
}

.employee-name {
    font-weight: 700;
    font-size: 15px;
    color: #0f1419;
}

.employee-meta {
    font-size: 13px;
    color: #536471;
}

.form-section {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    position: relative;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #536471;
    margin-bottom: 8px;
}

.required {
    color: #f91880;
}

.form-group input[type="date"],
.form-group input[type="number"] {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #cfd9de;
    border-radius: 4px;
    font-size: 15px;
    transition: all 0.2s;
}

.form-group input:focus {
    outline: none;
    border-color: #1d9bf0;
    box-shadow: 0 0 0 3px rgba(29, 155, 240, 0.1);
}

.rating-selector {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.rating-option {
    flex: 1;
    padding: 12px;
    border: 2px solid #cfd9de;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}

.rating-option:hover {
    border-color: #1d9bf0;
    background: #f7f9f9;
}

.rating-option input {
    display: none;
}

.rating-option.selected {
    border-color: #1d9bf0;
    background: #e8f5fd;
}

.rating-number {
    font-size: 20px;
    font-weight: 700;
    color: #0f1419;
    display: block;
    margin-bottom: 4px;
}

.rating-label {
    font-size: 12px;
    color: #536471;
}

.textarea-wrapper {
    position: relative;
}

.textarea-wrapper textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #cfd9de;
    border-radius: 4px;
    font-size: 15px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    resize: vertical;
    min-height: 100px;
    transition: all 0.2s;
}

.textarea-wrapper textarea:focus {
    outline: none;
    border-color: #1d9bf0;
    box-shadow: 0 0 0 3px rgba(29, 155, 240, 0.1);
}

.char-count {
    position: absolute;
    bottom: 12px;
    right: 12px;
    font-size: 13px;
    color: #536471;
}

.action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background: white;
    border-top: 1px solid #e1e8ed;
    position: sticky;
    bottom: 0;
}

.submit-btn {
    background: #1d9bf0;
    color: white;
    border: none;
    border-radius: 9999px;
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.submit-btn:hover {
    background: #1a8cd8;
}

.submit-btn:disabled {
    background: #cfd9de;
    cursor: not-allowed;
}

.help-text {
    font-size: 13px;
    color: #536471;
    margin-top: 6px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .rating-selector {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<div class="twitter-container">
    <div class="page-header">
        <div class="header-content">
            <button class="back-btn" onclick="window.location='manager_dashboard.php'">
                <svg viewBox="0 0 24 24">
                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
            </button>
            <h1 class="page-title">Create Performance Review</h1>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <span><?= $messageType === 'success' ? '✓' : '✕' ?></span>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" id="reviewForm" action="create_review.php">
        <div class="compose-box">
            <div class="employee-selector">
                <label for="emp_no">Select Employee <span class="required">*</span></label>
                
                <input type="text" 
                       id="employeeSearch" 
                       placeholder="Search by name or employee number..."
                       class="search-input"
                       autocomplete="off"
                       onkeyup="filterEmployees()"
                       onfocus="showDropdown()"
                       value="<?php 
                           if ($preselected_emp_no) {
                               $preselected = array_filter($employees, fn($e) => $e['emp_no'] == $preselected_emp_no);
                               if (!empty($preselected)) {
                                   $emp = reset($preselected);
                                   echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']);
                               }
                           }
                       ?>">
                
                <select name="emp_no" id="emp_no" required onchange="updateSelectedEmployee()" size="8" style="display: none;">
                    <option value="">Choose an employee to review...</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['emp_no'] ?>"
                                <?= ($preselected_emp_no == $emp['emp_no']) ? 'selected' : '' ?>
                                data-name="<?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>"
                                data-dept="<?= htmlspecialchars($emp['dept_name'] ?? 'N/A') ?>"
                                data-title="<?= htmlspecialchars($emp['title'] ?? 'N/A') ?>"
                                data-search="<?= strtolower($emp['emp_no'] . ' ' . $emp['first_name'] . ' ' . $emp['last_name']) ?>">
                            #<?= $emp['emp_no'] ?> - <?= htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name']) ?>
                            <?php if ($emp['dept_name']): ?>
                                • <?= htmlspecialchars($emp['dept_name']) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="search-stats" id="searchStats" style="display: none;">
                    <span id="matchCount">0</span> employees found
                </div>
                
                <div class="selected-employee" id="selectedEmployee">
                    <div class="employee-info">
                        <div class="employee-avatar" id="empAvatar"></div>
                        <div class="employee-details">
                            <div class="employee-name" id="empName"></div>
                            <div class="employee-meta" id="empMeta"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="review_date">Review Date <span class="required">*</span></label>
                    <input type="date" name="review_date" id="review_date" 
                           value="<?= date('Y-m-d') ?>" 
                           max="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label>Performance Rating <span class="required">*</span></label>
                    <div class="rating-selector">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="rating-option" onclick="selectRating(<?= $i ?>)">
                                <input type="radio" name="rating" value="<?= $i ?>" required>
                                <span class="rating-number"><?= $i ?></span>
                                <span class="rating-label">
                                    <?php 
                                        $labels = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                                        echo $labels[$i-1];
                                    ?>
                                </span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-group">
                    <label for="performance_summary">Performance Summary <span class="required">*</span></label>
                    <div class="textarea-wrapper">
                        <textarea name="performance_summary" id="performance_summary" 
                                  required maxlength="500" oninput="updateCharCount(this, 500)"></textarea>
                        <span class="char-count" id="summary-count">0 / 500</span>
                    </div>
                    <div class="help-text">Brief overview of employee's overall performance</div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-group">
                    <label for="strengths">Key Strengths</label>
                    <div class="textarea-wrapper">
                        <textarea name="strengths" id="strengths" 
                                  maxlength="300" oninput="updateCharCount(this, 300)"></textarea>
                        <span class="char-count" id="strengths-count">0 / 300</span>
                    </div>
                    <div class="help-text">What does the employee excel at?</div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-group">
                    <label for="areas_for_improvement">Areas for Improvement</label>
                    <div class="textarea-wrapper">
                        <textarea name="areas_for_improvement" id="areas_for_improvement" 
                                  maxlength="300" oninput="updateCharCount(this, 300)"></textarea>
                        <span class="char-count" id="improvement-count">0 / 300</span>
                    </div>
                    <div class="help-text">Constructive feedback for development</div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-group">
                    <label for="goals">Goals & Objectives</label>
                    <div class="textarea-wrapper">
                        <textarea name="goals" id="goals" 
                                  maxlength="300" oninput="updateCharCount(this, 300)"></textarea>
                        <span class="char-count" id="goals-count">0 / 300</span>
                    </div>
                    <div class="help-text">Future targets and development plans</div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-group">
                    <label for="comments">Additional Comments</label>
                    <div class="textarea-wrapper">
                        <textarea name="comments" id="comments" 
                                  maxlength="500" oninput="updateCharCount(this, 500)"></textarea>
                        <span class="char-count" id="comments-count">0 / 500</span>
                    </div>
                    <div class="help-text">Any other relevant information</div>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <span style="font-size: 13px; color: #536471;">
                <span class="required">*</span> Required fields
            </span>
            <button type="submit" class="submit-btn" id="submitBtn">Submit Review</button>
        </div>
    </form>
</div>

<script>
// auto show selected employee if preselected
window.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('emp_no');
    if (select.value) {
        updateSelectedEmployee();
    }
});

function updateSelectedEmployee() {
    const select = document.getElementById('emp_no');
    const option = select.options[select.selectedIndex];
    const container = document.getElementById('selectedEmployee');
    const searchInput = document.getElementById('employeeSearch');
    
    if (option.value) {
        const name = option.dataset.name;
        const dept = option.dataset.dept;
        const title = option.dataset.title;
        const initials = name.split(' ').map(n => n[0]).join('');
        
        document.getElementById('empAvatar').textContent = initials;
        document.getElementById('empName').textContent = name;
        document.getElementById('empMeta').textContent = `${title} • ${dept}`;
        container.classList.add('show');
        
        // hide dropdown and stats after selection
        select.style.display = 'none';
        document.getElementById('searchStats').style.display = 'none';
        
        // update search input with selected name
        searchInput.value = name;
    } else {
        container.classList.remove('show');
    }
}

function showDropdown() {
    const searchInput = document.getElementById('employeeSearch');
    const select = document.getElementById('emp_no');
    
    if (searchInput.value.trim()) {
        select.style.display = 'block';
    }
}

function filterEmployees() {
    const searchInput = document.getElementById('employeeSearch');
    const searchTerm = searchInput.value.toLowerCase().trim();
    const select = document.getElementById('emp_no');
    const options = select.options;
    const stats = document.getElementById('searchStats');
    let matchCount = 0;
    
    // show dropdown only when there's text
    if (searchTerm) {
        select.style.display = 'block';
    } else {
        select.style.display = 'none';
        stats.style.display = 'none';
        return;
    }
    
    for (let i = 1; i < options.length; i++) { 
        const option = options[i];
        const searchData = option.dataset.search || '';
        
        if (searchData.includes(searchTerm)) {
            option.style.display = '';
            matchCount++;
        } else {
            option.style.display = 'none';
        }
    }
    
    // show/hide stats
    if (searchTerm) {
        stats.style.display = 'block';
        document.getElementById('matchCount').textContent = matchCount;
    } else {
        stats.style.display = 'none';
    }
    
    // auto select if only one match
    if (matchCount === 1 && searchTerm) {
        for (let i = 1; i < options.length; i++) {
            if (options[i].style.display !== 'none') {
                select.value = options[i].value;
                updateSelectedEmployee();
                break;
            }
        }
    }
}

document.addEventListener('click', function(e) {
    const select = document.getElementById('emp_no');
    const searchInput = document.getElementById('employeeSearch');
    const selector = document.querySelector('.employee-selector');
    
    if (selector && !selector.contains(e.target)) {
        if (!select.value) {
            select.style.display = 'none';
        }
    }
});

function selectRating(rating) {
    document.querySelectorAll('.rating-option').forEach(option => {
        option.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
}

function updateCharCount(textarea, max) {
    const count = textarea.value.length;
    const counterId = textarea.id + '-count';
    document.getElementById(counterId).textContent = `${count} / ${max}`;
}

document.getElementById('reviewForm').addEventListener('submit', function(e) {
    const empNo = document.getElementById('emp_no').value;
    const rating = document.querySelector('input[name="rating"]:checked');
    const summary = document.getElementById('performance_summary').value;
    
    if (!empNo) {
        e.preventDefault();
        alert('Please select an employee');
        document.getElementById('employeeSearch').focus();
        return false;
    }
    
    if (!rating) {
        e.preventDefault();
        alert('Please select a performance rating');
        return false;
    }
    
    if (!summary.trim()) {
        e.preventDefault();
        alert('Please provide a performance summary');
        document.getElementById('performance_summary').focus();
        return false;
    }
    
    return true;
});

</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>