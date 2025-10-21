//
// One-file self-hosted dashboard for cleaning 0-balance / dust SPL accounts.
//   • Run:   RPC=<RPC_URL> node walletCleanupDashboard.js
//   • Opens http://localhost:3000
//
// WARNING: Never paste a main-wallet private key in a browser UI you don't fully
//          control. Use a temp keypair or run this on a trusted local machine.

const http = require('http');
const fs   = require('fs');
const url  = require('url');
const { PublicKey, Connection, Keypair, Transaction, SystemProgram } = require('@solana/web3.js');

const RPC  = process.env.RPC || 'https://api.mainnet-beta.solana.com';
const PORT = 3000;
const conn = new Connection(RPC, 'confirmed');
const TOKEN_PROGRAM_ID = new PublicKey('TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA');

function html(body){return `<!DOCTYPE html>
<html><head><meta charset=utf8><title>Wallet Cleaner</title>
<style>body{font-family:Arial;margin:2em}table{border-collapse:collapse}td,th{padding:4px 8px;border:1px solid #ccc}textarea{width:100%}</style>
</head><body>${body}</body></html>`}

function serveIndex(res){
  res.writeHead(200,{'Content-Type':'text/html'});
  res.end(html(`
<h2>SOL Wallet Cleanup Dashboard</h2>
<p>Paste a <b>base-58 secret key</b> (64-byte) or drag-drop a Keypair JSON file.<br>
Nothing is ever sent off-machine; transactions are signed client-side in the browser and posted directly to the RPC.</p>

<input type=file id=file accept=".json">
<textarea id=sk rows=4 placeholder="Base58 secret key"></textarea><br>
<label>Threshold&nbsp;<input id=th type=number value=0.000001 step=0.000001></label>
<button onclick="load()">Load Accounts</button>
<div id=out style="white-space:pre;margin-top:1em"></div>

<script>
let kp, conn, threshold=1e-6, accounts=[];
async function load(){
  threshold=parseFloat(document.getElementById('th').value||'0');
  const skTxt=document.getElementById('sk').value.trim();
  if(!skTxt&&accounts.length===0){alert('Paste secret key or drop JSON');return}
  if(skTxt){kp=window.solanaWeb3.Keypair.fromSecretKey(window.solanaWeb3.bs58.decode(skTxt))}
  document.getElementById('out').textContent='loading…';
  conn=new window.solanaWeb3.Connection('${RPC}','confirmed');
  const filters=[{dataSize:165},{memcmp:{offset:32,bytes:kp.publicKey.toBase58()}}];
  const accs=await conn.getProgramAccounts('${TOKEN_PROGRAM_ID}',{filters});
  const list=[];
  for(const a of accs){
    const info=await conn.getParsedAccountInfo(a.pubkey);
    const amt=info.value.data.parsed.info.tokenAmount.uiAmount;
    if(amt<=threshold) list.push({addr:a.pubkey.toBase58(),amt});
  }
  accounts=list;
  render();
}
function render(){
  let html='<table><tr><th>Account</th><th>Amount</th><th></th></tr>';
  accounts.forEach((a,i)=>html+=\`<tr><td>\${a.addr}</td><td>\${a.amt}</td><td><button onclick="closeOne(\${i})">Close</button></td></tr>\`);
  html+='</table><button onclick="closeAll()">Close All</button>';
  document.getElementById('out').innerHTML=html;
}
async function closeOne(i){
  const acc=accounts[i]; if(!acc)return;
  try{
    const ix=new window.solanaWeb3.TransactionInstruction({
      programId:'${TOKEN_PROGRAM_ID}',
      keys:[
        {pubkey:new window.solanaWeb3.PublicKey(acc.addr),isSigner:false,isWritable:true},
        {pubkey:kp.publicKey,isSigner:false,isWritable:true},
        {pubkey:kp.publicKey,isSigner:true,isWritable:false}
      ],
      data:Uint8Array.of(9)  // CloseAccount
    });
    const tx=new window.solanaWeb3.Transaction().add(ix);
    tx.feePayer=kp.publicKey; tx.recentBlockhash=(await conn.getLatestBlockhash()).blockhash;
    tx.sign(kp);
    const sig=await conn.sendRawTransaction(tx.serialize()); await conn.confirmTransaction(sig);
    alert('Closed '+acc.addr+' sig '+sig);
    accounts.splice(i,1); render();
  }catch(e){alert(e.message)}
}
async function closeAll(){for(let i=accounts.length-1;i>=0;i--)await closeOne(i);}
document.getElementById('file').onchange=e=>{
  const f=e.target.files[0]; if(!f)return;
  const r=new FileReader(); r.onload=ev=>{
    try{const arr=Uint8Array.from(JSON.parse(ev.target.result));document.getElementById('sk').value=window.solanaWeb3.bs58.encode(arr);}catch(e){alert('bad json')}}
  r.readAsText(f);
};
</script>
<script src="https://unpkg.com/@solana/web3.js@1.93.5/lib/index.iife.js"></script>`));
}

http.createServer((req,res)=>{
  const p=url.parse(req.url).pathname;
  if(p==='/'){serveIndex(res)}else{res.writeHead(404);res.end('not found')}
}).listen(PORT,()=>console.log(`Dashboard at http://localhost:${PORT} (RPC ${RPC})`));
