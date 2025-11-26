<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/Auditlogger.php';
require_once __DIR__ . '/../layout/header.php';

// redirect if not logged in
if (!isset($_SESSION['emp_no'])) {
    header("Location: login.php");
    exit;
}

// get filter parameters


$auditLogger = new AuditLogger($pdo);

$filter_manager = $_GET['manager_emp_no'] ?? '';
$filter_target = $_GET['target_emp_no'] ?? '';
$filter_action = $_GET['action_type'] ?? '';
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

$filters = [];
if ($filter_manager) $filters['manager_emp_no'] = (int)$filter_manager;
if ($filter_target) $filters['target_emp_no'] = (int)$filter_target;
if ($filter_action) $filters['action_type'] = $filter_action;
if ($filter_start) $filters['start_date'] = $filter_start . ' 00:00:00';
if ($filter_end) $filters['end_date'] = $filter_end . ' 23:59:59';

// get logs
$logs = $auditLogger->getLogs($filters, $per_page, $offset);

// get stat
$stats = $auditLogger->getStatistics($filter_manager ? (int)$filter_manager : null);

$action_types = [
    'DEPARTMENT_CHANGE' => 'Department Change',
    'TITLE_UPDATE' => 'Title Update',
    'SALARY_MODIFICATION' => 'Salary Modification',
    'EMPLOYEE_FIRED' => 'Employee Fired',
    'EMPLOYEE_HIRED' => 'Employee Hired',
];
?>

<style>
.audit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    opacity: 0.9;
}

.stat-card .number {
    font-size: 32px;
    font-weight: bold;
    margin: 0;
}

.filters-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.filter-grid label {
    display: flex;
    flex-direction: column;
    font-size: 14px;
    font-weight: 500;
}

.filter-grid input,
.filter-grid select {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-top: 5px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.audit-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.audit-table thead {
    background: #343a40;
    color: white;
}

.audit-table th,
.audit-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.audit-table th {
    font-weight: 600;
    font-size: 14px;
}

.audit-table tbody tr:hover {
    background: #f8f9fa;
}

.action-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-department {
    background: #e3f2fd;
    color: #1976d2;
}

.badge-title {
    background: #f3e5f5;
    color: #7b1fa2;
}

.badge-salary {
    background: #e8f5e9;
    color: #388e3c;
}

.badge-fired {
    background: #ffebee;
    color: #c62828;
}

.badge-hired {
    background: #e0f2f1;
    color: #00796b;
}

.change-display {
    font-size: 13px;
}

.old-value {
    text-decoration: line-through;
    color: #dc3545;
}

.new-value {
    color: #28a745;
    font-weight: 600;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}

.no-logs {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.export-btn {
    background: #28a745;
    color: white;
}

.export-btn:hover {
    background: #218838;
}
</style>

<div class="audit-header">
    <h2>Audit Logs</h2>
    <button class="btn export-btn" onclick="exportToCSV()">Export to CSV</button>
</div>

<!-- statistics cards, better for view data-->
<div class="stats-container">
    <div class="stat-card">
        <h3>Total Actions</h3>
        <p class="number"><?= number_format($stats['total_actions']) ?></p>
    </div>
    <?php foreach ($stats['by_action_type'] as $type => $count): ?>
        <div class="stat-card">
            <h3><?= str_replace('_', ' ', $type) ?></h3>
            <p class="number"><?= number_format($count) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- filters -->
<div class="filters-container">
    <h3 style="margin-top: 0;">Filter Logs</h3>
    <form method="GET" action="">
        <div class="filter-grid">
            <label>
                Manager Emp #
                <input type="number" name="manager_emp_no" value="<?= htmlspecialchars($filter_manager) ?>" placeholder="Filter by manager">
            </label>
            
            <label>
                Target Emp #
                <input type="number" name="target_emp_no" value="<?= htmlspecialchars($filter_target) ?>" placeholder="Filter by employee">
            </label>
            
            <label>
                Action Type
                <select name="action_type">
                    <option value="">All Actions</option>
                    <?php foreach ($action_types as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $filter_action === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            
            <label>
                Start Date
                <input type="date" name="start_date" value="<?= htmlspecialchars($filter_start) ?>">
            </label>
            
            <label>
                End Date
                <input type="date" name="end_date" value="<?= htmlspecialchars($filter_end) ?>">
            </label>
        </div>
        
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <a href="audit_logs.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block; line-height: 1;">Clear Filters</a>
        </div>
    </form>
</div>

<!-- audit logs table -->
<?php if (empty($logs)): ?>
    <div class="no-logs">
        <p>No audit logs found matching your filters.</p>
    </div>
<?php else: ?>
    <table class="audit-table">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Action</th>
                <th>Manager</th>
                <th>Target Employee</th>
                <th>Changes</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <?php
                $badge_class = 'badge-department';
                if ($log['action_type'] === 'TITLE_UPDATE') $badge_class = 'badge-title';
                elseif ($log['action_type'] === 'SALARY_MODIFICATION') $badge_class = 'badge-salary';
                elseif ($log['action_type'] === 'EMPLOYEE_FIRED') $badge_class = 'badge-fired';
                elseif ($log['action_type'] === 'EMPLOYEE_HIRED') $badge_class = 'badge-hired';
                ?>
                <tr>
                    <td style="white-space: nowrap;">
                        <?= date('M d, Y', strtotime($log['action_timestamp'])) ?><br>
                        <small style="color: #6c757d;"><?= date('g:i A', strtotime($log['action_timestamp'])) ?></small>
                    </td>
                    <td>
                        <span class="action-badge <?= $badge_class ?>">
                            <?= str_replace('_', ' ', $log['action_type']) ?>
                        </span>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($log['manager_name'] ?? 'Unknown') ?></strong><br>
                        <small style="color: #6c757d;">Emp #<?= $log['manager_emp_no'] ?></small>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($log['target_name'] ?? 'Unknown') ?></strong><br>
                        <small style="color: #6c757d;">
                            Emp #<?= $log['target_emp_no'] ?>
                            <?php if ($log['department_name']): ?>
                                • <?= htmlspecialchars($log['department_name']) ?>
                            <?php endif; ?>
                        </small>
                    </td>
                    <td class="change-display">
                        <?php if ($log['old_value'] && $log['new_value']): ?>
                            <span class="old-value"><?= htmlspecialchars($log['old_value']) ?></span>
                            →
                            <span class="new-value"><?= htmlspecialchars($log['new_value']) ?></span>
                        <?php else: ?>
                            <?= htmlspecialchars($log['new_value'] ?? $log['old_value'] ?? '-') ?>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 12px; color: #6c757d;">
                        <?= htmlspecialchars($log['ip_address'] ?? '-') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
 
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>" class="btn btn-secondary">Previous</a>
        <?php endif; ?>
        
        <span style="padding: 10px;">Page <?= $page ?></span>
        
        <?php if (count($logs) === $per_page): ?>
            <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)) ?>" class="btn btn-secondary">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
function exportToCSV() {
    // build CSV content
    const headers = ['Timestamp', 'Action', 'Manager', 'Manager Emp#', 'Target Employee', 'Target Emp#', 'Old Value', 'New Value', 'IP Address'];
    const rows = <?= json_encode(array_map(function($log) {
        return [
            $log['action_timestamp'],
            $log['action_type'],
            $log['manager_name'] ?? 'Unknown',
            $log['manager_emp_no'],
            $log['target_name'] ?? 'Unknown',
            $log['target_emp_no'],
            $log['old_value'] ?? '',
            $log['new_value'] ?? '',
            $log['ip_address'] ?? ''
        ];
    }, $logs)) ?>;
    
    let csv = headers.join(',') + '\n';
    rows.forEach(row => {
        csv += row.map(cell => '"' + (cell ?? '').toString().replace(/"/g, '""') + '"').join(',') + '\n';
    });
    
    // download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'audit_logs_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>