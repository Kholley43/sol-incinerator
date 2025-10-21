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

/* ---------- UI helpers: log, progress, display table (unchanged) ---------- */
/* paste the entire remaining script exactly as it is in your index.php,
   starting from `// Accept disclaimer` down to the bottom, **without** the
   Buffer shim lines.  Nothing else needs modification except connectMobile()
   below. */

/* ----------------  Mobile Deep-Link  ---------------- */
async function connectMobile() {
    try {
        const callbackUrl = '/mwa';   // same-origin to Express callback on Vercel
        const mwa = new solanaWalletAdapterMobile.MobileWalletAdapter({
            appIdentity: { name: 'SOL Incinerator' },
            cluster: 'mainnet-beta',
            authorizationResultCache: 'sessionStorage',
            callbackUrl
        });

        const { accounts } = await mwa.authorize();
        if (!accounts || !accounts.length) throw new Error('No account returned');

        currentWallet  = accounts[0].address;
        currentAdapter = mwa;

        document.getElementById('walletStatus').innerHTML =
            `<span class="success">✅ Connected: ${currentWallet.slice(0,8)}…${currentWallet.slice(-6)}</span>
             <button style="margin-left:10px;" class="btn-small" onclick="disconnectWallet()">Disconnect</button>`;

        log('✅ Connected via Mobile Wallet', 'success');
        document.getElementById('walletAddress').value = currentWallet;
        await checkWallet();
    } catch (e) {
        log('❌ Mobile connect failed: ' + e.message, 'error');
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
