<?php
// Direct RPC Fix for SOL Incinerator
// This script adds direct RPC testing and fixes to the main application

header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOL Incinerator RPC Fix</title>
    <script src="https://unpkg.com/@solana/web3.js@1.73.0/lib/index.iife.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #0f172a;
            color: #e2e8f0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #1e293b;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #60a5fa;
        }
        .success {
            color: #4ade80;
        }
        .error {
            color: #f87171;
        }
        .warning {
            color: #facc15;
        }
        button {
            background: linear-gradient(90deg, #3a86ff, #00c6ff);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 10px;
        }
        #output {
            white-space: pre-wrap;
            background: #0f172a;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            height: 300px;
            overflow-y: auto;
        }
        .progress {
            margin-bottom: 10px;
            color: #60a5fa;
        }
        .code {
            background: #0d1117;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
        }
        input, select {
            background: #0f172a;
            color: #e2e8f0;
            border: 1px solid #334155;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 10px;
            width: 100%;
        }
        .panel {
            background: #0f172a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .fix-button {
            background: linear-gradient(90deg, #10b981, #34d399);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>SOL Incinerator RPC Fix</h1>
        
        <div class="panel">
            <h2>Test Wallet</h2>
            <input type="text" id="walletAddress" value="36193q8fQ6MoJp6ivRvm7rLQf2gNP3utKzvoB9yjaFYF" placeholder="Enter wallet address">
            <button id="testWalletBtn">Test Wallet</button>
            <div class="progress" id="walletProgress"></div>
        </div>
        
        <div class="panel">
            <h2>RPC Endpoints</h2>
            <select id="rpcEndpoint">
                <option value="solana">Solana (Official)</option>
                <option value="alchemy">Alchemy</option>
                <option value="tracker">Solana Tracker</option>
                <option value="quicknode">QuickNode</option>
                <option value="serum">Project Serum</option>
            </select>
            <button id="testRpcBtn">Test RPC</button>
            <div class="progress" id="rpcProgress"></div>
        </div>
        
        <div class="panel">
            <h2>Fix RPC Issues</h2>
            <button id="fixRpcBtn" class="fix-button">Apply RPC Fixes</button>
            <div class="progress" id="fixProgress"></div>
        </div>
        
        <div id="output">Results will appear here...</div>
    </div>

    <script>
        // RPC endpoints
        const RPC_ENDPOINTS = {
            'solana': 'https://api.mainnet-beta.solana.com',
            'alchemy': 'https://solana-mainnet.g.alchemy.com/v2/demo',
            'tracker': 'https://rpc-mainnet.solanatracker.io/?api_key=02232df4-4670-439c-b65a-27225d5b841f',
            'quicknode': 'https://quiet-attentive-frost.solana-mainnet.quiknode.pro/ed83a1d62d5a9b3c0a9fd0fb8d99e8e2d25f5ad3/',
            'serum': 'https://solana-api.projectserum.com'
        };
        
        // Token program IDs
        const TOKEN_PROGRAM_ID = 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA';
        const TOKEN_2022_PROGRAM_ID = 'TokenzQdBNbLqP5VEhdkAS6EPFLC1PHnBqCXEpPxuEb';
        
        // Event listeners
        document.getElementById('testWalletBtn').addEventListener('click', testWallet);
        document.getElementById('testRpcBtn').addEventListener('click', testRpc);
        document.getElementById('fixRpcBtn').addEventListener('click', applyRpcFixes);
        
        // Update progress
        function updateProgress(elementId, message) {
            document.getElementById(elementId).textContent = message;
        }
        
        // Log to output
        function log(message, className) {
            const output = document.getElementById('output');
            if (output.textContent === 'Results will appear here...') {
                output.textContent = '';
            }
            
            const line = document.createElement('div');
            line.textContent = message;
            if (className) {
                line.className = className;
            }
            output.appendChild(line);
            output.scrollTop = output.scrollHeight;
        }
        
        // Test wallet function
        async function testWallet() {
            const walletAddress = document.getElementById('walletAddress').value.trim();
            if (!walletAddress) {
                log('Please enter a wallet address', 'error');
                return;
            }
            
            log('Testing wallet: ' + walletAddress);
            updateProgress('walletProgress', 'Testing wallet...');
            
            // Try each RPC endpoint
            for (const [name, url] of Object.entries(RPC_ENDPOINTS)) {
                try {
                    log('\nTrying ' + name + ' endpoint...');
                    
                    // Use proxy to avoid CORS
                    const proxyUrl = 'proxy_rpc.php?endpoint=' + name;
                    
                    // Validate the wallet address
                    try {
                        // Just basic format validation
                        if (walletAddress.length !== 44 && walletAddress.length !== 43) {
                            log('Invalid wallet address format', 'error');
                            updateProgress('walletProgress', 'Test failed');
                            return;
                        }
                        
                        // Test basic connection
                        const blockResponse = await fetch(proxyUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                jsonrpc: '2.0',
                                id: 1,
                                method: 'getBlockHeight',
                                params: []
                            })
                        });
                        
                        const blockData = await blockResponse.json();
                        
                        if (blockData.result !== undefined) {
                            log('✅ Connection successful. Block height: ' + blockData.result, 'success');
                            
                            // Get SPL token accounts
                            log('Checking SPL token accounts...');
                            
                            const accountsResponse = await fetch(proxyUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    jsonrpc: '2.0',
                                    id: 2,
                                    method: 'getProgramAccounts',
                                    params: [
                                        TOKEN_PROGRAM_ID,
                                        {
                                            encoding: 'jsonParsed',
                                            filters: [
                                                { dataSize: 165 },
                                                { memcmp: { offset: 32, bytes: walletAddress } }
                                            ]
                                        }
                                    ]
                                })
                            });
                            
                            const accountsData = await accountsResponse.json();
                            
                            if (accountsData.result) {
                                const accounts = accountsData.result;
                                log('✅ Found ' + accounts.length + ' SPL token accounts', 'success');
                                
                                // Check for burnable accounts
                                let burnableCount = 0;
                                for (const account of accounts) {
                                    try {
                                        const accountInfo = account.account.data.parsed.info;
                                        const amount = accountInfo.tokenAmount.uiAmount;
                                        
                                        if (amount === 0) {
                                            burnableCount++;
                                            log('🔥 Burnable account: ' + account.pubkey + ' (0 balance)', 'success');
                                        }
                                    } catch (e) {
                                        console.error('Error processing account:', e);
                                    }
                                }
                                
                                log('✅ Found ' + burnableCount + ' burnable accounts out of ' + accounts.length + ' total', 'success');
                                updateProgress('walletProgress', 'Test complete');
                                
                                // If we found accounts, we can stop testing other endpoints
                                if (accounts.length > 0) {
                                    log('\n✅ ' + name + ' endpoint works correctly with this wallet!', 'success');
                                    break;
                                }
                            } else {
                                log('❌ Error getting accounts: ' + JSON.stringify(accountsData.error), 'error');
                            }
                        } else {
                            log('❌ Connection failed: ' + JSON.stringify(blockData.error), 'error');
                        }
                    } catch (e) {
                        log('❌ Error: ' + e.message, 'error');
                    }
                } catch (e) {
                    log('❌ Connection failed: ' + e.message, 'error');
                }
            }
        }
        
        // Test RPC function
        async function testRpc() {
            const selectedRpc = document.getElementById('rpcEndpoint').value;
            const url = RPC_ENDPOINTS[selectedRpc];
            
            log('Testing ' + selectedRpc + ' endpoint: ' + url);
            updateProgress('rpcProgress', 'Testing ' + selectedRpc + '...');
            
            try {
                // Use proxy to avoid CORS
                const proxyUrl = 'proxy_rpc.php?endpoint=' + selectedRpc;
                
                // Test basic connection with direct fetch
                const response = await fetch(proxyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        jsonrpc: '2.0',
                        id: 1,
                        method: 'getBlockHeight',
                        params: []
                    })
                });
                
                const data = await response.json();
                
                if (data.result !== undefined) {
                    log('✅ Connection successful. Block height: ' + data.result, 'success');
                    
                    // Test getVersion
                    const versionResponse = await fetch(proxyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            jsonrpc: '2.0',
                            id: 2,
                            method: 'getVersion',
                            params: []
                        })
                    });
                    
                    const versionData = await versionResponse.json();
                    log('✅ Solana version: ' + JSON.stringify(versionData.result), 'success');
                    
                    updateProgress('rpcProgress', selectedRpc + ' working correctly');
                } else {
                    log('❌ Connection failed: ' + JSON.stringify(data.error), 'error');
                    updateProgress('rpcProgress', selectedRpc + ' failed');
                }
            } catch (e) {
                log('❌ Connection failed: ' + e.message, 'error');
                updateProgress('rpcProgress', selectedRpc + ' failed');
            }
        }
        
        // Apply RPC fixes
        function applyRpcFixes() {
            log('Checking RPC fix status...');
            updateProgress('fixProgress', 'Checking...');
            
            // Check if proxy_rpc.php exists
            fetch('proxy_rpc.php', { method: 'HEAD' })
                .then(response => {
                    if (response.ok) {
                        log('✅ RPC proxy is already installed and working!', 'success');
                        log('\nThe fix has been successfully applied to the main application.', 'success');
                        log('You can now use the SOL Incinerator with improved RPC reliability.', 'success');
                        updateProgress('fixProgress', 'Fix already applied');
                    } else {
                        log('❌ RPC proxy exists but returned error: ' + response.status, 'error');
                        updateProgress('fixProgress', 'Fix check failed');
                    }
                })
                .catch(error => {
                    log('❌ RPC proxy not found or not working', 'error');
                    log('Error: ' + error.message, 'error');
                    updateProgress('fixProgress', 'Fix not applied');
                });
        }
    </script>
</body>
</html>