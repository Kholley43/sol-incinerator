# Solana Wallet Cleanup Dashboard

A single-file dashboard for cleaning 0-balance / dust SPL token accounts.

## Features

- Lists every SPL-token account owned by a wallet that holds:
  - 0 tokens, or
  - less than a user-set threshold (default = 0.000001)
- Lets you close accounts individually or all at once
- Returns rent-exempt SOL to your wallet

## Installation

1. Make sure Node.js ≥ 18 is installed on your server
2. Clone or copy these files to your server:
   - `walletCleanupDashboard.js`
   - `package.json`
   - `ecosystem.config.js` (optional, for PM2)
3. Install dependencies:
   ```
   npm install
   ```

## Running the Dashboard

### Standard Method
```
node walletCleanupDashboard.js
```

### With Custom RPC
```
RPC=https://your-rpc-url.com node walletCleanupDashboard.js
```

### Using PM2 (Recommended for Production)
```
# Install PM2 if not already installed
npm install -g pm2

# Start the service
pm2 start ecosystem.config.js

# Make it start on system boot
pm2 startup
pm2 save
```

## Subdomain Setup

### Nginx Configuration

Add this to your Nginx configuration:

```nginx
server {
    listen 80;
    server_name wallet.yourdomain.com;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### Apache Configuration

Add this to your Apache configuration:

```apache
<VirtualHost *:80>
    ServerName wallet.yourdomain.com
    
    ProxyPreserveHost On
    ProxyPass / http://localhost:3000/
    ProxyPassReverse / http://localhost:3000/
    
    ErrorLog ${APACHE_LOG_DIR}/wallet-error.log
    CustomLog ${APACHE_LOG_DIR}/wallet-access.log combined
</VirtualHost>
```

Don't forget to enable the proxy modules:
```
a2enmod proxy
a2enmod proxy_http
```

## Security Note

WARNING: Never paste a main-wallet private key in a browser UI you don't fully control. Use a temp keypair or run this on a trusted local machine.
