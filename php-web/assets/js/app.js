// Main Application JavaScript

// Global app state
const App = {
    user: null,
    branding: null,
    
    init() {
        this.loadBranding();
        this.startNotificationPolling();
    },
    
    async loadBranding() {
        try {
            const response = await fetch('/php-web/config/branding.json');
            if (response.ok) {
                this.branding = await response.json();
                this.applyBranding();
            }
        } catch (error) {
            console.log('Using default branding');
        }
    },
    
    applyBranding() {
        if (!this.branding) return;
        
        // Apply site name
        document.querySelectorAll('#app-title').forEach(el => {
            el.textContent = this.branding.site_name;
        });
        
        // Apply colors
        document.documentElement.style.setProperty('--primary-color', this.branding.primary_color);
        document.documentElement.style.setProperty('--secondary-color', this.branding.secondary_color);
        
        // Apply logo
        if (this.branding.site_logo) {
            // Add logo to header
        }
    },
    
    startNotificationPolling() {
        // Poll for notifications every 30 seconds
        setInterval(async () => {
            if (Auth.isAuthenticated()) {
                await this.loadNotifications();
            }
        }, 30000);
        
        // Load immediately
        if (Auth.isAuthenticated()) {
            this.loadNotifications();
        }
    },
    
    async loadNotifications() {
        try {
            const result = await Auth.apiCall('notifications/list');
            
            if (result.success && result.notifications) {
                this.displayNotifications(result.notifications);
                this.updateNotificationBadge(result.unread_count || 0);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    },
    
    displayNotifications(notifications) {
        const container = document.getElementById('notifications-list');
        if (!container) return;
        
        if (notifications.length === 0) {
            container.innerHTML = '<p class="p-2">No notifications</p>';
            return;
        }
        
        let html = '';
        notifications.forEach(notif => {
            html += `
                <div class="notification-item ${notif.is_read ? '' : 'unread'}">
                    <strong>${notif.title}</strong>
                    <p>${notif.message}</p>
                    <small>${this.formatDate(notif.created_at)}</small>
                </div>
            `;
        });
        
        container.innerHTML = html;
    },
    
    updateNotificationBadge(count) {
        const badge = document.getElementById('notification-badge');
        if (!badge) return;
        
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    },
    
    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        
        return date.toLocaleDateString();
    }
};

// Initialize app when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => App.init());
} else {
    App.init();
}

// Helper functions
function showModal(title, content) {
    // Modal implementation
    alert(title + '\n\n' + content);
}

function confirmAction(message) {
    return confirm(message);
}

// Export for global use
window.App = App;
