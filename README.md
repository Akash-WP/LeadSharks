# LeadSharks LMS - Lead Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/Build-Stable-green.svg)](https://github.com/YourOrg/leadshark-lms)

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Development Workflow](#development-workflow)
- [API Documentation](#api-documentation)
- [Contributing](#contributing)
- [Security](#security)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Support](#support)

## 🎯 Overview

LeadSharks LMS is a comprehensive Lead Management System designed to streamline lead tracking, client management, and sales pipeline optimization. Built with PHP and modern web technologies, it provides a robust platform for managing leads from acquisition to conversion.

### Key Objectives
- **Lead Tracking**: Monitor leads throughout the sales funnel
- **Client Management**: Maintain detailed client profiles and interaction history
- **Analytics & Reporting**: Generate insights on lead performance and conversion rates
- **Automated Alerts**: Receive notifications for follow-ups and important updates

## ✨ Features

### Core Functionality
- 📊 **Dashboard Analytics** - Real-time lead metrics and performance indicators
- 👥 **Lead Management** - Comprehensive lead tracking and status updates
- 🏢 **Client Profiles** - Detailed client information and interaction history
- 📈 **Sales Pipeline** - Visual representation of the sales process
- 🔔 **Alert System** - Morning and evening alerts for follow-ups
- 📋 **Bulk Operations** - Import/export leads via Excel spreadsheets
- 👤 **User Management** - Role-based access control and permissions
- 📱 **Mobile Responsive** - Optimized for desktop and mobile devices

### Advanced Features
- 🔍 **Advanced Search & Filtering** - Multi-criteria lead search capabilities
- 📊 **Custom Reports** - Generate detailed reports and analytics
- 🔄 **Status History Tracking** - Complete audit trail of lead changes
- 💬 **Internal Messaging** - Team communication system
- 🎯 **Lead Scoring** - Automated lead qualification and prioritization
- 📅 **Calendar Integration** - Schedule and track follow-up activities

## 🛠 Technology Stack

### Backend
- **PHP 8.1+** - Server-side scripting
- **MySQL/MariaDB** - Database management
- **Composer** - Dependency management

### Frontend
- **HTML5** - Markup language
- **CSS3** - Styling and responsive design
- **JavaScript** - Interactive functionality
- **Bootstrap 4** - CSS framework
- **DataTables** - Advanced table functionality
- **jQuery** - JavaScript library

### Libraries & Dependencies
- **PHPSpreadsheet** - Excel file processing
- **PHPMailer** - Email functionality
- **AdminLTE** - Admin dashboard template
- **Chart.js** - Data visualization

### Development Tools
- **Git** - Version control
- **Composer** - PHP dependency manager
- **npm** - Node.js package manager (for frontend assets)

## 💻 System Requirements

### Server Requirements
- **PHP**: 8.1 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Memory**: 512MB RAM minimum, 2GB recommended
- **Storage**: 1GB available disk space

### PHP Extensions
- `mysqli` or `pdo_mysql`
- `gd` or `imagick` (for image processing)
- `zip` (for Excel file handling)
- `curl` (for API calls)
- `mbstring` (for string handling)
- `openssl` (for security features)

### Development Environment
- **Git** 2.20+
- **Composer** 2.0+
- **Node.js** 16+ (for asset compilation)
- **Code Editor**: VS Code recommended with PHP extensions

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/YourOrg/leadshark-lms.git
cd leadshark-lms
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install frontend dependencies (if applicable)
npm install
```

### 3. Database Setup
```bash
# Import the database
mysql -u your_username -p lms_db < database/lms_db.sql

# Or using phpMyAdmin:
# 1. Create database 'lms_db'
# 2. Import database/lms_db.sql
```

### 4. Configuration
```bash
# Copy configuration template
cp config.example.php config.php

# Edit configuration file
nano config.php
```

### 5. Set Permissions
```bash
# Set appropriate permissions for upload directories
chmod 755 uploads/
chmod 755 temp/
chmod 644 config.php
```

### 6. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## ⚙️ Configuration

### Database Configuration
Edit `initialize.php` with your database credentials:

```php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'lms_db');
```

### Base URL Configuration
Set your application base URL in `initialize.php`:

```php
define('base_url', 'https://yourdomain.com/lms/');
```

### Email Configuration
Configure SMTP settings in the admin panel or directly in the configuration files for email notifications.

### Environment Variables
Create a `.env` file for sensitive configuration:

```env
DB_HOST=localhost
DB_USER=your_username
DB_PASS=your_password
DB_NAME=lms_db
SMTP_HOST=your_smtp_host
SMTP_USER=your_smtp_user
SMTP_PASS=your_smtp_password
```

## 📖 Usage

### Initial Setup
1. **Access Admin Panel**: Navigate to `/admin` in your browser
2. **Default Login**: Use the developer credentials to set up your admin account
3. **System Settings**: Configure system preferences and company information
4. **User Management**: Create user accounts for your team members

### Daily Operations
1. **Lead Entry**: Add new leads manually or via bulk upload
2. **Follow-ups**: Use the dashboard to track pending follow-ups
3. **Status Updates**: Update lead status as they progress through the pipeline
4. **Reporting**: Generate reports to analyze performance

### Team Management
1. **User Roles**: Assign appropriate roles to team members
2. **Permissions**: Control access to different features
3. **Collaboration**: Use internal messaging for team communication

## 📁 Project Structure

```
lms/
├── 📄 index.php                 # Application entry point
├── 📄 config.php               # Main configuration file
├── 📄 initialize.php           # System initialization
├── 📄 composer.json            # PHP dependencies
├── 📄 README.md                # Project documentation
│
├── 📁 admin/                   # Admin panel modules
│   ├── 📄 index.php           # Admin entry point
│   ├── 📄 home.php             # Dashboard
│   ├── 📄 login.php            # Authentication
│   ├── 📁 leads/               # Lead management
│   ├── 📁 clients/             # Client management
│   ├── 📁 user/                # User management
│   └── 📁 inc/                 # Admin includes
│
├── 📁 classes/                 # PHP Classes
│   ├── 📄 DBConnection.php     # Database connection
│   ├── 📄 Master.php           # Main business logic
│   ├── 📄 Login.php            # Authentication logic
│   ├── 📄 Users.php            # User management
│   └── 📄 SystemSettings.php   # System configuration
│
├── 📁 database/               # Database files
│   └── 📄 lms_db.sql          # Database structure and data
│
├── 📁 assets/                 # Static assets
│   ├── 📁 images/             # Image files
│   ├── 📁 libs/               # CSS and JS libraries
│   └── 📁 vendor/             # Third-party assets
│
├── 📁 plugins/                # External plugins
│   ├── 📁 bootstrap/          # Bootstrap framework
│   ├── 📁 datatables/         # DataTables plugin
│   ├── 📁 jquery/             # jQuery library
│   └── 📁 fontawesome-free/   # Font Awesome icons
│
├── 📁 uploads/                # User uploaded files
├── 📁 temp/                   # Temporary files
├── 📁 vendor/                 # Composer dependencies
└── 📁 inc/                    # Global includes
    ├── 📄 header.php          # Common header
    ├── 📄 footer.php          # Common footer
    └── 📄 navigation.php      # Navigation menu
```

## 🔄 Development Workflow

### Branch Strategy
We use **Git Flow** for our development workflow:

- `main` - Production-ready code
- `develop` - Integration branch for features
- `feature/*` - New feature development
- `release/*` - Release preparation
- `hotfix/*` - Critical fixes for production

### Feature Development Process
1. **Create Feature Branch**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/lead-scoring-system
   ```

2. **Develop Feature**
   - Write code following PSR-12 standards
   - Add appropriate comments and documentation
   - Create/update tests as needed

3. **Commit Changes**
   ```bash
   git add .
   git commit -m "feat: implement lead scoring algorithm

   - Add lead scoring calculation logic
   - Create score display in lead list
   - Add configuration options for scoring weights
   
   Closes #123"
   ```

4. **Push and Create Pull Request**
   ```bash
   git push origin feature/lead-scoring-system
   ```

### Commit Convention
We follow [Conventional Commits](https://conventionalcommits.org/):

- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `style:` - Code formatting
- `refactor:` - Code refactoring
- `test:` - Adding tests
- `chore:` - Maintenance tasks

### Code Review Process
1. All code must be reviewed before merging
2. Pull requests require approval from team lead
3. Automated tests must pass
4. Code must follow established conventions

##  API Documentation

### Authentication Endpoints
```php
POST /admin/login.php          # User login
POST /admin/logout.php         # User logout
POST /classes/reset_password.php # Password reset
```

### Lead Management Endpoints
```php
GET  /admin/leads/             # List leads
POST /admin/leads/manage_lead.php # Create/update lead
GET  /admin/leads/view_lead.php   # View lead details
POST /admin/leads/delete_lead.php # Delete lead
```

### Client Management Endpoints
```php
GET  /admin/clients/           # List clients
POST /admin/clients/manage_client.php # Create/update client
GET  /admin/clients/view_client.php   # View client details
```

### Data Export/Import
```php
POST /admin/bulk_upload.php    # Bulk lead import
GET  /admin/excel/export_leads.php # Export leads to Excel
```

## 🤝 Contributing

### Getting Started
1. **Fork the Repository**
   ```bash
   # Fork on GitHub, then clone your fork
   git clone https://github.com/WoodpeckerLLM/leadshark-lms.git
   ```

2. **Set Up Development Environment**
   ```bash
   # Follow installation instructions above
   # Set up local database
   # Configure development settings
   ```

3. **Create Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

### Contribution Guidelines
- Follow PSR-12 PHP coding standards
- Write meaningful commit messages
- Include tests for new features
- Update documentation as needed
- Ensure backward compatibility

### Pull Request Process
1. Ensure your code follows project standards
2. Update README.md if needed
3. Add your changes to CHANGELOG.md
4. Request review from team members
5. Address any feedback promptly

### Code Standards
- **PHP**: Follow PSR-12 coding standard
- **JavaScript**: Use ES6+ features where possible
- **CSS**: Follow BEM methodology
- **Comments**: Write clear, concise comments
- **Testing**: Aim for good test coverage

## 🔒 Security

### Security Measures
- **Input Validation**: All user inputs are validated and sanitized
- **SQL Injection Prevention**: Using prepared statements
- **XSS Protection**: Output encoding and CSP headers
- **Authentication**: Secure session management
- **File Upload Security**: Type and size validation

### Security Best Practices
1. Keep PHP and dependencies updated
2. Use HTTPS in production
3. Regular security audits
4. Backup sensitive data regularly
5. Monitor for suspicious activities

### Reporting Security Issues
Please report security vulnerabilities privately to [security@yourcompany.com](mailto:security@yourcompany.com)

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Error
```
Error: Could not connect to database
```
**Solution**: Check database credentials in `initialize.php`

#### File Upload Issues
```
Error: File upload failed
```
**Solution**: Check directory permissions for `uploads/` folder

#### Email Notifications Not Working
```
Error: SMTP connection failed
```
**Solution**: Verify SMTP settings in admin panel

#### Performance Issues
**Symptoms**: Slow page loading, timeouts
**Solutions**:
- Check database indexes
- Optimize queries
- Increase PHP memory limit
- Enable caching

### Debug Mode
Enable debug mode by adding to `config.php`:
```php
define('DEBUG_MODE', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Log Files
Check log files for errors:
- Apache/Nginx error logs
- PHP error logs
- Application logs in `temp/` directory

## 📄 License

This project is proprietary software. All rights reserved.

**Restricted License**: This software is licensed for use by authorized team members only. Distribution, modification, or use outside the authorized team is strictly prohibited without explicit permission.

## 🆘 Support

### Getting Help
- **Documentation**: Check this README and project wiki
- **Issues**: Create GitHub issues for bugs and feature requests
- **Team Chat**: Use internal communication channels
- **Code Review**: Request help during PR reviews

### Contact Information
- **Project Lead**: [Your Email]
- **Technical Lead**: [Tech Lead Email]
- **Team Communication**: [Team Slack/Discord/etc.]

### Response Times
- **Critical Issues**: 4 hours
- **Bug Reports**: 24 hours
- **Feature Requests**: 48 hours
- **General Questions**: 72 hours

---

## 🔄 Version History

### Current Version: 2.0.0
- Complete LMS functionality
- Modern responsive design
- Advanced reporting features

### Roadmap
- [ ] API endpoint expansion
- [ ] Mobile app development
- [ ] Advanced analytics dashboard
- [ ] Integration with third-party CRMs
- [ ] Automated lead scoring improvements

---

**Built with ❤️ by the LeadSharks Team**

*For internal use only. This documentation is confidential and proprietary.*