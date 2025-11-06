# LeadSharks LMS - Development Environment Setup Guide

## 📋 Table of Contents

- [Prerequisites](#prerequisites)
- [System Requirements](#system-requirements)
- [Local Development Setup](#local-development-setup)
- [Database Configuration](#database-configuration)
- [Web Server Configuration](#web-server-configuration)
- [IDE Configuration](#ide-configuration)
- [Testing Environment](#testing-environment)
- [Troubleshooting](#troubleshooting)
- [Development Tools](#development-tools)
- [Performance Optimization](#performance-optimization)

## ✅ Prerequisites

Before setting up the development environment, ensure you have the following installed on your system:

### Required Software
```
✅ PHP 8.1 or higher
✅ MySQL 8.0+ or MariaDB 10.3+
✅ Web Server (Apache 2.4+ or Nginx 1.18+)
✅ Composer (PHP Package Manager)
✅ Git (Version Control)
✅ Node.js 16+ (for frontend asset management)
✅ Code Editor (VS Code recommended)
```

### Optional but Recommended
```
✅ XAMPP/WAMP/MAMP (for easy local server setup)
✅ phpMyAdmin (for database management)
✅ Postman (for API testing)
✅ Docker (for containerized development)
```

## 💻 System Requirements

### Minimum Requirements
```
OS: Windows 10/11, macOS 10.15+, or Linux (Ubuntu 18.04+)
RAM: 4GB (8GB recommended)
Storage: 2GB free space
Processor: Dual-core 2.0GHz or equivalent
```

### Recommended Requirements
```
OS: Windows 11, macOS 12+, or Linux (Ubuntu 20.04+)
RAM: 8GB or more
Storage: 5GB+ SSD space
Processor: Quad-core 2.5GHz or equivalent
Network: Stable internet connection
```

### PHP Extensions Required
```php
// Core extensions
✅ mysqli or pdo_mysql
✅ mbstring
✅ xml
✅ json
✅ openssl
✅ zip
✅ curl
✅ gd or imagick
✅ fileinfo
✅ iconv
```

## 🚀 Local Development Setup

### Option 1: XAMPP Setup (Recommended for Beginners)

#### Step 1: Download and Install XAMPP
```bash
# Download from: https://www.apachefriends.org/
# Choose version with PHP 8.1+
# Install with default settings
```

#### Step 2: Start Services
```bash
# Open XAMPP Control Panel
# Start Apache and MySQL services
# Verify Apache is running on http://localhost
# Verify MySQL is accessible via phpMyAdmin
```

#### Step 3: Clone Repository
```bash
# Navigate to XAMPP htdocs directory
cd C:\xampp\htdocs\  # Windows
cd /Applications/XAMPP/htdocs/  # macOS
cd /opt/lampp/htdocs/  # Linux

# Clone the repository
git clone https://github.com/YourUsername/leadshark-lms.git
cd leadshark-lms
```

### Option 2: Manual Setup

#### Step 1: Install PHP
```bash
# Windows (using Chocolatey)
choco install php --version=8.1.0

# macOS (using Homebrew)
brew install php@8.1

# Ubuntu/Debian
sudo apt update
sudo apt install php8.1 php8.1-common php8.1-cli php8.1-fpm
sudo apt install php8.1-mysql php8.1-mbstring php8.1-xml php8.1-zip php8.1-curl php8.1-gd
```

#### Step 2: Install MySQL
```bash
# Windows
# Download from: https://dev.mysql.com/downloads/installer/

# macOS
brew install mysql

# Ubuntu/Debian
sudo apt install mysql-server mysql-client
```

#### Step 3: Install Apache/Nginx
```bash
# Apache on Ubuntu
sudo apt install apache2

# Nginx on Ubuntu  
sudo apt install nginx

# Enable PHP module for Apache
sudo a2enmod php8.1
sudo systemctl restart apache2
```

#### Step 4: Install Composer
```bash
# Download from: https://getcomposer.org/download/
# Or via command line:

# Linux/macOS
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Windows (download installer)
# https://getcomposer.org/Composer-Setup.exe
```

### Option 3: Docker Setup (Advanced)

#### Step 1: Create Docker Configuration
Create `docker-compose.yml`:
```yaml
version: '3.8'

services:
  web:
    image: php:8.1-apache
    container_name: leadshark-web
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - database
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html
    
  database:
    image: mysql:8.0
    container_name: leadshark-db
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: lms_db
      MYSQL_USER: lms_user
      MYSQL_PASSWORD: lms_password
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database/lms_db.sql:/docker-entrypoint-initdb.d/init.sql

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: leadshark-phpmyadmin
    ports:
      - "8081:80"
    environment:
      PMA_HOST: database
      PMA_USER: root
      PMA_PASSWORD: rootpassword
    depends_on:
      - database

volumes:
  mysql_data:
```

#### Step 2: Start Docker Environment
```bash
# Start services
docker-compose up -d

# Install PHP extensions in web container
docker exec -it leadshark-web bash
docker-php-ext-install mysqli pdo_mysql zip gd
```

## 🗄️ Database Configuration

### Step 1: Create Database
```sql
-- Using phpMyAdmin or MySQL command line
CREATE DATABASE lms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Create user (optional, for security)
CREATE USER 'lms_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON lms_db.* TO 'lms_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 2: Import Database Structure
```bash
# Method 1: Using command line
mysql -u root -p lms_db < database/lms_db.sql

# Method 2: Using phpMyAdmin
# 1. Open phpMyAdmin (http://localhost/phpmyadmin)
# 2. Select lms_db database
# 3. Go to Import tab
# 4. Choose database/lms_db.sql file
# 5. Click Go
```

### Step 3: Verify Database Import
```sql
-- Check if tables were created
USE lms_db;
SHOW TABLES;

-- Verify sample data
SELECT COUNT(*) FROM client_list;
SELECT COUNT(*) FROM users;
```

### Step 4: Configure Database Connection
Edit `initialize.php`:
```php
<?php
// Development configuration
if(!defined('DB_SERVER')) define('DB_SERVER',"localhost");
if(!defined('DB_USERNAME')) define('DB_USERNAME',"lms_user");  // or "root"
if(!defined('DB_PASSWORD')) define('DB_PASSWORD',"secure_password");  // your password
if(!defined('DB_NAME')) define('DB_NAME',"lms_db");

// Development base URL
if(!defined('base_url')) define('base_url','http://localhost/leadshark-lms/');
if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );
?>
```

## 🌐 Web Server Configuration

### Apache Configuration

#### Virtual Host Setup (Optional but Recommended)
Create `leadshark-lms.conf`:
```apache
<VirtualHost *:80>
    ServerName leadshark-lms.local
    DocumentRoot "C:/xampp/htdocs/leadshark-lms"
    
    <Directory "C:/xampp/htdocs/leadshark-lms">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/leadshark-lms_error.log"
    CustomLog "logs/leadshark-lms_access.log" combined
</VirtualHost>
```

#### Update Hosts File
```bash
# Windows: C:\Windows\System32\drivers\etc\hosts
# macOS/Linux: /etc/hosts

127.0.0.1 leadshark-lms.local
```

#### .htaccess Configuration
Ensure `.htaccess` exists in root:
```apache
# LeadSharks LMS - Apache Configuration
RewriteEngine On

# Redirect to admin by default
RewriteRule ^$ admin/ [R=301,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# Prevent access to sensitive files
<FilesMatch "(^#.*#|\.(bak|config|dist|fla|inc|ini|log|psd|sh|sql|swp)|~)$">
    Order allow,deny
    Deny from all
    Satisfy All
</FilesMatch>

# Prevent access to configuration files
<Files "initialize.php">
    Order allow,deny
    Deny from all
</Files>

<Files "config.php">
    Order allow,deny
    Deny from all
</Files>

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
</IfModule>
```

### Nginx Configuration (Alternative)

Create `leadshark-lms.conf`:
```nginx
server {
    listen 80;
    server_name leadshark-lms.local;
    root /var/www/leadshark-lms;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security configurations
    location ~ /\. {
        deny all;
    }

    location ~* \.(log|sql|bak|backup)$ {
        deny all;
    }

    # Static asset caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

## 💿 Install Dependencies

### PHP Dependencies
```bash
# Navigate to project directory
cd /path/to/leadshark-lms

# Install PHP dependencies
composer install

# If composer.json doesn't exist, create it:
composer init
composer require phpoffice/phpspreadsheet:^4.2
composer require phpmailer/phpmailer:^6.10
```

### Frontend Dependencies (Optional)
```bash
# If using Node.js for asset management
npm init -y
npm install --save-dev sass bootstrap@4 jquery datatables.net

# Build assets (if build process exists)
npm run build
```

## 🛠 IDE Configuration

### VS Code Setup (Recommended)

#### Essential Extensions
```json
{
  "recommendations": [
    "ms-vscode.vscode-json",
    "bmewburn.vscode-intelephense-client",
    "xdebug.php-debug",
    "felixfbecker.php-intellisense",
    "ms-vscode.vscode-typescript-next",
    "bradlc.vscode-tailwindcss",
    "formulahendry.auto-rename-tag",
    "christian-kohler.path-intellisense",
    "ms-vscode.vscode-eslint",
    "esbenp.prettier-vscode"
  ]
}
```

#### Workspace Settings
Create `.vscode/settings.json`:
```json
{
    "php.validate.executablePath": "/path/to/php",
    "intelephense.files.maxSize": 5000000,
    "files.associations": {
        "*.php": "php"
    },
    "emmet.includeLanguages": {
        "php": "html"
    },
    "editor.formatOnSave": true,
    "editor.tabSize": 4,
    "editor.insertSpaces": true,
    "php.suggest.basic": false,
    "files.exclude": {
        "**/vendor": true,
        "**/node_modules": true,
        "**/.git": true
    }
}
```

#### Debug Configuration
Create `.vscode/launch.json`:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        },
        {
            "name": "Launch currently open script",
            "type": "php",
            "request": "launch",
            "program": "${file}",
            "cwd": "${fileDirname}",
            "port": 0,
            "runtimeArgs": [
                "-dxdebug.start_with_request=yes"
            ],
            "env": {
                "XDEBUG_MODE": "debug,develop",
                "XDEBUG_CONFIG": "client_port=${port}"
            }
        }
    ]
}
```

### Xdebug Setup

#### Install Xdebug
```bash
# For XAMPP, often pre-installed
# For manual PHP installation:
pecl install xdebug

# Ubuntu/Debian
sudo apt install php8.1-xdebug
```

#### Configure Xdebug
Add to `php.ini`:
```ini
[XDebug]
zend_extension = xdebug
xdebug.mode = debug
xdebug.start_with_request = yes
xdebug.client_port = 9003
xdebug.client_host = 127.0.0.1
xdebug.log = /tmp/xdebug_remote.log
```

## 🧪 Testing Environment

### Setup Test Database
```sql
CREATE DATABASE lms_test_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### PHPUnit Configuration
Create `phpunit.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false">
    
    <testsuites>
        <testsuite name="LeadSharks LMS Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    
    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">classes</directory>
            <exclude>
                <directory>vendor</directory>
                <directory>tests</directory>
            </exclude>
        </whitelist>
    </filter>
</phpunit>
```

### Install Testing Dependencies
```bash
composer require --dev phpunit/phpunit:^9.0
composer require --dev mockery/mockery
```

## 🔧 Development Tools

### Code Quality Tools

#### Install PHP_CodeSniffer
```bash
composer require --dev squizlabs/php_codesniffer
```

#### PHPStan for Static Analysis
```bash
composer require --dev phpstan/phpstan
```

#### Create quality check script
Create `scripts/quality-check.sh`:
```bash
#!/bin/bash
echo "Running code quality checks..."

echo "1. PHP Syntax Check"
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;

echo "2. Code Standards Check (PSR-12)"
./vendor/bin/phpcs --standard=PSR12 --ignore=vendor/ .

echo "3. Static Analysis"
./vendor/bin/phpstan analyse --level=5 classes/ admin/

echo "Quality check completed!"
```

### Git Hooks Setup

#### Pre-commit Hook
Create `.git/hooks/pre-commit`:
```bash
#!/bin/sh
# Pre-commit hook for LeadSharks LMS

echo "Running pre-commit checks..."

# Check for PHP syntax errors
for file in $(git diff --cached --name-only --diff-filter=ACM | grep '\.php$'); do
    php -l "$file" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "PHP syntax error in $file"
        exit 1
    fi
done

# Check for debugging code
if git diff --cached | grep -E "(var_dump|print_r|console\.log|debugger)" > /dev/null; then
    echo "Debugging code found. Please remove before committing."
    exit 1
fi

echo "Pre-commit checks passed!"
```

## ⚡ Performance Optimization

### PHP Configuration
Optimize `php.ini` for development:
```ini
; Memory and execution
memory_limit = 256M
max_execution_time = 60
max_input_time = 60

; File uploads
upload_max_filesize = 64M
post_max_size = 64M

; Error reporting (development)
display_errors = On
error_reporting = E_ALL

; Session settings
session.gc_maxlifetime = 1440
session.cookie_httponly = 1

; OPcache (for production-like testing)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
```

### Database Optimization
```sql
-- Add indexes for better performance (development testing)
USE lms_db;

-- Index on frequently queried fields
CREATE INDEX idx_client_status ON client_list(status);
CREATE INDEX idx_lead_created ON client_list(date_created);
CREATE INDEX idx_lead_updated ON client_list(date_updated);

-- Analyze tables
ANALYZE TABLE client_list;
ANALYZE TABLE users;
```

## 🐛 Troubleshooting

### Common Issues and Solutions

#### 1. Database Connection Error
```
Error: Could not connect to MySQL server
```
**Solutions:**
- Verify MySQL service is running
- Check database credentials in `initialize.php`
- Ensure database exists
- Check firewall settings

#### 2. PHP Extensions Missing
```
Error: Call to undefined function mysqli_connect()
```
**Solutions:**
- Install missing PHP extensions
- Restart web server after installation
- Check `php.ini` configuration

#### 3. Permission Issues
```
Error: Permission denied writing to directory
```
**Solutions:**
```bash
# Set proper permissions
chmod 755 uploads/
chmod 755 temp/
chmod 644 config.php

# For development (less secure but functional)
chmod -R 777 uploads/
chmod -R 777 temp/
```

#### 4. Composer Issues
```
Error: Composer command not found
```
**Solutions:**
- Reinstall Composer
- Add to system PATH
- Use full path: `/usr/local/bin/composer`

#### 5. Rewrite Rules Not Working
```
Error: 404 Not Found for admin pages
```
**Solutions:**
- Enable Apache mod_rewrite: `sudo a2enmod rewrite`
- Check `.htaccess` file exists
- Verify AllowOverride All in Apache config

### Debug Mode Setup

#### Enable Debug Mode
Add to `config.php`:
```php
// Development debug mode
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === 'leadshark-lms.local') {
    define('DEBUG_MODE', true);
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    // Log all errors
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/temp/php_errors.log');
} else {
    define('DEBUG_MODE', false);
}
```

#### Custom Error Handler
```php
// Add to config.php
if (DEBUG_MODE) {
    function debugHandler($errno, $errstr, $errfile, $errline) {
        $error_message = "Error [$errno]: $errstr in $errfile on line $errline\n";
        error_log($error_message);
        echo "<div style='color: red; background: #ffe6e6; padding: 10px; margin: 5px;'>";
        echo "<strong>Debug Error:</strong> $error_message";
        echo "</div>";
    }
    set_error_handler('debugHandler');
}
```

### Environment Verification Script

Create `scripts/verify-environment.php`:
```php
<?php
echo "<h2>LeadSharks LMS - Environment Check</h2>";

// PHP Version
echo "<h3>PHP Configuration</h3>";
echo "PHP Version: " . phpversion() . "<br>";

// Required extensions
$required_extensions = ['mysqli', 'pdo_mysql', 'mbstring', 'xml', 'json', 'openssl', 'zip', 'curl', 'gd'];
echo "<h4>Required Extensions:</h4>";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? "✅ Loaded" : "❌ Missing";
    echo "$ext: $status<br>";
}

// Database connection test
echo "<h3>Database Connection</h3>";
include_once '../initialize.php';
try {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        echo "❌ Connection failed: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ Database connection successful<br>";
        echo "Server version: " . $conn->server_info . "<br>";
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// File permissions
echo "<h3>File Permissions</h3>";
$directories = ['../uploads', '../temp'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? "✅ Writable" : "❌ Not writable";
        echo "$dir: $writable (Permissions: $perms)<br>";
    } else {
        echo "$dir: ❌ Directory not found<br>";
    }
}

// Configuration check
echo "<h3>Configuration</h3>";
echo "Base URL: " . (defined('base_url') ? base_url : 'Not defined') . "<br>";
echo "Base App: " . (defined('base_app') ? base_app : 'Not defined') . "<br>";

echo "<p><strong>Environment check completed!</strong></p>";
?>
```

## ✅ Development Environment Checklist

### Initial Setup Checklist
```
□ PHP 8.1+ installed and configured
□ MySQL/MariaDB installed and running
□ Web server (Apache/Nginx) configured
□ Composer installed globally
□ Git configured with user details
□ Repository cloned locally
□ Database created and imported
□ Configuration files updated
□ Dependencies installed via Composer
□ Web server virtual host configured (optional)
□ IDE/editor set up with extensions
□ Xdebug configured for debugging
□ Environment verification script passed
```

### Daily Development Checklist
```
□ Pull latest changes from develop branch
□ Check for any new dependencies
□ Run environment verification if issues arise
□ Create feature branch for new work
□ Commit changes regularly with good messages
□ Test functionality thoroughly
□ Run code quality checks before pushing
□ Create pull request for code review
```

This comprehensive development setup guide ensures that all team members can quickly set up a consistent and productive development environment for the LeadSharks LMS project. Whether you're a beginner using XAMPP or an advanced developer using Docker, these instructions will get you up and running efficiently.