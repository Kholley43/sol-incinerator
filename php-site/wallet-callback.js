import express from 'express';
import { Connection } from '@solana/web3.js';

// --- Configuration --------------------------------------------------------
const RPC      = process.env.SOL_RPC || 'https://api.mainnet-beta.solana.com';
const PORT     = process.env.PORT    || 4000;  // behind Apache proxy
// --------------------------------------------------------------------------

const conn = new Connection(RPC);
const app  = express();
app.use(express.json({ limit: '2mb' }));

// Simple in-memory session map { sessionId: true } (optional)
const sessions = new Map();

app.post('/mwa', async (req, res) => {
  try {
    const { signedTx, sessionId } = req.body;
    if (!signedTx) throw new Error('Missing signedTx');

    // Optional replay protection
    if (sessionId) {
      if (!sessions.has(sessionId)) throw new Error('Invalid session');
      sessions.delete(sessionId);
    }

    const sig = await conn.sendRawTransaction(Buffer.from(signedTx, 'base64'));
    await conn.confirmTransaction(sig, 'confirmed');
    res.json({ ok: true, sig });
  } catch (e) {
    console.error('MWA callback error:', e);
    res.status(500).json({ ok: false, error: e.message });
  }
});

app.listen(PORT, () => console.log(`[MWA] callback listening on :${PORT}`));
