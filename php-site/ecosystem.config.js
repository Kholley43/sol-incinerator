module.exports = {
  apps: [{
    name: 'wallet-cleanup',
    script: 'walletCleanupDashboard.js',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '200M',
    env: {
      NODE_ENV: 'production',
      PORT: 3000,
      RPC: 'https://api.mainnet-beta.solana.com'
    }
  }]
};
