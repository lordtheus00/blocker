<?php session_start();
if (!isset($_SESSION['admin'])) {
    exit;
}

// Save rotator files
if (isset($_POST['rotator']) && is_array($_POST['rotator'])) {
    foreach ($_POST['rotator'] as $filename => $content) {
        if (strpos($filename, '..') === false && strpos($filename, '/') === false) {
            file_put_contents('../rotator/' . $filename, trim($content));
        }
    }
}

// Save blacklist files
if (isset($_POST['blacklist']) && is_array($_POST['blacklist'])) {
    foreach ($_POST['blacklist'] as $filename => $content) {
        if (strpos($filename, '..') === false && strpos($filename, '/') === false) {
            file_put_contents('../blacklist/' . $filename, trim($content));
        }
    }
}

// Save config
$config = json_decode(file_get_contents('../config.json'), true);
$config['stopbot_api_key'] = $_POST['stopbot_api_key'] ?? '';
$config['allowed_countries'] = strtoupper(preg_replace('/[^a-zA-Z,]/', '', $_POST['allowed_countries'] ?? ''));

$block_types = ['ip', 'asn', 'ua', 'host', 'stopbot', 'country'];
foreach ($block_types as $type) {
    $config['block'][$type] = isset($_POST['toggle_' . $type]);
}

if (!empty($_POST['new_password'])) {
    $config['admin_password'] = $_POST['new_password'];
}

file_put_contents('../config.json', json_encode($config, JSON_PRETTY_PRINT));
header('Location: dashboard.php?save=success');
