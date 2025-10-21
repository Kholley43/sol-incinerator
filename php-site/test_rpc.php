<?php
// RPC Connection Test for SOL Incinerator
// Tests RPC endpoints and checks a specific wallet for burnable accounts

header('Content-Type: text/plain');

echo "===== SOL INCINERATOR RPC TEST =====\n\n";

// RPC endpoints to test
$rpc_endpoints = [
    'solana' => 'https://api.mainnet-beta.solana.com',
    'alchemy' => 'https://solana-mainnet.g.alchemy.com/v2/demo',
    'tracker' => 'https://rpc-mainnet.solanatracker.io/?api_key=02232df4-4670-439c-b65a-27225d5b841f',
    'helius' => 'https://mainnet.helius-rpc.com/?api-key=15319106-c24e-4a54-9e89-f6f3789499b6',
    'triton' => 'https://triton.api.mngo.cloud',
    'genesys' => 'https://ssc-dao.genesysgo.net'
];

// Test wallet address
$test_wallet = '36193q8fQ6MoJp6ivRvm7rLQf2gNP3utKzvoB9yjaFYF';

// Test each RPC endpoint
foreach ($rpc_endpoints as $name => $url) {
    echo "Testing $name endpoint: $url\n";
    
    // Test 1: Basic connection test (getBlockHeight)
    $data = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getBlockHeight',
        'params' => []
    ];
    
    $result = call_rpc($url, $data);
    
    if (isset($result['result'])) {
        echo "✅ Connection successful. Block height: " . $result['result'] . "\n";
        
        // Test 2: Check token accounts for the test wallet
        echo "   Checking token accounts for wallet $test_wallet...\n";
        
        // Test SPL Token accounts
        $token_accounts = get_token_accounts($url, $test_wallet, 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA');
        $token2022_accounts = get_token_accounts($url, $test_wallet, 'TokenzQdBNbLqP5VEhdkAS6EPFLC1PHnBqCXEpPxuEb');
        
        $total_accounts = count($token_accounts) + count($token2022_accounts);
        
        if ($total_accounts > 0) {
            echo "   ✅ Found $total_accounts token accounts (" . count($token_accounts) . " SPL, " . count($token2022_accounts) . " Token-2022)\n";
            
            // Check for burnable accounts (0 balance)
            $burnable_accounts = [];
            
            foreach ($token_accounts as $account) {
                $account_info = get_account_info($url, $account);
                if ($account_info && isset($account_info['amount']) && $account_info['amount'] == 0) {
                    $burnable_accounts[] = [
                        'address' => $account,
                        'type' => 'SPL Token'
                    ];
                }
            }
            
            foreach ($token2022_accounts as $account) {
                $account_info = get_account_info($url, $account);
                if ($account_info && isset($account_info['amount']) && $account_info['amount'] == 0) {
                    $burnable_accounts[] = [
                        'address' => $account,
                        'type' => 'Token-2022'
                    ];
                }
            }
            
            $burnable_count = count($burnable_accounts);
            if ($burnable_count > 0) {
                echo "   🔥 Found $burnable_count burnable accounts (0 balance):\n";
                foreach ($burnable_accounts as $index => $account) {
                    echo "      " . ($index + 1) . ". " . $account['address'] . " (" . $account['type'] . ")\n";
                }
            } else {
                echo "   ℹ️ No burnable accounts found (all have non-zero balances)\n";
            }
        } else {
            echo "   ⚠️ No token accounts found for this wallet\n";
        }
    } else {
        echo "❌ Connection failed: " . (isset($result['error']) ? json_encode($result['error']) : "Unknown error") . "\n";
    }
    
    echo "\n";
}

echo "===== TEST COMPLETE =====\n";

// Helper function to call RPC endpoint
function call_rpc($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => ['message' => "CURL error: $error"]];
    }
    
    return json_decode($response, true);
}

// Get token accounts for a wallet
function get_token_accounts($url, $wallet, $program_id) {
    $accounts = [];
    
    // Create memcmp filter for the wallet
    $data = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getProgramAccounts',
        'params' => [
            $program_id,
            [
                'encoding' => 'jsonParsed',
                'filters' => [
                    ['dataSize' => 165],
                    ['memcmp' => ['offset' => 32, 'bytes' => $wallet]]
                ]
            ]
        ]
    ];
    
    $result = call_rpc($url, $data);
    
    if (isset($result['result'])) {
        foreach ($result['result'] as $account) {
            $accounts[] = $account['pubkey'];
        }
    }
    
    return $accounts;
}

// Get account info
function get_account_info($url, $account) {
    $data = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getAccountInfo',
        'params' => [
            $account,
            ['encoding' => 'jsonParsed']
        ]
    ];
    
    $result = call_rpc($url, $data);
    
    if (isset($result['result']) && isset($result['result']['value']) && 
        isset($result['result']['value']['data']) && isset($result['result']['value']['data']['parsed']) && 
        isset($result['result']['value']['data']['parsed']['info']) && 
        isset($result['result']['value']['data']['parsed']['info']['tokenAmount'])) {
        
        return [
            'mint' => $result['result']['value']['data']['parsed']['info']['mint'],
            'amount' => $result['result']['value']['data']['parsed']['info']['tokenAmount']['uiAmount']
        ];
    }
    
    return null;
}
?>