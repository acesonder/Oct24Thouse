// Navigation and Page Management

// Navigation structure by role
const NAVIGATION = {
    1: [ // Client
        { category: 'My Dashboard', items: [
            { name: 'Dashboard', page: 'dashboard', icon: '📊' },
            { name: 'My Profile', page: 'profile', icon: '👤' },
        ]},
        { category: 'Services', items: [
            { name: 'Intake Form', page: 'intake', icon: '📝' },
            { name: 'Bed Request', page: 'bed-request', icon: '🛏️' },
            { name: 'My Goals', page: 'goals', icon: '🎯' },
            { name: 'Appointments', page: 'appointments', icon: '📅' },
            { name: 'Documents', page: 'documents', icon: '📄' },
        ]},
        { category: 'Communication', items: [
            { name: 'Messages', page: 'messages', icon: '💬' },
            { name: 'Community', page: 'community', icon: '👥' },
        ]},
        { category: 'Resources', items: [
            { name: 'Resource Directory', page: 'resources', icon: '📚' },
            { name: 'Find Peers', page: 'find-peers', icon: '🤝' },
        ]}
    ],
    2: [ // Staff
        { category: 'Staff Dashboard', items: [
            { name: 'Shift Dashboard', page: 'staff-dashboard', icon: '📊' },
            { name: 'Quick Actions', page: 'quick-actions', icon: '⚡' },
        ]},
        { category: 'Client Management', items: [
            { name: 'Intake Management', page: 'intake-management', icon: '📝' },
            { name: 'Bed Management', page: 'bed-management', icon: '🛏️' },
            { name: 'Case Management', page: 'case-management', icon: '📋' },
            { name: 'Client Directory', page: 'client-directory', icon: '👥' },
        ]},
        { category: 'Operations', items: [
            { name: 'Referrals', page: 'referrals', icon: '🔄' },
            { name: 'Incidents', page: 'incidents', icon: '⚠️' },
            { name: 'Inventory', page: 'inventory', icon: '📦' },
        ]},
        { category: 'Reporting', items: [
            { name: 'Analytics', page: 'analytics', icon: '📈' },
            { name: 'Reports', page: 'reports', icon: '📊' },
        ]}
    ],
    3: [ // Peer
        { category: 'Peer Dashboard', items: [
            { name: 'Dashboard', page: 'peer-dashboard', icon: '📊' },
            { name: 'My Profile', page: 'peer-profile', icon: '👤' },
        ]},
        { category: 'Mentorship', items: [
            { name: 'My Mentees', page: 'mentees', icon: '🤝' },
            { name: 'Engagements', page: 'engagements', icon: '📝' },
            { name: 'Find Mentees', page: 'find-mentees', icon: '🔍' },
        ]},
        { category: 'Development', items: [
            { name: 'Training', page: 'training', icon: '📚' },
            { name: 'Certifications', page: 'certifications', icon: '🏆' },
            { name: 'Shifts', page: 'peer-shifts', icon: '📅' },
        ]},
        { category: 'Community', items: [
            { name: 'Community Feed', page: 'community', icon: '👥' },
            { name: 'Messages', page: 'messages', icon: '💬' },
        ]}
    ],
    4: [ // Partner
        { category: 'Partner Portal', items: [
            { name: 'Dashboard', page: 'partner-dashboard', icon: '📊' },
            { name: 'My Organization', page: 'organization', icon: '🏢' },
        ]},
        { category: 'Referrals', items: [
            { name: 'Incoming Referrals', page: 'incoming-referrals', icon: '📥' },
            { name: 'My Clients', page: 'partner-clients', icon: '👥' },
        ]},
        { category: 'Services', items: [
            { name: 'Appointments', page: 'partner-appointments', icon: '📅' },
            { name: 'Documents', page: 'partner-documents', icon: '📄' },
        ]}
    ],
    5: [ // Admin
        { category: 'Administration', items: [
            { name: 'Dashboard', page: 'admin-dashboard', icon: '📊' },
            { name: 'System Overview', page: 'system-overview', icon: '🖥️' },
        ]},
        { category: 'User Management', items: [
            { name: 'Users', page: 'users', icon: '👥' },
            { name: 'Roles & Permissions', page: 'roles', icon: '🔐' },
        ]},
        { category: 'Configuration', items: [
            { name: 'Settings', page: 'settings', icon: '⚙️' },
            { name: 'Branding', page: 'branding', icon: '🎨' },
            { name: 'Templates', page: 'templates', icon: '📋' },
        ]},
        { category: 'Data & Compliance', items: [
            { name: 'Analytics', page: 'admin-analytics', icon: '📈' },
            { name: 'Audit Log', page: 'audit-log', icon: '📜' },
            { name: 'Data Export', page: 'data-export', icon: '💾' },
        ]}
    ]
};

function initializeNavigation(roleId) {
    const navMenu = document.getElementById('nav-menu');
    const navigation = NAVIGATION[roleId] || NAVIGATION[1];
    
    let html = '';
    navigation.forEach(section => {
        html += `<div class="nav-category">${section.category}</div>`;
        section.items.forEach(item => {
            html += `
                <a href="#" class="nav-item" onclick="navigateTo('${item.page}'); return false;">
                    <span>${item.icon}</span> ${item.name}
                </a>
            `;
        });
    });
    
    navMenu.innerHTML = html;
}

// Page navigation
function navigateTo(page) {
    const pageTitle = document.getElementById('page-title');
    const pageContainer = document.getElementById('page-container');
    
    // Update active nav item
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Load page content
    loadPage(page);
}

async function loadPage(page) {
    const pageContainer = document.getElementById('page-container');
    const pageTitle = document.getElementById('page-title');
    
    // Show loading
    pageContainer.innerHTML = '<div class="loader"></div>';
    
    try {
        // Try to load page from pages directory
        const response = await fetch(`pages/${page}.html`);
        
        if (response.ok) {
            const html = await response.text();
            pageContainer.innerHTML = html;
            pageTitle.textContent = formatPageTitle(page);
            
            // Initialize page-specific scripts
            if (window['init_' + page.replace(/-/g, '_')]) {
                window['init_' + page.replace(/-/g, '_')]();
            }
        } else {
            // Page not found, show placeholder
            pageContainer.innerHTML = generatePagePlaceholder(page);
            pageTitle.textContent = formatPageTitle(page);
        }
    } catch (error) {
        pageContainer.innerHTML = `
            <div class="card">
                <h2>Error Loading Page</h2>
                <p class="message error">${error.message}</p>
            </div>
        `;
    }
}

function generatePagePlaceholder(page) {
    const title = formatPageTitle(page);
    return `
        <div class="card">
            <h2>${title}</h2>
            <p>This page is under construction.</p>
            <p>Page ID: <code>${page}</code></p>
        </div>
    `;
}

function formatPageTitle(page) {
    return page
        .split('-')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function loadDashboard() {
    const user = Auth.getCurrentUser();
    const dashboardPages = {
        1: 'dashboard',
        2: 'staff-dashboard',
        3: 'peer-dashboard',
        4: 'partner-dashboard',
        5: 'admin-dashboard'
    };
    
    navigateTo(dashboardPages[user.role_id] || 'dashboard');
}

// Menu toggle for mobile
document.getElementById('menu-toggle')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('open');
});

// User menu toggle
document.getElementById('user-menu-btn')?.addEventListener('click', function() {
    document.getElementById('user-dropdown').classList.toggle('hidden');
});

// Close user menu when clicking outside
document.addEventListener('click', function(e) {
    const userMenu = document.getElementById('user-menu-btn');
    const dropdown = document.getElementById('user-dropdown');
    
    if (!userMenu?.contains(e.target)) {
        dropdown?.classList.add('hidden');
    }
});

// Notifications toggle
document.getElementById('notifications-btn')?.addEventListener('click', function() {
    document.getElementById('notifications-panel').classList.toggle('hidden');
});

document.getElementById('close-notifications')?.addEventListener('click', function() {
    document.getElementById('notifications-panel').classList.add('hidden');
});
