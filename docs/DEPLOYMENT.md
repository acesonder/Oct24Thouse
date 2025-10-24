# Deployment Guide - Transition House Platform

## Pre-Deployment Checklist

### 1. Server Requirements
- [ ] PHP 7.4+ installed
- [ ] MySQL 8.0+ installed
- [ ] Composer installed
- [ ] Apache/Nginx configured
- [ ] SSL certificate obtained
- [ ] Domain name configured

### 2. Security Setup
- [ ] Generate strong JWT secret
- [ ] Configure secure database credentials
- [ ] Set up firewall rules
- [ ] Enable HTTPS/SSL
- [ ] Configure file upload restrictions
- [ ] Set appropriate file permissions

### 3. Database Setup
- [ ] Create database and user
- [ ] Import schema.sql
- [ ] Create initial admin user
- [ ] Configure backup schedule

### 4. Application Configuration
- [ ] Copy .env.example to .env
- [ ] Configure all environment variables
- [ ] Set APP_ENV=production
- [ ] Set APP_DEBUG=false
- [ ] Configure email settings
- [ ] Set up SMTP for notifications

## Step-by-Step Deployment

### 1. Prepare Server

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install php7.4 php7.4-cli php7.4-fpm php7.4-mysql php7.4-mbstring php7.4-xml php7.4-curl php7.4-zip -y

# Install MySQL
sudo apt install mysql-server -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install web server (choose one)
# Apache:
sudo apt install apache2 -y
# Or Nginx:
sudo apt install nginx -y
```

### 2. Clone and Configure Application

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/acesonder/Oct24Thouse.git
cd Oct24Thouse

# Install dependencies
sudo composer install --no-dev --optimize-autoloader

# Configure environment
sudo cp .env.example .env
sudo nano .env  # Edit configuration

# Set permissions
sudo chown -R www-data:www-data /var/www/Oct24Thouse
sudo chmod -R 755 /var/www/Oct24Thouse
sudo chmod -R 775 /var/www/Oct24Thouse/uploads
```

### 3. Database Setup

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database
mysql -u root -p
```

```sql
CREATE DATABASE transition_house CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'th_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON transition_house.* TO 'th_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import schema
mysql -u th_user -p transition_house < /var/www/Oct24Thouse/database/schema.sql
```

### 4. Configure Web Server

#### Apache Configuration

```bash
# Create virtual host
sudo nano /etc/apache2/sites-available/transitionhouse.conf
```

```apache
<VirtualHost *:80>
    ServerName transitionhouse.org
    ServerAlias www.transitionhouse.org
    DocumentRoot /var/www/Oct24Thouse/public
    
    <Directory /var/www/Oct24Thouse/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    Alias /api /var/www/Oct24Thouse/api
    
    <Directory /var/www/Oct24Thouse/api>
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/transitionhouse-error.log
    CustomLog ${APACHE_LOG_DIR}/transitionhouse-access.log combined
</VirtualHost>
```

```bash
# Enable site and modules
sudo a2ensite transitionhouse.conf
sudo a2enmod rewrite headers expires deflate
sudo systemctl restart apache2
```

For complete deployment guide, see full documentation.
