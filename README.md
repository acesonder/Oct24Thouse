# Transition House Cobourg - Shelter Management Platform

A comprehensive web platform for modernizing shelter operations, peer engagement, and housing navigation for Transition House in Northumberland County.

## 🏠 Overview

Transition House is a unified system that streamlines:
- Shelter admissions and bed management
- Case management and goal tracking
- Peer mentorship and leadership
- Real-time communication and messaging
- Referrals and partner coordination
- Training and certifications
- Analytics and reporting

## 🌟 Key Features

### Client Features
- Smart intake system with trauma-informed design
- Personal dashboard with progress tracking
- Bed request and triage system
- Goals and task management (SMART goals)
- Appointment scheduling
- Encrypted document vault with QR retrieval
- Granular consent controls
- In-app messaging (staff, peers, groups)
- Community feed and discussion boards
- Self-assessments
- Resource directory with map
- Offline mode support (PWA)

### Staff Features
- Real-time shift dashboard
- Digital intake and admissions tools
- Comprehensive case management suite
- One-click referral system
- Bed management and occupancy tracking
- Incident logging and reporting
- Inventory management
- Analytics and data visualization
- Audit trail for compliance
- HIFIS-compatible data export

### Peer Features
- Verified peer profiles with specializations
- Mentorship matching and management
- Engagement logging
- Shift scheduling with self-sign-up
- Training portal with certifications
- Peer-to-peer messaging with safety filters
- Community room moderation
- Reputation and badge system
- Governance tools for community proposals

### Partner Portal
- Referral acceptance/decline
- Direct client appointment scheduling
- Document upload and outcome notes
- Consent-based limited access

### Admin & Compliance
- Role-based access control (RBAC)
- PHIPA/PIPEDA-compliant encryption
- Consent expiry tracking
- Configurable policy engine
- Data retention management
- System-wide analytics
- Template configuration

## 🔧 Technical Stack

- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Authentication**: JWT with optional 2FA
- **PWA**: Service Worker for offline support
- **Security**: HTTPS, bcrypt password hashing, encrypted document storage

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 8.0 or higher
- Composer (for dependency management)
- Web server (Apache/Nginx)
- SSL certificate (for production)

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/acesonder/Oct24Thouse.git
cd Oct24Thouse
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` and configure your database and other settings:
- Set database credentials
- Generate a secure JWT secret key
- Configure email settings for notifications

### 4. Create Database

```bash
mysql -u root -p < database/schema.sql
```

Or import via phpMyAdmin or your preferred MySQL client.

### 5. Set Up Web Server

#### Apache

Create a virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName transitionhouse.local
    DocumentRoot /path/to/Oct24Thouse/public
    
    <Directory /path/to/Oct24Thouse/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Redirect API requests
    Alias /api /path/to/Oct24Thouse/api
    
    ErrorLog ${APACHE_LOG_DIR}/transitionhouse-error.log
    CustomLog ${APACHE_LOG_DIR}/transitionhouse-access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name transitionhouse.local;
    root /path/to/Oct24Thouse/public;
    
    index index.html index.php;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    location /api {
        alias /path/to/Oct24Thouse/api;
        try_files $uri $uri/ /api/index.php?$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }
}
```

### 6. Set Permissions

```bash
chmod -R 755 /path/to/Oct24Thouse
chmod -R 775 /path/to/Oct24Thouse/uploads
chown -R www-data:www-data /path/to/Oct24Thouse
```

### 7. Access the Application

Open your browser and navigate to:
- Local: `http://localhost`
- Custom domain: `http://transitionhouse.local`

## 👥 Default User Roles

The system has 5 user roles:

1. **Client** - Shelter residents and seekers
2. **Peer** - Individuals with lived/living experience
3. **Staff** - Case managers, housing workers, outreach
4. **Admin** - System administrators
5. **Partner** - External service providers

## 🔐 Security Features

- JWT-based authentication
- bcrypt password hashing
- Two-factor authentication (2FA) for staff
- Granular consent management
- Encrypted document storage
- Session tracking and revocation
- Comprehensive audit logging
- HTTPS enforcement (production)
- PHIPA/PIPEDA compliance

## 📡 API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user

### Intake
- `POST /api/intake/create` - Create intake form
- `GET /api/intake/{id}` - Get intake details
- `PUT /api/intake/{id}` - Update intake
- `GET /api/intake/list` - List intakes

### Bed Management
- `GET /api/bed/list` - List all beds
- `GET /api/bed/stats` - Get bed statistics
- `POST /api/bed/assign` - Assign bed to client
- `POST /api/bed/release` - Release bed

### Case Management
- `POST /api/case/create` - Create case plan
- `GET /api/case/{id}` - Get case plan with goals
- `PUT /api/case/{id}` - Update case plan
- `GET /api/case/list` - List case plans

### Peer Engagement
- `GET /api/peer/profile/{id}` - Get peer profile
- `POST /api/peer/engagement` - Log engagement
- `POST /api/peer/mentorship/request` - Request mentorship
- `GET /api/peer/mentors` - List available mentors

### Messaging
- `POST /api/chat/send` - Send message
- `GET /api/chat/messages` - Get messages
- `POST /api/chat/group/create` - Create chat group
- `GET /api/chat/groups` - List chat groups
- `POST /api/chat/announcement` - Create announcement

### Referrals
- `POST /api/referral/send` - Send referral
- `PUT /api/referral/{id}` - Update referral
- `GET /api/referral/list` - List referrals

### Training
- `GET /api/training/modules` - List training modules
- `POST /api/training/complete` - Complete training
- `GET /api/training/my-completions` - Get user completions

### Analytics
- `GET /api/analytics/dashboard` - Dashboard analytics
- `GET /api/analytics/occupancy` - Occupancy trends
- `GET /api/analytics/export` - Export data

## 📱 Progressive Web App (PWA)

The application supports offline functionality through Service Workers:
- Caches critical assets for offline access
- Auto-syncs data when connection returns
- Can be installed on mobile devices
- Works like a native app

## 🎨 Customization

### Branding
Edit `/public/css/style.css` to customize colors, fonts, and styles.

### Templates
Modify HTML templates in `/public/index.html` and create additional pages as needed.

### Forms
Intake and assessment forms can be customized through the database configuration.

## 📊 Database Schema

The system uses 40+ tables including:
- Users and roles
- Beds and stays
- Intakes and assessments
- Case plans, goals, and tasks
- Messages and chat groups
- Peer profiles and engagements
- Training modules and certifications
- Referrals and appointments
- Documents and consents
- Incidents and inventory
- Community posts and proposals
- Audit logs and sessions

See `/database/schema.sql` for full schema details.

## 🔄 Backup and Maintenance

### Database Backup
```bash
mysqldump -u root -p transition_house > backup_$(date +%Y%m%d).sql
```

### Log Rotation
Configure log rotation for error logs and access logs.

### Updates
```bash
git pull origin main
composer update
# Run any database migrations
```

## 🐛 Troubleshooting

### Can't connect to database
- Check database credentials in `.env`
- Ensure MySQL service is running
- Verify database exists and user has permissions

### API returns 404
- Check web server configuration
- Verify rewrite rules are enabled
- Check file permissions

### Login fails
- Clear browser cache and cookies
- Check JWT secret is set in `.env`
- Verify database connection

## 📞 Support

For issues, questions, or contributions:
- GitHub Issues: [https://github.com/acesonder/Oct24Thouse/issues](https://github.com/acesonder/Oct24Thouse/issues)
- Email: support@transitionhouse.org

## 📝 License

This project is proprietary software developed for Transition House Cobourg.

## 🙏 Acknowledgments

Built to serve the community of Northumberland County and support individuals experiencing homelessness through coordinated care, peer leadership, and data-driven decision making.

---

**Note**: This is a comprehensive shelter management system. Always ensure compliance with local privacy laws (PHIPA/PIPEDA) and follow best practices for handling sensitive client data.
