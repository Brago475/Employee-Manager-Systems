<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// require login and manager permissions
if (!isset($_SESSION['emp_no']) || !($_SESSION['is_manager'] ?? false)) {
    header("Location: ../index.php");
    exit;
}

$manager_emp_no = $_SESSION['emp_no'];

// check for success message
$success = $_GET['success'] ?? '';

// get filter parameters
$filter_employee = $_GET['employee'] ?? '';
$filter_rating = $_GET['rating'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// build query with filters
$sql = "
    SELECT pr.*, 
           e.first_name, e.last_name,
           r.first_name as reviewer_first, r.last_name as reviewer_last,
           d.dept_name, t.title
    FROM performance_reviews pr
    JOIN employees e ON pr.emp_no = e.emp_no
    JOIN employees r ON pr.reviewer_emp_no = r.emp_no
    LEFT JOIN dept_emp de ON e.emp_no = de.emp_no 
        AND (de.to_date IS NULL OR de.to_date > CURRENT_DATE)
    LEFT JOIN departments d ON de.dept_no = d.dept_no
    LEFT JOIN titles t ON e.emp_no = t.emp_no 
        AND (t.to_date IS NULL OR t.to_date > CURRENT_DATE)
    WHERE 1=1
";

$params = [];

if ($filter_employee) {
    $sql .= " AND (e.emp_no = ? OR CONCAT(e.first_name, ' ', e.last_name) LIKE ?)";
    $params[] = $filter_employee;
    $params[] = "%$filter_employee%";
}

if ($filter_rating) {
    $sql .= " AND pr.rating = ?";
    $params[] = $filter_rating;
}

if ($filter_date_from) {
    $sql .= " AND pr.review_date >= ?";
    $params[] = $filter_date_from;
}

if ($filter_date_to) {
    $sql .= " AND pr.review_date <= ?";
    $params[] = $filter_date_to;
}

$sql .= " ORDER BY pr.review_date DESC, pr.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$total_reviews = count($reviews);
$avg_rating = $total_reviews > 0 ? round(array_sum(array_column($reviews, 'rating')) / $total_reviews, 1) : 0;
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

.create-btn {
    background: #1d9bf0;
    color: white;
    border: none;
    border-radius: 9999px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.create-btn:hover {
    background: #1a8cd8;
}

.stats-bar {
    background: white;
    border-bottom: 1px solid #e1e8ed;
    padding: 12px 20px;
    display: flex;
    gap: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #536471;
}

.stat-value {
    font-weight: 700;
    color: #0f1419;
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

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 1px solid #cfd9de;
    border-radius: 4px;
    font-size: 14px;
}

.filter-group input:focus,
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

.reviews-feed {
    background: white;
}

.review-card {
    border-bottom: 1px solid #e1e8ed;
    padding: 20px;
    transition: all 0.3s;
    cursor: pointer;
    position: relative;
}

.review-card:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transform: translateX(4px);
}

.review-header {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.review-avatar {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.review-info {
    flex: 1;
    min-width: 0;
}

.review-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.employee-name {
    font-weight: 700;
    color: #0f1419;
    font-size: 16px;
    letter-spacing: -0.2px;
}

.employee-title {
    color: #536471;
    font-size: 14px;
    font-weight: 500;
}

.rating-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.3px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.rating-5 { 
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: 1px solid #c3e6cb;
}

.rating-4 { 
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.rating-3 { 
    background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
    color: #856404;
    border: 1px solid #ffeeba;
}

.rating-2 { 
    background: linear-gradient(135deg, #ffe5d0 0%, #ffd7ba 100%);
    color: #8a4b1f;
    border: 1px solid #ffd7ba;
}

.rating-1 { 
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.review-date {
    color: #536471;
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
}

.review-date::before {
    font-size: 12px;
}

.review-content {
    margin: 15px 0 15px 71px;
}

.review-excerpt {
    color: #0f1419;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-weight: 400;
}

.review-section {
    margin-bottom: 16px;
}

.review-section:last-child {
    margin-bottom: 0;
}

.review-section-title {
    font-size: 12px;
    font-weight: 700;
    color: #667eea;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.review-section-title::before {
    content: "";
    width: 3px;
    height: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 2px;
}

.review-section-content {
    color: #0f1419;
    font-size: 14px;
    line-height: 1.6;
    padding-left: 12px;
    border-left: 2px solid #f0f0f0;
}

.review-footer {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-left: 71px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
    font-size: 13px;
    color: #536471;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f7f9f9;
    padding: 6px 12px;
    border-radius: 12px;
    font-weight: 500;
}

.reviewer-info strong {
    color: #0f1419;
    font-weight: 600;
}

.dept-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #e3f2fd;
    color: #1565c0;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #536471;
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

.review-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.review-modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 16px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e1e8ed;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f1419;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #536471;
    padding: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    background: rgba(0, 0, 0, 0.05);
}

.modal-body {
    padding: 20px;
}

.detail-section {
    margin-bottom: 24px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.detail-section.rating-section {
    background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 100%);
    border-left-color: #667eea;
}

.detail-label {
    font-size: 12px;
    font-weight: 700;
    color: #667eea;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-value {
    font-size: 15px;
    color: #0f1419;
    line-height: 1.7;
    white-space: pre-line;
}

.modal-review-section {
    margin-bottom: 20px;
    padding: 16px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #667eea;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.modal-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #667eea;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-section-content {
    color: #0f1419;
    font-size: 15px;
    line-height: 1.7;
}

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
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
                    <h1 class="page-title">Performance Reviews</h1>
                    <div class="subtitle"><?= number_format($total_reviews) ?> reviews</div>
                </div>
            </div>
            <button class="create-btn" onclick="window.location='create_review.php'">
                + New
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert" style="padding: 16px 20px; background: #e8f5e9; color: #2e7d32; border-bottom: 1px solid #e1e8ed; display: flex; align-items: center; gap: 12px; font-size: 15px;">
            <span>✓</span>
            <span>Performance review created successfully!</span>
        </div>
    <?php endif; ?>

    <div class="stats-bar">
        <div class="stat-item">
            <span>Total Reviews:</span>
            <span class="stat-value"><?= number_format($total_reviews) ?></span>
        </div>
        <div class="stat-item">
            <span>Average Rating:</span>
            <span class="stat-value"><?= $avg_rating ?>/5</span>
        </div>
    </div>

    <div class="filter-bar">
        <button class="filter-toggle" onclick="toggleFilters()">
            <span>Filters</span>
        </button>
        
        <form method="get" class="filter-content" id="filterContent">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Employee</label>
                    <input type="text" name="employee" placeholder="Name or ID..." 
                           value="<?= htmlspecialchars($filter_employee) ?>">
                </div>
                
                <div class="filter-group">
                    <label>Rating</label>
                    <select name="rating">
                        <option value="">All Ratings</option>
                        <option value="5" <?= $filter_rating == '5' ? 'selected' : '' ?>>5 - Excellent</option>
                        <option value="4" <?= $filter_rating == '4' ? 'selected' : '' ?>>4 - Very Good</option>
                        <option value="3" <?= $filter_rating == '3' ? 'selected' : '' ?>>3 - Good</option>
                        <option value="2" <?= $filter_rating == '2' ? 'selected' : '' ?>>2 - Fair</option>
                        <option value="1" <?= $filter_rating == '1' ? 'selected' : '' ?>>1 - Poor</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($filter_date_from) ?>">
                </div>
                
                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($filter_date_to) ?>">
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="button" class="filter-btn btn-clear" 
                        onclick="window.location='reviews.php'">Clear</button>
                <button type="submit" class="filter-btn btn-apply">Apply Filters</button>
            </div>
        </form>
    </div>

    <div class="reviews-feed">
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <div class="empty-title">No reviews found</div>
                <div class="empty-text">
                    <?php if ($filter_employee || $filter_rating || $filter_date_from || $filter_date_to): ?>
                        Try adjusting your filters or create a new review.
                    <?php else: ?>
                        Get started by creating your first performance review.
                    <?php endif; ?>
                </div>
                <button class="create-btn" onclick="window.location='create_review.php'">
                    Create Review
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <?php 
                    $initials = strtoupper(substr($review['first_name'], 0, 1) . substr($review['last_name'], 0, 1));
                    $ratingClass = 'rating-' . $review['rating'];
                    $ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
                ?>
                <div class="review-card" onclick='showReviewModal(<?= json_encode($review) ?>)'>
                    <div class="review-header">
                        <div class="review-avatar"><?= $initials ?></div>
                        <div class="review-info">
                            <div class="review-meta">
                                <span class="employee-name">
                                    <?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?>
                                </span>
                                <span class="employee-title">
                                    <?= htmlspecialchars($review['title'] ?? 'No Title') ?>
                                </span>
                            </div>
                            <div class="review-meta" style="margin-top: 8px;">
                                <span class="rating-badge <?= $ratingClass ?>">
                                     <?= $review['rating'] ?> • <?= $ratingLabels[$review['rating']] ?>
                                </span>
                                <span class="review-date">
                                    <?= date('M j, Y', strtotime($review['review_date'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="review-content">
                        <?php if ($review['comments']): ?>
                            <?php
                            // goes through the structured review comments
                            $comments = $review['comments'];
                            $sections = [];
                            
                            // extract sections
                            if (preg_match('/PERFORMANCE SUMMARY:\s*(.+?)(?=\n\n|KEY STRENGTHS:|AREAS FOR IMPROVEMENT:|ADDITIONAL COMMENTS:|$)/s', $comments, $match)) {
                                $sections['summary'] = trim($match[1]);
                            }
                            if (preg_match('/KEY STRENGTHS:\s*(.+?)(?=\n\nAREAS FOR IMPROVEMENT:|ADDITIONAL COMMENTS:|$)/s', $comments, $match)) {
                                $sections['strengths'] = trim($match[1]);
                            }
                            if (preg_match('/AREAS FOR IMPROVEMENT:\s*(.+?)(?=\n\nADDITIONAL COMMENTS:|$)/s', $comments, $match)) {
                                $sections['improvements'] = trim($match[1]);
                            }
                            if (preg_match('/ADDITIONAL COMMENTS:\s*(.+?)$/s', $comments, $match)) {
                                $sections['additional'] = trim($match[1]);
                            }
                            
                            // show first 2 sections in preview or just summary if it is long. the reveiw is open page
                            $preview_shown = 0;
                            $max_preview = 2;
                            ?>
                            
                            <?php if (isset($sections['summary']) && $preview_shown < $max_preview): ?>
                                <div class="review-section">
                                    <div class="review-section-title"> Performance Summary</div>
                                    <div class="review-section-content">
                                        <?php 
                                            $summary = $sections['summary'];
                                            echo htmlspecialchars(strlen($summary) > 150 ? substr($summary, 0, 150) . '...' : $summary);
                                            $preview_shown++;
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($sections['strengths']) && $preview_shown < $max_preview): ?>
                                <div class="review-section">
                                    <div class="review-section-title"> Key Strengths</div>
                                    <div class="review-section-content">
                                        <?php 
                                            echo htmlspecialchars(substr($sections['strengths'], 0, 100) . '...');
                                            $preview_shown++;
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (count($sections) > $preview_shown): ?>
                                <div style="font-size: 13px; color: #667eea; font-weight: 600; margin-top: 12px;">
                                    Click to view full review →
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="review-footer">
                        <div class="reviewer-info">
                            <span>👤</span>
                            <span>By <strong><?= htmlspecialchars($review['reviewer_first'] . ' ' . $review['reviewer_last']) ?></strong></span>
                        </div>
                        <?php if ($review['dept_name']): ?>
                            <div class="dept-badge">
                                <span></span>
                                <span><?= htmlspecialchars($review['dept_name']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- review center -->
<div class="review-modal" id="reviewModal" onclick="closeModalOnBackdrop(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Performance Review</h2>
            <button class="modal-close" onclick="closeReviewModal()">×</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- content is loaded by js -->
        </div>
    </div>
</div>

<script>
function toggleFilters() {
    const content = document.getElementById('filterContent');
    content.classList.toggle('show');
}

function showReviewModal(review) {
    const modal = document.getElementById('reviewModal');
    const modalBody = document.getElementById('modalBody');
    
    const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    const initials = review.first_name.charAt(0) + review.last_name.charAt(0);
    
    let html = `
        <div class="review-header" style="border-bottom: 1px solid #e1e8ed; padding-bottom: 16px; margin-bottom: 20px;">
            <div style="display: flex; gap: 12px; align-items: center;">
                <div class="review-avatar">${initials.toUpperCase()}</div>
                <div>
                    <div class="employee-name">${review.first_name} ${review.last_name}</div>
                    <div class="employee-title">${review.title || 'No Title'} ${review.dept_name ? '• ' + review.dept_name : ''}</div>
                </div>
            </div>
        </div>
        
        <div class="detail-section" style="background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 100%); border-left-color: #667eea;">
            <div class="detail-label"> Review Date</div>
            <div class="detail-value">${new Date(review.review_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
        </div>
        
        <div class="detail-section rating-section">
            <div class="detail-label"> Performance Rating</div>
            <div class="detail-value">
                <span class="rating-badge rating-${review.rating}">${review.rating} • ${ratingLabels[review.rating]}</span>
            </div>
        </div>
    `;
    
    if (review.comments) {
        // Parse structured comments
        const comments = review.comments;
        
        // Performance Summary
        const summaryMatch = comments.match(/PERFORMANCE SUMMARY:\s*(.+?)(?=\n\nKEY STRENGTHS:|AREAS FOR IMPROVEMENT:|ADDITIONAL COMMENTS:|$)/s);
        if (summaryMatch) {
            html += `
                <div class="modal-review-section" style="border-left-color: #667eea;">
                    <div class="modal-section-title"> Performance Summary</div>
                    <div class="modal-section-content">${summaryMatch[1].trim().replace(/\n/g, '<br>')}</div>
                </div>
            `;
        }
        
        // Key Strengths
        const strengthsMatch = comments.match(/KEY STRENGTHS:\s*(.+?)(?=\n\nAREAS FOR IMPROVEMENT:|ADDITIONAL COMMENTS:|$)/s);
        if (strengthsMatch) {
            html += `
                <div class="modal-review-section" style="border-left-color: #28a745;">
                    <div class="modal-section-title" style="color: #28a745;"> Key Strengths</div>
                    <div class="modal-section-content">${strengthsMatch[1].trim().replace(/\n/g, '<br>')}</div>
                </div>
            `;
        }
        
        // Areas for Improvement
        const improvementsMatch = comments.match(/AREAS FOR IMPROVEMENT:\s*(.+?)(?=\n\nADDITIONAL COMMENTS:|$)/s);
        if (improvementsMatch) {
            html += `
                <div class="modal-review-section" style="border-left-color: #ff9800;">
                    <div class="modal-section-title" style="color: #ff9800;"> Areas for Improvement</div>
                    <div class="modal-section-content">${improvementsMatch[1].trim().replace(/\n/g, '<br>')}</div>
                </div>
            `;
        }
        
        // additional comments
        const additionalMatch = comments.match(/ADDITIONAL COMMENTS:\s*(.+?)$/s);
        if (additionalMatch) {
            html += `
                <div class="modal-review-section" style="border-left-color: #2196f3;">
                    <div class="modal-section-title" style="color: #2196f3;"> Additional Comments</div>
                    <div class="modal-section-content">${additionalMatch[1].trim().replace(/\n/g, '<br>')}</div>
                </div>
            `;
        }
        
        // if no sections found, show raw comments
        if (!summaryMatch && !strengthsMatch && !improvementsMatch && !additionalMatch) {
            html += `
                <div class="modal-review-section">
                    <div class="modal-section-title">Review Details</div>
                    <div class="modal-section-content">${review.comments.replace(/\n/g, '<br>')}</div>
                </div>
            `;
        }
    }
    
    if (review.goals) {
        html += `
            <div class="modal-review-section" style="border-left-color: #9c27b0;">
                <div class="modal-section-title" style="color: #9c27b0;"> Goals & Objectives</div>
                <div class="modal-section-content">${review.goals.replace(/\n/g, '<br>')}</div>
            </div>
        `;
    }
    
    html += `
        <div class="detail-section" style="background: #f8f9fa; border-left-color: #667eea;">
            <div class="detail-label"> Reviewed By</div>
            <div class="detail-value"><strong>${review.reviewer_first} ${review.reviewer_last}</strong></div>
        </div>
    `;
    
    modalBody.innerHTML = html;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
}

function closeModalOnBackdrop(event) {
    if (event.target.id === 'reviewModal') {
        closeReviewModal();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReviewModal();
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>