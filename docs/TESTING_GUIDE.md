# Testing & Validation Guide for Transition House Platform

## Overview
This document provides comprehensive testing procedures for all features and database operations in the Transition House platform.

## 🔐 Authentication Testing

### Registration Flow
1. **Navigate to Registration**
   - Click "Register" link on login page
   - Verify navigation to #register screen
   - ✅ Expected: Registration form appears

2. **Form Validation**
   - Try submitting empty form
   - ✅ Expected: "Please fill out all required fields" error
   - Enter mismatched passwords
   - ✅ Expected: "Passwords do not match" error
   - Enter short password (< 8 chars)
   - ✅ Expected: "Password must be at least 8 characters" error

3. **Successful Registration**
   - Fill all required fields correctly
   - Select a role
   - Submit form
   - ✅ Expected: "Registration successful!" message
   - ✅ Expected: Redirect to login after 1.5 seconds

### Login Flow
1. **Empty Field Validation**
   - Try logging in with empty email
   - ✅ Expected: "Please enter your email address" error
   - Try logging in with empty password
   - ✅ Expected: "Please enter your password" error

2. **Invalid Credentials**
   - Enter non-existent email
   - ✅ Expected: "Invalid email or password" error

3. **Successful Login**
   - Use demo credentials (from setup page)
   - ✅ Expected: "Login successful!" message
   - ✅ Expected: Redirect to dashboard

### Forgot Password Flow
1. **Navigate to Forgot Password**
   - Click "Forgot Password?" link
   - ✅ Expected: Forgot password form appears

2. **Form Validation**
   - Submit empty email
   - ✅ Expected: "Please enter your email address" error

3. **Password Reset Request**
   - Enter valid email
   - ✅ Expected: "Password reset link sent to your email!" message
   - Check server logs for reset token (in development)

## 🗄️ Database Operations Testing

### Setup Page Testing
1. **Access Setup Page**
   - Navigate to `/setup.html`
   - ✅ Expected: Password prompt appears

2. **Authentication**
   - Try wrong password
   - ✅ Expected: "Incorrect password" error
   - Enter correct password: `079777`
   - ✅ Expected: Setup interface appears

3. **Database Status Check**
   - Click "Check Database" button
   - ✅ Expected: Shows number of tables found
   - ✅ Expected: Green success message

4. **Reset Tables**
   - Click "Reset Tables" button
   - Confirm the action
   - ✅ Expected: "Tables reset successfully!" message
   - ✅ Expected: Database status updates

5. **Import Demo Data**
   - Click "Import Demo Data" button
   - ✅ Expected: "Demo data imported successfully!" message
   - ✅ Expected: 5 demo accounts created

6. **Quick Login**
   - Click any "Login as..." button
   - ✅ Expected: Redirected to dashboard as that user

## 📊 Dashboard & Data Display

### Overview Section
1. **Bed Statistics**
   - ✅ Expected: Total, available, and occupied bed counts display
   - ✅ Expected: Numbers are accurate and update in real-time

2. **Announcements**
   - ✅ Expected: Latest announcements appear
   - ✅ Expected: "No announcements" message if none exist

3. **Quick Actions**
   - Click each quick action button
   - ✅ Expected: Navigates to correct section

### Bed Management
1. **View Beds**
   - Navigate to Beds section
   - ✅ Expected: Bed grid displays with status colors
   - ✅ Expected: Each bed shows number and status

2. **Refresh Beds**
   - Click "Refresh" button
   - ✅ Expected: Bed data reloads

### Intake Management
1. **View Intakes**
   - Navigate to Intake section
   - ✅ Expected: List of intakes displays
   - ✅ Expected: "No intakes found" message if empty

2. **Create New Intake** (if implemented)
   - Click "New Intake" button
   - Fill form and submit
   - ✅ Expected: Intake created successfully
   - ✅ Expected: Appears in list

## 💬 Messaging System Testing

### Conversations
1. **View Conversations**
   - Navigate to Messages section
   - ✅ Expected: Conversation list appears (empty if none)

2. **Start New Conversation**
   - Click "+ New" button
   - ✅ Expected: Modal opens

3. **Username Search**
   - Type username in search field
   - ✅ Expected: Suggestions appear after 2 characters
   - ✅ Expected: "No users found" if no matches
   - Click a suggestion
   - ✅ Expected: Username populates field

4. **Send First Message**
   - Fill username and message
   - Submit form
   - ✅ Expected: Conversation starts
   - ✅ Expected: Modal closes
   - ✅ Expected: New conversation appears in list

5. **Message Exchange**
   - Click a conversation
   - ✅ Expected: Messages load
   - ✅ Expected: Message input enabled
   - Type and send message
   - ✅ Expected: Message appears in chat
   - ✅ Expected: Sound notification plays (if enabled)

6. **Conversation Search**
   - Type in conversation search box
   - ✅ Expected: Conversations filter in real-time

## 🔔 Notifications Testing

### Notification Display
1. **View Notifications**
   - Click bell icon
   - ✅ Expected: Dropdown appears
   - ✅ Expected: Shows notifications or "No new notifications"

2. **Unread Badge**
   - ✅ Expected: Badge shows count when unread notifications exist
   - ✅ Expected: Badge hidden when count is 0

3. **Mark as Read**
   - Click a notification
   - ✅ Expected: Notification marked as read
   - ✅ Expected: Badge count decreases

4. **Mark All as Read**
   - Click "Mark all read" button
   - ✅ Expected: All notifications marked as read
   - ✅ Expected: Badge disappears

5. **Sound Notifications**
   - Wait for new notification
   - ✅ Expected: Sound plays (if sounds enabled)
   - ✅ Expected: Badge updates

## 🔊 Sound System Testing

### Sound Toggle
1. **Toggle Sounds**
   - Click sound icon in navbar
   - ✅ Expected: Icon changes (🔊 ↔ 🔇)
   - ✅ Expected: Preference saved

2. **Test Each Sound**
   - Notification sound: Wait for or trigger notification
   - Message sound: Receive new message
   - Success sound: Enable sounds (plays test sound)
   - ✅ Expected: Appropriate sound plays
   - ✅ Expected: No sound when disabled

## 🎨 Theme System Testing

### Theme Selector
1. **Open Theme Selector**
   - Click user avatar
   - Click "Change Theme"
   - ✅ Expected: Theme modal opens

2. **Switch Themes**
   - Click each theme card
   - ✅ Expected: Theme applies immediately
   - ✅ Expected: Active theme highlighted

3. **Toggle Mode**
   - Click "Light Mode" / "Dark Mode"
   - ✅ Expected: All themes update to selected mode
   - ✅ Expected: Active button highlighted

4. **Custom Colors**
   - Select a theme
   - Change custom color
   - ✅ Expected: Color updates immediately
   - ✅ Expected: Custom color persists

5. **Persistence**
   - Set a theme and mode
   - Refresh page
   - ✅ Expected: Theme and mode remain set

## 👤 User Profile Testing

### Profile Dropdown
1. **Open Dropdown**
   - Click user avatar
   - ✅ Expected: Dropdown menu appears
   - ✅ Expected: User name and email displayed

2. **Dropdown Actions**
   - Click "View Profile"
   - ✅ Expected: Action acknowledged
   - Click "Edit Profile"
   - ✅ Expected: Action acknowledged
   - Click "Settings"
   - ✅ Expected: Action acknowledged

3. **Close Dropdown**
   - Click outside dropdown
   - ✅ Expected: Dropdown closes

## 📰 News Marquee Testing

1. **Marquee Display**
   - ✅ Expected: News items scroll across screen
   - ✅ Expected: Smooth animation

2. **Pause on Hover**
   - Hover over marquee
   - ✅ Expected: Animation pauses
   - Move mouse away
   - ✅ Expected: Animation resumes

3. **Dynamic Content**
   - ✅ Expected: Loads announcements from API
   - ✅ Expected: Falls back to default news if API fails

## 🔒 Security Testing

### Input Validation
1. **SQL Injection**
   - Try SQL injection in login: `' OR '1'='1`
   - ✅ Expected: Properly escaped, no injection
   
2. **XSS Testing**
   - Try XSS in message: `<script>alert('XSS')</script>`
   - ✅ Expected: Properly escaped, no script execution

3. **CSRF Protection**
   - Verify API requires authentication
   - ✅ Expected: 401 error without valid token

### Authentication
1. **Token Expiry**
   - Wait for token to expire (1 hour)
   - ✅ Expected: Auto-logout or token refresh

2. **Token Validation**
   - Try accessing API with invalid token
   - ✅ Expected: 401 Unauthorized error

## 📱 Responsive Testing

### Mobile View (< 768px)
1. **Navigation**
   - ✅ Expected: Navigation adapts to mobile
   - ✅ Expected: All features accessible

2. **Messaging**
   - ✅ Expected: Conversations list toggles on mobile
   - ✅ Expected: Messages view takes full width

3. **Theme Selector**
   - ✅ Expected: Theme cards stack vertically
   - ✅ Expected: All themes accessible

## 🌐 Browser Compatibility

Test in:
- ✅ Chrome/Edge (Chromium)
- ✅ Firefox
- ✅ Safari (if available)
- ✅ Mobile browsers

## 📝 Error Handling

### Network Errors
1. **Offline Mode**
   - Disconnect internet
   - Try any API call
   - ✅ Expected: "Network error. Please check your connection."

2. **Server Error**
   - Simulate 500 error
   - ✅ Expected: Appropriate error message

### Form Errors
1. **Required Fields**
   - Leave required field empty
   - ✅ Expected: Field-specific error message

2. **Field Format**
   - Enter invalid email
   - ✅ Expected: "Invalid email format" error

## 🔄 Data Persistence

1. **LocalStorage**
   - Login, set theme, enable sounds
   - Refresh page
   - ✅ Expected: All preferences persist

2. **Session**
   - Login and navigate
   - Close browser, reopen
   - ✅ Expected: Still logged in (if token valid)

## 📊 Performance Testing

1. **Page Load**
   - Measure initial page load time
   - ✅ Expected: < 3 seconds on broadband

2. **API Response**
   - Check API response times
   - ✅ Expected: < 500ms for most queries

3. **Real-time Updates**
   - Check notification polling
   - ✅ Expected: 30-second interval
   - Check message polling
   - ✅ Expected: 5-second interval

## ✅ Final Checklist

Before deployment:
- [ ] All authentication flows work correctly
- [ ] Database operations function without errors
- [ ] All forms have proper validation
- [ ] Error messages are user-friendly
- [ ] Notifications work and display correctly
- [ ] Messaging system fully functional
- [ ] Sound system works across browsers
- [ ] Theme system applies and persists correctly
- [ ] User profile dropdown functional
- [ ] News marquee displays and scrolls
- [ ] Setup page secured and functional
- [ ] No console errors in browser
- [ ] No PHP errors in server logs
- [ ] Responsive design works on mobile
- [ ] All links navigate correctly
- [ ] Security measures in place
- [ ] Performance acceptable
- [ ] Documentation complete
