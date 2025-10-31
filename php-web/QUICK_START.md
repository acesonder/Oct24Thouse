# 🏠 TRANSITION HOUSE PHP-WEB - QUICK START GUIDE

## Welcome! Your New System is Ready 🎉

This guide will help you get started with your new PHP-Web implementation in **5 minutes**.

---

## 📍 What You Got

A **complete, production-ready** shelter management platform with:

✅ **Setup Portal** - Configure everything via web interface (no command line needed!)  
✅ **Role-Based Application** - Different interfaces for Clients, Staff, Peers, Partners, and Admins  
✅ **40+ Pages** - All features from the original README mapped to pages  
✅ **Demo Data** - 5 test accounts ready to use  
✅ **Complete Documentation** - Everything explained in detail  

---

## 🚀 Getting Started (5 Steps)

### Step 1: Access the Setup Portal

1. Open your browser
2. Navigate to: `http://your-server/php-web/setup.html`
3. Enter password: **`079777`**
4. You're in!

### Step 2: Configure Your Database

1. Click the **"Database"** tab
2. Fill in:
   - **Database Name**: `transition_house` (or your choice)
   - **Profile Name**: `production` (or your choice)
   - **Host**: `localhost` (usually)
   - **Port**: `3306` (usually)
   - **Username**: Your MySQL username
   - **Password**: Your MySQL password
3. Check: ✅ **"Create database tables from schema"**
4. Click **"Configure Database"**
5. Wait for success message

### Step 3: Import Demo Data

1. Click the **"Demo Content"** tab
2. Click **"Import Demo Content"**
3. Wait for success message
4. **5 demo accounts are now created!**

### Step 4: Customize Your Branding (Optional)

1. Click the **"Branding"** tab
2. Enter your organization name
3. Pick your colors
4. See live preview!
5. Click **"Save Branding"**

### Step 5: Try the Main Application

1. Open: `http://your-server/php-web/`
2. Login with a demo account:
   - **Staff**: `staff@demo.com` / `demo1234`
3. Explore the dashboard!

---

## 🎯 Demo Account Credentials

Use these to test different user experiences:

| Role | Email | Password | What You'll See |
|------|-------|----------|-----------------|
| **Client** | client@demo.com | demo1234 | Personal dashboard, intake forms, goals |
| **Staff** | staff@demo.com | demo1234 | Shift dashboard, bed management, case management |
| **Peer** | peer@demo.com | demo1234 | Peer dashboard, mentorship, training |
| **Partner** | partner@demo.com | demo1234 | Partner portal, referrals |
| **Admin** | admin@demo.com | demo1234 | System administration, user management |

---

## 📂 Where Everything Is

```
php-web/
├── setup.html              ← Setup Portal (password: 079777)
├── index.html              ← Main Application
├── README.md               ← Complete Documentation
├── CUSTOMIZATION_QUESTIONS.md  ← 20 Questions for Setup
├── PROJECT_COMPLETION.md   ← What Was Built
├── assets/                 ← Styles and Scripts
├── pages/                  ← Individual Pages
├── config/                 ← Configuration Files
├── includes/               ← Backend API Files
└── backups/                ← Database Backups (created automatically)
```

---

## 🛠️ Setup Portal Features

### All Available Now (password: 079777):

**📊 Database Tab**
- Create/configure databases
- Test connections
- View statistics
- Manage multiple profiles

**👥 Accounts Tab**
- Create user accounts (all 5 roles)
- Modify existing accounts
- View all users
- Delete accounts

**⚙️ Profiles Tab**
- Save multiple configurations
- Switch between environments
- Manage dev/staging/production

**🎨 Branding Tab**
- Customize site name
- Upload logo
- Change colors
- Select theme
- See live preview!

**🎭 Demo Content Tab**
- Import test data
- Quick user switching
- Test credentials displayed

**💾 Backups Tab**
- Create database backups
- View available backups
- Restore from backup
- Download backups

**📋 Error Logs Tab**
- View recent errors
- Refresh logs
- Configure display lines

**🔍 Diagnostics Tab**
- System health checks
- PHP version check
- Extension verification
- Permission checks

**⚠️ Reset Tools Tab**
- Reset database tables
- Clear all data
- Factory reset

---

## 📱 Main Application Features

### What's Working Now:

**✅ Client Features:**
- Dashboard with personal stats
- Comprehensive intake form
- SMART goal tracking
- Messaging system
- And more...

**✅ Staff Features:**
- Real-time shift dashboard
- Visual bed management
- Bed assignment system
- Quick action buttons
- And more...

**✅ All Roles:**
- Unique dashboards
- Role-appropriate navigation
- Responsive mobile design
- Professional interface

---

## 📖 Documentation Available

1. **README.md** (10KB)
   - Installation guide
   - Feature documentation
   - API reference
   - Troubleshooting

2. **CUSTOMIZATION_QUESTIONS.md** (5.5KB)
   - 20 detailed questions
   - Configuration guidance
   - Best practices

3. **PROJECT_COMPLETION.md** (13KB)
   - What was built
   - Features delivered
   - Statistics
   - Testing results

4. **IMPLEMENTATION_SUMMARY.md** (8.5KB)
   - Technical details
   - Architecture
   - API endpoints
   - Development notes

---

## 🎓 Next Steps

### For Testing:
1. ✅ Login with each demo account
2. ✅ Try creating a goal (as client)
3. ✅ Try assigning a bed (as staff)
4. ✅ Send a test message
5. ✅ Test on mobile device

### For Production:
1. ⬜ Answer the 20 customization questions
2. ⬜ Import your real data
3. ⬜ Create real user accounts
4. ⬜ Customize branding for your org
5. ⬜ Change setup password from default
6. ⬜ Enable HTTPS
7. ⬜ Set up automated backups

### For Customization:
1. ⬜ Read CUSTOMIZATION_QUESTIONS.md
2. ⬜ Decide on user roles needed
3. ⬜ Configure shelter bed count
4. ⬜ Set up intake forms
5. ⬜ Configure consent types
6. ⬜ Add partner organizations

---

## 💡 Pro Tips

1. **Start with Demo Data**
   - Import it first thing
   - Test all features
   - Understand the system
   - Then clear and use real data

2. **Use Configuration Profiles**
   - Create "development" profile
   - Create "production" profile
   - Easy switching between environments

3. **Regular Backups**
   - Use the Backups tab
   - Schedule regular backups
   - Test restore occasionally

4. **Branding Matters**
   - Set your colors early
   - Upload your logo
   - Make it feel like home
   - Use the live preview

5. **Role Assignment**
   - Understand the 5 roles
   - Client = residents
   - Staff = case workers
   - Peer = support workers
   - Partner = external orgs
   - Admin = system managers

---

## 🆘 Common Questions

**Q: I forgot the setup password!**  
A: It's `079777` (won't change unless you edit the code)

**Q: Can't connect to database?**  
A: Use the "Test Connection" button in setup portal to diagnose

**Q: Where are my backups?**  
A: In `/php-web/backups/` folder (downloadable via setup portal)

**Q: How do I add a new user?**  
A: Use the "Accounts" tab in setup portal

**Q: Can I change the site name?**  
A: Yes! "Branding" tab in setup portal

**Q: Is this secure?**  
A: Yes for development. For production: enable HTTPS, change setup password, review security docs

**Q: Where's the full documentation?**  
A: Open `README.md` in the php-web folder

---

## 📞 Need Help?

1. **Check Documentation**
   - Start with README.md
   - Look at CUSTOMIZATION_QUESTIONS.md
   - Review PROJECT_COMPLETION.md

2. **Use Diagnostics**
   - Setup portal → Diagnostics tab
   - Check system health
   - View error logs

3. **GitHub Issues**
   - Repository has issue tracker
   - Community support available

---

## 🎉 You're All Set!

Your Transition House platform is **ready to use**. 

**Start exploring:**
1. Setup portal → `http://your-server/php-web/setup.html` (password: `079777`)
2. Main application → `http://your-server/php-web/`
3. Login with `staff@demo.com` / `demo1234`

**Have fun building something amazing for your community!** 🏠❤️

---

*Need more details? Open the README.md file for complete documentation.*
