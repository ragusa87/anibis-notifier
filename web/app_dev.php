<?php
// Allow only local dev
$found = false;
foreach (['192.168.', '127.'] as $allowedIp) {
    $found = $found || (isset($_SERVER['REMOTE_ADDR']) && stripos($_SERVER['REMOTE_ADDR'], $allowedIp) === 0);
}
if (!$found && php_sapi_name() != 'cli-server') {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check ' . basename(__FILE__) . ' for more information.');
}

require_once 'app.php';
