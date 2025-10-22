import * as solanaWeb3 from '@solana/web3.js';
  import {
      SolanaMobileWalletAdapter,
      createDefaultAuthorizationResultCache
  } from '@solana-mobile/wallet-adapter-mobile';

window.solanaWeb3                = solanaWeb3;
window.solanaWalletAdapterMobile = { SolanaMobileWalletAdapter };
// RPC endpoints
const RPC_ENDPOINTS = {
    solana:   'https://api.mainnet-beta.solana.com',
    alchemy:  'https://solana-mainnet.g.alchemy.com/v2/demo',
    tracker:  'https://rpc-mainnet.solanatracker.io/?api_key=02232df4-4670-439c-b65a-27225d5b841f',
    quicknode:'https://quiet-attentive-frost.solana-mainnet.quiknode.pro/ed83a1d62d5a9b3c0a9fd0fb8d99e8e2d25f5ad3/',
    serum:    'https://solana-api.projectserum.com'
};

// Token program IDs
const TOKEN_PROGRAM_ID  = 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA';
const TOKEN_2022_PROGRAM_ID = 'TokenzQdBNbLqP5VEhdkAS6EPFLC1PHnBqCXEpPxuEb';

// =============== Token List ===============
const TOKEN_LIST_PRIMARY  = 'https://cdn.jsdelivr.net/gh/solana-labs/token-list@main/src/tokens/solana.tokenlist.json';
const TOKEN_LIST_FALLBACK = 'https://raw.githubusercontent.com/solana-labs/token-list/main/src/tokens/solana.tokenlist.json';
let TOKEN_MAP = null;       // mint -> { symbol, name }
const CLOSED_SET = new Set(); // token accounts recently closed this session

async function loadTokenList() {
    if (TOKEN_MAP) return;
    try {
        let resp = await fetch(TOKEN_LIST_PRIMARY);
        if (!resp.ok) resp = await fetch(TOKEN_LIST_FALLBACK);
        const data = await resp.json();
        TOKEN_MAP = {};
        data.tokens.forEach(t => { TOKEN_MAP[t.address] = { symbol: t.symbol, name: t.name }; });
        console.log('Token list loaded:', Object.keys(TOKEN_MAP).length);
    } catch (e) {
        console.warn('Failed to load token list', e);
        TOKEN_MAP = {};
    }
}

let currentWallet   = null;
let currentAdapter  = null;
let foundAccounts   = [];
let PRICE_MAP       = {};   // mint -> price
let DECIMALS_MAP    = {};   // mint -> decimals (filled during account scan)

/* ---------- Jupiter price helpers (unchanged) ---------- */
async function fetchPricesJupiter(mints) {
    if (mints.length === 0) return;
    const SOL_MINT = 'So11111111111111111111111111111111111111112';
    if (!mints.includes(SOL_MINT)) mints.push(SOL_MINT);

    // batch price endpoint
    const chunks = [];
    for (let i = 0; i < mints.length; i += 100) chunks.push(mints.slice(i, i + 100));
    for (const chunk of chunks) {
        const url = 'price_proxy.php?ids=' + chunk.join(',');
        try {
            const resp = await fetch(url);
            const d = await resp.json();
            if (d && d.data) Object.keys(d.data).forEach(m => { PRICE_MAP[m] = d.data[m].price; });
        } catch (e) { console.warn('price fetch failed', e); }
    }

    // fallback quote
    const solPrice = PRICE_MAP[SOL_MINT] || 0;
    const missing  = mints.filter(m => !PRICE_MAP[m]);
    for (const mint of missing) {
        const decimals = DECIMALS_MAP[mint] ?? 0;
        const amount   = BigInt(10) ** BigInt(decimals);
        const quoteUrl = `https://quote-api.jup.ag/v6/quote?inputMint=${mint}&outputMint=${SOL_MINT}&amount=${amount}`;
        try {
            const resp = await fetch(quoteUrl);
            const q = await resp.json();
            if (Array.isArray(q.data) && q.data.length) {
                const solAmt = Number(BigInt(q.data[0].outAmount)) / 1e9;
                if (solAmt > 0 && solPrice > 0) PRICE_MAP[mint] = solAmt * solPrice;
            }
        } catch (_) {}
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


/* ----------------  Mobile Deep-Link  ---------------- */
async function connectMobile () {
  try {
    const mwa = new SolanaMobileWalletAdapter({
      appIdentity: { name: 'SOL Incinerator' },
      cluster: 'mainnet-beta',
      authorizationResultCache: createDefaultAuthorizationResultCache(),
      callbackUrl: 'https://incinerator-seven.vercel.app/api/mwa'
    });

    await mwa.connect();               // opens Phantom
    if (!mwa.publicKey) throw new Error('No wallet address returned');

    currentWallet  = mwa.publicKey.toString();
    currentAdapter = mwa;
    document.getElementById('walletStatus').innerHTML =
      `<span class="success">✅ Connected: ${currentWallet.slice(0,8)}…${currentWallet.slice(-6)}</span>
       <button class="btn-small" style="margin-left:10px" onclick="disconnectWallet()">Disconnect</button>`;
    log('✅ Connected via Mobile Wallet', 'success');
    document.getElementById('walletAddress').value = currentWallet;
    await checkWallet();
  } catch (e) {
    if (e.name === 'WalletNotReadyError') {
      log('❌ No compatible mobile wallet detected. Open the page in Safari/Chrome (not inside another app) and ensure Phantom or Backpack is installed.', 'error');
    } else {
      log('❌ Mobile connect failed: ' + e.message, 'error');
    }
  }
}

/* ---- initialisation ---- */
window.addEventListener('DOMContentLoaded', () => {
    loadTokenList();
    // Detach modals from tab panels so they always render
    ['burnModal','closeAllModal','walletPickerModal','disclaimerModal','feeModal']
      .forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.parentNode && el.parentNode.removeChild(el); document.body.appendChild(el); }
      });
});

Object.assign(window, {
  acceptDisclaimer,
  switchTab,
  openWalletPicker,
  closeWalletPicker,
  connectWallet,
  connectPrivateKey,
  testRpcEndpoint,
  checkWallet,
  closeAccount,
  closeAllAccounts,
  closeAllConfirm,
  feeConfirm,
  openBurnModal,
  closeBurnModal,
  burnAccount,
  disconnectWallet,
  connectDetectedProvider,
  connectMobile
});