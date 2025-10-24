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
}

// Authentication
async function handleLogin(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
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
            showMessage('login-message', data.message || 'Login failed', 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showMessage('login-message', 'An error occurred during login', 'error');
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
async function loadConversations() {
    // For now, show placeholder
    const conversations = document.getElementById('conversations');
    conversations.innerHTML = '<p>No conversations yet</p>';
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

// Service Worker registration for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => console.log('SW registered:', registration))
            .catch(error => console.log('SW registration failed:', error));
    });
}
