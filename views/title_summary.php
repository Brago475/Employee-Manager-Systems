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

$total_titles = count($rows);
$total_employees = array_sum(array_column($rows, 'title_count'));
$avg_per_title = $total_titles > 0 ? round($total_employees / $total_titles, 1) : 0;
$max_title = !empty($rows) ? max(array_column($rows, 'title_count')) : 0;
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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 35px;
}

.stat-card {
    background: linear-gradient(135deg, #6a1b9a 0%, #4a148c 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(106, 27, 154, 0.25);
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(106, 27, 154, 0.35);
}

.stat-label {
    font-size: 13px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    line-height: 1;
}

.content-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 30px;
}

.table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(106, 27, 154, 0.1);
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.table-header {
    padding: 25px 30px;
    background: linear-gradient(135deg, #f3e5f5 0%, #ffffff 100%);
    border-bottom: 2px solid #e1bee7;
}

.table-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.title-table {
    width: 100%;
    border-collapse: collapse;
}

.title-table thead {
    background: linear-gradient(135deg, #6a1b9a 0%, #4a148c 100%);
    color: white;
}

.title-table th {
    padding: 16px 30px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 3px solid #4a148c;
}

.title-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s;
}

.title-table tbody tr:hover {
    background-color: #f3e5f5;
}

.title-table td {
    padding: 18px 30px;
    font-size: 14px;
    color: #333;
}

.job-title {
    font-weight: 600;
    color: #4a148c;
}

.title-count {
    font-weight: 700;
    color: #2e7d32;
    font-size: 16px;
}

.chart-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(106, 27, 154, 0.1);
    padding: 30px;
    border: 1px solid #e9ecef;
}

.chart-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.chart-bars {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.chart-bar-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.bar-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.bar-title-name {
    font-weight: 600;
    color: #333;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-right: 10px;
}

.bar-count {
    font-weight: 700;
    color: #6a1b9a;
}

.bar-track {
    height: 28px;
    background: #e9ecef;
    border-radius: 14px;
    overflow: hidden;
    position: relative;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #6a1b9a 0%, #9c27b0 100%);
    border-radius: 14px;
    transition: width 1s ease-out;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 12px;
    color: white;
    font-size: 11px;
    font-weight: 600;
    min-width: 30px;
}

.top-titles {
    margin-top: 25px;
    padding-top: 25px;
    border-top: 2px solid #e9ecef;
}

.top-titles-header {
    font-size: 16px;
    font-weight: 600;
    color: #6a1b9a;
    margin-bottom: 15px;
}

.top-title-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.top-title-item:last-child {
    border-bottom: none;
}

.top-title-rank {
    font-weight: 700;
    color: #6a1b9a;
    margin-right: 10px;
}

.top-title-name {
    flex: 1;
    font-size: 13px;
    color: #333;
}

.top-title-count {
    font-weight: 700;
    color: #2e7d32;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}


.empty-title {
    font-size: 20px;
    color: #333;
    margin-bottom: 10px;
}

@media (max-width: 1200px) {
    .content-container {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        order: -1;
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 24px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .title-table th,
    .title-table td {
        padding: 12px 15px;
    }
}
</style>

<div class="page-header">
    <h1 class="page-title">Title Summary</h1>
    <p class="page-subtitle">Overview of job titles and employee distribution</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Job Titles</div>
        <div class="stat-value"><?= number_format($total_titles) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Total Employees</div>
        <div class="stat-value"><?= number_format($total_employees) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Average per Title</div>
        <div class="stat-value"><?= number_format($avg_per_title, 1) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Most Common Title</div>
        <div class="stat-value"><?= number_format($max_title) ?></div>
    </div>
</div>

<div class="content-container">
    <div class="table-container">
        <div class="table-header">
            <div class="table-title">All Job Titles</div>
        </div>
        
        <?php if (empty($rows)): ?>
            <div class="empty-state">
                <div class="empty-title">No Titles Found</div>
                <p>There are no job titles in the system</p>
            </div>
        <?php else: ?>
            <table class="title-table">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Employee Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rows as $r): ?>
                        <tr>
                            <td><span class="job-title"><?= htmlspecialchars($r['title']) ?></span></td>
                            <td><span class="title-count"><?= number_format($r['title_count']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if (!empty($rows)): ?>
        <div class="chart-container">
            <div class="chart-title">Title Distribution</div>
            <div class="chart-bars">
                <?php 
                $displayRows = array_slice($rows, 0, 8);
                foreach($displayRows as $r): 
                    $percentage = $max_title > 0 ? ($r['title_count'] / $max_title) * 100 : 0;
                ?>
                    <div class="chart-bar-item">
                        <div class="bar-label">
                            <span class="bar-title-name" title="<?= htmlspecialchars($r['title']) ?>">
                                <?= htmlspecialchars($r['title']) ?>
                            </span>
                            <span class="bar-count"><?= number_format($r['title_count']) ?></span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $percentage ?>%">
                                <?php if ($percentage > 15): ?>
                                    <?= number_format($percentage, 0) ?>%
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($rows) > 0): ?>
                <div class="top-titles">
                    <div class="top-titles-header">Top 5 Most Common</div>
                    <?php 
                    $topFive = array_slice($rows, 0, 5);
                    foreach($topFive as $index => $r): 
                    ?>
                        <div class="top-title-item">
                            <span class="top-title-rank">#<?= $index + 1 ?></span>
                            <span class="top-title-name"><?= htmlspecialchars($r['title']) ?></span>
                            <span class="top-title-count"><?= number_format($r['title_count']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>