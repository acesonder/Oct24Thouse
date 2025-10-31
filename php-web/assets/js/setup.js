// Setup Portal JavaScript
const SETUP_PASSWORD = '079777';
const API_BASE = '/php-web/includes/setup-api.php';

// Check if unlocked
let isUnlocked = sessionStorage.getItem('setupUnlocked') === 'true';

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    if (isUnlocked) {
        showSetup();
        loadBranding();
    }
    
    // Unlock form
    document.getElementById('unlock-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const password = document.getElementById('setup-password').value;
        
        if (password === SETUP_PASSWORD) {
            sessionStorage.setItem('setupUnlocked', 'true');
            showSetup();
            loadBranding();
        } else {
            showMessage('lock-message', 'Incorrect password', 'error');
        }
    });
    
    // Lock button
    document.getElementById('lock-btn').addEventListener('click', function() {
        sessionStorage.removeItem('setupUnlocked');
        location.reload();
    });
    
    // Tab navigation
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            switchTab(tabName);
        });
    });
    
    // Database configuration
    document.getElementById('db-config-form').addEventListener('submit', handleDatabaseConfig);
    document.getElementById('test-connection').addEventListener('click', testConnection);
    document.getElementById('refresh-stats').addEventListener('click', loadDatabaseStats);
    
    // Accounts
    document.getElementById('create-account-form').addEventListener('submit', createAccount);
    document.getElementById('refresh-accounts').addEventListener('click', loadAccounts);
    
    // Profiles
    document.getElementById('refresh-profiles').addEventListener('click', loadProfiles);
    
    // Branding
    document.getElementById('branding-form').addEventListener('submit', saveBranding);
    
    // Demo
    document.getElementById('import-demo-btn').addEventListener('click', importDemo);
    
    // Backups
    document.getElementById('create-backup-btn').addEventListener('click', createBackup);
    document.getElementById('refresh-backups').addEventListener('click', loadBackups);
    
    // Logs
    document.getElementById('refresh-logs').addEventListener('click', loadErrorLogs);
    
    // Diagnostics
    document.getElementById('run-diagnostics').addEventListener('click', runDiagnostics);
    
    // Reset
    document.getElementById('reset-tables-btn').addEventListener('click', resetTables);
    document.getElementById('clear-data-btn').addEventListener('click', clearData);
    document.getElementById('factory-reset-btn').addEventListener('click', factoryReset);
    
    // Live preview for branding
    ['site-name', 'primary-color', 'secondary-color'].forEach(id => {
        document.getElementById(id).addEventListener('input', updateBrandingPreview);
    });
});

function showSetup() {
    document.getElementById('lock-screen').classList.add('hidden');
    document.getElementById('setup-interface').classList.remove('hidden');
}

function switchTab(tabName) {
    // Update nav
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Update content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`tab-${tabName}`).classList.add('active');
    
    // Load data for specific tabs
    if (tabName === 'accounts') loadAccounts();
    if (tabName === 'profiles') loadProfiles();
    if (tabName === 'backups') loadBackups();
    if (tabName === 'logs') loadErrorLogs();
    if (tabName === 'database') loadDatabaseStats();
}

function showMessage(elementId, message, type) {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = message;
        el.className = `message ${type}`;
        setTimeout(() => {
            el.className = 'message';
        }, 5000);
    }
}

async function apiCall(action, data = {}, method = 'POST') {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Setup-Password': SETUP_PASSWORD
            }
        };
        
        if (method === 'POST' && data) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(`${API_BASE}/${action}`, options);
        const result = await response.json();
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: error.message };
    }
}

// Database functions
async function handleDatabaseConfig(e) {
    e.preventDefault();
    
    const data = {
        database_name: document.getElementById('db-name').value,
        profile_name: document.getElementById('db-profile').value,
        host: document.getElementById('db-host').value,
        port: parseInt(document.getElementById('db-port').value),
        username: document.getElementById('db-user').value,
        password: document.getElementById('db-password').value,
        overwrite: document.getElementById('db-overwrite').checked,
        create_tables: document.getElementById('db-create-tables').checked
    };
    
    const result = await apiCall('configure-database', data);
    
    if (result.success) {
        showMessage('db-message', result.message, 'success');
        loadDatabaseStats();
    } else {
        showMessage('db-message', result.message, 'error');
    }
}

async function testConnection() {
    const result = await apiCall('test-connection', {}, 'GET');
    
    if (result.success) {
        showMessage('db-message', `✓ ${result.message} (MySQL ${result.mysql_version})`, 'success');
    } else {
        showMessage('db-message', `✗ ${result.message}`, 'error');
    }
}

async function loadDatabaseStats() {
    const result = await apiCall('database-stats', {}, 'GET');
    const container = document.getElementById('db-stats');
    
    if (result.success && result.stats) {
        const stats = result.stats;
        let html = `
            <div class="stat-item">
                <h4>Database: ${stats.database}</h4>
                <p>Total Tables: ${stats.tables}</p>
            </div>
        `;
        
        if (stats.table_list && stats.table_list.length > 0) {
            html += '<div class="stat-grid">';
            stats.table_list.forEach(table => {
                html += `
                    <div class="stat-item">
                        <strong>${table.name}</strong><br>
                        ${table.rows} rows
                    </div>
                `;
            });
            html += '</div>';
        }
        
        container.innerHTML = html;
    } else {
        container.innerHTML = '<p>No database statistics available</p>';
    }
}

// Account functions
async function createAccount(e) {
    e.preventDefault();
    
    const data = {
        email: document.getElementById('acc-email').value,
        username: document.getElementById('acc-username').value,
        first_name: document.getElementById('acc-firstname').value,
        last_name: document.getElementById('acc-lastname').value,
        password: document.getElementById('acc-password').value,
        role_id: parseInt(document.getElementById('acc-role').value)
    };
    
    const result = await apiCall('create-account', data);
    
    if (result.success) {
        showMessage('create-account-message', result.message, 'success');
        document.getElementById('create-account-form').reset();
        loadAccounts();
    } else {
        showMessage('create-account-message', result.message, 'error');
    }
}

async function loadAccounts() {
    const result = await apiCall('list-accounts', {}, 'GET');
    const container = document.getElementById('accounts-list');
    
    if (result.success && result.accounts) {
        const roleNames = ['', 'Client', 'Staff', 'Peer', 'Partner', 'Admin'];
        const roleClasses = ['', 'client', 'staff', 'peer', 'partner', 'admin'];
        
        let html = '';
        result.accounts.forEach(acc => {
            const roleName = roleNames[acc.role_id] || 'Unknown';
            const roleClass = roleClasses[acc.role_id] || '';
            
            html += `
                <div class="account-item">
                    <div class="account-info">
                        <h4>
                            ${acc.first_name} ${acc.last_name}
                            <span class="role-badge role-${roleClass}">${roleName}</span>
                        </h4>
                        <p>Email: ${acc.email} | Username: ${acc.username}</p>
                        <p>Created: ${new Date(acc.created_at).toLocaleDateString()}</p>
                    </div>
                    <div class="account-actions">
                        <button class="btn btn-sm btn-secondary" onclick="editAccount(${acc.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteAccount(${acc.id})">Delete</button>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html || '<p>No accounts found</p>';
    } else {
        container.innerHTML = '<p>Error loading accounts</p>';
    }
}

async function deleteAccount(userId) {
    if (!confirm('Are you sure you want to delete this account?')) return;
    
    const result = await apiCall('delete-account', { user_id: userId });
    
    if (result.success) {
        loadAccounts();
    } else {
        alert('Failed to delete account: ' + result.message);
    }
}

// Profile functions
async function loadProfiles() {
    const result = await apiCall('list-profiles', {}, 'GET');
    const container = document.getElementById('profiles-list');
    
    if (result.success && result.profiles) {
        let html = '';
        result.profiles.forEach(profile => {
            html += `
                <div class="profile-item">
                    <div>
                        <strong>${profile}</strong>
                    </div>
                    <button class="btn btn-sm btn-secondary" onclick="loadProfile('${profile}')">Load</button>
                </div>
            `;
        });
        
        container.innerHTML = html || '<p>No profiles found</p>';
    }
}

// Branding functions
async function loadBranding() {
    const result = await apiCall('get-branding', {}, 'GET');
    
    if (result.success && result.branding) {
        const b = result.branding;
        document.getElementById('site-name').value = b.site_name;
        document.getElementById('site-logo').value = b.site_logo;
        document.getElementById('primary-color').value = b.primary_color;
        document.getElementById('secondary-color').value = b.secondary_color;
        document.getElementById('site-theme').value = b.theme;
        
        // Update header
        document.getElementById('site-title').textContent = `🏠 ${b.site_name} - Setup Portal`;
        
        updateBrandingPreview();
    }
}

async function saveBranding(e) {
    e.preventDefault();
    
    const data = {
        site_name: document.getElementById('site-name').value,
        site_logo: document.getElementById('site-logo').value,
        primary_color: document.getElementById('primary-color').value,
        secondary_color: document.getElementById('secondary-color').value,
        theme: document.getElementById('site-theme').value
    };
    
    const result = await apiCall('update-branding', data);
    
    if (result.success) {
        showMessage('branding-message', result.message, 'success');
        document.getElementById('site-title').textContent = `🏠 ${data.site_name} - Setup Portal`;
    } else {
        showMessage('branding-message', result.message, 'error');
    }
}

function updateBrandingPreview() {
    const siteName = document.getElementById('site-name').value;
    const primaryColor = document.getElementById('primary-color').value;
    const secondaryColor = document.getElementById('secondary-color').value;
    
    document.getElementById('preview-site-name').textContent = siteName || 'Transition House';
    document.getElementById('preview-header').style.backgroundColor = primaryColor;
    document.getElementById('preview-header').style.color = '#fff';
}

// Demo functions
async function importDemo() {
    if (!confirm('This will create demo accounts and data. Continue?')) return;
    
    const result = await apiCall('import-demo');
    
    if (result.success) {
        showMessage('demo-message', result.message, 'success');
    } else {
        showMessage('demo-message', result.message, 'error');
    }
}

async function quickLogin(email, password) {
    // This would redirect to main app with credentials
    alert(`Quick login feature:\nEmail: ${email}\nPassword: ${password}\n\nRedirect to main application login with these credentials.`);
    // In production, this would call the auth API and redirect
}

// Backup functions
async function createBackup() {
    const result = await apiCall('backup');
    
    if (result.success) {
        showMessage('backup-message', `Backup created: ${result.filename} (${formatBytes(result.size)})`, 'success');
        loadBackups();
    } else {
        showMessage('backup-message', result.message, 'error');
    }
}

async function loadBackups() {
    const result = await apiCall('list-backups', {}, 'GET');
    const container = document.getElementById('backups-list');
    
    if (result.success && result.backups) {
        let html = '';
        result.backups.forEach(backup => {
            html += `
                <div class="backup-item">
                    <div class="backup-info">
                        <strong>${backup.filename}</strong>
                        <p>Size: ${formatBytes(backup.size)} | Created: ${backup.created}</p>
                    </div>
                    <div class="backup-actions">
                        <button class="btn btn-sm btn-secondary" onclick="restoreBackup('${backup.filename}')">Restore</button>
                        <button class="btn btn-sm btn-secondary" onclick="downloadBackup('${backup.filename}')">Download</button>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html || '<p>No backups found</p>';
    }
}

async function restoreBackup(filename) {
    if (!confirm(`Restore from ${filename}? This will overwrite current data!`)) return;
    
    const result = await apiCall('restore', { filename });
    
    if (result.success) {
        alert('Database restored successfully');
        loadDatabaseStats();
    } else {
        alert('Restore failed: ' + result.message);
    }
}

// Error logs
async function loadErrorLogs() {
    const lines = parseInt(document.getElementById('log-lines').value);
    const result = await apiCall('error-logs', { lines });
    
    if (result.success) {
        document.getElementById('error-logs').textContent = result.logs || 'No errors logged';
    } else {
        document.getElementById('error-logs').textContent = 'Error loading logs';
    }
}

// Diagnostics
async function runDiagnostics() {
    const container = document.getElementById('diagnostics-results');
    container.innerHTML = '<div class="spinner"></div>';
    
    const result = await apiCall('diagnostics', {}, 'GET');
    
    if (result.success && result.diagnostics) {
        let html = '';
        
        for (const [key, value] of Object.entries(result.diagnostics)) {
            const statusClass = value.status ? value.status.toLowerCase() : 'info';
            html += `
                <div class="diagnostic-item">
                    <div>
                        <strong>${formatDiagnosticKey(key)}</strong><br>
                        <small>${JSON.stringify(value.value || value.message || value)}</small>
                    </div>
                    <div class="diagnostic-status ${statusClass}">${value.status || 'N/A'}</div>
                </div>
            `;
        }
        
        container.innerHTML = html;
    } else {
        container.innerHTML = '<p>Diagnostics failed</p>';
    }
}

// Reset functions
async function resetTables() {
    if (!confirm('WARNING: This will delete ALL data and recreate tables. Continue?')) return;
    if (!confirm('Are you ABSOLUTELY sure? This cannot be undone!')) return;
    
    const result = await apiCall('reset-system', { confirm: true });
    
    if (result.success) {
        showMessage('reset-message', result.message, 'success');
        loadDatabaseStats();
    } else {
        showMessage('reset-message', result.message, 'error');
    }
}

async function clearData() {
    if (!confirm('This will delete all records but keep table structure. Continue?')) return;
    
    // Implement clear data logic
    showMessage('reset-message', 'Clear data feature coming soon', 'info');
}

async function factoryReset() {
    if (!confirm('FACTORY RESET: This will reset everything to default. Continue?')) return;
    if (!confirm('Last chance! This will delete ALL data, profiles, and configurations!')) return;
    
    // Implement factory reset logic
    showMessage('reset-message', 'Factory reset feature coming soon', 'info');
}

// Helper functions
function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function formatDiagnosticKey(key) {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

// Make functions available globally
window.quickLogin = quickLogin;
window.editAccount = function(id) { alert('Edit account feature coming soon'); };
window.deleteAccount = deleteAccount;
window.loadProfile = function(name) { alert(`Load profile: ${name}`); };
window.restoreBackup = restoreBackup;
window.downloadBackup = function(filename) { alert(`Download: ${filename}`); };
