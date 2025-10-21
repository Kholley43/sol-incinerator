<?php
// Include protection script
require_once 'protect.php';

// Include simplified security script
require_once 'security.php';

// SOL Incinerator - Burn unwanted SPL token accounts and reclaim SOL
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0e17">
    <meta name="description" content="SOL Incinerator - Burn unwanted SPL token accounts and reclaim SOL">
    <title>SOL Incinerator - ScorpTech</title>
    <link rel="icon" href="favicon.php" type="image/png">
    <script src="https://unpkg.com/@solana/web3.js@1.73.0/lib/index.iife.js"></script>
    <script src="https://unpkg.com/@solana/wallet-adapter-base@0.9.20/lib/index.iife.js"></script>
    <script src="https://unpkg.com/@solana/wallet-adapter-wallets@0.19.10/lib/index.iife.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        :root {
            --bg-color: #0a0e17;
            --text-color: #e0e0e0;
            --accent-color: #3a86ff;
            --accent-gradient: linear-gradient(135deg, #3a86ff, #00c6ff);
            --danger-gradient: linear-gradient(135deg, #ff4500, #ff8700);
            --panel-color: #111827;
            --panel-gradient: linear-gradient(to bottom, #131b2e, #0f1623);
            --border-color: rgba(255, 255, 255, 0.1);
            --danger-color: #ff4500;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(180deg, #0a0e17 0%, #1a1e2e 100%);
            color: var(--text-color);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px 20px;
            background: var(--panel-gradient);
            border-radius: 24px;
            border: 1px solid var(--border-color);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--danger-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #94a3b8;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .panel {
            background: var(--panel-gradient);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        h2 {
            color: var(--accent-color);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        input[type="text"], select {
            width: 100%;
            padding: 14px 18px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-color);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus, select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.1);
        }
        
        button {
            background: var(--accent-gradient);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
            width: 100%;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(58, 134, 255, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button.danger {
            background: var(--danger-gradient);
            box-shadow: 0 4px 12px rgba(255, 69, 0, 0.3);
        }
        
        button.danger:hover {
            box-shadow: 0 6px 20px rgba(255, 69, 0, 0.4);
        }
        
        button.success {
            background: linear-gradient(135deg, #10b981, #34d399);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        button.success:hover {
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .progress {
            margin: 15px 0;
            padding: 12px;
            background: rgba(58, 134, 255, 0.1);
            border-radius: 10px;
            color: var(--accent-color);
            font-weight: 500;
            text-align: center;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #output {
            white-space: pre-wrap;
            background: rgba(15, 23, 42, 0.8);
            padding: 20px;
            border-radius: 16px;
            margin-top: 20px;
            min-height: 400px;
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        #output::-webkit-scrollbar {
            width: 8px;
        }
        
        #output::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        
        #output::-webkit-scrollbar-thumb {
            background: var(--accent-color);
            border-radius: 4px;
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
        
        .info {
            color: #60a5fa;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: rgba(58, 134, 255, 0.1);
            color: var(--accent-color);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: rgba(58, 134, 255, 0.05);
        }
        
        .account-row {
            transition: background 0.2s ease;
        }
        
        .account-addr {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #94a3b8;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 0.85rem;
            width: auto;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--panel-gradient);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid var(--border-color);
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translate(-50%, -40%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        
        .modal h2 {
            margin-bottom: 20px;
        }
        
        .modal p {
            margin-bottom: 15px;
            line-height: 1.6;
            color: #cbd5e1;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        .modal-buttons button {
            flex: 1;
        }
        
        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .loader {
            border: 3px solid rgba(58, 134, 255, 0.1);
            border-top: 3px solid var(--accent-color);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .tab {
            padding: 12px 24px;
            background: transparent;
            color: #64748b;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            box-shadow: none;
        }
        
        .tab:hover {
            color: var(--accent-color);
            transform: none;
            box-shadow: none;
        }
        
        .tab.active {
            color: var(--accent-color);
            border-bottom-color: var(--accent-color);
            box-shadow: none;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .wallet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .wallet-btn {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid var(--border-color);
            border-radius: 16px;
            transition: all 0.3s ease;
        }
        
        .wallet-btn:hover {
            border-color: var(--accent-color);
            background: rgba(58, 134, 255, 0.1);
            transform: translateY(-4px);
        }
        
        .wallet-logo {
            width: 48px;
            height: 48px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔥 SOL Incinerator</h1>
            <p class="subtitle">Close empty token accounts & reclaim SOL</p>
        </header>
        
        <!-- Disclaimer Modal -->
        <div id="disclaimerModal" class="modal" style="display: flex;">
            <div class="modal-content">
                <h2>⚠️ Important Warning</h2>
                <p>This tool closes SPL token accounts to reclaim rent-exempt SOL (~0.002 SOL per account).</p>
                <p><strong>Warning:</strong> Accounts with token balances will be skipped. Only empty accounts will be closed.</p>
                <p>Always verify transactions before approving in your wallet.</p>
                <div class="modal-buttons">
                    <button onclick="acceptDisclaimer()">I Understand</button>
                </div>
            </div>
        </div>
        
        <div class="panel">
            <h2>📋 Check Your Wallet</h2>
            <p style="margin-bottom: 20px; color: #94a3b8;">Enter your Solana wallet address to check for burnable accounts:</p>
            <input type="text" id="walletAddress" placeholder="Enter Solana wallet address (e.g., 36193q8fQ6MoJp...)" value="">
            <button onclick="checkWallet()">Check for Burnable Accounts</button>
            <div class="progress" id="walletProgress" style="display: none;"></div>
        </div>
        
        <div class="panel">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('browser')">🔌 Browser Wallets</button>
                <button class="tab" onclick="switchTab('privatekey')">🔑 Private Key</button>
                <button class="tab" onclick="switchTab('settings')">⚙️ Settings</button>
            </div>
            
            <div id="browser-tab" class="tab-content active">
                <p style="margin-bottom: 20px; color: #94a3b8;">Connect your browser wallet to close accounts:</p>
                <div class="wallet-grid">
                    <button class="wallet-btn" onclick="connectWallet('phantom')">
                        <svg class="wallet-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" fill="none">
                            <rect width="128" height="128" rx="24" fill="url(#phantom-gradient)"/>
                            <path d="M100 60C100 38.9 83.1 22 62 22C40.9 22 24 38.9 24 60C24 81.1 40.9 98 62 98C65.3 98 68.5 97.5 71.5 96.6C72.8 96.2 73.6 94.8 73.2 93.5C72.8 92.2 71.4 91.4 70.1 91.8C67.5 92.6 64.8 93 62 93C43.8 93 29 78.2 29 60C29 41.8 43.8 27 62 27C80.2 27 95 41.8 95 60C95 65.5 93.6 70.7 91.2 75.3C90.5 76.5 90.9 78 92.1 78.7C93.3 79.4 94.8 79 95.5 77.8C98.3 72.5 100 66.5 100 60Z" fill="white"/>
                            <circle cx="52" cy="55" r="6" fill="white"/>
                            <circle cx="72" cy="55" r="6" fill="white"/>
                            <defs>
                                <linearGradient id="phantom-gradient" x1="0" y1="0" x2="128" y2="128">
                                    <stop stop-color="#534bb1"/>
                                    <stop offset="1" stop-color="#551bf9"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <span>Phantom</span>
                    </button>
                    <button class="wallet-btn" onclick="connectWallet('solflare')">
                        <svg class="wallet-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" fill="none">
                            <rect width="128" height="128" rx="24" fill="#FC802A"/>
                            <circle cx="64" cy="64" r="32" fill="white"/>
                            <path d="M64 40 L80 64 L64 88 L48 64 Z" fill="#FC802A"/>
                        </svg>
                        <span>Solflare</span>
                    </button>
                    <button class="wallet-btn" onclick="connectWallet('backpack')">
                        <svg class="wallet-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" fill="none">
                            <rect width="128" height="128" rx="24" fill="#000"/>
                            <rect x="30" y="40" width="68" height="60" rx="8" fill="#E33E3F"/>
                            <rect x="50" y="30" width="28" height="12" rx="6" fill="#E33E3F"/>
                            <circle cx="64" cy="70" r="12" fill="white"/>
                        </svg>
                        <span>Backpack</span>
                    </button>
                </div>
                <div id="walletStatus" style="margin-top: 20px; text-align: center; color: #94a3b8;"></div>
            </div>
            
            <div id="privatekey-tab" class="tab-content">
                <p style="margin-bottom: 20px; color: #94a3b8;">Import a private key (base58 format):</p>
                <input type="text" id="privateKey" placeholder="Paste private key here">
                <button onclick="connectPrivateKey()">Import Private Key</button>
                <p style="margin-top: 15px; color: #f87171; font-size: 0.9rem;">⚠️ Only use on a trusted device. Never share your private key.</p>
            </div>
            
            <div id="settings-tab" class="tab-content">
                <h3 style="color: var(--accent-color); margin-bottom: 15px;">RPC Endpoint</h3>
                <select id="rpcEndpoint">
                    <option value="auto" selected>Auto (Fallback Mode)</option>
                    <option value="solana">Solana (Official)</option>
                    <option value="alchemy">Alchemy</option>
                    <option value="tracker">Solana Tracker</option>
                    <option value="quicknode">QuickNode</option>
                    <option value="serum">Project Serum</option>
                    <option value="custom">Custom RPC</option>
                </select>
                <input type="text" id="customRpc" placeholder="Enter custom RPC URL" style="display: none;">
                <button onclick="testRpcEndpoint()">Test Connection</button>
                <div class="progress" id="rpcProgress" style="display: none;"></div>
            </div>
        </div>
        
        <div id="output" class="panel">
            <p style="color: #64748b; text-align: center;">Results will appear here...</p>
        </div>
        
        <footer>
            <p>ScorpTech © 2024 | Use at your own risk</p>
        </footer>
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
        
        let currentWallet = null;
        let currentAdapter = null;
        let foundAccounts = [];
        
        // Accept disclaimer
        function acceptDisclaimer() {
            document.getElementById('disclaimerModal').style.display = 'none';
        }
        
        // Switch tabs
        function switchTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Show selected tab
            if (tab === 'browser') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('browser-tab').classList.add('active');
            } else if (tab === 'privatekey') {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('privatekey-tab').classList.add('active');
            } else if (tab === 'settings') {
                document.querySelectorAll('.tab')[2].classList.add('active');
                document.getElementById('settings-tab').classList.add('active');
            }
        }
        
        // Show/hide custom RPC input
        document.getElementById('rpcEndpoint').addEventListener('change', function() {
            const customRpcInput = document.getElementById('customRpc');
            if (this.value === 'custom') {
                customRpcInput.style.display = 'block';
            } else {
                customRpcInput.style.display = 'none';
            }
        });
        
        // Log to output
        function log(message, className) {
            const output = document.getElementById('output');
            if (output.innerHTML.includes('Results will appear here')) {
                output.innerHTML = '';
            }
            
            const line = document.createElement('div');
            line.textContent = message;
            if (className) {
                line.className = className;
            }
            output.appendChild(line);
            output.scrollTop = output.scrollHeight;
        }
        
        // Update progress
        function updateProgress(elementId, message, show = true) {
            const el = document.getElementById(elementId);
            if (show) {
                el.textContent = message;
                el.style.display = 'flex';
            } else {
                el.style.display = 'none';
            }
        }
        
        // Check wallet for burnable accounts
        async function checkWallet() {
            const walletAddress = document.getElementById('walletAddress').value.trim();
            if (!walletAddress) {
                alert('Please enter a wallet address');
                return;
            }
            
            log('🔍 Testing wallet: ' + walletAddress, 'info');
            updateProgress('walletProgress', 'Checking wallet...');
            
            // Validate address format
            if (walletAddress.length !== 44 && walletAddress.length !== 43) {
                log('❌ Invalid wallet address format', 'error');
                updateProgress('walletProgress', 'Invalid address', false);
                return;
            }
            
            // Try each RPC endpoint
            for (const [name, url] of Object.entries(RPC_ENDPOINTS)) {
                try {
                    log('Trying ' + name + ' endpoint...', 'info');
                    
                    // Use proxy to avoid CORS
                    const proxyUrl = 'proxy_rpc.php?endpoint=' + name;
                    
                    // Test basic connection
                    const blockResponse = await fetch(proxyUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            jsonrpc: '2.0',
                            id: 1,
                            method: 'getBlockHeight',
                            params: []
                        })
                    });
                    
                    const blockData = await blockResponse.json();
                    
                    if (blockData.result !== undefined) {
                        log('✅ Connected! Block height: ' + blockData.result, 'success');
                        log('Checking SPL token accounts...', 'info');
                        
                        // Get SPL token accounts
                        const accountsResponse = await fetch(proxyUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
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
                            foundAccounts = [];
                            
                            for (const account of accounts) {
                                try {
                                    const accountInfo = account.account.data.parsed.info;
                                    const amount = accountInfo.tokenAmount.uiAmount;
                                    
                                    if (amount === 0) {
                                        burnableCount++;
                                        foundAccounts.push(account.pubkey);
                                        log('🔥 Burnable: ' + account.pubkey + ' (0 balance)', 'success');
                                    }
                                } catch (e) {
                                    console.error('Error processing account:', e);
                                }
                            }
                            
                            log('✅ Found ' + burnableCount + ' burnable accounts out of ' + accounts.length + ' total', 'success');
                            log('💰 Potential SOL to reclaim: ~' + (burnableCount * 0.00203928).toFixed(6) + ' SOL ($' + (burnableCount * 0.00203928 * 230).toFixed(2) + ')', 'success');
                            updateProgress('walletProgress', 'Check complete! Found ' + burnableCount + ' burnable accounts');
                            
                            if (burnableCount > 0) {
                                log('\n💡 Connect your wallet to close these accounts', 'warning');
                            }
                            
                            // Success, stop trying other endpoints
                            return;
                        } else {
                            log('❌ Error getting accounts: ' + JSON.stringify(accountsData.error), 'error');
                        }
                    } else {
                        log('❌ Connection failed: ' + JSON.stringify(blockData.error), 'error');
                    }
                } catch (e) {
                    log('❌ Error: ' + e.message, 'error');
                }
            }
            
            // If we get here, all endpoints failed
            log('❌ Failed to connect to any RPC endpoint', 'error');
            updateProgress('walletProgress', 'All endpoints failed');
        }
        
        // Test RPC endpoint
        async function testRpcEndpoint() {
            const selectedRpc = document.getElementById('rpcEndpoint').value;
            
            if (selectedRpc === 'custom') {
                const customUrl = document.getElementById('customRpc').value.trim();
                if (!customUrl) {
                    alert('Please enter a custom RPC URL');
                    return;
                }
                log('🔍 Testing custom RPC: ' + customUrl, 'info');
                updateProgress('rpcProgress', 'Testing custom RPC...');
                // Test custom RPC directly (no proxy)
                try {
                    const response = await fetch(customUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            jsonrpc: '2.0',
                            id: 1,
                            method: 'getBlockHeight',
                            params: []
                        })
                    });
                    const data = await response.json();
                    if (data.result !== undefined) {
                        log('✅ Custom RPC working! Block height: ' + data.result, 'success');
                        updateProgress('rpcProgress', 'Custom RPC working');
                    } else {
                        log('❌ Custom RPC failed: ' + JSON.stringify(data.error), 'error');
                        updateProgress('rpcProgress', 'Custom RPC failed');
                    }
                } catch (e) {
                    log('❌ Error: ' + e.message, 'error');
                    updateProgress('rpcProgress', 'Test failed');
                }
                return;
            }
            
            const url = RPC_ENDPOINTS[selectedRpc] || RPC_ENDPOINTS['solana'];
            log('🔍 Testing ' + selectedRpc + ' endpoint', 'info');
            updateProgress('rpcProgress', 'Testing ' + selectedRpc + '...');
            
            try {
                // Use proxy
                const proxyUrl = 'proxy_rpc.php?endpoint=' + selectedRpc;
                
                const response = await fetch(proxyUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        jsonrpc: '2.0',
                        id: 1,
                        method: 'getBlockHeight',
                        params: []
                    })
                });
                
                const data = await response.json();
                
                if (data.result !== undefined) {
                    log('✅ ' + selectedRpc + ' working! Block height: ' + data.result, 'success');
                    updateProgress('rpcProgress', selectedRpc + ' working correctly');
                } else {
                    log('❌ ' + selectedRpc + ' failed: ' + JSON.stringify(data.error), 'error');
                    updateProgress('rpcProgress', selectedRpc + ' failed');
                }
            } catch (e) {
                log('❌ Error: ' + e.message, 'error');
                updateProgress('rpcProgress', 'Test failed');
            }
        }
        
        // Connect wallet (placeholder - wallet adapter integration needed)
        function connectWallet(walletType) {
            log('🔌 Connecting to ' + walletType + '...', 'info');
            document.getElementById('walletStatus').innerHTML = '<span class="warning">Wallet adapter integration coming soon...</span>';
            log('⚠️ Wallet adapter integration in progress', 'warning');
        }
        
        // Connect private key (placeholder)
        function connectPrivateKey() {
            const privateKey = document.getElementById('privateKey').value.trim();
            if (!privateKey) {
                alert('Please enter a private key');
                return;
            }
            log('🔑 Importing private key...', 'info');
            log('⚠️ Private key import functionality in progress', 'warning');
        }
    </script>
</body>
</html>

