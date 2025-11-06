# LeadSharks LMS - Project Structure Documentation

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Directory Structure](#directory-structure)
- [Core Components](#core-components)
- [Database Schema](#database-schema)
- [File Organization](#file-organization)
- [Dependencies](#dependencies)
- [Configuration Files](#configuration-files)
- [Asset Management](#asset-management)

## 🎯 Project Overview

LeadSharks LMS follows a modular PHP architecture with clear separation of concerns. The project uses a traditional MVC-inspired pattern with dedicated directories for different functionalities.

## 📁 Directory Structure

### Root Level Files
```
lms/
├── 📄 index.php              # Main entry point - redirects to admin
├── 📄 config.php             # Global configuration and helper functions
├── 📄 initialize.php         # System initialization and constants
├── 📄 composer.json          # PHP dependency management
├── 📄 README.md              # Project documentation
├── 📄 home.php               # Public home page (if any)
├── 📄 404.html               # Error page for missing resources
└── 📄 debug_post.txt         # Debug information file
```

### Admin Module (`/admin/`)
The admin directory contains the main application interface and functionality:

```
admin/
├── 📄 index.php              # Admin panel entry point
├── 📄 login.php              # User authentication
├── 📄 home.php               # Main dashboard
├── 📄 dashboard_new.php      # Alternative dashboard view
├── 📄 bulk_upload.php        # Excel file import functionality
├── 📄 forgot_password.php    # Password recovery
├── 📄 lead_overview.php      # Lead summary and analytics
├── 📄 lead_update.php        # Lead modification interface
├── 📄 morning_alert.php      # Morning notification system
├── 📄 evening_alert.php      # Evening notification system
├── 📄 execute_query.php      # Database query execution
├── 📄 fetch_status_history.php # Lead status tracking
├── 📄 maintanence_page.php   # System maintenance page
│
├── 📁 chatbot/               # AI chatbot integration
├── 📁 clients/               # Client management module
├── 📁 excel/                 # Excel processing utilities
├── 📁 inc/                   # Admin-specific includes
├── 📁 leads/                 # Lead management module
├── 📁 opportunities/         # Sales opportunities module
├── 📁 Sharks_portal/         # Special portal features
├── 📁 sources/               # Lead source management
├── 📁 system_info/           # System information and settings
├── 📁 transactions/          # Transaction management
├── 📁 user/                  # User management module
└── 📁 view_lead/             # Lead viewing interface
```

### Core Classes (`/classes/`)
PHP classes containing business logic and data access:

```
classes/
├── 📄 DBConnection.php       # Database connection management
├── 📄 Master.php             # Main business logic controller
├── 📄 Master_working18July.php # Backup/working version
├── 📄 Login.php              # Authentication logic
├── 📄 Users.php              # User management operations
├── 📄 SystemSettings.php     # System configuration management
├── 📄 Messaging.php          # Internal messaging system
├── 📄 get_messages.php       # Message retrieval
└── 📄 reset_password.php     # Password reset functionality
```

### Database (`/database/`)
Database-related files and backups:

```
database/
└── 📄 lms_db.sql            # Complete database structure and sample data
```

### Assets (`/assets/`)
Static files for the frontend:

```
assets/
├── 📁 images/               # Application images and icons
├── 📁 libs/                 # Custom CSS and JavaScript libraries
└── 📁 vendor/               # Third-party frontend assets
```

### Build System (`/build/`)
Frontend build tools and compiled assets:

```
build/
├── 📁 config/               # Build configuration
├── 📁 js/                   # Compiled JavaScript
├── 📁 npm/                  # Node.js modules
└── 📁 scss/                 # SCSS source files
```

### Plugins (`/plugins/`)
External plugins and libraries:

```
plugins/
├── 📁 bootstrap/            # Bootstrap CSS framework
├── 📁 datatables/           # DataTables jQuery plugin
├── 📁 jquery/               # jQuery JavaScript library
├── 📁 fontawesome-free/     # Font Awesome icons
├── 📁 chart.js/             # Chart.js for data visualization
├── 📁 daterangepicker/      # Date range selection
├── 📁 fullcalendar/         # Calendar functionality
├── 📁 inputmask/            # Input formatting
├── 📁 moment/               # Date manipulation
└── 📁 [40+ other plugins]   # Additional UI and functionality plugins
```

### Global Includes (`/inc/`)
Shared components across the application:

```
inc/
├── 📄 header.php            # HTML head section and meta tags
├── 📄 footer.php            # Footer content and scripts
├── 📄 navigation.php        # Main navigation menu
├── 📄 topBarNav.php         # Top navigation bar
├── 📄 defaultNav.php        # Default navigation structure
├── 📄 packages.php          # Common package includes
└── 📄 sess_auth.php         # Session authentication
```

### Additional Directories
```
libs/                        # Custom libraries and utilities
├── 📄 style.css            # Custom CSS styles
├── 📄 navbarclock.js       # Navigation clock functionality
├── 📁 css/                 # Additional CSS files
└── 📁 phpqrcode/           # QR code generation library

python/                     # Python scripts (if any)
temp/                       # Temporary files and cache
uploads/                    # User uploaded files
user/                       # User-related files and data
vendor/                     # Composer dependencies
```

## 🏗 Core Components

### 1. Authentication System
- **Location**: `/admin/login.php`, `/classes/Login.php`
- **Features**: User login, session management, password reset
- **Security**: Session-based authentication with role management

### 2. Dashboard
- **Location**: `/admin/home.php`, `/admin/dashboard_new.php`
- **Features**: Lead metrics, charts, quick actions
- **Dependencies**: Chart.js, DataTables

### 3. Lead Management
- **Location**: `/admin/leads/`
- **Features**: CRUD operations, status tracking, bulk import
- **Database**: `leads` table with related tables

### 4. Client Management
- **Location**: `/admin/clients/`
- **Features**: Client profiles, contact management, interaction history
- **Database**: `client_list` table

### 5. User Management
- **Location**: `/admin/user/`, `/classes/Users.php`
- **Features**: User accounts, roles, permissions
- **Database**: `users` table

### 6. Notification System
- **Location**: `/admin/morning_alert.php`, `/admin/evening_alert.php`
- **Features**: Automated alerts, follow-up reminders
- **Dependencies**: PHPMailer for email notifications

### 7. Reporting System
- **Location**: `/admin/excel/`
- **Features**: Excel export/import, custom reports
- **Dependencies**: PHPSpreadsheet

## 🗄 Database Schema

### Key Tables

#### `leads` Table
- Primary lead information
- Status tracking
- Source attribution
- Contact details

#### `client_list` Table
- Client company information
- Contact details
- Follow-up scheduling

#### `users` Table
- User accounts and authentication
- Role-based permissions
- Profile information

#### `system_settings` Table
- Application configuration
- System preferences
- Company information

#### Status and History Tables
- `lead_status_history`
- `follow_up_history`
- `interaction_logs`

### Database Relationships
```
leads (1) → (N) client_list
users (1) → (N) leads (assigned_to)
leads (1) → (N) lead_status_history
clients (1) → (N) interaction_logs
```

## 📄 File Organization

### Naming Conventions
- **PHP Files**: snake_case (e.g., `manage_lead.php`)
- **Classes**: PascalCase (e.g., `DBConnection.php`)
- **JavaScript**: camelCase (e.g., `navbarClock.js`)
- **CSS**: kebab-case (e.g., `custom-styles.css`)

### File Purposes

#### Entry Points
- `index.php` - Application entry, redirects to admin
- `admin/index.php` - Admin panel entry point
- `admin/login.php` - Authentication entry

#### Configuration
- `config.php` - Global configuration and helper functions
- `initialize.php` - System constants and initialization
- `composer.json` - PHP dependencies

#### Business Logic
- `classes/Master.php` - Main controller with business logic
- `classes/DBConnection.php` - Database abstraction
- Individual module files in respective directories

#### User Interface
- `admin/home.php` - Main dashboard
- Module-specific directories contain UI files
- `inc/` directory contains reusable UI components

#### Data Processing
- `admin/bulk_upload.php` - Excel import processing
- `admin/execute_query.php` - Direct database operations
- `classes/` contain data manipulation logic

## 📦 Dependencies

### PHP Dependencies (Composer)
```json
{
  "phpoffice/phpspreadsheet": "^4.2",  // Excel file processing
  "phpmailer/phpmailer": "^6.10"       // Email functionality
}
```

### Frontend Dependencies
- **Bootstrap 4** - CSS framework for responsive design
- **jQuery 3.x** - JavaScript library for DOM manipulation
- **DataTables** - Advanced table functionality
- **Chart.js** - Data visualization
- **Font Awesome** - Icon library
- **Moment.js** - Date manipulation
- **DateRangePicker** - Date selection component

### Development Dependencies
- **SCSS** - CSS preprocessor
- **npm** - Package management for frontend assets

## ⚙️ Configuration Files

### Primary Configuration
```php
// initialize.php
define('base_url', 'https://nexus360.woodpeckerind.com/lms/');
define('base_app', str_replace('\\','/',__DIR__).'/');
define('DB_SERVER', "localhost");
define('DB_USERNAME', "root");
define('DB_PASSWORD', "W@@dP@fgtrev#2024");
define('DB_NAME', "lms_db");
```

### Helper Functions
```php
// config.php
function redirect($url='')          // URL redirection
function validate_image($file)      // Image validation
function isMobileDevice()           // Mobile detection
```

### Environment-Specific Settings
- Database connection parameters
- Base URL configuration
- Debug mode settings
- Email SMTP configuration

## 🎨 Asset Management

### CSS Architecture
```
assets/libs/css/               # Custom stylesheets
plugins/bootstrap/css/         # Bootstrap framework
plugins/fontawesome-free/css/  # Icon fonts
libs/style.css                 # Main custom styles
```

### JavaScript Organization
```
plugins/jquery/               # Core jQuery library
plugins/bootstrap/js/         # Bootstrap components
plugins/datatables/js/        # Table functionality
plugins/chart.js/            # Data visualization
libs/navbarclock.js          # Custom functionality
```

### Image Management
```
assets/images/               # Application images
uploads/                    # User uploaded files
assets/vendor/              # Third-party assets
```

### Build Process
The project uses a build system for asset compilation:
- SCSS compilation to CSS
- JavaScript minification
- Asset optimization
- Cache busting for production

## 🔗 Module Integration

### Inter-Module Communication
- **Database Layer**: Shared through `DBConnection.php`
- **Session Management**: Centralized in `sess_auth.php`
- **Common Functions**: Available via `config.php`
- **UI Components**: Reusable includes in `/inc/`

### Data Flow
```
User Request → Entry Point → Authentication → Business Logic → Database → Response
     ↓              ↓              ↓              ↓            ↓         ↓
 index.php → admin/login.php → sess_auth.php → Master.php → DBConnection.php → UI
```

### Security Layers
1. **Entry Point Validation** - Request validation
2. **Authentication** - Session verification  
3. **Authorization** - Permission checking
4. **Input Sanitization** - Data validation
5. **Output Encoding** - XSS prevention

This structure ensures maintainability, security, and scalability while providing clear separation of concerns throughout the application.