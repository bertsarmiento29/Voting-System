# Online Voting System - Setup Instructions

A secure, professional, and fully functional Online Voting System built with PHP 8+, MySQL, and Bootstrap.

## Features

- **User Roles**: Admin and Voter (Student/User)
- **Authentication**: Secure login/logout with password hashing and role-based access control
- **Voting Module**: Cast votes per category, prevent duplicate voting, confirmation before submitting
- **Voter Management**: Add, edit, delete voters with unique voter ID login
- **Candidate Management**: Add candidates per category/position with photo uploads
- **Results Module**: Real-time vote counting with Chart.js visualizations
- **Security**: SQL injection prevention, CSRF protection, session management

## System Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx) or XAMPP/WAMP/MAMP

## Installation for XAMPP/WAMP

### Step 1: Download and Extract

1. Copy the `VotingSystem` folder to your XAMPP's `htdocs` directory:
   ```
   C:\xampp\htdocs\VotingSystem\
   ```

### Step 2: Create Database

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Click "Import" tab
3. Browse to `database/schema.sql` in the project folder
4. Click "Go" to import

Or via command line:
```bash
mysql -u root -p < database/schema.sql
```

### Step 3: Configure Database

Edit `config/config.php` if your database credentials differ:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Step 4: Update App URL

Edit `config/config.php` to match your setup:

```php
define('APP_URL', 'http://localhost/VotingSystem');
```

### Step 5: Set Folder Permissions (Linux/Mac)

```bash
chmod -R 755 public/uploads/
chmod -R 755 public/images/
```

### Step 6: Access the Application

- **Home Page**: http://localhost/VotingSystem/public/
- **Admin Login**: http://localhost/VotingSystem/public/admin/login.php
- **Voter Login**: http://localhost/VotingSystem/public/voter/login.php

## Default Admin Credentials

**Username**: admin
**Email**: admin@example.com
**Password**: password

⚠️ **IMPORTANT**: Change the default admin password immediately after first login!

## Quick Start Guide

### 1. Configure Election Settings

1. Login as admin
2. Go to Settings
3. Set election name and enable voting

### 2. Add Categories (Positions)

1. Go to Categories
2. Add positions (e.g., President, Vice President, Secretary, etc.)

### 3. Add Candidates

1. Go to Candidates
2. Add candidates and assign them to categories
3. Upload photos and add bio/party information

### 4. Add Voters

1. Go to Voters
2. Add voters individually or in bulk
3. Each voter gets a unique Voter ID and default password

### 5. Start Election

1. Go to Settings
2. Enable "Election Active"
3. Enable "Allow Voting"

## Project Structure

```
VotingSystem/
├── config/
│   └── config.php          # Application configuration
├── core/
│   ├── Auth.php            # Authentication class
│   ├── CSRF.php            # CSRF protection
│   ├── Database.php        # Database connection (PDO)
│   ├── Helpers.php         # Helper functions
│   ├── Request.php         # HTTP request handler
│   ├── Response.php        # HTTP response handler
│   ├── Router.php          # URL routing
│   ├── Session.php         # Session management
│   └── Validator.php      # Form validation
├── app/
│   └── Models/
│       ├── User.php        # Admin user model
│       ├── Voter.php       # Voter model
│       ├── Candidate.php   # Candidate model
│       ├── Category.php    # Category/Position model
│       ├── Vote.php        # Vote model
│       ├── ElectionSetting.php
│       └── AuditLog.php    # Audit logging
├── public/
│   ├── index.php           # Home page
│   ├── results.php         # Public results page
│   ├── css/
│   │   └── style.css       # Main stylesheet
│   ├── js/                 # JavaScript files
│   ├── images/             # Image assets
│   ├── uploads/            # Uploaded files
│   ├── admin/              # Admin panel
│   │   ├── dashboard.php
│   │   ├── voters.php
│   │   ├── candidates.php
│   │   ├── categories.php
│   │   ├── election-settings.php
│   │   ├── results.php
│   │   ├── audit-logs.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── api/
│   └── voter/              # Voter panel
│       ├── login.php
│       ├── dashboard.php
│       └── logout.php
├── database/
│   └── schema.sql          # Database schema
├── README.md               # This file
└── SETUP.md                # Setup instructions
```

## Security Features

### Password Security
- Passwords are hashed using `password_hash()` with BCRYPT
- Default cost factor of 12

### SQL Injection Prevention
- All database queries use PDO prepared statements
- Input sanitization on all user inputs

### CSRF Protection
- CSRF tokens generated for all forms
- Token validation on POST requests

### Session Security
- HTTP-only cookies
- Session regeneration on login
- SameSite cookie attribute

## API Endpoints (Admin)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/public/admin/api/settings.php` | POST | Update settings |
| `/public/admin/api/toggle-voting.php` | POST | Toggle voting status |
| `/public/admin/api/toggle-results.php` | POST | Toggle results visibility |

## Troubleshooting

### Database Connection Error
- Check database credentials in `config/config.php`
- Ensure MySQL service is running
- Verify database exists

### Login Not Working
- Check PHP session directory is writable
- Clear browser cookies and cache
- Verify password hash matches

### File Upload Issues
- Check `public/uploads/` permissions
- Verify PHP `upload_max_filesize` setting
- Ensure file extensions are allowed

## Support

For issues or questions, please check:
1. PHP error logs
2. MySQL error logs
3. Browser console for JavaScript errors

## License

This project is open source and available for educational and organizational use.

---

**Version**: 1.0.0
**Last Updated**: April 2026
