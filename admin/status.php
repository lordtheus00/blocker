<?php
session_start();
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized</title>
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { display: flex; align-items: center; justify-content: center; text-align: center; background-color: #212529; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="display-1">401</h1>
        <h2 class="mb-4">Unauthorized Access</h2>
        <p class="lead">You do not have permission to view this page. Please log in to continue.</p>
        <a href="index.php" class="btn btn-primary mt-3">Go to Login Page</a>
    </div>
</body>
</html>
HTML;
    exit;
}

$log_file = '../log/status.txt';
$succ = '';
$err = '';

// Handle Reset Stats
if (isset($_GET['action']) && $_GET['action'] === 'reset_stats') {
    if (file_exists($log_file)) {
        file_put_contents($log_file, '');
        $succ = "Statistics have been successfully reset.";
    }
}

// Read and parse logs
$lines = file_exists($log_file) ? file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$all_visitors = count($lines);
$total_humans = 0;
$total_bots = 0;
$visitor_logs = [];

foreach ($lines as $line) {
    $log_parts = explode(' | ', trim($line), 2);
    $timestamp = $log_parts[0];
    $data_string = $log_parts[1] ?? '';
    
    $data = explode("|", $data_string);
    $status = $data[3] ?? 'Unknown';

    if ($status === 'Human') {
        $total_humans++;
    } elseif ($status === 'Blocked') {
        $total_bots++;
    }

    $visitor_logs[] = [
        'timestamp' => $timestamp,
        'ip'        => $data[0] ?? '',
        'host'      => $data[1] ?? '',
        'device'    => $data[2] ?? '',
        'status'    => $status,
        'reason'    => $data[4] ?? '',
        'url'       => $data[5] ?? ''
    ];
}

function flag($ip) {
    static $ip_cache = [];
    if (isset($ip_cache[$ip])) return $ip_cache[$ip];
    $j = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode");
    $d = json_decode($j, true);
    if (isset($d['countryCode'])) {
        $c = strtolower($d['countryCode']);
        $ip_cache[$ip] = "<img src=\"https://flagcdn.com/16x12/{$c}.png\" class=\"me-2\" alt=\"{$c}\"> {$c}";
        return $ip_cache[$ip];
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Log - BLOKER_V8</title>
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { display: flex; min-height: 100vh; background-color: #212529; }
        .sidebar { width: 250px; flex-shrink: 0; background-color: #343a40; }
        .sidebar .nav-link { color: #adb5bd; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link .fa { width: 20px; text-align: center; }
        .content { flex-grow: 1; }
        .stats-card { background-color: #343a40; border-radius: .5rem; }
        .stats-card .card-body p { margin: 0; color: #adb5bd; }
        .stats-card .card-body h4 { margin: 0; }
        .table { --bs-table-bg: #343a40; --bs-table-striped-bg: #3e444a; --bs-table-hover-bg: #454b51; }
        .user-agent-cell { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>
    <div class="sidebar p-3 d-flex flex-column">
        <h3 class="mb-4 text-white">BLOKER_V8</h3>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fa fa-tachometer-alt me-2"></i>Dashboard</a></li>
            <li class="nav-item"><a href="status.php" class="nav-link active"><i class="fa fa-chart-bar me-2"></i>Status Log</a></li>
        </ul>
        <div class="mt-auto">
             <a href="logout.php" class="nav-link"><i class="fa fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <main class="content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="text-white">Status Log</h2>
                <small class="text-muted">Auto-refreshes every <span id="refresh-time-display">30</span> seconds</small>
            </div>
            <div>
                <button class="btn btn-outline-danger" onclick="resetStats()"><i class="fas fa-trash-alt me-1"></i> Reset Stats</button>
            </div>
        </div>

        <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
        <?php if ($succ): ?><div class="alert alert-success"><?= $succ ?></div><?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="card stats-card"><div class="card-body p-3"><p><i class="fas fa-users me-2"></i>All Visitors</p><h4><?= $all_visitors ?></h4></div></div></div>
            <div class="col-md-4"><div class="card stats-card"><div class="card-body p-3"><p><i class="fas fa-user-check me-2"></i>Total Humans</p><h4><?= $total_humans ?></h4></div></div></div>
            <div class="col-md-4"><div class="card stats-card"><div class="card-body p-3"><p><i class="fas fa-robot me-2"></i>Total Blocked</p><h4><?= $total_bots ?></h4></div></div></div>
        </div>

        <div class="card" style="background-color: #343a40;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clipboard-list me-2"></i>Visitor Details</span>
                <div class="d-flex align-items-center gap-2">
                    <select id="typeFilter" class="form-select form-select-sm" style="width:auto;">
                        <option value="all">All Types</option><option value="Human">Humans</option><option value="Blocked">Blocked</option>
                    </select>
                    <select id="rowCount" class="form-select form-select-sm" style="width:auto;">
                        <option value="25">25 Rows</option><option value="50">50 Rows</option><option value="100">100 Rows</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="visitorTable">
                    <thead><tr><th>Country</th><th>IP</th><th>Host</th><th>Device/OS</th><th>Status</th><th>Reason</th><th>URL</th><th>Date</th></tr></thead>
                    <tbody id="visitorTableBody">
                        <?php foreach (array_reverse($visitor_logs) as $log): ?>
                        <tr class="visitor-row" data-type="<?= htmlspecialchars($log['status']) ?>">
                            <td><?= flag($log['ip']) ?></td>
                            <td><?= htmlspecialchars($log['ip']) ?></td>
                            <td class="user-agent-cell" title="<?= htmlspecialchars($log['host']) ?>"><?= htmlspecialchars($log['host']) ?></td>
                            <td class="user-agent-cell" title="<?= htmlspecialchars($log['device']) ?>"><?= htmlspecialchars($log['device']) ?></td>
                            <td><span class="badge bg-<?= $log['status'] == 'Blocked' ? 'danger' : 'success' ?>"><?= htmlspecialchars($log['status']) ?></span></td>
                            <td><?= htmlspecialchars($log['reason']) ?></td>
                            <td class="user-agent-cell" title="<?= htmlspecialchars($log['url']) ?>"><a href="<?= htmlspecialchars($log['url']) ?>" target="_blank"><?= htmlspecialchars(parse_url($log['url'])['host'] ?? '-') ?></a></td>
                            <td><?= htmlspecialchars($log['timestamp']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <nav class="mt-3"><ul class="pagination justify-content-center" id="paginationContainer"></ul></nav>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let refreshTimeout;
    function setRefresh(seconds) {
        if (refreshTimeout) clearTimeout(refreshTimeout);
        document.getElementById('refresh-time-display').textContent = seconds;
        refreshTimeout = setTimeout(() => window.location.reload(), seconds * 1000);
    }
    function resetStats() {
        if (confirm('Are you sure you want to reset all statistics? This action cannot be undone.')) {
            window.location.href = '?action=reset_stats';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        setRefresh(30);
        const typeFilter = document.getElementById('typeFilter');
        const rowCount = document.getElementById('rowCount');
        const visitorTableBody = document.getElementById('visitorTableBody');
        const paginationContainer = document.getElementById('paginationContainer');
        const allRows = Array.from(visitorTableBody.querySelectorAll('.visitor-row'));
        let currentPage = 1, rowsPerPage = 25, filteredData = allRows;

        function applyFilters() {
            const selectedType = typeFilter.value;
            filteredData = allRows.filter(row => selectedType === 'all' || row.getAttribute('data-type') === selectedType);
            rowsPerPage = parseInt(rowCount.value);
            currentPage = 1;
            renderTable();
        }

        function renderTable() {
            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            allRows.forEach(row => row.style.display = 'none');
            const pageData = filteredData.slice(startIndex, endIndex);
            pageData.forEach(row => row.style.display = '');
            renderPagination();
        }

        function renderPagination() {
            const totalPages = Math.ceil(filteredData.length / rowsPerPage);
            paginationContainer.innerHTML = '';
            if (totalPages <= 1) return;
            // Previous Button
            paginationContainer.innerHTML += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Prev</a></li>`;
            // Page Numbers
            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    paginationContainer.innerHTML += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                } else {
                    paginationContainer.innerHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`;
                }
            }
            // Next Button
            paginationContainer.innerHTML += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Next</a></li>`;
        }
        
        window.changePage = function(page) {
            const totalPages = Math.ceil(filteredData.length / rowsPerPage);
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderTable();
        }
        
        typeFilter.addEventListener('change', applyFilters);
        rowCount.addEventListener('change', applyFilters);
        applyFilters();
    });
    </script>
</body>
</html>
