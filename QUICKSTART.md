# Quick Start Guide - Transition House Platform

## For Developers

### Prerequisites
- PHP 7.4+
- MySQL 8.0+
- Composer
- Web server (Apache or Nginx)

### Installation (5 minutes)

1. **Clone & Install**
```bash
git clone https://github.com/acesonder/Oct24Thouse.git
cd Oct24Thouse
composer install
```

2. **Configure**
```bash
cp .env.example .env
# Edit .env with your database credentials
```

3. **Database Setup**
```bash
mysql -u root -p
CREATE DATABASE transition_house;
exit;
mysql -u root -p transition_house < database/schema.sql
```

4. **Run** (Development)
```bash
php -S localhost:8000 -t public
```

5. **Access**: Open http://localhost:8000

### Default Test Login
Since the system starts empty, you'll need to create a user directly in the database or via the register endpoint.

## For Non-Technical Users

### What is Transition House Platform?

A web application that helps shelters:
- Track bed availability in real-time
- Manage client intake and case plans
- Coordinate peer mentorship
- Facilitate secure messaging
- Generate reports and analytics

### Key Features

**For Clients:**
- Apply for shelter beds online
- Track your housing goals
- Message with staff and peers
- Access resource directory
- Store important documents securely

**For Staff:**
- Real-time bed management
- Digital intake forms
- Case management tools
- Referral system
- Analytics dashboard

**For Peers:**
- Mentorship matching
- Engagement logging
- Training portal
- Community leadership tools

### User Roles

1. **Client** - People seeking shelter services
2. **Peer** - People with lived experience who support others
3. **Staff** - Case managers and shelter workers
4. **Admin** - System administrators
5. **Partner** - External service providers

## Quick Feature Tour

### Dashboard
- See bed availability at a glance
- View announcements
- Quick access to key features

### Bed Management
- Real-time bed status (available/occupied/maintenance)
- Assign beds to clients
- Track occupancy rates

### Intake System
- Trauma-informed intake forms
- Automatic vulnerability scoring
- Digital consent management

### Case Management
- Create SMART goals
- Track progress with visual indicators
- Manage tasks and appointments

### Messaging
- One-on-one chats
- Group discussions
- Crisis announcements
- Safety features

### Peer Network
- Find mentors by specialization
- Log engagement hours
- Complete training modules
- Earn badges

### Resource Directory
- Find local services
- Filter by category
- Map integration
- Contact information

## Common Tasks

### Creating an Intake
1. Login as staff
2. Click "New Intake"
3. Fill out form sections
4. System auto-calculates vulnerability score
5. Submit for review

### Assigning a Bed
1. Go to Bed Management
2. Click available bed
3. Select client
4. Set expected exit date
5. Confirm assignment

### Starting a Mentorship
1. Browse mentor profiles
2. Review specializations
3. Click "Request Mentorship"
4. Wait for approval
5. Start chatting

### Sending a Referral
1. Open client case
2. Click "New Referral"
3. Select partner agency
4. Choose service type
5. Add notes and send

## Mobile Access

The platform is a Progressive Web App (PWA):
- Works offline
- Installable on phones
- Fast and responsive
- Native app experience

### Installing on Phone
1. Open in mobile browser
2. Tap "Add to Home Screen"
3. Icon appears like a regular app
4. Works even offline

## Security & Privacy

### Your Data is Protected
- All connections encrypted (HTTPS)
- Passwords securely hashed
- Sensitive data encrypted
- Audit trail of all access
- PHIPA/PIPEDA compliant

### Consent Controls
Clients control:
- Who can see their data
- What information is shared
- How long data is kept
- When consent expires

## Getting Help

### In-App Support
- Hover over (?) icons for help
- Check announcements for updates
- Contact staff via messaging

### Technical Issues
- Email: support@transitionhouse.org
- Report bugs on GitHub
- Check documentation

### Training
- Online training modules available
- Video tutorials (coming soon)
- Staff onboarding guide
- Peer mentor training

## System Requirements

### For Users
- Modern web browser (Chrome, Firefox, Safari, Edge)
- Internet connection (offline mode available)
- Email address

### Recommended Browsers
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Tips & Tricks

### Keyboard Shortcuts
- `Ctrl+/` - Quick search
- `Ctrl+N` - New item (context dependent)
- `Esc` - Close modals

### Efficiency Tips
- Use filters to find data quickly
- Star important items
- Set up notifications
- Use templates for common tasks

### Best Practices
- Log out when done
- Keep passwords secure
- Report suspicious activity
- Update contact info
- Check announcements daily

## Troubleshooting

### Can't Login?
- Check email and password
- Try "Forgot Password"
- Clear browser cache
- Contact admin

### Page Won't Load?
- Check internet connection
- Refresh the page
- Clear browser cache
- Try different browser

### Features Not Working?
- Check your user role/permissions
- Report to staff
- Check for system announcements

## What's Next?

### Planned Features
- Mobile apps (iOS/Android)
- Video chat integration
- AI-powered insights
- Enhanced analytics
- Multi-language support

### Contributing
- Report bugs
- Suggest features
- Help with testing
- Share feedback

## Resources

- **Full Documentation**: See README.md
- **API Guide**: See docs/API.md
- **Deployment**: See docs/DEPLOYMENT.md
- **Security**: See SECURITY.md
- **Contributing**: See CONTRIBUTING.md

## Contact

- **General**: info@transitionhouse.org
- **Support**: support@transitionhouse.org
- **Security**: security@transitionhouse.org
- **Emergency**: [Emergency Number]

---

**Welcome to Transition House!** 
This platform was built to serve the community and support those experiencing homelessness. Together, we're making a difference.
