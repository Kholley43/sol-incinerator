<?php
// Include protection script
require_once 'protect.php';

// Include simplified security script
require_once 'security.php';

// Remove any existing CSP and set relaxed one (includes price.jup.ag)
if (function_exists('header_remove')) {
    header_remove('Content-Security-Policy');
}
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.jsdelivr.net; connect-src 'self' https://unpkg.com https://cdn.jsdelivr.net https://price.jup.ag; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:");

// SOL Incinerator - Burn unwanted SPL token accounts and reclaim SOL
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0e17">
    <!-- Relaxed CSP to allow required CDNs -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.jsdelivr.net; connect-src 'self' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:;">
    <meta name="description" content="SOL Incinerator - Burn unwanted SPL token accounts and reclaim SOL">
    <title>SOL Incinerator - ScorpTech</title>
    <link rel="icon" href="favicon.php" type="image/png">
    <script src="https://unpkg.com/@solana/web3.js@1.73.0/lib/index.iife.js"></script>
    <!-- Solana Mobile Wallet Adapter (deep-link) -->
    <script src="https://unpkg.com/@solana-mobile/wallet-adapter-mobile@latest/lib/index.iife.js"></script>
    <script>
        if(!window.Buffer) window.Buffer = {};
        if(typeof window.Buffer.from!=='function')  window.Buffer.from  = (arr)=>Uint8Array.from(arr);
        if(typeof window.Buffer.alloc!=='function') window.Buffer.alloc = (len)=>new Uint8Array(len);
    </script>
    <!-- spl-token will be loaded dynamically when required -->
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
            background: #0c0f16;
            padding: 20px;
            border-radius: 16px;
            margin-top: 20px;
            min-height: 400px;
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            pointer-events:none;
            position:relative;
            z-index:1;
        }
        #output:hover{pointer-events:auto;}
        .log-line { display:flex; gap:6px; align-items:center; }
        .log-icon { width:12px; }
        .info   { color: #60a5fa; }
        .success{ color: #4ade80; }
        .warning{ color: #facc15; }
        .error  { color: #f87171; }
        
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
            z-index: 10000; /* above results */
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
        #results {
            position:relative;
            z-index:9999;
            margin-top:25px;
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
                <p>This tool closes empty SPL token accounts to reclaim the rent-exempt SOL they lock (≈ 0.002 SOL each).</p>
                <p><strong>Service fee:</strong> 25 % of every rent refund is sent to the developer wallet <em>inside the same transaction</em>. You pay nothing if no accounts are closed.</p>
                <p><strong>Note:</strong> Accounts that still hold tokens are never closed automatically — review carefully before confirming.</p>
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
                    <button class="wallet-btn" onclick="connectWallet('kraken')">
                        <svg class="wallet-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" fill="none">
                            <rect width="128" height="128" rx="24" fill="#7255FF"/>
                            <path d="M64 34c-19.9 0-30 11.6-30 32v28h12V66c0-13 6-19 18-19s18 6 18 19v28h12V66c0-20.4-10.1-32-30-32z" fill="white"/>
                        </svg>
                        <span>Kraken</span>
                    </button>
                    <button class="wallet-btn" onclick="connectWallet('jupiter')">
                        <svg class="wallet-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" fill="none">
                            <rect width="128" height="128" rx="24" fill="#45C4B0"/>
                            <circle cx="64" cy="64" r="32" fill="white"/>
                            <path d="M64 40v48" stroke="#45C4B0" stroke-width="8" stroke-linecap="round"/>
                            <path d="M88 64H40" stroke="#45C4B0" stroke-width="8" stroke-linecap="round"/>
                        </svg>
                        <span>Jupiter</span>
                    </button>
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
                    <button class="wallet-btn" onclick="connectMobile()">
                        <svg class="wallet-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" fill="none">
                            <rect width="128" height="128" rx="24" fill="#34d399"/>
                            <path d="M64 32l32 32H80v32H48V64H32L64 32z" fill="white"/>
                        </svg>
                        <span>Mobile&nbsp;Wallet</span>
                    </button>
                </div>
                <div style="margin-top:20px;text-align:center;">
                    <button class="danger" style="width:auto;padding:12px 24px;" title="Can't find your wallet? Click to view all detected wallets and connect." onclick="openWalletPicker()">🔍 More Wallets…</button>
                </div>
                <div id="walletPickerModal" class="modal">
                    <div class="modal-content">
                        <h2>Select a Wallet</h2>
                        <p id="walletPickerSubtitle" style="color:#94a3b8;margin-top:10px;"></p>
                        <div id="walletPickerList" style="display:flex;flex-direction:column;gap:12px;margin-top:20px;"></div>
                        <div class="modal-buttons" style="margin-top:30px;">
                            <button onclick="closeWalletPicker()">Cancel</button>
                        </div>
                    </div>
                </div>
                <!-- Close All Confirmation Modal -->
                <div id="closeAllModal" class="modal">
                    <div class="modal-content">
                        <h2>Confirm Close Accounts</h2>
                        <p id="closeAllMessage" style="color:#cbd5e1;line-height:1.6;"></p>
                        <div class="modal-buttons" style="margin-top:25px;">
                            <button class="danger" onclick="closeAllConfirm(true)">Yes, Close</button>
                            <button onclick="closeAllConfirm(false)">Cancel</button>
                        </div>
                    </div>
                </div>
                <!-- Service Fee Summary Modal -->
                <div id="feeModal" class="modal">
                    <div class="modal-content">
                        <h2>Service Fee</h2>
                        <p id="feeSummary" style="color:#cbd5e1;line-height:1.6;"></p>
                        <div class="modal-buttons" style="margin-top:25px;">
                            <button class="danger" onclick="runBatchClose()">Proceed</button>
                            <button onclick="feeConfirm(false)">Cancel</button>
                        </div>
                    </div>
                </div>
                <!-- Burn & Close Modal -->
                <div id="burnModal" class="modal">
                    <div class="modal-content">
                        <h2 style="color:var(--danger-color)">🔥 Burn & Close Account</h2>
                        <p id="burnInfo" style="color:#cbd5e1;margin-top:10px"></p>
                        <p style="margin-top:15px;color:#f87171;font-size:0.9rem;">Type the token symbol below to confirm you want to burn these tokens and close the account. This action is irreversible.</p>
                        <input type="text" id="burnConfirmInput" placeholder="Enter token symbol to enable" style="margin-top:10px;width:100%;padding:12px;border-radius:8px;border:1px solid var(--border-color);background:rgba(15,23,42,0.8);color:var(--text-color);">
                        <div class="modal-buttons" style="margin-top:25px;">
                            <button id="burnConfirmBtn" class="danger" disabled>Burn & Close</button>
                            <button onclick="closeBurnModal()">Cancel</button>
                        </div>
                    </div>
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
        
        <div style="display:flex;align-items:center;gap:8px;margin-top:10px;">
            <label style="color:#94a3b8;font-size:0.85rem;">
                <input type="checkbox" id="autoScrollToggle" checked style="margin-right:6px;"> Autoscroll
            </label>
        </div>
        <div id="output" class="panel">
            <p style="color: #64748b; text-align: center;">Results will appear here...</p>
        </div>

        <div id="results" style="margin-top:20px;"></div>
        
        <footer>
            <p>ScorpTech © 2025 | Use at your own risk</p>
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
        
        // =============== Token List ===============
        const TOKEN_LIST_PRIMARY = 'https://cdn.jsdelivr.net/gh/solana-labs/token-list@main/src/tokens/solana.tokenlist.json';
        const TOKEN_LIST_FALLBACK = 'https://raw.githubusercontent.com/solana-labs/token-list/main/src/tokens/solana.tokenlist.json';
        let TOKEN_MAP = null; // mint -> {symbol, name}
        const CLOSED_SET = new Set(); // token accounts recently closed this session
        async function loadTokenList() {
            if (TOKEN_MAP) return;
            try {
                let resp = await fetch(TOKEN_LIST_PRIMARY);
                if (!resp.ok) {
                    resp = await fetch(TOKEN_LIST_FALLBACK);
                }
                const data = await resp.json();
                TOKEN_MAP = {};
                data.tokens.forEach(t => { TOKEN_MAP[t.address] = { symbol: t.symbol, name: t.name }; });
                console.log('Token list loaded:', Object.keys(TOKEN_MAP).length);
            } catch (e) {
                console.warn('Failed to load token list', e);
                TOKEN_MAP = {};
            }
        }
        
        let currentWallet = null;
        let currentAdapter = null;
        let foundAccounts = [];
        
        let PRICE_MAP = {}; // mint -> price
        let DECIMALS_MAP = {}; // mint -> decimals (filled during account scan)

        async function fetchPricesJupiter(mints){
            if(mints.length===0) return;
            // Always ask for SOL so we can convert quote output
            const SOL_MINT = 'So11111111111111111111111111111111111111112';
            if(!mints.includes(SOL_MINT)) mints.push(SOL_MINT);

            // 1) regular batch price endpoint
            const chunks = [];
            for(let i=0;i<mints.length;i+=100){chunks.push(mints.slice(i,i+100));}
            for(const chunk of chunks){
                const url = 'price_proxy.php?ids='+chunk.join(',');
                try{
                    const resp = await fetch(url);
                    const d = await resp.json();
                    if(d && d.data){
                        Object.keys(d.data).forEach(m=>{PRICE_MAP[m]=d.data[m].price;});
                    }
                }catch(e){console.warn('price fetch failed',e);}
            }

            // 2) fallback quote for missing prices
            const solPrice = PRICE_MAP[SOL_MINT]||0;
            const missing = mints.filter(m=>!PRICE_MAP[m]);
            for(const mint of missing){
                const decimals = DECIMALS_MAP[mint] ?? 0;
                const amount = BigInt(10)**BigInt(decimals); // 1 whole token
                const quoteUrl = `https://quote-api.jup.ag/v6/quote?inputMint=${mint}&outputMint=${SOL_MINT}&amount=${amount.toString()}`;
                try{
                    const resp = await fetch(quoteUrl);
                    const q = await resp.json();
                    if(Array.isArray(q.data) && q.data.length>0){
                        const outLamports = BigInt(q.data[0].outAmount);
                        const solAmount = Number(outLamports)/1e9;
                        if(solAmount>0 && solPrice>0){
                            PRICE_MAP[mint] = solAmount*solPrice; // token price in USD
                        }
                    }
                }catch(e){/* ignore */}
            }
        }
        
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
        
        // Display accounts in a table
        function displayAccountsTable() {
            const resultsDiv = document.getElementById('results');
            if ((emptyAccounts?.length||0)===0 && (dustAccounts?.length||0)===0) {
                resultsDiv.innerHTML = '';
                return;
            }
            
            // Create table
            let tableHtml = '<div style="margin-top: 30px;">';

            // Empty accounts section
            if (emptyAccounts.length > 0) {
                tableHtml += '<h3 style="color: var(--accent-color); margin-bottom: 10px;">🔥 Empty Accounts</h3>';
                tableHtml += '<table><thead><tr><th>Token Account</th><th>Rent (SOL)</th><th>Action</th></tr></thead><tbody>';
                emptyAccounts.forEach(acct=>{
                tableHtml += '<tr class="account-row">';
                    tableHtml += '<td class="account-addr">'+acct.address.substring(0,8)+'...'+acct.address.slice(-6)+'</td>';
                    tableHtml += '<td style="color:#4ade80;">+0.00203928</td>';
                    tableHtml += '<td><button class="btn-small danger" onclick="closeAccount(\''+acct.address+'\')">Close</button></td>';
                tableHtml += '</tr>';
                });
                tableHtml += '</tbody></table>';
                tableHtml += '<div style="margin-top:15px;"><button class="danger" style="width:100%;" onclick="closeAllAccounts()">Close All ('+emptyAccounts.length+')</button></div>';
            }

            // Dust accounts section
            if (dustAccounts.length > 0) {
                tableHtml += '<h3 style="color: var(--warning-color, #facc15); margin:20px 0 10px;">⚠️ Accounts With Balance (Dust)</h3>';
                tableHtml += '<table><thead><tr><th>Balance</th><th>Value (USD)</th><th>Token Account</th><th>Action</th></tr></thead><tbody>';
                dustAccounts.forEach(acct=>{
                    tableHtml += `
                        <tr class="account-row">
                            <td>${acct.balance}</td>
                            <td>${acct.usdValue && acct.usdValue>0 ? '$'+acct.usdValue : '— not listed —'}</td>
                            <td class="account-addr">${acct.address.substring(0,8)}...${acct.address.slice(-6)}</td>
                            <td><button class="btn-small danger" style="opacity:${acct.usdValue && parseFloat(acct.usdValue)>0?1:0.6};" title="${acct.usdValue && parseFloat(acct.usdValue)>0?'' :'Unknown price — confirm inside modal'}" onclick="openBurnModal('${acct.address}')">Burn & Close</button></td>
                        </tr>`;
                });
            tableHtml += '</tbody></table>';
            }

            tableHtml += '</div>';
            
            // Append to output
            const div = document.createElement('div');
            div.innerHTML = tableHtml;
            resultsDiv.appendChild(div);
        }
        
        // Check wallet for burnable accounts
        async function checkWallet() {
            const walletAddress = document.getElementById('walletAddress').value.trim();
            if (!walletAddress) {
                alert('Please enter a wallet address');
                return;
            }
            
            await loadTokenList();
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
                            emptyAccounts = [];
                            dustAccounts = [];
                            
                            const mintSet = new Set();
                            for (const account of accounts) {
                                try {
                                    const accountInfo = account.account.data.parsed.info;
                                    if(CLOSED_SET.has(account.pubkey)) continue; // skip recently closed rows
                                    const amount = accountInfo.tokenAmount.uiAmount;
                                    const mintMeta = TOKEN_MAP[accountInfo.mint] || {};
                                    
                                    mintSet.add(accountInfo.mint);
                                    DECIMALS_MAP[accountInfo.mint] = accountInfo.tokenAmount.decimals;
                                    const acctObj = { address: account.pubkey, mint: accountInfo.mint, balance: amount, rawAmount: accountInfo.tokenAmount.amount, decimals: accountInfo.tokenAmount.decimals, symbol: mintMeta.symbol || 'Unknown', name: mintMeta.name || 'Unknown', usdValue: 0 };
                                    if (amount === 0) {
                                        burnableCount++;
                                        emptyAccounts.push(acctObj);
                                        log('🔥 Burnable: ' + account.pubkey + ' (0 balance)', 'success');
                                    } else {
                                        dustAccounts.push(acctObj);
                                    }
                                } catch (e) {
                                    console.error('Error processing account:', e);
                                }
                            }

                            // Fetch USD prices via Jupiter
                            await fetchPricesJupiter(Array.from(mintSet));
                            // Attach usdValue
                            [...dustAccounts, ...emptyAccounts].forEach(a=>{
                                if(PRICE_MAP[a.mint]){
                                    const p = PRICE_MAP[a.mint];
                                    a.usdValue = (a.balance * p).toFixed(4);
                                }
                            });
                            
                            foundAccounts = emptyAccounts; // maintain backward compat
                            
                            log('✅ Found ' + burnableCount + ' burnable accounts out of ' + accounts.length + ' total', 'success');
                            log('💰 Potential SOL to reclaim: ~' + (burnableCount * 0.00203928).toFixed(6) + ' SOL ($' + (burnableCount * 0.00203928 * 230).toFixed(2) + ')', 'success');
                            updateProgress('walletProgress', 'Check complete! Found ' + burnableCount + ' burnable accounts');
                            
                            // Display accounts table if there are any empty or dust accounts
                            const totalFound = emptyAccounts.length + dustAccounts.length;
                            if (totalFound > 0 && currentWallet) {
                                displayAccountsTable();
                            } else if (totalFound > 0) {
                                log('\n💡 Connect your wallet to manage these accounts', 'warning');
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
                const proxyUrl = window.location.origin + '/proxy_rpc.php?endpoint=' + selectedRpc;
                
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
        
        // Get list of injected Solana providers (supports multiple wallets)
        function getInjectedSolanaProviders() {
            const list = [];
            if (window.solana) {
                // If wallet exposes providers array (wallet-standard)
                if (Array.isArray(window.solana.providers)) {
                    list.push(...window.solana.providers);
                }
                // Also include main window.solana itself if it advertises wallet name flag
                if (window.solana.isPhantom || window.solana.walletName) {
                    list.push(window.solana);
                }
            }
            // Fallback: brute-scan window for objects that look like Solana wallets
            Object.keys(window).forEach(key => {
                if (['solana','backpack','solflare'].includes(key)) return;
                try {
                    const obj = window[key];
                    if (obj && typeof obj === 'object') {
                        if (typeof obj.connect === 'function' && (typeof obj.signTransaction === 'function' || typeof obj.signAndSendTransaction === 'function')) {
                            if (!list.includes(obj)) {
                                obj.walletName = obj.walletName || key; // tag name
                                list.push(obj);
                            }
                        }
                    }
                } catch (e) { /* cross-origin frames etc */ }
            });
            // Wallet-standard API via navigator.wallets
            try {
                if (navigator.wallets && typeof navigator.wallets.get === 'function') {
                    const std = navigator.wallets.get();
                    if (std && typeof std[Symbol.iterator] === 'function') {
                        for (const w of std) {
                            if (!list.includes(w)) {
                                w.walletName = w.name || w.walletName || 'Standard Wallet';
                                // provide connect shim for uniformity
                                if (!w.connect && w.features?.connect?.connect) {
                                    w.connect = w.features.connect.connect;
                                }
                                if (!w.signAndSendTransaction && w.features?.['solana:signAndSendTransaction']) {
                                    w.signAndSendTransaction = w.features['solana:signAndSendTransaction'].signAndSendTransaction;
                                }
                                list.push(w);
                            }
                        }
                    }
                }
            } catch (e) {}
            return list;
        }
        
        // Connect wallet
        async function connectWallet(walletType) {
            log('🔌 Connecting to ' + walletType + '...', 'info');
            
            try {
                let adapter = null;
                
                if (walletType === 'kraken' && window.kraken) {
                    adapter = window.kraken;
                } else if (walletType === 'jupiter' && window.jupiter) {
                    adapter = window.jupiter;
                } else if (walletType === 'phantom' && window.solana && window.solana.isPhantom) {
                    adapter = window.solana;
                } else if (walletType === 'any') {
                    const providers = getInjectedSolanaProviders();
                    if (providers.length === 0) {
                        log('❌ No Solana wallet detected.', 'error');
                        return;
                    }
                    // Always ask user, even if only one provider
                    const names = providers.map((p,i)=>`${i+1}: ${(p.walletName)|| (p.isPhantom?'Phantom':'Wallet')}`).join('\n');
                    const choice = prompt('Select a wallet to connect:\n'+names+'\n(Enter number or Cancel)');
                    if (choice === null) { return; }
                    const idx = parseInt(choice,10)-1;
                    if (idx>=0 && idx<providers.length) {
                        adapter = providers[idx];
                        walletType = adapter.walletName || (adapter.isPhantom ? 'Phantom':'Injected Wallet');
                } else {
                        log('❌ Invalid selection', 'error');
                        return;
                    }
                }
                
                if (!adapter) {
                    log('❌ ' + walletType + ' wallet not found. Please install or unlock the extension.', 'error');
                    document.getElementById('walletStatus').innerHTML = '<span class="error">Wallet not detected.</span>';
                    return;
                }
                
                // Connect
                const response = await adapter.connect();
                currentWallet = adapter.publicKey.toString();
                currentAdapter = adapter;
                
                log('✅ Connected to ' + walletType + '!', 'success');
                log('📍 Wallet: ' + currentWallet, 'info');
                document.getElementById('walletStatus').innerHTML = '<span class="success">✅ Connected: ' + currentWallet.substring(0, 8) + '...' + currentWallet.substring(currentWallet.length - 6) + '</span> <button style="margin-left:10px;" class="btn-small" onclick="disconnectWallet()">Disconnect</button>';
                
                // Automatically load accounts for connected wallet
                document.getElementById('walletAddress').value = currentWallet;
                await checkWallet();
                
            } catch (e) {
                log('❌ Failed to connect: ' + e.message, 'error');
                document.getElementById('walletStatus').innerHTML = '<span class="error">Connection failed</span>';
            }
        }
        
        // Simple base58 decoder (avoids external dependency)
        const BASE58_ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        function base58Decode(s) {
            const bytes = [0];
            for (let i = 0; i < s.length; i++) {
                const c = s[i];
                const val = BASE58_ALPHABET.indexOf(c);
                if (val < 0) throw new Error('Invalid base58 character');
                for (let j = 0; j < bytes.length; j++) bytes[j] *= 58;
                bytes[0] += val;
                let carry = 0;
                for (let k = 0; k < bytes.length; ++k) {
                    bytes[k] += carry;
                    carry = bytes[k] >> 8;
                    bytes[k] &= 0xff;
                }
                while (carry) {
                    bytes.push(carry & 0xff);
                    carry >>= 8;
                }
            }
            // handle leading zeros
            for (let i = 0; s[i] === '1' && i < s.length - 1; i++) bytes.push(0);
            return Uint8Array.from(bytes.reverse());
        }
        
        // Connect private key
        async function connectPrivateKey() {
            const privateKeyStr = document.getElementById('privateKey').value.trim();
            if (!privateKeyStr) {
                alert('Please enter a private key');
                return;
            }
            
            try {
                log('🔑 Importing private key...', 'info');
                
                let secretKey;
                try {
                    secretKey = base58Decode(privateKeyStr);
                } catch (e) {
                    throw new Error('Invalid base58 private key');
                }
                const keypair = window.solanaWeb3.Keypair.fromSecretKey(secretKey);
                
                currentWallet = keypair.publicKey.toString();
                currentAdapter = { keypair: keypair, isKeypair: true };
                
                log('✅ Private key imported!', 'success');
                log('📍 Wallet: ' + currentWallet, 'info');
                
                // Automatically load accounts for this wallet
                document.getElementById('walletAddress').value = currentWallet;
                await checkWallet();
                
            } catch (e) {
                log('❌ Failed to import private key: ' + e.message, 'error');
            }
        }
        
        // Close a specific account
        async function closeAccount(accountAddress) {
            if (!currentWallet || !currentAdapter) {
                alert('Please connect your wallet first');
                return;
            }
            
            log('🔥 Closing account: ' + accountAddress, 'info');
            
            try {
                // Get the selected RPC endpoint
                const selectedRpc = document.getElementById('rpcEndpoint').value;
                let rpcUrl;
                
                if (selectedRpc === 'custom') {
                    rpcUrl = document.getElementById('customRpc').value.trim();
                } else {
                    // Route through same-origin proxy to avoid CORS
                    const name = selectedRpc === 'auto' ? 'solana' : selectedRpc;
                    rpcUrl = window.location.origin + '/proxy_rpc.php?endpoint=' + name;
                }
                
                // For wallet adapters, we need to use direct RPC (they handle signing)
                const connection = new window.solanaWeb3.Connection(rpcUrl, { commitment: 'confirmed', wsEndpoint: '' });
                // suppress internal websocket attempts
                connection._rpcWebSocket = { connect() {}, close() {}, isConnected() { return false; } };
                
                const accountPubkey = new window.solanaWeb3.PublicKey(accountAddress);
                const ownerPubkey = new window.solanaWeb3.PublicKey(currentWallet);
                
                // Get current lamports in the token account (rent refund)
                const info = await connection.getAccountInfo(accountPubkey);
                const rentLamports = info ? BigInt(info.lamports) : 2039280n; // fallback standard size
                const rawFee = Number(rentLamports * BigInt(FEE_BPS) / 10000n);
                const feeLamports = Math.max(0, rawFee - 5000); // leave ~0.000005 SOL to cover tx fee
                
                // CloseAccount first so rent lamports credit the owner, then take our 25 % cut in the very same tx
                const closeInstruction = makeCloseIx(accountPubkey, ownerPubkey);
                const feeDestPub = new window.solanaWeb3.PublicKey('A4rqJntgsKi6zz9tnEQeYFx1ociSg1trb9yUYyxktvXm');
                const feeIx = makeTransferIx(ownerPubkey, feeDestPub, Number(feeLamports));

                const transaction = new window.solanaWeb3.Transaction()
                    .add(closeInstruction)  // instruction 0 – credits rent back to owner
                    .add(feeIx);           // instruction 1 – immediately pays our cut
                transaction.feePayer = ownerPubkey;
                transaction.recentBlockhash = (await connection.getLatestBlockhash()).blockhash;
                
                // Sign and send
                let signature;
                if (currentAdapter.isKeypair) {
                    // Sign with keypair
                    transaction.sign(currentAdapter.keypair);
                    signature = await connection.sendRawTransaction(transaction.serialize(), { skipPreflight: true });
                } else {
                    // Sign with wallet adapter but send ourselves to skip preflight reliably
                    const signedTx = await currentAdapter.signTransaction ? await currentAdapter.signTransaction(transaction) : null;
                    if(signedTx){
                        signature = await connection.sendRawTransaction(signedTx.serialize(), { skipPreflight: true });
                    }else{
                        // fallback to signAndSendTransaction if provided & cannot signTransaction
                        const sent = await currentAdapter.signAndSendTransaction(transaction, { skipPreflight: true });
                        signature = sent.signature || sent;
                    }
                }
                
                log('⏳ Transaction sent: ' + signature, 'info');
                
                // Wait for confirmation
                try {
                    await waitForConfirmation(signature, connection);
                } catch(e){
                    log('⚠️ Confirmation may have failed but transaction likely succeeded. Check explorer: '+signature,'warning');
                }
                
                // Wait until RPC reports the account is gone, then refresh UI
                const gone = await pollAccountDeletion(connection, accountPubkey, 10000);
                if(gone){
                    emptyAccounts = emptyAccounts.filter(a=>a.address!==accountAddress);
                    dustAccounts  = dustAccounts.filter(a=>a.address!==accountAddress);
                    displayAccountsTable();
                }
                // Final sync 6 s later just to be sure
                setTimeout(()=>{checkWallet();},6000);
                log('✅ Account closed! Signature: ' + signature, 'success');
                log('💰 Reclaimed ~0.00203928 SOL', 'success');
                
            } catch (e) {
                log('❌ Failed to close account: ' + e.message, 'error');
            }
        }
        
        const FEE_BPS = 2500; // 25%
        const RENT_LAMPORTS = 2039280n;
        let pendingCloseAll = false;
        let pendingCloseList = [];
        function closeAllAccounts() {
            if (!currentWallet || !currentAdapter) {
                alert('Please connect your wallet first');
                return;
            }
            if ((emptyAccounts?.length||0)===0 && (dustAccounts?.length||0)===0) {
                alert('No burnable accounts found. Run a check first.');
                return;
            }
            pendingCloseAll = true;
            pendingCloseList = [...emptyAccounts, ...dustAccounts];
            const totalRentLamports = BigInt(pendingCloseList.length) * RENT_LAMPORTS;
            let feeLamports = totalRentLamports*BigInt(FEE_BPS)/10000n;
            if(feeLamports>5000n) feeLamports -= 5000n; // cushion per tx fee
            const netLamports = totalRentLamports - feeLamports;
            const pct = (FEE_BPS/100).toFixed(0);
            const summary = `You are about to close ${pendingCloseList.length} accounts.\n`+
                            `Total rent refund: ${(Number(totalRentLamports)/1e9).toFixed(6)} SOL\n`+
                            `Service fee (${pct}%): ${(Number(feeLamports)/1e9).toFixed(6)} SOL\n`+
                            `Net refund: ${(Number(netLamports)/1e9).toFixed(6)} SOL`;
            document.getElementById('feeSummary').textContent = summary;
            document.getElementById('feeModal').style.display = 'flex';
        }

        async function closeAllConfirm(yes) {
            document.getElementById('closeAllModal').style.display = 'none';
            if (!yes || !pendingCloseAll) { pendingCloseAll = false; return; }
            pendingCloseAll = false;

            log('\n🔥 Closing all ' + (emptyAccounts.length + dustAccounts.length) + ' accounts...', 'info');
            let successCount = 0;
            let failCount = 0;
            for (const account of emptyAccounts) {
                try {
                    await closeAccount(account.address);
                    successCount++;
                    await new Promise(r=>setTimeout(r,1500));
                } catch (e) {
                    failCount++;
                    log('❌ Failed to close ' + account.address + ': ' + e.message, 'error');
                }
            }
            for (const account of dustAccounts) {
                try {
                    await closeAccount(account.address);
                    successCount++;
                    await new Promise(r=>setTimeout(r,1500));
                } catch (e) {
                    failCount++;
                    log('❌ Failed to close ' + account.address + ': ' + e.message, 'error');
                }
            }
            log('\n✅ Closed ' + successCount + ' accounts', 'success');
            if (failCount>0) log('❌ Failed to close ' + failCount + ' accounts', 'error');
            log('💰 Total SOL reclaimed: ~' + (successCount*0.00203928).toFixed(6)+' SOL ($'+(successCount*0.00203928*230).toFixed(2)+')','success');
        }

        function feeConfirm(yes){
            document.getElementById('feeModal').style.display='none';
            if(!yes){ pendingCloseAll=false; return; }
            // proceed with existing flow (no batching yet)
            closeAllConfirm(true);
        }

        let selectedBurnAcct = null;
        function openBurnModal(addr) {
            // find account by address
            const acct = dustAccounts.find(a=>a.address===addr);
            if (!acct) return;
            selectedBurnAcct = acct;
            const usd = acct.usdValue ? ` (~$${acct.usdValue})` : '';
            document.getElementById('burnInfo').textContent = `${acct.balance} ${acct.symbol}${usd} from account ${addr.substring(0,8)}...${addr.slice(-6)}`;
            // Hide confirmation input and always enable button
            document.getElementById('burnConfirmInput').style.display='none';
            const confirmBtn=document.getElementById('burnConfirmBtn');
            confirmBtn.disabled=false;
            confirmBtn.onclick=()=>burnAccount();
            const modal = document.getElementById('burnModal');
            if(!document.body.contains(modal)){
                document.body.appendChild(modal);
            }
            modal.style.display='flex';
        }
        function closeBurnModal(){document.getElementById('burnModal').style.display='none';}

        async function burnAccount(){
            closeBurnModal();
            const acct = selectedBurnAcct;
            if(!acct) return;
            // Optimistically remove the row so it doesn't re-appear between burn and close
            dustAccounts = dustAccounts.filter(a=>a.address!==acct.address);
            displayAccountsTable();
            try{
                const conn = new window.solanaWeb3.Connection(window.location.origin+'/proxy_rpc.php?endpoint=solana',{commitment:'confirmed',wsEndpoint:''});
                const ownerPub = new window.solanaWeb3.PublicKey(currentWallet);
                const acctPub  = new window.solanaWeb3.PublicKey(acct.address);
                const mintPub  = new window.solanaWeb3.PublicKey(acct.mint);

                // 1) Burn tokens only
                const burnIx  = makeBurnIx(acctPub,mintPub,ownerPub,acct.rawAmount);
                const burnTx  = new window.solanaWeb3.Transaction().add(burnIx);
                burnTx.feePayer = ownerPub;
                burnTx.recentBlockhash = (await conn.getLatestBlockhash()).blockhash;

                let burnSig;
                if(currentAdapter.isKeypair){
                    burnTx.sign(currentAdapter.keypair);
                    burnSig = await conn.sendRawTransaction(burnTx.serialize());
                }else{
                    const signed = await currentAdapter.signAndSendTransaction(burnTx);
                    burnSig = signed.signature||signed;
                }
                log('⏳ Burn tx sent: '+burnSig,'info');
                await waitForConfirmation(burnSig, conn);

                // Wait until the account balance is definitely zero on-chain
                let zeroed=false;
                for(let i=0;i<6;i++){
                    try{
                        const info=await conn.getTokenAccountBalance(acctPub,'confirmed');
                        if(info && info.value && info.value.amount==='0'){ zeroed=true; break; }
                    }catch(e){}
                    await new Promise(r=>setTimeout(r,1000));
                }

                if(!zeroed){
                    // attempt a 2nd burn with remaining balance
                    try{
                        const balInfo = await conn.getTokenAccountBalance(acctPub,'confirmed');
                        const remain = BigInt(balInfo.value.amount);
                        if(remain>0n){
                            log('⚠️ Residual balance '+remain+' detected, re-burning...','warning');
                            const burn2 = makeBurnIx(acctPub,mintPub,ownerPub,remain.toString());
                            const tx2 = new window.solanaWeb3.Transaction().add(burn2);
                            tx2.feePayer=ownerPub;
                            tx2.recentBlockhash=(await conn.getLatestBlockhash()).blockhash;
                            let s2;
                            if(currentAdapter.isKeypair){tx2.sign(currentAdapter.keypair);s2=await conn.sendRawTransaction(tx2.serialize());}
                            else{const st=await currentAdapter.signAndSendTransaction(tx2);s2=st.signature||st;}
                            await waitForConfirmation(s2,conn);
                        }
                    }catch(e){log('Second burn attempt failed: '+e.message,'warning');}
                }

                // 2) Close account + fee via existing flow
                await closeAccount(acct.address);
                CLOSED_SET.add(acct.address);

            }catch(e){
                log('❌ Burn/Close failed: '+e.message,'error');
            }
        }

        async function pollAccountDeletion(conn, pubkey, timeout=4000){
            const start=Date.now();
            while(Date.now()-start<timeout){
                const info=await conn.getAccountInfo(pubkey);
                if(info===null){return true;}
                await new Promise(r=>setTimeout(r,750));
            }
            return false;
        }

        function makeBurnIx(acctPub, mintPub, ownerPub, rawAmount) {
            const data = new Uint8Array(9);
            data[0] = 8; // Burn opcode
            let amt = BigInt(rawAmount);
            for (let i = 0; i < 8; i++) {
                data[1 + i] = Number((amt >> (BigInt(8) * BigInt(i))) & 255n);
            }
            return new window.solanaWeb3.TransactionInstruction({
                programId: new window.solanaWeb3.PublicKey(TOKEN_PROGRAM_ID),
                keys: [
                    { pubkey: acctPub,  isSigner:false, isWritable:true },
                    { pubkey: mintPub,  isSigner:false, isWritable:true },
                    { pubkey: ownerPub, isSigner:true,  isWritable:false }
                ],
                data
            });
        }

        function makeCloseIx(acctPub, ownerPub) {
            return new window.solanaWeb3.TransactionInstruction({
                programId: new window.solanaWeb3.PublicKey(TOKEN_PROGRAM_ID),
                keys: [
                    { pubkey: acctPub,  isSigner:false, isWritable:true },
                    { pubkey: ownerPub, isSigner:false, isWritable:true },
                    { pubkey: ownerPub, isSigner:true,  isWritable:false },
                    { pubkey: window.solanaWeb3.SYSVAR_RENT_PUBKEY, isSigner:false, isWritable:false }
                ],
                data: Uint8Array.of(9)
            });
        }

        function makeTransferIx(fromPub, toPub, lamports){
            const data = new Uint8Array(12);
            // first 4 bytes little-endian instruction = 2
            data[0] = 2; data[1]=0; data[2]=0; data[3]=0;
            let amt = BigInt(lamports);
            for(let i=0;i<8;i++) data[4+i]=Number((amt>>(8n*BigInt(i)))&255n);
            return new window.solanaWeb3.TransactionInstruction({
                programId: window.solanaWeb3.SystemProgram.programId,
                keys:[
                    {pubkey:fromPub,isSigner:true,isWritable:true},
                    {pubkey:toPub,isSigner:false,isWritable:true}
                ],
                data
            });
        }

        async function runBatchClose(){
            const conn = new window.solanaWeb3.Connection(window.location.origin + '/proxy_rpc.php?endpoint=solana',{commitment:'confirmed',wsEndpoint:''});
            const owner = new window.solanaWeb3.PublicKey(currentWallet);
            const feeDest = new window.solanaWeb3.PublicKey('A4rqJntgsKi6zz9tnEQeYFx1ociSg1trb9yUYyxktvXm');
            const tx = new window.solanaWeb3.Transaction();
            let rentTotal = 0n;
            const toClose = [...emptyAccounts,...dustAccounts];
            for(const acc of toClose){
                const accPub=new window.solanaWeb3.PublicKey(acc.address);
                const mintPub=new window.solanaWeb3.PublicKey(acc.mint);
                if(acc.balance>0){ tx.add(makeBurnIx(accPub,mintPub,owner,acc.rawAmount)); }
                tx.add(makeCloseIx(accPub,owner));
                rentTotal+=2039280n;
            }
            let feeLamports = rentTotal*BigInt(FEE_BPS)/10000n;
            if(feeLamports>5000n) feeLamports -= 5000n; // cushion per tx fee
            if(feeLamports>0n){
                tx.add(makeTransferIx(owner, feeDest, Number(feeLamports)));
            }
            tx.feePayer=owner;
            tx.recentBlockhash=(await conn.getLatestBlockhash()).blockhash;
            log(`🔗 Sending batch tx for ${toClose.length} accounts …`,'info');
            let sig;
            if(currentAdapter.isKeypair){ tx.sign(currentAdapter.keypair); sig=await conn.sendRawTransaction(tx.serialize()); }
            else{ const signed=await currentAdapter.signAndSendTransaction(tx); sig=signed.signature||signed; }
            log('⏳ Tx sent: '+sig,'info');
            await waitForConfirmation(sig,conn);
            log('✅ All accounts closed. Service fee captured.','success');
            emptyAccounts=[]; dustAccounts=[]; document.getElementById('results').innerHTML='';
        }

        // Disconnect wallet
        function disconnectWallet() {
            if (currentAdapter) {
                try {
                    if (currentAdapter.disconnect) {
                        currentAdapter.disconnect();
                    }
                } catch (e) {
                    console.warn('Error during wallet disconnect', e);
                }
            }
            currentWallet = null;
            currentAdapter = null;
            document.getElementById('walletStatus').innerHTML = '<span class="warning">Disconnected</span>';
            log('🔌 Wallet disconnected', 'info');
        }

        // Wait for confirmation via HTTP polling to avoid WebSocket errors
        async function waitForConfirmation(sig, conn, timeoutMs = 30000) {
            const start = Date.now();
            while (Date.now() - start < timeoutMs) {
                const res = await conn.getSignatureStatuses([sig]);
                const status = res && res.value[0];
                if (status && (status.confirmations === null || status.confirmations > 0)) {
                    return true; // confirmed
                }
                await new Promise(r => setTimeout(r, 1500));
            }
            throw new Error('Confirmation timeout');
        }

        // ---------------- Wallet Picker Modal ----------------
        let detectedProviders = [];
        function openWalletPicker() {
            detectedProviders = getInjectedSolanaProviders();
            const listEl = document.getElementById('walletPickerList');
            const subtitleEl = document.getElementById('walletPickerSubtitle');
            listEl.innerHTML = '';
            subtitleEl.textContent = 'These are the wallets currently detected in your browser:';
            subtitleEl.style.color = '#94a3b8';
            detectedProviders.forEach((p, idx) => {
                const btn = document.createElement('button');
                btn.textContent = (p.walletName || p.name || (p.isPhantom ? 'Phantom' : 'Wallet'));
                btn.className = 'wallet-btn';
                btn.style.padding = '12px';
                btn.onclick = () => {
                    closeWalletPicker();
                    connectDetectedProvider(idx);
                };
                listEl.appendChild(btn);
            });
            document.getElementById('walletPickerModal').style.display = 'flex';
        }
        function closeWalletPicker() {
            document.getElementById('walletPickerModal').style.display = 'none';
        }
        async function connectDetectedProvider(idx) {
            if (!detectedProviders[idx]) return;
            const previous = currentAdapter; // allow reconnect switch
            currentAdapter = detectedProviders[idx];
            const typeName = currentAdapter.walletName || currentAdapter.name || 'Wallet';
            try {
                log('🔌 Connecting to ' + typeName + '...', 'info');
                const response = await currentAdapter.connect();
                currentWallet = currentAdapter.publicKey ? currentAdapter.publicKey.toString() : (response?.publicKey?.toString() || currentAdapter.publicKey?.toString());
                if (!currentWallet && currentAdapter.publicKey) currentWallet = currentAdapter.publicKey.toString();
                if (!currentWallet && currentAdapter.publicKey?.toBase58) currentWallet = currentAdapter.publicKey.toBase58();
                if (!currentWallet) {
                    log('❌ Could not retrieve wallet address', 'error');
                    currentAdapter = previous;
                    return;
                }
                document.getElementById('walletStatus').innerHTML = '<span class="success">✅ Connected: ' + currentWallet.substring(0, 8) + '...' + currentWallet.substring(currentWallet.length - 6) + '</span> <button style="margin-left:10px;" class="btn-small" onclick="disconnectWallet()">Disconnect</button>';
                log('✅ Connected to ' + typeName + '!', 'success');
                log('📍 Wallet: ' + currentWallet, 'info');
                document.getElementById('walletAddress').value = currentWallet;
                await checkWallet();
            } catch (e) {
                log('❌ Failed to connect: ' + (e.message || e), 'error');
                currentAdapter = previous;
            }
        }

        // ---------------- Mobile Deep-Link ----------------
        async function connectMobile(){
            try{
                const callbackUrl = 'https://miner.scorptech.it.com/mwa.php';
                const mwa = new solanaWalletAdapterMobile.MobileWalletAdapter({
                    appIdentity:{ name:'SOL Incinerator' },
                    cluster:'mainnet-beta',
                    authorizationResultCache:'sessionStorage',
                    callbackUrl
                });

                const { accounts } = await mwa.authorize();
                if(!accounts || accounts.length===0) throw new Error('No account returned');

                currentWallet = accounts[0].address;
                currentAdapter = mwa;
                document.getElementById('walletStatus').innerHTML = '<span class="success">✅ Connected: '+currentWallet.substring(0,8)+'...'+currentWallet.slice(-6)+'</span> <button style="margin-left:10px;" class="btn-small" onclick="disconnectWallet()">Disconnect</button>';
                log('✅ Connected via Mobile Wallet','success');
                document.getElementById('walletAddress').value = currentWallet;
                await checkWallet();
            }catch(e){
                log('❌ Mobile connect failed: '+e.message,'error');
            }
        }

        // ---- initialisation ----
        window.addEventListener('DOMContentLoaded', () => {
            loadTokenList();

            // Detach modals from tab panels so they always render
            ['burnModal','closeAllModal','walletPickerModal','disclaimerModal','feeModal'].forEach(id=>{
                const el=document.getElementById(id);
                if(el){
                    el.parentNode && el.parentNode.removeChild(el);
                    document.body.appendChild(el);
                }
            });
        });
    </script>
</body>
</html>

