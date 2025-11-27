<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../layout/header.php';

// restrict access: only logged in users
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

$sql = "
SELECT e.emp_no, e.first_name, e.last_name, d.dept_no, d.dept_name
FROM dept_manager dm
JOIN employees e ON dm.emp_no = e.emp_no
JOIN departments d ON dm.dept_no = d.dept_no
WHERE dm.to_date IS NULL OR dm.to_date > CURRENT_DATE
ORDER BY d.dept_name, e.last_name, e.first_name
";
$rows = $pdo->query($sql)->fetchAll();

$total_managers = count($rows);
$departments_with_managers = count(array_unique(array_column($rows, 'dept_no')));

$managers_by_dept = [];
foreach ($rows as $row) {
    $managers_by_dept[$row['dept_name']][] = $row;
}
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
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 35px;
}

.stat-card {
    background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(21, 101, 192, 0.25);
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(21, 101, 192, 0.35);
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
    grid-template-columns: 1fr 380px;
    gap: 30px;
}

.managers-container {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.department-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(21, 101, 192, 0.1);
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.department-header {
    padding: 20px 25px;
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-bottom: 2px solid #90caf9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dept-name {
    font-size: 18px;
    font-weight: 700;
    color: #0d47a1;
}

.manager-count-badge {
    background: #1565c0;
    color: white;
    padding: 5px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.managers-list {
    padding: 0;
    margin: 0;
    list-style: none;
}

.manager-item {
    padding: 20px 25px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background-color 0.2s;
}

.manager-item:last-child {
    border-bottom: none;
}

.manager-item:hover {
    background-color: #f8f9fa;
}

.manager-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.manager-avatar {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 16px;
    box-shadow: 0 2px 6px rgba(21, 101, 192, 0.3);
}

.manager-details {
    display: flex;
    flex-direction: column;
}

.manager-name {
    font-weight: 600;
    font-size: 15px;
    color: #333;
}

.manager-emp-no {
    font-size: 12px;
    color: #666;
    font-family: 'Courier New', monospace;
}

.sidebar {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.info-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(21, 101, 192, 0.1);
    padding: 25px;
    border: 1px solid #e9ecef;
}

.info-card-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.dept-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dept-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.dept-item-name {
    font-size: 13px;
    font-weight: 600;
    color: #333;
    flex: 1;
}

.dept-item-count {
    font-size: 14px;
    font-weight: 700;
    color: #1565c0;
    background: white;
    padding: 4px 10px;
    border-radius: 10px;
    border: 1px solid #e3f2fd;
}

.all-managers-table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(21, 101, 192, 0.1);
    overflow: hidden;
    border: 1px solid #e9ecef;
}

.table-header {
    padding: 25px 30px;
    background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
    border-bottom: 2px solid #90caf9;
}

.table-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.managers-table {
    width: 100%;
    border-collapse: collapse;
}

.managers-table thead {
    background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
    color: white;
}

.managers-table th {
    padding: 16px 30px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 3px solid #0d47a1;
}

.managers-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s;
}

.managers-table tbody tr:hover {
    background-color: #e3f2fd;
}

.managers-table td {
    padding: 18px 30px;
    font-size: 14px;
    color: #333;
}

.table-emp-no {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    color: #1565c0;
}

.table-name {
    font-weight: 600;
}

.table-dept {
    color: #0d47a1;
    font-weight: 500;
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

.view-toggle {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
}

.toggle-btn {
    flex: 1;
    padding: 12px 20px;
    border: 2px solid #e9ecef;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: #666;
    transition: all 0.3s;
}

.toggle-btn.active {
    background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
    color: white;
    border-color: #1565c0;
}

.toggle-btn:hover:not(.active) {
    border-color: #1565c0;
    color: #1565c0;
}

.view-content {
    display: none;
}

.view-content.active {
    display: block;
}

@media (max-width: 1200px) {
    .content-container {
        grid-template-columns: 1fr;
    }
    
    .sidebar {
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
    
    .managers-table th,
    .managers-table td {
        padding: 12px 15px;
    }
}
</style>

<div class="page-header">
    <h1 class="page-title">Managers Directory</h1>
    <p class="page-subtitle">View all department managers and their assignments</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Managers</div>
        <div class="stat-value"><?= number_format($total_managers) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Departments Managed</div>
        <div class="stat-value"><?= number_format($departments_with_managers) ?></div>
    </div>
</div>

<?php if (empty($rows)): ?>
    <div class="all-managers-table-container">
        <div class="empty-state">
            <div class="empty-title">No Managers Found</div>
            <p>There are no active managers in the system</p>
        </div>
    </div>
<?php else: ?>
    <div class="view-toggle">
        <button class="toggle-btn active" onclick="switchView('grouped')">By Department</button>
        <button class="toggle-btn" onclick="switchView('table')">All Managers</button>
    </div>

    <div id="grouped-view" class="view-content active">
        <div class="content-container">
            <div class="managers-container">
                <?php foreach($managers_by_dept as $dept_name => $managers): ?>
                    <div class="department-section">
                        <div class="department-header">
                            <div class="dept-name"><?= htmlspecialchars($dept_name) ?></div>
                            <div class="manager-count-badge">
                                <?= count($managers) ?> <?= count($managers) === 1 ? 'Manager' : 'Managers' ?>
                            </div>
                        </div>
                        <ul class="managers-list">
                            <?php foreach($managers as $manager): ?>
                                <?php 
                                    $initials = strtoupper(substr($manager['first_name'], 0, 1) . substr($manager['last_name'], 0, 1));
                                ?>
                                <li class="manager-item">
                                    <div class="manager-info">
                                        <div class="manager-avatar"><?= $initials ?></div>
                                        <div class="manager-details">
                                            <div class="manager-name">
                                                <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']) ?>
                                            </div>
                                            <div class="manager-emp-no">Emp #<?= htmlspecialchars($manager['emp_no']) ?></div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="sidebar">
                <div class="info-card">
                    <div class="info-card-title">Department Overview</div>
                    <div class="dept-list">
                        <?php foreach($managers_by_dept as $dept_name => $managers): ?>
                            <div class="dept-item">
                                <span class="dept-item-name"><?= htmlspecialchars($dept_name) ?></span>
                                <span class="dept-item-count"><?= count($managers) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="table-view" class="view-content">
        <div class="all-managers-table-container">
            <div class="table-header">
                <div class="table-title">All Managers</div>
            </div>
            <table class="managers-table">
                <thead>
                    <tr>
                        <th>Emp #</th>
                        <th>Name</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rows as $r): ?>
                        <tr>
                            <td><span class="table-emp-no">#<?= htmlspecialchars($r['emp_no']) ?></span></td>
                            <td><span class="table-name"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></span></td>
                            <td><span class="table-dept"><?= htmlspecialchars($r['dept_name']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
function switchView(view) {
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    document.querySelectorAll('.view-content').forEach(content => {
        content.classList.remove('active');
    });
    
    if (view === 'grouped') {
        document.getElementById('grouped-view').classList.add('active');
    } else {
        document.getElementById('table-view').classList.add('active');
    }
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>