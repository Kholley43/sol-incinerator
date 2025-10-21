<?php
// CORS headers to allow browser access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Map of RPC endpoints
$endpoints = [
    'solana' => 'https://api.mainnet-beta.solana.com',
    'alchemy' => 'https://solana-mainnet.g.alchemy.com/v2/demo',
    'tracker' => 'https://rpc-mainnet.solanatracker.io/?api_key=02232df4-4670-439c-b65a-27225d5b841f',
    'quicknode' => 'https://quiet-attentive-frost.solana-mainnet.quiknode.pro/ed83a1d62d5a9b3c0a9fd0fb8d99e8e2d25f5ad3/',
    'serum' => 'https://solana-api.projectserum.com'
];

// Get endpoint from query parameter
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : null;

if (!$endpoint || !isset($endpoints[$endpoint])) {
    echo json_encode(['error' => 'Invalid endpoint specified']);
    exit;
}

$url = $endpoints[$endpoint];

// Get POST data
$postData = file_get_contents('php://input');

// Forward the request to the actual RPC endpoint
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo json_encode(['error' => 'CURL error: ' . $error]);
} else {
    // Pass through the response
    echo $response;
}