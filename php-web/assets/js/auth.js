// Authentication Module
const Auth = {
    API_BASE: '/php-web/includes/api.php',
    TOKEN_KEY: 'authToken',
    USER_KEY: 'currentUser',
    
    // Check if user is logged in
    isAuthenticated() {
        return localStorage.getItem(this.TOKEN_KEY) !== null;
    },
    
    // Get current user
    getCurrentUser() {
        const userStr = localStorage.getItem(this.USER_KEY);
        return userStr ? JSON.parse(userStr) : null;
    },
    
    // Login
    async login(email, password) {
        try {
            const response = await fetch(`${this.API_BASE}/auth/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.success && data.token) {
                localStorage.setItem(this.TOKEN_KEY, data.token);
                localStorage.setItem(this.USER_KEY, JSON.stringify(data.user));
                return { success: true, user: data.user };
            }
            
            return { success: false, message: data.message || 'Login failed' };
        } catch (error) {
            return { success: false, message: error.message };
        }
    },
    
    // Logout
    logout() {
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.USER_KEY);
        window.location.reload();
    },
    
    // Make authenticated API call
    async apiCall(endpoint, data = {}, method = 'GET') {
        const token = localStorage.getItem(this.TOKEN_KEY);
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        };
        
        if (method !== 'GET' && data) {
            options.body = JSON.stringify(data);
        }
        
        const url = method === 'GET' && Object.keys(data).length > 0
            ? `${this.API_BASE}/${endpoint}?${new URLSearchParams(data)}`
            : `${this.API_BASE}/${endpoint}`;
        
        const response = await fetch(url, options);
        return await response.json();
    }
};

// Initialize auth on page load
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const logoutBtn = document.getElementById('logout-btn');
    
    // Check authentication status
    if (Auth.isAuthenticated()) {
        showApp();
    } else {
        showLogin();
    }
    
    // Login form handler
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            const result = await Auth.login(email, password);
            
            if (result.success) {
                showApp();
            } else {
                showMessage('login-message', result.message, 'error');
            }
        });
    }
    
    // Logout handler
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Auth.logout();
        });
    }
});

function showLogin() {
    document.getElementById('loading-screen').classList.add('hidden');
    document.getElementById('login-screen').classList.remove('hidden');
    document.getElementById('app').classList.add('hidden');
}

function showApp() {
    document.getElementById('loading-screen').classList.add('hidden');
    document.getElementById('login-screen').classList.add('hidden');
    document.getElementById('app').classList.remove('hidden');
    
    // Initialize app
    const user = Auth.getCurrentUser();
    if (user) {
        document.getElementById('user-name').textContent = user.first_name + ' ' + user.last_name;
        initializeNavigation(user.role_id);
        loadDashboard();
    }
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
