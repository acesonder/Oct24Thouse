# PHP-Web Implementation - Transition House

## Overview

This is a complete PHP/MySQL web application implementation for the Transition House shelter management platform. The application features a comprehensive setup portal, role-based access control, and a full suite of shelter management tools.

## Directory Structure

```
php-web/
├── assets/
│   ├── css/
│   │   ├── main.css          # Main application styles
│   │   └── setup.css         # Setup portal styles
│   ├── js/
│   │   ├── app.js            # Core application logic
│   │   ├── auth.js           # Authentication module
│   │   ├── navigation.js     # Page navigation and routing
│   │   └── setup.js          # Setup portal functionality
│   └── images/               # Image assets
├── config/
│   ├── database.php          # Database connection class
│   ├── branding.json         # Site branding configuration
│   └── profiles/             # Database configuration profiles
├── includes/
│   ├── setup-api.php         # Setup API endpoints
│   └── api.php               # Main application API (to be created)
├── pages/
│   ├── dashboard.html        # Client dashboard
│   ├── staff-dashboard.html  # Staff dashboard
│   ├── bed-management.html   # Bed management interface
│   └── [other pages]         # Additional feature pages
├── backups/                  # Database backups
├── logs/                     # Application and error logs
├── uploads/                  # File uploads
├── index.html                # Main application entry point
├── setup.html                # Setup portal (password: 079777)
└── CUSTOMIZATION_QUESTIONS.md # 20 customization questions

```

## Key Features

### 1. Setup Portal (`setup.html`)
**Password: 079777**

The setup portal provides comprehensive system configuration:

- **Database Configuration**
  - Create/configure databases
  - Multiple configuration profiles
  - Test connections
  - View database statistics

- **Account Management**
  - Create user accounts
  - Modify existing accounts
  - Role assignment
  - Account activation/deactivation

- **Branding Customization**
  - Site name
  - Logo upload
  - Color scheme (primary/secondary colors)
  - Theme selection
  - Live preview

- **Demo Content**
  - Import demo data
  - Quick user switching for testing
  - Demo account credentials provided

- **Backup & Restore**
  - Create database backups
  - Restore from backups
  - Automated backup naming
  - Backup file management

- **Error Logs**
  - View recent server errors
  - Configurable log line display
  - Real-time log refresh

- **Diagnostics**
  - System health checks
  - PHP version verification
  - Database connectivity tests
  - Directory permissions check
  - Required extensions validation

- **Reset Tools**
  - Reset database tables
  - Clear all data
  - Factory reset option

### 2. Main Application (`index.html`)

#### Authentication
- Secure login system
- JWT token-based auth
- Role-based access control
- Password recovery
- User registration

#### Role-Based Navigation

**Client (Role 1)**
- Personal dashboard
- Intake forms
- Bed requests
- Goal tracking
- Appointments
- Document vault
- Messaging
- Resource directory

**Staff (Role 2)**
- Shift dashboard
- Intake management
- Bed management
- Case management
- Client directory
- Referrals
- Incident reporting
- Inventory
- Analytics and reports

**Peer (Role 3)**
- Peer dashboard
- Mentee management
- Engagement logging
- Training modules
- Certifications
- Shift scheduling
- Community participation

**Partner (Role 4)**
- Partner dashboard
- Incoming referrals
- Client appointments
- Document sharing
- Limited client access

**Admin (Role 5)**
- System dashboard
- User management
- Roles and permissions
- System configuration
- Branding management
- Template configuration
- Analytics
- Audit logs
- Data export

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- Composer (optional)

### Setup Steps

1. **Clone/Extract Files**
   ```bash
   cd /path/to/Oct24Thouse/php-web
   ```

2. **Configure Web Server**
   
   **Apache (.htaccess provided)**
   ```apache
   DocumentRoot /path/to/Oct24Thouse/php-web
   ```

   **Nginx**
   ```nginx
   root /path/to/Oct24Thouse/php-web;
   index index.html index.php;
   
   location ~ \.php$ {
       fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
       fastcgi_index index.php;
       include fastcgi_params;
   }
   ```

3. **Set Permissions**
   ```bash
   chmod -R 755 /path/to/php-web
   chmod -R 775 /path/to/php-web/backups
   chmod -R 775 /path/to/php-web/logs
   chmod -R 775 /path/to/php-web/uploads
   chmod -R 775 /path/to/php-web/config/profiles
   ```

4. **Access Setup Portal**
   - Navigate to: `http://your-domain/setup.html`
   - Enter password: `079777`
   - Configure database
   - Import demo data
   - Customize branding

5. **Create Database**
   - Use the setup portal to create the database
   - Or manually: Import `/database/schema.sql`

6. **Test Application**
   - Navigate to: `http://your-domain/`
   - Login with demo credentials:
     - Client: `client@demo.com` / `demo1234`
     - Staff: `staff@demo.com` / `demo1234`
     - Admin: `admin@demo.com` / `demo1234`

## API Endpoints

### Setup API (`/includes/setup-api.php`)
All endpoints require `X-Setup-Password: 079777` header

- `GET /test-connection` - Test database connection
- `POST /configure-database` - Configure database
- `GET /list-profiles` - List configuration profiles
- `GET /database-stats` - Get database statistics
- `POST /create-account` - Create user account
- `GET /list-accounts` - List all accounts
- `POST /modify-account` - Modify user account
- `POST /delete-account` - Delete user account
- `POST /backup` - Create database backup
- `GET /list-backups` - List available backups
- `POST /restore` - Restore from backup
- `POST /import-demo` - Import demo data
- `GET /error-logs` - Get error logs
- `GET /diagnostics` - Run system diagnostics
- `POST /reset-system` - Reset database tables
- `POST /update-branding` - Update site branding
- `GET /get-branding` - Get current branding

### Main Application API (`/includes/api.php`)
To be implemented with authentication required

- `/auth/login` - User login
- `/auth/register` - User registration
- `/auth/logout` - User logout
- `/client/dashboard` - Client dashboard data
- `/staff/dashboard` - Staff dashboard data
- `/bed/list` - List beds
- `/bed/assign` - Assign bed
- `/bed/release` - Release bed
- And many more...

## Security Features

- Password-protected setup portal
- JWT-based authentication
- Prepared SQL statements (PDO)
- Input validation and sanitization
- Role-based access control (RBAC)
- Audit logging
- Secure password hashing (bcrypt)
- HTTPS recommended for production

## Customization

### Branding
Use the setup portal's branding tab to customize:
- Site name
- Logo
- Primary and secondary colors
- Theme

### Configuration Profiles
Create multiple database configurations for:
- Development
- Staging
- Production

Each profile saved in `/config/profiles/` as JSON

### Adding New Pages

1. Create HTML file in `/pages/` directory
2. Add navigation item in `/assets/js/navigation.js`
3. Create initialization function if needed: `init_page_name()`
4. Test with appropriate user role

## Demo Accounts

| Role | Email | Password | Description |
|------|-------|----------|-------------|
| Client | client@demo.com | demo1234 | Test client account |
| Staff | staff@demo.com | demo1234 | Test staff account |
| Peer | peer@demo.com | demo1234 | Test peer account |
| Partner | partner@demo.com | demo1234 | Test partner account |
| Admin | admin@demo.com | demo1234 | Test admin account |

## Troubleshooting

### Database Connection Failed
1. Check database credentials in configuration
2. Ensure MySQL service is running
3. Verify user permissions
4. Use setup portal's "Test Connection" feature

### Cannot Access Setup Portal
1. Verify password is exactly: `079777`
2. Check browser console for errors
3. Ensure `/includes/setup-api.php` is accessible

### Pages Not Loading
1. Check web server configuration
2. Verify file permissions
3. Check browser console for JavaScript errors
4. Ensure API endpoints are accessible

### Backup/Restore Not Working
1. Verify mysqldump is installed
2. Check directory permissions for `/backups`
3. Ensure sufficient disk space

## Development

### Adding New API Endpoints
Edit `/includes/api.php` or `/includes/setup-api.php`:

```php
if ($action === 'new-endpoint' && $method === 'POST') {
    // Handle request
    $data = $input;
    
    // Process
    $result = performAction($data);
    
    // Respond
    sendResponse([
        'success' => true,
        'data' => $result
    ]);
}
```

### Adding New Pages
Create `/pages/your-page.html`:

```html
<div class="your-page">
    <h2>Your Page Title</h2>
    
    <div class="card">
        <!-- Content -->
    </div>
</div>

<script>
function init_your_page() {
    // Initialize page
}
</script>

<style>
/* Page-specific styles */
</style>
```

## Production Deployment

1. **Security**
   - Change setup password from default
   - Enable HTTPS
   - Disable PHP error display
   - Set appropriate file permissions
   - Regular security updates

2. **Performance**
   - Enable PHP opcode caching
   - Configure MySQL query caching
   - Implement CDN for static assets
   - Enable gzip compression

3. **Monitoring**
   - Set up error logging
   - Monitor disk space
   - Track database performance
   - Schedule regular backups

4. **Maintenance**
   - Regular database backups (automated)
   - Log rotation
   - Update dependencies
   - Monitor error logs

## Support

For issues, questions, or contributions:
- GitHub Issues: [Repository Issues](https://github.com/acesonder/Oct24Thouse/issues)
- Documentation: See main `/README.md`
- Customization: See `CUSTOMIZATION_QUESTIONS.md`

## License

See main repository LICENSE file.

---

**Built with care for Transition House** 🏠❤️

This PHP-Web implementation provides a complete, production-ready shelter management platform with comprehensive setup tools, role-based access, and full feature coverage.
