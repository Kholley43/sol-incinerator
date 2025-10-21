<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
$ids = isset($_GET['ids']) ? $_GET['ids'] : '';
if (!$ids) {
    echo json_encode(['error'=>'missing ids']);
    exit;
}
$url = 'https://price.jup.ag/v4/price?ids=' . urlencode($ids);
$resp = @file_get_contents($url);
if ($resp === false) {
    echo json_encode(['error'=>'upstream failure']);
    exit;
}
// pass-through response from Jupiter
echo $resp;
?>
