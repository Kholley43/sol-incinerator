<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!isset($data['signedTx'])) {
    echo json_encode(['ok'=>false,'error'=>'missing signedTx']);
    exit;
}
$signed = $data['signedTx'];
$rpcUrl = 'https://api.mainnet-beta.solana.com';

$payload = [
    'jsonrpc'=>'2.0',
    'id'=>1,
    'method'=>'sendRawTransaction',
    'params'=>[$signed]
];

$ch = curl_init($rpcUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
if($response === false){
    echo json_encode(['ok'=>false,'error'=>'RPC error']);
    exit;
}
$res = json_decode($response, true);
if(isset($res['result'])){
    echo json_encode(['ok'=>true,'sig'=>$res['result']]);
}else{
    echo json_encode(['ok'=>false,'error'=>$res['error']['message']??'unknown']);
}
?>
