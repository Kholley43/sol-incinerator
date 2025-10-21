<?php
// Include protection script
require_once 'protect.php';

// Include simplified security script
require_once 'security.php';

// SOL Incinerator - Burn unwanted SPL token accounts and reclaim SOL
// Protected source code - Do not distribute

// Check if we need to proxy to the Node.js service
$node_service_running = false;
$node_port = 3000;

// Try to connect to the Node.js service
$connection = @fsockopen('127.0.0.1', $node_port, $errno, $errstr, 1);
if (is_resource($connection)) {
    $node_service_running = true;
    fclose($connection);
}

// If Node service is running, proxy to it
if ($node_service_running) {
    // Proxy the request to the Node.js service
    $url = "http://127.0.0.1:{$node_port}" . $_SERVER['REQUEST_URI'];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    // Forward request headers
    $request_headers = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $request_headers[] = "$header: $value";
        }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
    
    // Forward request method and body
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
    }
    
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Set status code
    http_response_code($status_code);
    
    // Forward response headers
    $header_lines = explode("\n", $headers);
    foreach ($header_lines as $header_line) {
        if (trim($header_line) && !preg_match('/^(HTTP|Transfer-Encoding|Connection)/', $header_line)) {
            header($header_line);
        }
    }
    
    // Output response body
    echo $body;
    exit;
}

// If Node service is not running, show the dashboard directly in PHP
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
    <!-- Wallet adapter dependencies -->
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
        }
        
        body {
            font-family: 'Inter', 'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0a0e17, #131b2e);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            font-size: 16px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header {
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 1px;
        }
        
        .logo span {
            font-size: 16px;
            display: block;
            color: var(--text-color);
        }
        
        .connect-btn {
            background: var(--accent-gradient);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        }
        
        .connect-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(58, 134, 255, 0.4);
        }
        
        .panel {
            background: var(--panel-gradient);
            border: 1px solid var(--border-color);
            padding: 24px;
            margin: 20px 0;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .disclaimer {
            background: rgba(17, 24, 39, 0.8);
            border: 1px solid var(--border-color);
            padding: 30px;
            margin: 20px 0;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
        }
        
        h2, h3 {
            color: var(--accent-color);
            text-align: center;
        }
        
        textarea {
            width: 100%;
            background-color: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-color);
            padding: 12px;
            font-family: monospace;
            margin-bottom: 15px;
            resize: vertical;
            transition: all 0.2s;
        }
        
        textarea:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(58, 134, 255, 0.2);
            outline: none;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: rgba(15, 23, 42, 0.5);
            color: #fff;
            font-weight: 600;
            position: sticky;
            top: 0;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        
        tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }
        
        tr:first-child th:first-child {
            border-top-left-radius: 8px;
        }
        
        tr:first-child th:last-child {
            border-top-right-radius: 8px;
        }
        
        .btn {
            background: var(--accent-gradient);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(58, 134, 255, 0.3);
        }
        
        .btn-warning {
            background: var(--danger-gradient);
            box-shadow: 0 4px 12px rgba(255, 69, 0, 0.2);
        }
        
        .btn-warning:hover {
            box-shadow: 0 6px 16px rgba(255, 69, 0, 0.3);
        }
        
        .status {
            margin-top: 20px;
            padding: 20px;
            background: var(--panel-gradient);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        input[type="number"], input[type="file"] {
            background-color: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-color);
            padding: 10px 12px;
            margin-right: 10px;
            transition: all 0.2s;
        }
        
        input[type="number"]:focus, input[type="file"]:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(58, 134, 255, 0.2);
            outline: none;
        }
        
        label {
            margin-right: 10px;
        }
        
        .footer {
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
            border-top: 1px solid var(--border-color);
            font-size: 14px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .connect-btn {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                SOL INCINERATOR
                <span style="display:block; font-size:14px; font-weight:500; opacity:0.7; margin-top:4px;">a scorptech project</span>
            </div>
            <div>
                <button class="btn" style="margin-right:10px;" onclick="showPublicCheck()">CHECK WALLET</button>
                <button class="connect-btn" onclick="showWalletInput()">CONNECT WALLET</button>
            </div>
        </div>
        
        <div id="disclaimer" class="disclaimer" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:90%; max-width:600px; z-index:100;">
            <div class="disclaimer-backdrop" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px); z-index:-1;"></div>
            <h2>Disclaimer</h2>
            <p>The burn tool on the ScorpTech SOL-Incinerator platform is used to
            facilitate the irreversible burning of your tokens.</p>
            
            <p>By using this site, you are doing so at your own risk. The
            ScorpTech SOL-Incinerator is not responsible for any tokens burned as a
            result of its usage.</p>
            
            <p>By using the platform you explicitly accept full
            responsibility for any and all burns.</p>
            
            <p>The ScorpTech SOL-Incinerator platform additionally does not assume
            liability for any mistakes, accidents, miss-intentions or any
            other actions that led to an undesired burn.</p>
            
            <button class="btn" style="background:var(--accent-gradient); min-width:200px;" onclick="agreeAndClose()">AGREE AND CLOSE</button>
        </div>
        
        <div id="publicCheckPanel" class="panel" style="display:none;">
            <h3>Check Your Wallet</h3>
            <p>Enter your public wallet address to check for burnable accounts without connecting your private key.</p>
            
            <input type="text" id="publicAddress" placeholder="Public Wallet Address (e.g., GgE7...)" style="width:100%; padding:12px; margin-bottom:15px; background-color:rgba(15,23,42,0.5); border:1px solid var(--border-color); border-radius:8px; color:var(--text-color);"><br>
            <label>Dust Threshold&nbsp;<input id="publicThreshold" type="number" value="0.000001" step="0.000001"></label>
            <button class="btn" onclick="checkPublicAddress()">CHECK FOR BURNABLE ACCOUNTS</button>
        </div>

        <div id="walletPanel" class="panel" style="display:none;">
            <h3>Connect Your Wallet</h3>
            
            <div class="wallet-methods">
                <div class="wallet-method-tabs">
                    <button id="walletAdapterTab" class="wallet-tab active" onclick="switchWalletMethod('walletAdapter')">Browser Wallets</button>
                    <button id="privateKeyTab" class="wallet-tab" onclick="switchWalletMethod('privateKey')">Private Key</button>
                    <button id="settingsTab" class="wallet-tab" onclick="switchWalletMethod('settings')">Settings</button>
                </div>
                
                <div id="walletAdapterMethod" class="wallet-method-content">
                    <p>Connect your browser wallet to burn unwanted SPL accounts and reclaim SOL rent.</p>
                    
                    <div class="wallet-buttons">
                        <button class="wallet-button" onclick="connectWallet('phantom')">
                            <svg width="30" height="30" viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="128" height="128" rx="64" fill="#AB9FF2"/>
                                <path d="M110.584 64.9142H99.142C99.142 41.7651 80.173 23 56.7724 23C33.6612 23 14.8354 41.3057 14.4067 64.0583C13.9543 87.8993 35.2287 108 59.3353 108H64.0583C84.4896 108 110.584 91.7243 110.584 64.9142Z" fill="white"/>
                                <path d="M93.7911 65.6615H78.8953C78.8953 73.3741 72.6349 79.6344 64.9223 79.6344C57.2098 79.6344 50.9494 73.3741 50.9494 65.6615C50.9494 57.949 57.2098 51.6886 64.9223 51.6886H71.2349C83.3056 51.6886 93.7911 61.3706 93.7911 65.6615Z" fill="url(#paint0_radial)"/>
                                <defs>
                                    <radialGradient id="paint0_radial" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(64.9223 65.6615) rotate(90) scale(13.9729 21.4208)">
                                        <stop stop-color="#534BB1"/>
                                        <stop offset="1" stop-color="#551BF9"/>
                                    </radialGradient>
                                </defs>
                            </svg>
                            <span>Phantom</span>
                        </button>
                        
                        <button class="wallet-button" onclick="connectWallet('solflare')">
                            <svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 30C23.2843 30 30 23.2843 30 15C30 6.71573 23.2843 0 15 0C6.71573 0 0 6.71573 0 15C0 23.2843 6.71573 30 15 30Z" fill="black"/>
                                <path d="M21.6663 15.0002C21.6663 14.4022 21.5313 13.8122 21.2713 13.2722C21.0113 12.7322 20.6323 12.2582 20.1663 11.8882C19.7003 11.5182 19.1603 11.2642 18.5853 11.1442C18.0103 11.0242 17.4173 11.0422 16.8513 11.1962C16.2853 11.3502 15.7613 11.6362 15.3213 12.0302C14.8823 12.4242 14.5403 12.9142 14.3223 13.4622C14.1043 14.0102 14.0163 14.6002 14.0663 15.1862C14.1163 15.7722 14.3033 16.3402 14.6113 16.8482L14.9993 17.5002L14.6113 18.1522C14.3033 18.6602 14.1163 19.2282 14.0663 19.8142C14.0163 20.4002 14.1043 20.9902 14.3223 21.5382C14.5403 22.0862 14.8823 22.5762 15.3213 22.9702C15.7613 23.3642 16.2853 23.6502 16.8513 23.8042C17.4173 23.9582 18.0103 23.9762 18.5853 23.8562C19.1603 23.7362 19.7003 23.4822 20.1663 23.1122C20.6323 22.7422 21.0113 22.2682 21.2713 21.7282C21.5313 21.1882 21.6663 20.5982 21.6663 20.0002" stroke="url(#paint0_linear_1_2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8.33301 15.0002C8.33301 14.4022 8.46801 13.8122 8.72801 13.2722C8.98801 12.7322 9.36701 12.2582 9.83301 11.8882C10.299 11.5182 10.839 11.2642 11.414 11.1442C11.989 11.0242 12.582 11.0422 13.148 11.1962C13.714 11.3502 14.238 11.6362 14.678 12.0302C15.117 12.4242 15.459 12.9142 15.677 13.4622C15.895 14.0102 15.983 14.6002 15.933 15.1862C15.883 15.7722 15.696 16.3402 15.388 16.8482L15.0003 17.5002L15.388 18.1522C15.696 18.6602 15.883 19.2282 15.933 19.8142C15.983 20.4002 15.895 20.9902 15.677 21.5382C15.459 22.0862 15.117 22.5762 14.678 22.9702C14.238 23.3642 13.714 23.6502 13.148 23.8042C12.582 23.9582 11.989 23.9762 11.414 23.8562C10.839 23.7362 10.299 23.4822 9.83301 23.1122C9.36701 22.7422 8.98801 22.2682 8.72801 21.7282C8.46801 21.1882 8.33301 20.5982 8.33301 20.0002" stroke="url(#paint1_linear_1_2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <defs>
                                    <linearGradient id="paint0_linear_1_2" x1="17.8663" y1="11.0002" x2="17.8663" y2="24.0002" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#FE9A3E"/>
                                        <stop offset="1" stop-color="#FD4A85"/>
                                    </linearGradient>
                                    <linearGradient id="paint1_linear_1_2" x1="12.133" y1="11.0002" x2="12.133" y2="24.0002" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#FE9A3E"/>
                                        <stop offset="1" stop-color="#FD4A85"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                            <span>Solflare</span>
                        </button>
                        
                        <button class="wallet-button" onclick="connectWallet('backpack')">
                            <svg width="30" height="30" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="512" height="512" rx="256" fill="black"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M355.548 135.017C373.655 151.158 385.101 174.526 385.101 200.506C385.101 252.142 343.243 294 291.607 294H220.393C168.757 294 126.899 252.142 126.899 200.506C126.899 174.526 138.345 151.158 156.452 135.017C174.559 118.876 198.921 109 225.393 109H286.607C313.079 109 337.441 118.876 355.548 135.017ZM156.452 376.983C138.345 360.842 126.899 337.474 126.899 311.494C126.899 259.858 168.757 218 220.393 218H291.607C343.243 218 385.101 259.858 385.101 311.494C385.101 337.474 373.655 360.842 355.548 376.983C337.441 393.124 313.079 403 286.607 403H225.393C198.921 403 174.559 393.124 156.452 376.983Z" fill="white"/>
                            </svg>
                            <span>Backpack</span>
                        </button>
                    </div>
                    
                    <div id="walletStatus" style="margin-top:15px; text-align:center;"></div>
                    
                    <div style="margin-top:20px;">
                        <label>Dust Threshold&nbsp;<input id="adapterTh" type="number" value="0.000001" step="0.000001"></label>
                        <button id="adapterLoadBtn" class="btn" onclick="loadWithAdapter()" disabled>FIND BURNABLE ACCOUNTS</button>
                    </div>
                </div>
                
                <div id="settingsMethod" class="wallet-method-content" style="display:none;">
                    <h4>RPC Settings</h4>
                    <p>Select an RPC endpoint to use for connecting to Solana.</p>
                    
                    <div style="margin-bottom:15px;">
                        <select id="rpcSelect" onchange="changeRpcEndpoint()" style="width:100%; padding:10px; background:rgba(15,23,42,0.5); color:var(--text-color); border:1px solid var(--border-color); border-radius:6px;">
                            <option value="auto" selected>Auto (Fallback Mode)</option>
                            <option value="solana">Solana (Official)</option>
                            <option value="alchemy">Alchemy</option>
                            <option value="tracker">Solana Tracker</option>
                            <option value="quicknode">QuickNode</option>
                            <option value="serum">Project Serum</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    
                    <div id="customRpcContainer" style="display:none; margin-bottom:15px;">
                        <input type="text" id="customRpc" placeholder="Enter custom RPC URL" style="width:100%; padding:10px; background:rgba(15,23,42,0.5); color:var(--text-color); border:1px solid var(--border-color); border-radius:6px;">
                        <button class="btn" onclick="setCustomRpc()" style="margin-top:10px;">Set Custom RPC</button>
                    </div>
                    
                    <div id="rpcStatus" style="margin-top:15px; padding:10px; background:rgba(15,23,42,0.5); border-radius:6px;">Auto Fallback Mode: Will try all endpoints</div>
                </div>
                
                <div id="privateKeyMethod" class="wallet-method-content" style="display:none;">
                    <p>Paste a <b>base-58 secret key</b> (64-byte) or drag-drop a Keypair JSON file to burn unwanted SPL accounts and reclaim SOL rent.</p>
                    <p><small>All transactions are signed locally. Your keys never leave your device.</small></p>
                    
                    <input type="file" id="file" accept=".json">
                    <textarea id="sk" rows="4" placeholder="Base58 secret key"></textarea><br>
                    <label>Dust Threshold&nbsp;<input id="th" type="number" value="0.000001" step="0.000001"></label>
                    <button class="btn" onclick="load()">FIND BURNABLE ACCOUNTS</button>
                </div>
            </div>
        </div>
        
        <div id="out" class="status">
            <div style="text-align:center; padding:20px;">
                <h3>Welcome to SOL Incinerator</h3>
                <p>Burn unwanted SPL token accounts and reclaim your SOL rent.</p>
                
                <div style="display:flex; justify-content:center; gap:20px; margin-top:30px;">
                    <div style="text-align:center; padding:20px; background:linear-gradient(135deg, rgba(58,134,255,0.05), rgba(0,198,255,0.05)); border-radius:12px; width:45%; border:1px solid rgba(58,134,255,0.1);">
                        <h4 style="margin-top:0;">Check First</h4>
                        <p>View burnable accounts using just your public address</p>
                        <button class="btn" onclick="showPublicCheck()">CHECK WALLET</button>
                    </div>
                    
                    <div style="text-align:center; padding:20px; background:linear-gradient(135deg, rgba(255,69,0,0.05), rgba(255,135,0,0.05)); border-radius:12px; width:45%; border:1px solid rgba(255,69,0,0.1);">
                        <h4 style="margin-top:0;">Burn Accounts</h4>
                        <p>Connect your wallet to burn accounts and reclaim SOL</p>
                        <button class="connect-btn" onclick="showWalletInput()">CONNECT WALLET</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            &copy; 2025 ScorpTech SOL-Incinerator | Burn unwanted SPL token accounts and reclaim SOL
        </div>
    </div>

    <style>
    .wallet-methods {
        width: 100%;
    }
    
    .wallet-method-tabs {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .wallet-tab {
        background: none;
        border: none;
        padding: 10px 20px;
        color: var(--text-color);
        font-size: 16px;
        cursor: pointer;
        opacity: 0.7;
        position: relative;
    }
    
    .wallet-tab.active {
        opacity: 1;
        font-weight: 600;
    }
    
    .wallet-tab.active:after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--accent-gradient);
    }
    
    .wallet-method-content {
        padding: 10px 0;
    }
    
    .wallet-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin: 20px 0;
        justify-content: center;
    }
    
    .wallet-button {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 15px 20px;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-color);
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .wallet-button:hover {
        background: rgba(15, 23, 42, 0.8);
        transform: translateY(-2px);
    }
    
    .wallet-button img {
        width: 24px;
        height: 24px;
        object-fit: contain;
    }
</style>

<script src="https://unpkg.com/@solana/web3.js@1.93.5/lib/index.iife.js"></script>
    <script>
    let kp, conn, threshold=1e-6, accounts=[], walletAdapter=null, walletAdapterConnected=false;
    
    // List of reliable RPC endpoints
    const RPC_ENDPOINTS = [
        { name: 'solana', url: 'https://api.mainnet-beta.solana.com' },
        { name: 'alchemy', url: 'https://solana-mainnet.g.alchemy.com/v2/demo' },
        { name: 'tracker', url: 'https://rpc-mainnet.solanatracker.io/?api_key=02232df4-4670-439c-b65a-27225d5b841f' },
        { name: 'quicknode', url: 'https://quiet-attentive-frost.solana-mainnet.quiknode.pro/ed83a1d62d5a9b3c0a9fd0fb8d99e8e2d25f5ad3/' },
        { name: 'serum', url: 'https://solana-api.projectserum.com' }
    ];
    
    // RPC configuration
    const RPC_CONFIG = {
        commitment: 'confirmed',  // Use confirmed instead of finalized for better performance
        timeout: 30000,          // 30 second timeout
        batchSize: 25,           // Process accounts in smaller batches to avoid RPC limits
        retryCount: 3,           // Number of retries per endpoint
        retryDelay: 1000         // Delay between retries in ms
    };
    
    // Custom RPC endpoint
    let customRpcEndpoint = '';
    
    // Current RPC index
    let currentRpcIndex = 0;
    
    // Default to auto mode
    const defaultRpcMode = 'auto';
    
    // Token program ID constants
    const TOKEN_PROGRAM_ID = 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA';
    const TOKEN_2022_PROGRAM_ID = 'TokenzQdBNbLqP5VEhdkAS6EPFLC1PHnBqCXEpPxuEb';
    
    // Wallet change detection
    let lastKnownWalletAddress = null;
    let walletCheckInterval = null;
    
    // Get current RPC endpoint
    function getCurrentRpcEndpoint() {
        // Try to get the selected RPC value
        const select = document.getElementById('rpcSelect');
        const selectedRpc = select ? select.value : defaultRpcMode;
        
        if (selectedRpc === 'custom' && customRpcEndpoint) {
            // For custom endpoints, use direct URL
            return customRpcEndpoint;
        } else if (selectedRpc && selectedRpc !== 'custom' && selectedRpc !== 'auto') {
            // For specific endpoints, use proxy URL
            return 'proxy_rpc.php?endpoint=' + selectedRpc;
        }
        // For auto mode or fallback, use proxy URL
        return 'proxy_rpc.php?endpoint=' + RPC_ENDPOINTS[currentRpcIndex].name;
    }
    
    // Try next RPC endpoint
    async function tryNextRpcEndpoint() {
        currentRpcIndex = (currentRpcIndex + 1) % RPC_ENDPOINTS.length;
        const endpoint = RPC_ENDPOINTS[currentRpcIndex];
        document.getElementById('rpcStatus').innerHTML = `Current RPC: ${endpoint.name} (auto-switched)`;
        document.getElementById('rpcSelect').value = endpoint.name;
        return endpoint.url;
    }
    
    function showPublicCheck() {
        document.getElementById('publicCheckPanel').style.display = 'block';
        document.getElementById('walletPanel').style.display = 'none';
        document.getElementById('out').innerHTML = 'Enter your public wallet address to check for burnable accounts...';
    }
    
    function showWalletInput() {
        document.getElementById('disclaimer').style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    }
    
    
    async function connectWallet(walletName) {
        try {
            // Reset status
            document.getElementById('walletStatus').innerHTML = 'Connecting...';
            document.getElementById('adapterLoadBtn').disabled = true;
            
            // Check if solana is available in window
            if (!window.solana && !window.phantom && !window.solflare) {
                throw new Error('No Solana wallet adapter found. Please install a wallet extension.');
            }
            
            let provider;
            let walletLabel = '';
            
            // Connect to the selected wallet
            if (walletName === 'phantom') {
                provider = window.phantom?.solana || window.solana;
                walletLabel = 'Phantom';
                if (!provider) throw new Error('Phantom wallet not found. Please install the Phantom extension.');
            } else if (walletName === 'solflare') {
                provider = window.solflare || window.solana;
                walletLabel = 'Solflare';
                if (!provider) throw new Error('Solflare wallet not found. Please install the Solflare extension.');
            } else if (walletName === 'backpack') {
                provider = window.backpack?.solana || window.solana;
                walletLabel = 'Backpack';
                if (!provider) throw new Error('Backpack wallet not found. Please install the Backpack extension.');
            } else {
                provider = window.solana;
                walletLabel = 'Wallet';
                if (!provider) throw new Error('No compatible wallet found.');
            }
            
            // Check if this wallet is already connected
            if (walletAdapter && walletAdapter === provider && provider.isConnected && provider.publicKey) {
                const publicKey = provider.publicKey.toString();
                const shortAddress = publicKey.substring(0,4) + '...' + publicKey.substring(publicKey.length-4);
                
                // Show already connected message
                showAlreadyConnectedModal(walletLabel, shortAddress);
                
                // Update status just in case
                document.getElementById('walletStatus').innerHTML = `<p style="color:var(--accent-color)">Connected: ${shortAddress}</p>`;
                document.getElementById('adapterLoadBtn').disabled = false;
                return;
            }
            
            // Show connection modal
            showConnectionModal(walletLabel);
            
            // Connect to the wallet
            const response = await provider.connect();
            
            // Hide connection modal
            hideConnectionModal();
            
            // Save the adapter for later use
            walletAdapter = provider;
            walletAdapterConnected = true;
            
            // Update status
            const publicKey = provider.publicKey.toString();
            lastKnownWalletAddress = publicKey;
            const shortAddress = publicKey.substring(0,4) + '...' + publicKey.substring(publicKey.length-4);
            document.getElementById('walletStatus').innerHTML = `<p style="color:var(--accent-color)">Connected: ${shortAddress}</p>`;
            document.getElementById('adapterLoadBtn').disabled = false;
            
            // Start wallet change detection
            startWalletChangeDetection(provider, walletName);
        } catch (error) {
            // Hide connection modal
            hideConnectionModal();
            
            console.error('Wallet connection error:', error);
            document.getElementById('walletStatus').innerHTML = `<p style="color:var(--danger-color)">Error: ${error.message}</p>`;
        }
    }
    
    function agreeAndClose() {
        document.getElementById('disclaimer').style.display = 'none';
        document.getElementById('publicCheckPanel').style.display = 'none';
        document.getElementById('walletPanel').style.display = 'block';
        document.body.style.overflow = 'auto'; // Re-enable scrolling
        
        // Check if any wallet is already connected in the browser
        checkForConnectedWallets();
    }
    
    function changeRpcEndpoint() {
        const selectedRpc = document.getElementById('rpcSelect').value;
        
        if (selectedRpc === 'custom') {
            document.getElementById('customRpcContainer').style.display = 'block';
            return;
        } else {
            document.getElementById('customRpcContainer').style.display = 'none';
            
            if (selectedRpc === 'auto') {
                document.getElementById('rpcStatus').innerHTML = `Auto Fallback Mode: Will try all endpoints`;
                return;
            }
            
            const endpoint = RPC_ENDPOINTS.find(rpc => rpc.name === selectedRpc);
            if (endpoint) {
                currentRpcIndex = RPC_ENDPOINTS.indexOf(endpoint);
                document.getElementById('rpcStatus').innerHTML = `Current RPC: ${endpoint.name}`;
            }
        }
    }
    
    function setCustomRpc() {
        const customRpcUrl = document.getElementById('customRpc').value.trim();
        if (!customRpcUrl) {
            alert('Please enter a valid RPC URL');
            return;
        }
        
        customRpcEndpoint = customRpcUrl;
        document.getElementById('rpcStatus').innerHTML = `Current RPC: Custom (${customRpcUrl.substring(0, 20)}...)`;
    }
    
    function switchWalletMethod(method) {
        try {
            // Make sure wallet panel is visible
            const walletPanel = document.getElementById('walletPanel');
            if (walletPanel) walletPanel.style.display = 'block';
            
            console.log('Switching to method:', method);
            
            // Fix method name if needed
            if (method === 'adapter') method = 'walletAdapter';
            
            // Update tabs
            const adapterTab = document.getElementById('walletAdapterTab');
            const privateTab = document.getElementById('privateKeyTab');
            const settingsTab = document.getElementById('settingsTab');
            
            if (adapterTab) adapterTab.classList.remove('active');
            if (privateTab) privateTab.classList.remove('active');
            if (settingsTab) settingsTab.classList.remove('active');
            
            // Handle special case for adapter tab
            let activeTab;
            if (method === 'walletAdapter') {
                activeTab = adapterTab;
            } else {
                activeTab = document.getElementById(method + 'Tab');
            }
            
            if (activeTab) activeTab.classList.add('active');
            
            // Update content
            const adapterMethod = document.getElementById('walletAdapterMethod');
            const privateMethod = document.getElementById('privateKeyMethod');
            const settingsMethod = document.getElementById('settingsMethod');
            
            if (adapterMethod) adapterMethod.style.display = 'none';
            if (privateMethod) privateMethod.style.display = 'none';
            if (settingsMethod) settingsMethod.style.display = 'none';
            
            // Handle special case for adapter method
            let activeMethod;
            if (method === 'walletAdapter') {
                activeMethod = adapterMethod;
            } else {
                activeMethod = document.getElementById(method + 'Method');
            }
            
            if (activeMethod) activeMethod.style.display = 'block';
        } catch (e) {
            console.error('Error switching wallet method:', e);
            alert('Error switching tabs: ' + e.message);
        }
    }
    
    async function checkPublicAddress() {
      const publicAddress = document.getElementById('publicAddress').value.trim();
      if(!publicAddress) {
          alert('Please enter a valid Solana wallet address');
          return;
      }
      
      threshold = parseFloat(document.getElementById('publicThreshold').value || '0');
      document.getElementById('out').innerHTML = '<p style="text-align:center">Searching for burnable accounts...</p>';
      
      // Get selected RPC endpoint or auto mode
      const selectedRpc = document.getElementById('rpcSelect').value;
      const isAutoMode = selectedRpc === 'auto';
      
      // Try to connect with current or auto fallback
      let connected = false;
      let attempts = 0;
      const maxAttempts = isAutoMode ? RPC_ENDPOINTS.length : 1;
      
      while (!connected && attempts < maxAttempts) {
          try {
              const endpoint = getCurrentRpcEndpoint();
              document.getElementById('out').innerHTML = `<p style="text-align:center">Connecting to ${isAutoMode ? 'RPC endpoint ' + (attempts+1) : 'selected RPC'}...</p>`;
              
              conn = new window.solanaWeb3.Connection(endpoint, 'confirmed');
              connected = true;
              
              // Test the connection
              await conn.getBlockHeight();
              
          } catch (e) {
              console.error('Failed to connect to RPC:', e);
              
              if (isAutoMode && attempts < RPC_ENDPOINTS.length - 1) {
                  // Try next endpoint in auto mode
                  await tryNextRpcEndpoint();
                  attempts++;
              } else {
                  // All endpoints failed or not in auto mode
                  document.getElementById('out').innerHTML = `
                  <div style="text-align:center; padding:20px;">
                    <div style="background:linear-gradient(135deg, rgba(255,69,0,0.1), rgba(255,135,0,0.1)); border-radius:16px; padding:20px; margin-bottom:20px; border:1px solid rgba(255,69,0,0.2);">
                      <h3 style="color:var(--danger-color); margin-bottom:15px;">RPC Connection Error</h3>
                      <p>${e.message}</p>
                      <p>Try selecting "Auto (Fallback Mode)" in Settings.</p>
                    </div>
                    <button class="btn" onclick="goToSettings()">Go to Settings</button>
                  </div>
                `;
                  return;
              }
          }
      }
      
      try {
          // Validate the address
          let walletAddress;
          try {
              walletAddress = new window.solanaWeb3.PublicKey(publicAddress).toString();
          } catch(e) {
              throw new Error('Invalid Solana address format');
          }
          
          document.getElementById('out').innerHTML = `<p style="text-align:center">Scanning wallet ${walletAddress.substring(0,4)}...${walletAddress.substring(walletAddress.length-4)}</p>`;
          
          const filters = [{dataSize:165}, {memcmp:{offset:32, bytes:walletAddress}}];
          
          // Get accounts from both TOKEN and TOKEN_2022 programs
          const tokenProgramId = new window.solanaWeb3.PublicKey(TOKEN_PROGRAM_ID);
          const token2022ProgramId = new window.solanaWeb3.PublicKey(TOKEN_2022_PROGRAM_ID);
          
          // Show progress
          document.getElementById('out').innerHTML = `<p style="text-align:center">Scanning for token accounts...</p>`;
          
          // Get both types of accounts
          let splAccounts = [];
          let token2022Accounts = [];
          
          try {
              splAccounts = await conn.getProgramAccounts(tokenProgramId, {filters});
              document.getElementById('out').innerHTML = `<p style="text-align:center">Found ${splAccounts.length} SPL token accounts. Checking for Token-2022 accounts...</p>`;
          } catch (err) {
              console.warn('Error fetching SPL accounts:', err.message);
          }
          
          try {
              token2022Accounts = await conn.getProgramAccounts(token2022ProgramId, {filters});
          } catch (err) {
              console.warn('Error fetching Token-2022 accounts:', err.message);
          }
          
          const totalAccounts = splAccounts.length + token2022Accounts.length;
          document.getElementById('out').innerHTML = `<p style="text-align:center">Found ${totalAccounts} token accounts (${splAccounts.length} SPL + ${token2022Accounts.length} Token-2022). Analyzing balances...</p>`;
          
          // Process accounts in batches to avoid RPC overload
          const list = [];
          const batchSize = 25; // Process 25 accounts at a time
          
          // Process all accounts (both types)
          const allAccounts = [
              ...splAccounts.map(acc => ({ account: acc, programType: 'SPL' })),
              ...token2022Accounts.map(acc => ({ account: acc, programType: 'Token-2022' }))
          ];
          
          // Process in batches
          for (let i = 0; i < allAccounts.length; i += batchSize) {
              const batch = allAccounts.slice(i, i + batchSize);
              
              // Update progress for each batch
              document.getElementById('out').innerHTML = `<p style="text-align:center">Analyzing accounts... (${Math.min(i + batchSize, allAccounts.length)}/${allAccounts.length})</p>`;
              
              // Process each account in the batch
              for (const {account, programType} of batch) {
                  try {
                      const info = await conn.getParsedAccountInfo(account.pubkey);
                      if (info?.value?.data?.parsed?.info?.tokenAmount) {
                          const amt = info.value.data.parsed.info.tokenAmount.uiAmount;
                          const symbol = info.value.data.parsed.info.tokenAmount.symbol || 'Unknown';
                          const decimals = info.value.data.parsed.info.tokenAmount.decimals;
                          const mintAddress = info.value.data.parsed.info.mint;
                          
                          if (amt <= threshold) {
                              list.push({
                                  addr: account.pubkey.toBase58(),
                                  amt,
                                  symbol,
                                  decimals,
                                  mint: mintAddress,
                                  programType,
                                  canBurn: false
                              });
                          }
                      }
                  } catch (err) {
                      console.error(`Error processing account ${account.pubkey.toString()}:`, err);
                  }
              }
              
              // Small delay between batches to avoid rate limits
              if (i + batchSize < allAccounts.length) {
                  await new Promise(resolve => setTimeout(resolve, 200));
              }
          }
          
          accounts = list;
          renderPublicCheck(walletAddress);
      } catch(e) {
          console.error('Error in checkPublicAddress:', e);
          document.getElementById('out').innerHTML = `
          <div style="text-align:center; padding:20px;">
            <div style="background:linear-gradient(135deg, rgba(255,69,0,0.1), rgba(255,135,0,0.1)); border-radius:16px; padding:20px; margin-bottom:20px; border:1px solid rgba(255,69,0,0.2);">
              <h3 style="color:var(--danger-color); margin-bottom:15px;">Error Occurred</h3>
              <p>${e.message}</p>
              <p style="margin-top:10px;">Try switching to "Auto (Fallback Mode)" in Settings.</p>
              <button class="btn" onclick="goToSettings()" style="margin-top:15px;">Go to Settings</button>
            </div>
          </div>
        `;
      }
    }
    
    // Safe function to go to settings tab
    function goToSettings() {
        try {
            // Make sure wallet panel is visible first
            const walletPanel = document.getElementById('walletPanel');
            if (walletPanel) walletPanel.style.display = 'block';
            
            // Then switch to settings tab
            switchWalletMethod('settings');
        } catch (e) {
            console.error('Error going to settings:', e);
            alert('Could not go to settings. Please try again.');
        }
    }
    
    // Check for already connected wallets
    async function checkForConnectedWallets() {
        try {
            // Check for common wallet providers
            const providers = [
                { name: 'phantom', provider: window.phantom?.solana },
                { name: 'solflare', provider: window.solflare },
                { name: 'backpack', provider: window.backpack?.solana },
                { name: 'generic', provider: window.solana }
            ];
            
            for (const {name, provider} of providers) {
                if (provider) {
                    try {
                        // Check if already connected
                        if (provider.isConnected) {
                            console.log(`Found connected wallet: ${name}`);
                            walletAdapter = provider;
                            walletAdapterConnected = true;
                            
                            // Update status
                            const publicKey = provider.publicKey.toString();
                            lastKnownWalletAddress = publicKey;
                            const shortAddress = publicKey.substring(0,4) + '...' + publicKey.substring(publicKey.length-4);
                            document.getElementById('walletStatus').innerHTML = `<p style="color:var(--accent-color)">Connected: ${shortAddress}</p>`;
                            document.getElementById('adapterLoadBtn').disabled = false;
                            
                            // Switch to wallet adapter tab
                            switchWalletMethod('walletAdapter');
                            
                            // Start wallet change detection
                            startWalletChangeDetection(provider, name);
                            return true;
                        }
                    } catch (e) {
                        console.log(`Error checking ${name} connection:`, e);
                    }
                }
            }
            return false;
        } catch (e) {
            console.error('Error checking for connected wallets:', e);
            return false;
        }
    }
    
    // Start wallet change detection
    function startWalletChangeDetection(provider, walletName) {
        // Clear any existing interval
        if (walletCheckInterval) {
            clearInterval(walletCheckInterval);
        }
        
        // Set up wallet change detection
        walletCheckInterval = setInterval(() => {
            try {
                if (!provider.isConnected) {
                    console.log('Wallet disconnected');
                    handleWalletDisconnect();
                    return;
                }
                
                if (!provider.publicKey) {
                    console.log('Wallet has no public key');
                    handleWalletDisconnect();
                    return;
                }
                
                const currentAddress = provider.publicKey.toString();
                if (lastKnownWalletAddress && currentAddress !== lastKnownWalletAddress) {
                    console.log('Wallet address changed from', lastKnownWalletAddress, 'to', currentAddress);
                    handleWalletChange(provider, currentAddress, walletName);
                }
            } catch (e) {
                console.error('Error in wallet change detection:', e);
            }
        }, 1000); // Check every second
    }
    
    // Handle wallet disconnect
    function handleWalletDisconnect() {
        walletAdapter = null;
        walletAdapterConnected = false;
        lastKnownWalletAddress = null;
        
        if (walletCheckInterval) {
            clearInterval(walletCheckInterval);
            walletCheckInterval = null;
        }
        
        document.getElementById('walletStatus').innerHTML = `<p style="color:var(--danger-color)">Wallet disconnected</p>`;
        document.getElementById('adapterLoadBtn').disabled = true;
    }
    
    // Handle wallet change
    function handleWalletChange(provider, newAddress, walletName) {
        console.log(`Wallet changed to ${newAddress}`);
        
        // Update stored wallet info
        walletAdapter = provider;
        lastKnownWalletAddress = newAddress;
        
        // Update UI
        const shortAddress = newAddress.substring(0,4) + '...' + newAddress.substring(newAddress.length-4);
        document.getElementById('walletStatus').innerHTML = `
            <p style="color:var(--accent-color)">
                Wallet changed: ${shortAddress}
                <span style="display:block; font-size:12px; opacity:0.7;">(${walletName})</span>
            </p>
        `;
        
        // Refresh accounts if they were loaded
        if (accounts.length > 0) {
            loadWithAdapter();
        }
    }
    
    async function loadWithAdapter() {
      if (!walletAdapterConnected || !walletAdapter) {
          alert('Please connect your wallet first');
          return;
      }
      
      threshold = parseFloat(document.getElementById('adapterTh').value || '0');
      document.getElementById('out').innerHTML = '<p style="text-align:center">Searching for burnable accounts...</p>';
      
      // Get selected RPC endpoint or auto mode
      const selectedRpc = document.getElementById('rpcSelect').value;
      const isAutoMode = selectedRpc === 'auto';
      
      // Try to connect with current or auto fallback
      let connected = false;
      let attempts = 0;
      const maxAttempts = isAutoMode ? RPC_ENDPOINTS.length : 1;
      
      while (!connected && attempts < maxAttempts) {
          try {
              const endpoint = getCurrentRpcEndpoint();
              document.getElementById('out').innerHTML = `<p style="text-align:center">Connecting to ${isAutoMode ? 'RPC endpoint ' + (attempts+1) : 'selected RPC'}...</p>`;
              
              conn = new window.solanaWeb3.Connection(endpoint, 'confirmed');
              connected = true;
              
              // Test the connection
              await conn.getBlockHeight();
              
          } catch (e) {
              console.error('Failed to connect to RPC:', e);
              
              if (isAutoMode && attempts < RPC_ENDPOINTS.length - 1) {
                  // Try next endpoint in auto mode
                  await tryNextRpcEndpoint();
                  attempts++;
              } else {
                  // All endpoints failed or not in auto mode
                  document.getElementById('out').innerHTML = `
                  <div style="text-align:center; padding:20px;">
                    <div style="background:linear-gradient(135deg, rgba(255,69,0,0.1), rgba(255,135,0,0.1)); border-radius:16px; padding:20px; margin-bottom:20px; border:1px solid rgba(255,69,0,0.2);">
                      <h3 style="color:var(--danger-color); margin-bottom:15px;">RPC Connection Error</h3>
                      <p>${e.message}</p>
                      <p>Try selecting "Auto (Fallback Mode)" in Settings.</p>
                    </div>
                    <button class="btn" onclick="goToSettings()">Go to Settings</button>
                  </div>
                `;
                  return;
              }
          }
      }
      
      try {
          // Get wallet public key
          const publicKeyObj = walletAdapter.publicKey;
          const walletAddress = publicKeyObj.toString();
          
          document.getElementById('out').innerHTML = `<p style="text-align:center">Scanning wallet ${walletAddress.substring(0,4)}...${walletAddress.substring(walletAddress.length-4)}</p>`;
          
          // Create the token program ID
          const tokenProgramId = new window.solanaWeb3.PublicKey(TOKEN_PROGRAM_ID);
          
          // Get all token accounts owned by this wallet
          const response = await conn.getParsedTokenAccountsByOwner(
              publicKeyObj,
              { programId: tokenProgramId }
          );
          
          document.getElementById('out').innerHTML = `<p style="text-align:center">Found ${response.value.length} SPL token accounts. Analyzing balances...</p>`;
          
          const list = [];
          for (const item of response.value) {
              const accountInfo = item.account;
              const parsedInfo = accountInfo.data.parsed.info;
              const tokenAmount = parsedInfo.tokenAmount;
              
              const amt = tokenAmount.uiAmount || 0;
              const symbol = tokenAmount.symbol || 'Unknown';
              const decimals = tokenAmount.decimals;
              const mint = parsedInfo.mint;
              
              if (amt <= threshold) {
                  list.push({
                      addr: item.pubkey.toString(),
                      amt,
                      symbol,
                      decimals,
                      mint,
                      canBurn: true,
                      useAdapter: true
                  });
              }
          }
          
          accounts = list;
          render();
      } catch(e) {
          console.error('Error loading accounts:', e);
          document.getElementById('out').innerHTML = `
          <div style="text-align:center; padding:20px;">
            <div style="background:linear-gradient(135deg, rgba(255,69,0,0.1), rgba(255,135,0,0.1)); border-radius:16px; padding:20px; margin-bottom:20px; border:1px solid rgba(255,69,0,0.2);">
              <h3 style="color:var(--danger-color); margin-bottom:15px;">Error Occurred</h3>
              <p>${e.message}</p>
            </div>
          </div>
        `;
      }
    }
    
    async function load() {
      threshold = parseFloat(document.getElementById('th').value || '0');
      const skTxt = document.getElementById('sk').value.trim();
      if(!skTxt && accounts.length === 0) {
          alert('Paste secret key or drop JSON');
          return;
      }
      if(skTxt) {
          kp = window.solanaWeb3.Keypair.fromSecretKey(window.solanaWeb3.bs58.decode(skTxt));
      }
      document.getElementById('out').innerHTML = '<p style="text-align:center">Searching for burnable accounts...</p>';
      
      // Get selected RPC endpoint or auto mode
      const selectedRpc = document.getElementById('rpcSelect').value;
      const isAutoMode = selectedRpc === 'auto';
      
      // Try to connect with current or auto fallback
      let connected = false;
      let attempts = 0;
      const maxAttempts = isAutoMode ? RPC_ENDPOINTS.length : 1;
      
      while (!connected && attempts < maxAttempts) {
          try {
              const endpoint = getCurrentRpcEndpoint();
              document.getElementById('out').innerHTML = `<p style="text-align:center">Connecting to ${isAutoMode ? 'RPC endpoint ' + (attempts+1) : 'selected RPC'}...</p>`;
              
              conn = new window.solanaWeb3.Connection(endpoint, 'confirmed');
              connected = true;
              
              // Test the connection
              await conn.getBlockHeight();
              
          } catch (e) {
              console.error('Failed to connect to RPC:', e);
              
              if (isAutoMode && attempts < RPC_ENDPOINTS.length - 1) {
                  // Try next endpoint in auto mode
                  await tryNextRpcEndpoint();
                  attempts++;
              } else {
                  // All endpoints failed or not in auto mode
                  document.getElementById('out').innerHTML = `
                  <div style="text-align:center; padding:20px;">
                    <div style="background:linear-gradient(135deg, rgba(255,69,0,0.1), rgba(255,135,0,0.1)); border-radius:16px; padding:20px; margin-bottom:20px; border:1px solid rgba(255,69,0,0.2);">
                      <h3 style="color:var(--danger-color); margin-bottom:15px;">RPC Connection Error</h3>
                      <p>${e.message}</p>
                      <p>Try selecting "Auto (Fallback Mode)" in Settings.</p>
                    </div>
                    <button class="btn" onclick="goToSettings()">Go to Settings</button>
                  </div>
                `;
                  return;
              }
          }
      }
      
      try {
          const walletAddress = kp.publicKey.toBase58();
          document.getElementById('out').innerHTML = `<p style="text-align:center">Scanning wallet ${walletAddress.substring(0,4)}...${walletAddress.substring(walletAddress.length-4)}</p>`;
          
          const filters = [{dataSize:165}, {memcmp:{offset:32, bytes:walletAddress}}];
          const tokenProgramId = new window.solanaWeb3.PublicKey(TOKEN_PROGRAM_ID);
          const accs = await conn.getProgramAccounts(tokenProgramId, {filters});
          
          document.getElementById('out').innerHTML = `<p style="text-align:center">Found ${accs.length} SPL token accounts. Analyzing balances...</p>`;
          
          const list = [];
          for(const a of accs) {
              const info = await conn.getParsedAccountInfo(a.pubkey);
              const amt = info.value.data.parsed.info.tokenAmount.uiAmount;
              const symbol = info.value.data.parsed.info.tokenAmount.symbol || 'Unknown';
              const decimals = info.value.data.parsed.info.tokenAmount.decimals;
              if(amt <= threshold) list.push({addr:a.pubkey.toBase58(), amt, symbol, decimals, canBurn: true});
          }
          accounts = list;
          render();
      } catch(e) {
          document.getElementById('out').innerHTML = `
          <div style="text-align:center; padding:20px;">
            <div style="background:linear-gradient(135deg, rgba(255,69,0,0.1), rgba(255,135,0,0.1)); border-radius:16px; padding:20px; margin-bottom:20px; border:1px solid rgba(255,69,0,0.2);">
              <h3 style="color:var(--danger-color); margin-bottom:15px;">Error Occurred</h3>
              <p>${e.message}</p>
            </div>
          </div>
        `;
      }
    }
    
    function renderPublicCheck(walletAddress) {
      if(accounts.length === 0) {
        document.getElementById('out').innerHTML = `
          <div style="text-align:center; padding:30px;">
            <div style="background:linear-gradient(135deg, rgba(255,69,0,0.05), rgba(255,135,0,0.05)); border-radius:16px; padding:30px; border:1px solid rgba(255,69,0,0.1);">
              <h3 style="margin-bottom:15px; color:#ff8700;">No Burnable Accounts Found</h3>
              <p>No dust accounts found for wallet ${walletAddress.substring(0,4)}...${walletAddress.substring(walletAddress.length-4)}.</p>
              <p>Try increasing the dust threshold value to find more accounts.</p>
            </div>
          </div>
        `;
        return;
      }
      
      let html = `<h3>Found ${accounts.length} Burnable Accounts</h3>`;
      html += `<p>The following accounts for wallet <strong>${walletAddress.substring(0,4)}...${walletAddress.substring(walletAddress.length-4)}</strong> can be closed to reclaim SOL:</p>`;
      html += '<table><tr><th>Account</th><th>Token</th><th>Amount</th></tr>';
      
      let totalReclaimable = 0.00204928 * accounts.length; // Approximate SOL per account
      
      accounts.forEach((a,i)=>{
        const shortAddr = a.addr.substring(0,6) + '...' + a.addr.substring(a.addr.length-4);
        html += `<tr>
          <td>${shortAddr}</td>
          <td>${a.symbol || 'Unknown'}</td>
          <td>${a.amt}</td>
        </tr>`;
      });
      
      html += '</table>';
      html += `<div style="background:linear-gradient(135deg, rgba(58,134,255,0.1), rgba(0,198,255,0.1)); border-radius:8px; padding:15px; margin-top:20px; text-align:center;">
        <p style="margin:0; font-size:18px;">Estimated SOL to reclaim: <span style="font-weight:bold;">~${totalReclaimable.toFixed(6)} SOL</span></p>
      </div>`;
      html += `<div style="text-align:center; margin-top:20px;">
        <p>To burn these accounts and reclaim SOL, you need to connect your wallet.</p>
        <button class="connect-btn" onclick="showWalletInput()">CONNECT WALLET TO BURN</button>
      </div>`;
      
      document.getElementById('out').innerHTML = html;
    }
    
    function render() {
      if(accounts.length === 0) {
        document.getElementById('out').innerHTML = `
          <div style="text-align:center; padding:30px;">
            <div style="background:linear-gradient(135deg, rgba(255,69,0,0.05), rgba(255,135,0,0.05)); border-radius:16px; padding:30px; border:1px solid rgba(255,69,0,0.1);">
              <h3 style="margin-bottom:15px; color:#ff8700;">No Burnable Accounts Found</h3>
              <p>Try increasing the dust threshold value to find more accounts.</p>
            </div>
          </div>
        `;
        return;
      }
      
      let html = `<h3>Found ${accounts.length} Burnable Accounts</h3>`;
      html += '<p>The following accounts can be closed to reclaim SOL:</p>';
      html += '<table><tr><th>Account</th><th>Token</th><th>Amount</th><th>Action</th></tr>';
      
      let totalReclaimable = 0.00204928 * accounts.length; // Approximate SOL per account
      
      accounts.forEach((a,i)=>{
        const shortAddr = a.addr.substring(0,6) + '...' + a.addr.substring(a.addr.length-4);
        html += `<tr>
          <td>${shortAddr}</td>
          <td>${a.symbol || 'Unknown'}</td>
          <td>${a.amt}</td>
          <td><button class="btn btn-warning" onclick="closeOne(${i})">BURN</button></td>
        </tr>`;
      });
      
      html += '</table>';
      html += `<div style="background:linear-gradient(135deg, rgba(58,134,255,0.1), rgba(0,198,255,0.1)); border-radius:8px; padding:15px; margin-top:20px; text-align:center;">
        <p style="margin:0; font-size:18px;">Estimated SOL to reclaim: <span style="font-weight:bold;">~${totalReclaimable.toFixed(6)} SOL</span></p>
      </div>`;
      html += '<button class="btn btn-warning" onclick="closeAll()">BURN ALL</button>';
      
      document.getElementById('out').innerHTML = html;
    }
    async function closeOne(i){
      const acc=accounts[i]; if(!acc)return;
      try{
        document.getElementById('out').innerHTML = `<p style="text-align:center">Burning account ${acc.addr}...</p>`;
        
        // Create the instruction
        const ix=new window.solanaWeb3.TransactionInstruction({
          programId: new window.solanaWeb3.PublicKey(TOKEN_PROGRAM_ID),
          keys:[
            {pubkey:new window.solanaWeb3.PublicKey(acc.addr),isSigner:false,isWritable:true},
            {pubkey:acc.useAdapter ? walletAdapter.publicKey : kp.publicKey,isSigner:false,isWritable:true},
            {pubkey:acc.useAdapter ? walletAdapter.publicKey : kp.publicKey,isSigner:true,isWritable:false}
          ],
          data:Uint8Array.of(9)  // CloseAccount
        });
        
        let sig;
        
        if (acc.useAdapter) {
            // Use wallet adapter for signing
            const tx = new window.solanaWeb3.Transaction().add(ix);
            tx.feePayer = walletAdapter.publicKey;
            tx.recentBlockhash = (await conn.getLatestBlockhash()).blockhash;
            
            try {
                // Sign and send with wallet adapter
                const { signature } = await walletAdapter.signAndSendTransaction(tx);
                sig = signature;
            } catch (walletError) {
                // Some wallets return different formats, try alternative method
                sig = await walletAdapter.sendTransaction(tx, conn);
            }
        } else {
            // Use private key for signing
            const tx = new window.solanaWeb3.Transaction().add(ix);
            tx.feePayer = kp.publicKey;
            tx.recentBlockhash = (await conn.getLatestBlockhash()).blockhash;
            tx.sign(kp);
            sig = await conn.sendRawTransaction(tx.serialize());
        }
        
        document.getElementById('out').innerHTML = `<p style="text-align:center">Transaction submitted! Confirming...</p>`;
        await conn.confirmTransaction(sig);
        
        const shortSig = typeof sig === 'string' ? 
            sig.substring(0,8) + '...' + sig.substring(sig.length-8) : 
            'Confirmed';
            
        const successHtml = `
          <div style="text-align:center; padding: 20px;">
            <div style="background:linear-gradient(135deg, rgba(58,134,255,0.1), rgba(0,198,255,0.1)); border-radius:16px; padding:30px; margin-bottom:20px; border:1px solid rgba(58,134,255,0.2);">
              <h3 style="background:var(--accent-gradient); -webkit-background-clip:text; background-clip:text; color:transparent; font-size:24px; margin-bottom:20px;">🔥 ACCOUNT BURNED SUCCESSFULLY 🔥</h3>
              <p>Account: ${acc.addr.substring(0,8)}...${acc.addr.substring(acc.addr.length-8)}</p>
              <p>Transaction: ${shortSig}</p>
              <p style="font-size:20px; margin-top:15px;">SOL reclaimed: <span style="font-weight:bold;">~0.00204928 SOL</span></p>
            </div>
            <button class="btn" onclick="render()">Back to Account List</button>
          </div>
        `;
        document.getElementById('out').innerHTML = successHtml;
        accounts.splice(i,1);
      }catch(e){
        document.getElementById('out').innerHTML = `
          <div style="text-align:center; padding:20px;">
            <div style="background:linear-gradient(135deg, rgba(255,69,0,0.1), rgba(255,135,0,0.1)); border-radius:16px; padding:20px; margin-bottom:20px; border:1px solid rgba(255,69,0,0.2);">
              <h3 style="color:var(--danger-color); margin-bottom:15px;">Error Occurred</h3>
              <p>${e.message}</p>
            </div>
            <button class="btn" onclick="render()">Back to Account List</button>
          </div>
        `;
      }
    }
    async function closeAll(){
      if(accounts.length === 0) return;
      
      const confirmBurn = confirm(`Are you sure you want to burn all ${accounts.length} accounts? This action cannot be undone.`);
      if(!confirmBurn) return;
      
      document.getElementById('out').innerHTML = `<p style="text-align:center">Burning ${accounts.length} accounts...</p>`;
      
      let successCount = 0;
      let failCount = 0;
      
      // Check if we're using adapter or private key
      const useAdapter = accounts[0].useAdapter;
      
      for(let i=accounts.length-1;i>=0;i--) {
        try {
          const acc = accounts[i];
          document.getElementById('out').innerHTML = `<p style="text-align:center">Burning account ${successCount + failCount + 1} of ${accounts.length}...</p>`;
          
          // Create the instruction
          const ix=new window.solanaWeb3.TransactionInstruction({
            programId: new window.solanaWeb3.PublicKey(TOKEN_PROGRAM_ID),
            keys:[
              {pubkey:new window.solanaWeb3.PublicKey(acc.addr),isSigner:false,isWritable:true},
              {pubkey:useAdapter ? walletAdapter.publicKey : kp.publicKey,isSigner:false,isWritable:true},
              {pubkey:useAdapter ? walletAdapter.publicKey : kp.publicKey,isSigner:true,isWritable:false}
            ],
            data:Uint8Array.of(9)  // CloseAccount
          });
          
          let sig;
          
          if (useAdapter) {
              // Use wallet adapter for signing
              const tx = new window.solanaWeb3.Transaction().add(ix);
              tx.feePayer = walletAdapter.publicKey;
              tx.recentBlockhash = (await conn.getLatestBlockhash()).blockhash;
              
              // Sign and send with wallet adapter
              sig = await walletAdapter.signAndSendTransaction(tx);
          } else {
              // Use private key for signing
              const tx = new window.solanaWeb3.Transaction().add(ix);
              tx.feePayer = kp.publicKey;
              tx.recentBlockhash = (await conn.getLatestBlockhash()).blockhash;
              tx.sign(kp);
              sig = await conn.sendRawTransaction(tx.serialize());
          }
          
          await conn.confirmTransaction(sig);
          
          successCount++;
          accounts.splice(i,1);
        } catch(e) {
          console.error(`Failed to burn account at index ${i}:`, e);
          failCount++;
        }
      }
      
      const totalReclaimed = successCount * 0.00204928;
      
      const summaryHtml = `
        <div style="text-align:center; padding: 20px;">
          <div style="background:linear-gradient(135deg, rgba(58,134,255,0.1), rgba(0,198,255,0.1)); border-radius:16px; padding:30px; margin-bottom:20px; border:1px solid rgba(58,134,255,0.2);">
            <h3 style="background:var(--accent-gradient); -webkit-background-clip:text; background-clip:text; color:transparent; font-size:24px; margin-bottom:20px;">🔥 BURN COMPLETE 🔥</h3>
            <p>Successfully burned: <span style="font-weight:bold;">${successCount} accounts</span></p>
            <p>Failed: ${failCount} accounts</p>
            <p style="font-size:20px; margin-top:15px;">Total SOL reclaimed: <span style="font-weight:bold;">~${totalReclaimed.toFixed(6)} SOL</span></p>
          </div>
          <button class="btn" onclick="${useAdapter ? 'loadWithAdapter' : 'load'}()">Refresh Account List</button>
        </div>
      `;
      document.getElementById('out').innerHTML = summaryHtml;
    }
    document.getElementById('file').onchange=e=>{
      const f=e.target.files[0]; if(!f)return;
      const r=new FileReader(); r.onload=ev=>{
        try{const arr=Uint8Array.from(JSON.parse(ev.target.result));document.getElementById('sk').value=window.solanaWeb3.bs58.encode(arr);}catch(e){alert('bad json')}
      }
      r.readAsText(f);
    };
    
    // Connection modal functions
    function showConnectionModal(walletName) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('connectionModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'connectionModal';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0,0,0,0.8)';
            modal.style.backdropFilter = 'blur(5px)';
            modal.style.zIndex = '9999';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            document.body.appendChild(modal);
        } else {
            modal.style.display = 'flex';
        }
        
        // Set modal content
        modal.innerHTML = `
            <div style="background: #1a1f2e; border-radius: 16px; width: 90%; max-width: 400px; padding: 20px; color: white; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #2a2f3e; display: flex; justify-content: center; align-items: center; margin-right: 15px;">
                        <div style="width: 20px; height: 20px; background: var(--accent-gradient); border-radius: 50%;"></div>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 20px;">Approve Connection</h3>
                        <div style="color: #8a8f9e; font-size: 14px;">solincinerate.scorptech.it.com</div>
                    </div>
                </div>
                
                <div style="background: #12151e; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                    <div style="margin-bottom: 10px;">Connecting will allow the site to:</div>
                    
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <div style="color: var(--accent-color); margin-right: 10px;">✓</div>
                        <div>View your addresses</div>
                    </div>
                    
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <div style="color: var(--accent-color); margin-right: 10px;">✓</div>
                        <div>View your balances</div>
                    </div>
                    
                    <div style="display: flex; align-items: center;">
                        <div style="color: var(--accent-color); margin-right: 10px;">✓</div>
                        <div>View your on-chain activity</div>
                    </div>
                </div>
                
                <div style="background: #12151e; border-radius: 12px; padding: 15px;">
                    <div style="margin-bottom: 10px;">This will not allow the site to:</div>
                    
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <div style="color: #ff4500; margin-right: 10px;">✗</div>
                        <div>Sign without your approval</div>
                    </div>
                    
                    <div style="display: flex; align-items: center;">
                        <div style="color: #ff4500; margin-right: 10px;">✗</div>
                        <div>Steal your private keys</div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px; color: #8a8f9e; font-size: 14px;">
                    Only connect to websites you trust
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                    <button id="cancelConnection" style="background: #2a2f3e; border: none; color: white; padding: 12px 0; border-radius: 8px; cursor: pointer; width: 48%; font-weight: 600;">Cancel</button>
                    <button style="background: linear-gradient(90deg, #3a86ff, #00c6ff); border: none; color: white; padding: 12px 0; border-radius: 8px; cursor: pointer; width: 48%; font-weight: 600;">Connect</button>
                </div>
            </div>
        `;
        
        // Add cancel button handler
        document.getElementById('cancelConnection').addEventListener('click', function() {
            hideConnectionModal();
            throw new Error('Connection cancelled by user');
        });
    }
    
    function hideConnectionModal() {
        const modal = document.getElementById('connectionModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    function showAlreadyConnectedModal(walletName, address) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('alreadyConnectedModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'alreadyConnectedModal';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0,0,0,0.8)';
            modal.style.backdropFilter = 'blur(5px)';
            modal.style.zIndex = '9999';
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            document.body.appendChild(modal);
        } else {
            modal.style.display = 'flex';
        }
        
        // Set modal content
        modal.innerHTML = `
            <div style="background: #1a1f2e; border-radius: 16px; width: 90%; max-width: 400px; padding: 20px; color: white; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #2a2f3e; display: flex; justify-content: center; align-items: center; margin-right: 15px;">
                        <div style="width: 20px; height: 20px; background: var(--accent-gradient); border-radius: 50%;"></div>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 20px;">Already Connected</h3>
                        <div style="color: #8a8f9e; font-size: 14px;">solincinerate.scorptech.it.com</div>
                    </div>
                </div>
                
                <div style="background: #12151e; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                    <div style="margin-bottom: 10px; text-align: center;">
                        <span style="color: var(--accent-color);">✓</span> ${walletName} is already connected
                    </div>
                    <div style="text-align: center; font-weight: bold; color: var(--accent-color);">${address}</div>
                </div>
                
                <div style="background: #12151e; border-radius: 12px; padding: 15px;">
                    <div style="margin-bottom: 10px;">To connect a different wallet:</div>
                    <ol style="margin-left: 20px; padding-left: 0;">
                        <li style="margin-bottom: 8px;">Open your desired wallet extension</li>
                        <li style="margin-bottom: 8px;">Switch accounts in the wallet if needed</li>
                        <li>Then try connecting again</li>
                    </ol>
                </div>
                
                <div style="display: flex; justify-content: center; margin-top: 20px;">
                    <button id="closeAlreadyConnectedModal" style="background: linear-gradient(90deg, #3a86ff, #00c6ff); border: none; color: white; padding: 12px 0; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600;">Got it</button>
                </div>
            </div>
        `;
        
        // Add close button handler
        document.getElementById('closeAlreadyConnectedModal').addEventListener('click', function() {
            hideAlreadyConnectedModal();
        });
    }
    
    function hideAlreadyConnectedModal() {
        const modal = document.getElementById('alreadyConnectedModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    // Check for already connected wallets when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(checkForConnectedWallets, 500);
    });
    </script>
    <!-- Direct fix script -->
    <script src="direct_fix.js"></script>
</body>
</html>
