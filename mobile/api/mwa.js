// mobile/api/mwa.js
import { Connection } from '@solana/web3.js';

export default async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).end('Method not allowed');
  const { signedTx } = req.body || {};
  if (!signedTx) return res.status(400).json({ ok:false, error:'missing signedTx' });

  try {
    const conn = new Connection('https://api.mainnet-beta.solana.com');
    const sig  = await conn.sendRawTransaction(Buffer.from(signedTx, 'base64'));
    await conn.confirmTransaction(sig, 'confirmed');
    return res.json({ ok:true, sig });
  } catch (e) {
    return res.status(500).json({ ok:false, error:e.message });
  }
}
