<?php session_start(); if (!isset($_SESSION['admin'])) {
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
function load($f) { return htmlspecialchars(file_get_contents("../$f") ?? ''); }
$config = json_decode(file_get_contents('../config.json'), true);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { display: flex; min-height: 100vh; background-color: #212529; }
        .sidebar { width: 250px; flex-shrink: 0; background-color: #343a40; }
        .sidebar .nav-link { color: #adb5bd; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link .fa { width: 20px; text-align: center; }
        .content { flex-grow: 1; }
        .card { background-color: #343a40; }
    </style>
</head>
<body>
    <div class="sidebar p-3 d-flex flex-column">
        <h3 class="mb-4 text-white">BLOKER_V8</h3>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active"><i class="fa fa-tachometer-alt me-2"></i>Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="status.php" class="nav-link"><i class="fa fa-chart-bar me-2"></i>Status Log</a>
            </li>
        </ul>
        <div class="mt-auto">
             <a href="logout.php" class="nav-link"><i class="fa fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <div class="content p-4">
        <h1 class="mb-4 text-white">Dashboard</h1>

        <?php if (isset($_GET['save']) && $_GET['save'] === 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Settings saved successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="save.php">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>ROTATOR</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="rotator_real" class="form-label">Real Traffic URLs</label>
                                <textarea id="rotator_real" name="rotator[real.txt]" class="form-control" rows="5"><?= load("rotator/real.txt") ?></textarea>
                            </div>
                            <div>
                                <label for="rotator_block" class="form-label">Blocked Traffic URLs</label>
                                <textarea id="rotator_block" name="rotator[block.txt]" class="form-control" rows="5"><?= load("rotator/block.txt") ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>StopBot</h3>
                        </div>
                        <div class="card-body">
                             <div class="mb-3">
                                <label for="stopbot_api_key" class="form-label">StopBot API Key</label>
                                <input id="stopbot_api_key" name="stopbot_api_key" class="form-control" value="<?= htmlspecialchars($config['stopbot_api_key']) ?>">
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="toggle_stopbot" id="toggle_stopbot" <?= $config['block']['stopbot'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="toggle_stopbot">Enable StopBot</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>Country Whitelist</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="allowed_countries" class="form-label">Allowed Country Codes</label>
                                <input type="text" id="allowed_countries" name="allowed_countries" class="form-control" value="<?= htmlspecialchars($config['allowed_countries']) ?>" placeholder="e.g. ID,US,NZ">
                                <div class="form-text">Comma-separated two-letter country codes. Leave blank to allow all.</div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="toggle_country" id="toggle_country" <?= $config['block']['country'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="toggle_country">Enable Country Whitelist</label>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>BLACKLIST</h3>
                        </div>
                        <div class="card-body">
                        <?php
                        $blacklist_types = [
                            'ip' => ['label' => 'IP Blacklist', 'file' => 'Ip_Blacklist.dat'],
                            'asn' => ['label' => 'ASN Blacklist', 'file' => 'ASN.dat'],
                            'ua' => ['label' => 'User Agent Blacklist', 'file' => 'UA.dat'],
                            'host' => ['label' => 'Hostname Blacklist', 'file' => 'hostname.dat']
                        ];
                        foreach ($blacklist_types as $type => $details):
                        ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label mb-0"><?= $details['label'] ?></label>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $type ?>" aria-expanded="false" aria-controls="collapse-<?= $type ?>">
                                        Edit
                                    </button>
                                    <div class="form-check form-switch d-inline-block align-middle">
                                        <input class="form-check-input" type="checkbox" role="switch" id="toggle_<?= $type ?>" name="toggle_<?= $type ?>" <?= $config['block'][$type] ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="collapse mt-2" id="collapse-<?= $type ?>">
                                <textarea name="blacklist[<?= $details['file'] ?>]" class="form-control" rows="5" placeholder="One entry per line"><?= load("blacklist/{$details['file']}") ?></textarea>
                            </div>
                        </div>
                        <hr>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3>Change Password</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-4">Save All Settings</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
