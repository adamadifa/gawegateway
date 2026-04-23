# VPS Deployment Guide for Gawegateway

This guide explains how to deploy this application to a VPS (Ubuntu 20.04/22.04 recommended).

## 1. Prerequisites

Ensure your VPS has the following installed:
- **PHP 8.1+** (with extensions: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `curl`)
- **Composer** (PHP Package Manager)
- **Node.js 16+** & **npm**
- **MySQL** or **MariaDB**
- **Nginx**
- **PM2** (Process Manager for Node.js)
- **Git**

## 2. Server Setup

### Install Dependencies (Ubuntu)
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php-fpm php-mysql php-curl php-gd php-mbstring php-xml php-zip php-bcmath

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install PM2
sudo npm install -g pm2

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 3. Clone and Initialize

### Clone the repository
```bash
cd /var/www
sudo git clone https://github.com/adamadifa/gawegateway.git
sudo chown -R $USER:$USER /var/www/gawegateway
cd gawegateway
```

### Initialize Laravel
```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Create .env from example
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env
# DB_DATABASE=gawegateway
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate --force

# Storage link and permissions
php artisan storage:link
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Initialize Node Service
```bash
cd node-service
npm install
cp .env.example .env
# Edit node-service/.env if needed
```

## 4. Running the Services

### Start Node.js with PM2
```bash
cd /var/www/gawegateway/node-service
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

### Configure Nginx
Create a new site configuration: `/etc/nginx/sites-available/gawegateway`

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/gawegateway/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Proxy requests to Node.js service if needed
    # Example: if Node service runs on port 3000
    location /node/ {
        proxy_pass http://localhost:3000/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; # Adjust PHP version
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/gawegateway /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 5. Maintenance

### Updating Code
```bash
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan optimize
pm2 restart all
```
