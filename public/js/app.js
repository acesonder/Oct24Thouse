/**
 * Transition House - Main Application JavaScript
 */

const API_BASE = '/api';
let currentUser = null;
let authToken = null;

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is already logged in
    authToken = localStorage.getItem('authToken');
    
    if (authToken) {
        verifyToken();
    } else {
        showScreen('login-screen');
    }
    
    // Setup event listeners
    setupEventListeners();
});

function setupEventListeners() {
    // Login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Register form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // Forgot password form
    const forgotForm = document.getElementById('forgot-form');
    if (forgotForm) {
        forgotForm.addEventListener('submit', handleForgotPassword);
    }
    
    // Logout button
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
    
    // Navigation buttons
    const navButtons = document.querySelectorAll('.nav-btn');
    navButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const section = this.getAttribute('data-section');
            showSection(section);
        });
    });
    
    // Refresh beds button
    const refreshBedsBtn = document.getElementById('refresh-beds');
    if (refreshBedsBtn) {
        refreshBedsBtn.addEventListener('click', loadBeds);
    }
    
    // Hash navigation for login/register/forgot
    window.addEventListener('hashchange', handleHashNavigation);
    handleHashNavigation(); // Handle initial hash
}

// Handle hash navigation
function handleHashNavigation() {
    const hash = window.location.hash.substring(1); // Remove the #
    
    if (!authToken) { // Only handle these hashes when not logged in
        switch(hash) {
            case 'register':
                showScreen('register-screen');
                break;
            case 'forgot':
                showScreen('forgot-screen');
                break;
            case 'login':
            case '':
                showScreen('login-screen');
                break;
        }
    }
}

// Authentication
async function handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    // Validate fields
    if (!email) {
        showMessage('login-message', 'Please enter your email address', 'error');
        return;
    }
    
    if (!password) {
        showMessage('login-message', 'Please enter your password', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            authToken = data.token;
            currentUser = data.user;
            localStorage.setItem('authToken', authToken);
            
            showMessage('login-message', 'Login successful!', 'success');
            
            setTimeout(() => {
                showScreen('dashboard-screen');
                initDashboard();
            }, 500);
        } else {
            showMessage('login-message', data.message || 'Invalid email or password', 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showMessage('login-message', 'Network error. Please check your connection.', 'error');
    }
}

// Registration handler
async function handleRegister(e) {
    e.preventDefault();
    
    const email = document.getElementById('reg-email').value;
    const username = document.getElementById('reg-username').value;
    const firstName = document.getElementById('reg-first-name').value;
    const lastName = document.getElementById('reg-last-name').value;
    const password = document.getElementById('reg-password').value;
    const passwordConfirm = document.getElementById('reg-password-confirm').value;
    const roleId = document.getElementById('reg-role').value;
    
    // Validate required fields
    if (!email || !password || !roleId) {
        showMessage('register-message', 'Please fill out all required fields', 'error');
        return;
    }
    
    // Validate password match
    if (password !== passwordConfirm) {
        showMessage('register-message', 'Passwords do not match', 'error');
        return;
    }
    
    // Validate password strength
    if (password.length < 8) {
        showMessage('register-message', 'Password must be at least 8 characters', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email,
                username,
                first_name: firstName,
                last_name: lastName,
                password,
                role_id: roleId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('register-message', 'Registration successful! Redirecting to login...', 'success');
            
            setTimeout(() => {
                window.location.hash = 'login';
                document.getElementById('register-form').reset();
            }, 1500);
        } else {
            showMessage('register-message', data.message || 'Registration failed', 'error');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showMessage('register-message', 'Network error. Please check your connection.', 'error');
    }
}

// Forgot password handler
async function handleForgotPassword(e) {
    e.preventDefault();
    
    const email = document.getElementById('forgot-email').value;
    
    if (!email) {
        showMessage('forgot-message', 'Please enter your email address', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/auth/forgot-password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('forgot-message', 'Password reset link sent to your email!', 'success');
            document.getElementById('forgot-form').reset();
        } else {
            showMessage('forgot-message', data.message || 'Failed to send reset link', 'error');
        }
    } catch (error) {
        console.error('Forgot password error:', error);
        showMessage('forgot-message', 'Network error. Please check your connection.', 'error');
    }
}

async function verifyToken() {
    try {
        const response = await fetch(`${API_BASE}/auth/me`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.user;
            showScreen('dashboard-screen');
            initDashboard();
        } else {
            localStorage.removeItem('authToken');
            showScreen('login-screen');
        }
    } catch (error) {
        console.error('Token verification error:', error);
        localStorage.removeItem('authToken');
        showScreen('login-screen');
    }
}

async function handleLogout() {
    try {
        await fetch(`${API_BASE}/auth/logout`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
    } catch (error) {
        console.error('Logout error:', error);
    }
    
    localStorage.removeItem('authToken');
    authToken = null;
    currentUser = null;
    
    showScreen('login-screen');
}

// Screen management
function showScreen(screenId) {
    const screens = document.querySelectorAll('.screen');
    screens.forEach(screen => screen.classList.remove('active'));
    
    const targetScreen = document.getElementById(screenId);
    if (targetScreen) {
        targetScreen.classList.add('active');
    }
}

function showSection(sectionName) {
    // Update navigation
    const navButtons = document.querySelectorAll('.nav-btn');
    navButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-section') === sectionName) {
            btn.classList.add('active');
        }
    });
    
    // Update content sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => section.classList.remove('active'));
    
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
        
        // Load section-specific data
        loadSectionData(sectionName);
    }
}

// Dashboard initialization
function initDashboard() {
    // Set user info
    const userName = document.getElementById('user-name');
    const userRole = document.getElementById('user-role');
    
    if (currentUser) {
        userName.textContent = `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim() || currentUser.email;
        userRole.textContent = currentUser.role;
    }
    
    // Setup notifications
    setupNotifications();
    
    // Setup messaging
    setupMessaging();
    
    // Load initial data
    loadBedStats();
    loadAnnouncements();
    showSection('overview');
}

function loadSectionData(sectionName) {
    switch(sectionName) {
        case 'beds':
            loadBeds();
            break;
        case 'intake':
            loadIntakes();
            break;
        case 'messages':
            loadConversations();
            break;
        case 'peers':
            loadMentors();
            break;
        case 'resources':
            loadResources();
            break;
    }
}

// Bed management
async function loadBedStats() {
    try {
        const response = await fetch(`${API_BASE}/bed/stats`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const stats = data.stats;
            document.getElementById('total-beds').textContent = stats.total_beds || 0;
            document.getElementById('available-beds').textContent = stats.available_beds || 0;
            document.getElementById('occupied-beds').textContent = stats.occupied_beds || 0;
        }
    } catch (error) {
        console.error('Error loading bed stats:', error);
    }
}

async function loadBeds() {
    try {
        const response = await fetch(`${API_BASE}/bed/list`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayBeds(data.beds);
        }
    } catch (error) {
        console.error('Error loading beds:', error);
    }
}

function displayBeds(beds) {
    const bedMap = document.getElementById('bed-map');
    bedMap.innerHTML = '';
    
    beds.forEach(bed => {
        const bedCard = document.createElement('div');
        bedCard.className = `bed-card ${bed.status}`;
        bedCard.innerHTML = `
            <div class="bed-number">${bed.bed_number}</div>
            <div class="bed-status">${bed.status}</div>
            ${bed.first_name ? `<div class="bed-occupant">${bed.first_name} ${bed.last_name}</div>` : ''}
        `;
        
        bedCard.addEventListener('click', () => showBedDetails(bed));
        
        bedMap.appendChild(bedCard);
    });
}

function showBedDetails(bed) {
    // Show bed details modal (to be implemented)
    console.log('Bed details:', bed);
}

// Intakes
async function loadIntakes() {
    try {
        const response = await fetch(`${API_BASE}/intake/list`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayIntakes(data.intakes);
        }
    } catch (error) {
        console.error('Error loading intakes:', error);
    }
}

function displayIntakes(intakes) {
    const intakeList = document.getElementById('intake-list');
    intakeList.innerHTML = '';
    
    if (intakes.length === 0) {
        intakeList.innerHTML = '<p>No intakes found</p>';
        return;
    }
    
    intakes.forEach(intake => {
        const intakeCard = document.createElement('div');
        intakeCard.className = 'intake-card';
        intakeCard.innerHTML = `
            <h4>${intake.first_name} ${intake.last_name}</h4>
            <p>Status: ${intake.status}</p>
            <p>Created: ${new Date(intake.created_at).toLocaleDateString()}</p>
        `;
        
        intakeList.appendChild(intakeCard);
    });
}

// Announcements
async function loadAnnouncements() {
    // For now, show placeholder
    const announcementsList = document.getElementById('announcements-list');
    announcementsList.innerHTML = '<p>No new announcements</p>';
}

// Conversations
// Messaging
let currentConversationId = null;
let messagePolling = null;

function setupMessaging() {
    // New conversation button
    document.getElementById('new-conversation-btn')?.addEventListener('click', showNewConversationModal);
    
    // Close modal
    document.getElementById('close-new-conversation')?.addEventListener('click', hideNewConversationModal);
    
    // New conversation form
    document.getElementById('new-conversation-form')?.addEventListener('submit', handleNewConversation);
    
    // Username search
    document.getElementById('recipient-username')?.addEventListener('input', handleUsernameSearch);
    
    // Message form
    document.getElementById('message-form')?.addEventListener('submit', handleSendMessage);
    
    // Conversation search
    document.getElementById('conversation-search')?.addEventListener('input', handleConversationSearch);
}

function showNewConversationModal() {
    const modal = document.getElementById('new-conversation-modal');
    if (modal) {
        modal.classList.add('active');
    }
}

function hideNewConversationModal() {
    const modal = document.getElementById('new-conversation-modal');
    if (modal) {
        modal.classList.remove('active');
        document.getElementById('new-conversation-form')?.reset();
        document.getElementById('username-suggestions').innerHTML = '';
    }
}

async function handleUsernameSearch(e) {
    const query = e.target.value.trim();
    
    if (query.length < 2) {
        document.getElementById('username-suggestions').innerHTML = '';
        document.getElementById('username-suggestions').classList.remove('active');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/chat/search-users?q=${encodeURIComponent(query)}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        
        if (data.success && data.users.length > 0) {
            displayUserSuggestions(data.users);
        } else {
            document.getElementById('username-suggestions').innerHTML = '<div class="suggestion-item">No users found</div>';
            document.getElementById('username-suggestions').classList.add('active');
        }
    } catch (error) {
        console.error('Error searching users:', error);
    }
}

function displayUserSuggestions(users) {
    const suggestions = document.getElementById('username-suggestions');
    
    suggestions.innerHTML = users.map(user => `
        <div class="suggestion-item" data-username="${escapeHtml(user.username)}">
            <div class="suggestion-username">@${escapeHtml(user.username)}</div>
            <div class="suggestion-name">${escapeHtml(user.first_name || '')} ${escapeHtml(user.last_name || '')}</div>
        </div>
    `).join('');
    
    suggestions.classList.add('active');
    
    // Add click handlers
    suggestions.querySelectorAll('.suggestion-item').forEach(item => {
        item.addEventListener('click', function() {
            document.getElementById('recipient-username').value = this.dataset.username;
            suggestions.innerHTML = '';
            suggestions.classList.remove('active');
        });
    });
}

async function handleNewConversation(e) {
    e.preventDefault();
    
    const username = document.getElementById('recipient-username').value.trim();
    const message = document.getElementById('first-message').value.trim();
    
    if (!username || !message) {
        alert('Please fill in all fields');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/chat/start-conversation`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ recipient_username: username, message })
        });
        
        const data = await response.json();
        
        if (data.success) {
            hideNewConversationModal();
            loadConversations();
            if (data.conversation_id) {
                selectConversation(data.conversation_id);
            }
        } else {
            alert(data.message || 'Failed to start conversation');
        }
    } catch (error) {
        console.error('Error starting conversation:', error);
        alert('An error occurred');
    }
}

async function loadConversations() {
    try {
        const response = await fetch(`${API_BASE}/chat/conversations`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayConversations(data.conversations || []);
        }
    } catch (error) {
        console.error('Error loading conversations:', error);
    }
}

function displayConversations(conversations) {
    const conversationsEl = document.getElementById('conversations');
    
    if (!conversationsEl) return;
    
    if (conversations.length === 0) {
        conversationsEl.innerHTML = '<p style="padding: 20px; text-align: center; color: #999;">No conversations yet</p>';
        return;
    }
    
    conversationsEl.innerHTML = conversations.map(conv => `
        <div class="conversation-item ${conv.id === currentConversationId ? 'active' : ''}" data-id="${conv.id}">
            <div class="conversation-name">${escapeHtml(conv.other_user_name || 'Unknown User')}</div>
            <div class="conversation-preview">${escapeHtml(conv.last_message || 'No messages yet')}</div>
            <div class="conversation-time">${conv.last_message_time ? formatTimeAgo(conv.last_message_time) : ''}</div>
            ${conv.unread_count > 0 ? `<div class="conversation-unread">${conv.unread_count}</div>` : ''}
        </div>
    `).join('');
    
    // Add click handlers
    conversationsEl.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', function() {
            selectConversation(parseInt(this.dataset.id));
        });
    });
}

function selectConversation(conversationId) {
    currentConversationId = conversationId;
    
    // Update active state
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.toggle('active', parseInt(item.dataset.id) === conversationId);
    });
    
    // Load messages
    loadMessages(conversationId);
    
    // Start polling for new messages
    if (messagePolling) {
        clearInterval(messagePolling);
    }
    messagePolling = setInterval(() => loadMessages(conversationId, true), 5000);
}

async function loadMessages(conversationId, silent = false) {
    try {
        const response = await fetch(`${API_BASE}/chat/messages/${conversationId}`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayMessages(data.messages || [], data.conversation || {});
        }
    } catch (error) {
        if (!silent) {
            console.error('Error loading messages:', error);
        }
    }
}

function displayMessages(messages, conversation) {
    const container = document.getElementById('messages-container');
    const title = document.getElementById('conversation-title');
    
    if (!container || !title) return;
    
    // Update header
    title.textContent = conversation.other_user_name || 'Conversation';
    
    if (messages.length === 0) {
        container.innerHTML = '<p class="empty-state">No messages yet. Start the conversation!</p>';
        return;
    }
    
    // Store current scroll position
    const wasAtBottom = container.scrollHeight - container.scrollTop === container.clientHeight;
    
    container.innerHTML = messages.map(msg => {
        const isSent = msg.sender_id === currentUser.id;
        return `
            <div class="message ${isSent ? 'sent' : 'received'}">
                ${!isSent ? `<div class="message-sender">${escapeHtml(msg.sender_name || 'User')}</div>` : ''}
                <div class="message-bubble">${escapeHtml(msg.content)}</div>
                <div class="message-time">${formatTimeAgo(msg.created_at)}</div>
            </div>
        `;
    }).join('');
    
    // Scroll to bottom if was at bottom before
    if (wasAtBottom || messages.length > 0) {
        container.scrollTop = container.scrollHeight;
    }
}

async function handleSendMessage(e) {
    e.preventDefault();
    
    if (!currentConversationId) {
        alert('Please select a conversation first');
        return;
    }
    
    const input = document.getElementById('message-content');
    const content = input.value.trim();
    
    if (!content) return;
    
    try {
        const response = await fetch(`${API_BASE}/chat/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({
                conversation_id: currentConversationId,
                content
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            input.value = '';
            loadMessages(currentConversationId, true);
        } else {
            alert(data.message || 'Failed to send message');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('An error occurred');
    }
}

function handleConversationSearch(e) {
    const query = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.conversation-item');
    
    items.forEach(item => {
        const name = item.querySelector('.conversation-name').textContent.toLowerCase();
        const preview = item.querySelector('.conversation-preview').textContent.toLowerCase();
        
        if (name.includes(query) || preview.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Mentors
async function loadMentors() {
    try {
        const response = await fetch(`${API_BASE}/peer/mentors`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayMentors(data.mentors);
        }
    } catch (error) {
        console.error('Error loading mentors:', error);
    }
}

function displayMentors(mentors) {
    const mentorsGrid = document.getElementById('mentors-grid');
    mentorsGrid.innerHTML = '';
    
    if (mentors.length === 0) {
        mentorsGrid.innerHTML = '<p>No mentors available</p>';
        return;
    }
    
    mentors.forEach(mentor => {
        const mentorCard = document.createElement('div');
        mentorCard.className = 'mentor-card';
        
        const specializations = mentor.specializations || [];
        const specializationsHTML = specializations.map(spec => 
            `<span class="specialization-tag">${spec}</span>`
        ).join('');
        
        mentorCard.innerHTML = `
            <h4>${mentor.first_name} ${mentor.last_name}</h4>
            <p>${mentor.bio || 'No bio available'}</p>
            <div class="mentor-specializations">${specializationsHTML}</div>
            <button class="btn btn-primary" onclick="requestMentorship(${mentor.user_id})">Request Mentorship</button>
        `;
        
        mentorsGrid.appendChild(mentorCard);
    });
}

async function requestMentorship(mentorId) {
    try {
        const response = await fetch(`${API_BASE}/peer/mentorship/request`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ mentor_id: mentorId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Mentorship request sent!');
        } else {
            alert(data.message || 'Failed to send request');
        }
    } catch (error) {
        console.error('Error requesting mentorship:', error);
        alert('An error occurred');
    }
}

// Resources
function loadResources() {
    const resourcesGrid = document.getElementById('resources-grid');
    resourcesGrid.innerHTML = '<p>Resource directory coming soon</p>';
}

// Utility functions
function showMessage(elementId, message, type) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = message;
        element.className = `message ${type}`;
        element.style.display = 'block';
        
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    }
}

// API helper
async function apiRequest(endpoint, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authToken}`
        }
    };
    
    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    };
    
    try {
        const response = await fetch(`${API_BASE}${endpoint}`, mergedOptions);
        return await response.json();
    } catch (error) {
        console.error('API request error:', error);
        throw error;
    }
}

// Notifications System
let notificationPollInterval = null;
let unreadNotificationCount = 0;

function setupNotifications() {
    const notificationBell = document.getElementById('notification-bell');
    const notificationDropdown = document.getElementById('notification-dropdown');
    
    if (!notificationBell) return;
    
    // Toggle dropdown on click
    notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('active');
        
        if (notificationDropdown.classList.contains('active')) {
            loadNotifications();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationBell.contains(e.target)) {
            notificationDropdown.classList.remove('active');
        }
    });
    
    // Mark all as read
    document.getElementById('mark-all-read')?.addEventListener('click', markAllNotificationsRead);
    
    // Load initial notifications
    loadNotifications();
    
    // Poll for new notifications every 30 seconds
    notificationPollInterval = setInterval(loadNotifications, 30000);
}

async function loadNotifications() {
    if (!authToken) return;
    
    try {
        const response = await fetch(`${API_BASE}/notifications/list`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayNotifications(data.notifications || []);
            updateNotificationBadge(data.unread_count || 0);
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

function displayNotifications(notifications) {
    const notificationList = document.getElementById('notification-list');
    
    if (!notificationList) return;
    
    if (notifications.length === 0) {
        notificationList.innerHTML = '<p class="no-notifications">No new notifications</p>';
        return;
    }
    
    notificationList.innerHTML = '';
    
    notifications.forEach(notification => {
        const item = document.createElement('div');
        item.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
        item.dataset.id = notification.id;
        
        item.innerHTML = `
            <div class="notification-title">${escapeHtml(notification.title)}</div>
            <div class="notification-message">${escapeHtml(notification.message)}</div>
            <div class="notification-time">${formatTimeAgo(notification.created_at)}</div>
        `;
        
        item.addEventListener('click', () => handleNotificationClick(notification));
        
        notificationList.appendChild(item);
    });
}

function updateNotificationBadge(count) {
    unreadNotificationCount = count;
    const badge = document.getElementById('notification-badge');
    
    if (badge) {
        badge.textContent = count;
        if (count > 0) {
            badge.classList.add('active');
        } else {
            badge.classList.remove('active');
        }
    }
}

async function handleNotificationClick(notification) {
    // Mark as read
    if (!notification.is_read) {
        try {
            await fetch(`${API_BASE}/notifications/${notification.id}/read`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${authToken}`
                }
            });
            
            // Reload notifications
            loadNotifications();
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    // Handle notification action (navigate to related section)
    if (notification.action_url) {
        window.location.href = notification.action_url;
    }
}

async function markAllNotificationsRead() {
    try {
        await fetch(`${API_BASE}/notifications/mark-all-read`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        loadNotifications();
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
}

function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
    
    return date.toLocaleDateString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Service Worker registration for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => console.log('SW registered:', registration))
            .catch(error => console.log('SW registration failed:', error));
    });
}
