import express from 'express';
import path     from 'path';
import { fileURLToPath } from 'url';
import { Connection } from '@solana/web3.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const app  = express();
const conn = new Connection('https://api.mainnet-beta.solana.com');

app.use(express.json({ limit: '2mb' }));

// Mobile-wallet-adapter callback
app.post('/mwa', async (req, res) => {
  try {
    const { signedTx } = req.body;
    if (!signedTx) throw new Error('missing signedTx');
    const sig = await conn.sendRawTransaction(
      Buffer.from(signedTx, 'base64')
    );
    await conn.confirmTransaction(sig, 'confirmed');
    res.json({ ok: true, sig });
  } catch (e) {
    res.status(500).json({ ok: false, error: e.message });
  }
});

// serve compiled front-end
app.use(express.static(path.join(__dirname, 'dist')));
app.get('*', (_, res) =>
  res.sendFile(path.join(__dirname, 'dist/index.html'))
);

app.listen(process.env.PORT || 3000);
