/**
 * Theme System for Transition House
 * Supports 5 themes, each with light and dark mode
 */

const themes = {
    default: {
        light: {
            name: 'Default Light',
            primary: '#2c3e50',
            secondary: '#3498db',
            success: '#27ae60',
            warning: '#f39c12',
            danger: '#e74c3c',
            lightBg: '#ecf0f1',
            textColor: '#2c3e50',
            borderColor: '#bdc3c7',
            background: '#ffffff'
        },
        dark: {
            name: 'Default Dark',
            primary: '#ecf0f1',
            secondary: '#3498db',
            success: '#27ae60',
            warning: '#f39c12',
            danger: '#e74c3c',
            lightBg: '#34495e',
            textColor: '#ecf0f1',
            borderColor: '#7f8c8d',
            background: '#2c3e50'
        }
    },
    ocean: {
        light: {
            name: 'Ocean Light',
            primary: '#006994',
            secondary: '#00a8cc',
            success: '#05c46b',
            warning: '#ffa801',
            danger: '#ee5a6f',
            lightBg: '#e3f4f4',
            textColor: '#004d61',
            borderColor: '#7cc6d0',
            background: '#ffffff'
        },
        dark: {
            name: 'Ocean Dark',
            primary: '#00a8cc',
            secondary: '#0abde3',
            success: '#05c46b',
            warning: '#ffa801',
            danger: '#ee5a6f',
            lightBg: '#1e3a5f',
            textColor: '#e3f4f4',
            borderColor: '#2e5266',
            background: '#0c2340'
        }
    },
    forest: {
        light: {
            name: 'Forest Light',
            primary: '#2d6a4f',
            secondary: '#52b788',
            success: '#74c69d',
            warning: '#e9c46a',
            danger: '#e76f51',
            lightBg: '#d8f3dc',
            textColor: '#1b4332',
            borderColor: '#95d5b2',
            background: '#ffffff'
        },
        dark: {
            name: 'Forest Dark',
            primary: '#95d5b2',
            secondary: '#74c69d',
            success: '#52b788',
            warning: '#e9c46a',
            danger: '#e76f51',
            lightBg: '#2d6a4f',
            textColor: '#d8f3dc',
            borderColor: '#40916c',
            background: '#1b4332'
        }
    },
    sunset: {
        light: {
            name: 'Sunset Light',
            primary: '#d62828',
            secondary: '#f77f00',
            success: '#06d6a0',
            warning: '#fcbf49',
            danger: '#e63946',
            lightBg: '#fcf6f5',
            textColor: '#370617',
            borderColor: '#ffccd5',
            background: '#ffffff'
        },
        dark: {
            name: 'Sunset Dark',
            primary: '#f77f00',
            secondary: '#fcbf49',
            success: '#06d6a0',
            warning: '#f3722c',
            danger: '#e63946',
            lightBg: '#6a040f',
            textColor: '#fcf6f5',
            borderColor: '#9d0208',
            background: '#370617'
        }
    },
    lavender: {
        light: {
            name: 'Lavender Light',
            primary: '#7209b7',
            secondary: '#b5179e',
            success: '#06d6a0',
            warning: '#ffd60a',
            danger: '#d00000',
            lightBg: '#f8f3ff',
            textColor: '#3c096c',
            borderColor: '#d0a5e8',
            background: '#ffffff'
        },
        dark: {
            name: 'Lavender Dark',
            primary: '#c77dff',
            secondary: '#e0aaff',
            success: '#06d6a0',
            warning: '#ffd60a',
            danger: '#ff006e',
            lightBg: '#5a189a',
            textColor: '#f8f3ff',
            borderColor: '#7209b7',
            background: '#3c096c'
        }
    }
};

class ThemeManager {
    constructor() {
        this.currentTheme = localStorage.getItem('theme') || 'default';
        this.currentMode = localStorage.getItem('themeMode') || 'light';
        this.customColors = this.loadCustomColors();
    }

    loadCustomColors() {
        const saved = localStorage.getItem('customColors');
        return saved ? JSON.parse(saved) : {};
    }

    saveCustomColors() {
        localStorage.setItem('customColors', JSON.stringify(this.customColors));
    }

    applyTheme(themeName = this.currentTheme, mode = this.currentMode) {
        const theme = themes[themeName]?.[mode];
        
        if (!theme) {
            console.error('Theme not found:', themeName, mode);
            return;
        }

        // Apply theme colors
        const root = document.documentElement;
        root.style.setProperty('--primary-color', theme.primary);
        root.style.setProperty('--secondary-color', theme.secondary);
        root.style.setProperty('--success-color', theme.success);
        root.style.setProperty('--warning-color', theme.warning);
        root.style.setProperty('--danger-color', theme.danger);
        root.style.setProperty('--light-bg', theme.lightBg);
        root.style.setProperty('--text-color', theme.textColor);
        root.style.setProperty('--border-color', theme.borderColor);
        root.style.setProperty('--background-color', theme.background);

        // Apply custom colors if any
        if (this.customColors[themeName]) {
            Object.entries(this.customColors[themeName]).forEach(([key, value]) => {
                root.style.setProperty(`--${key}`, value);
            });
        }

        // Update body background
        document.body.style.backgroundColor = theme.background;
        document.body.style.color = theme.textColor;

        // Save preferences
        this.currentTheme = themeName;
        this.currentMode = mode;
        localStorage.setItem('theme', themeName);
        localStorage.setItem('themeMode', mode);

        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme: themeName, mode: mode }
        }));
    }

    setCustomColor(colorKey, colorValue) {
        if (!this.customColors[this.currentTheme]) {
            this.customColors[this.currentTheme] = {};
        }
        
        this.customColors[this.currentTheme][colorKey] = colorValue;
        this.saveCustomColors();
        this.applyTheme();
    }

    toggleMode() {
        const newMode = this.currentMode === 'light' ? 'dark' : 'light';
        this.applyTheme(this.currentTheme, newMode);
    }

    setTheme(themeName) {
        if (themes[themeName]) {
            this.applyTheme(themeName, this.currentMode);
        }
    }

    getAllThemes() {
        return Object.keys(themes).map(key => ({
            id: key,
            name: themes[key].light.name.replace(' Light', ''),
            light: themes[key].light,
            dark: themes[key].dark
        }));
    }

    getCurrentTheme() {
        return {
            theme: this.currentTheme,
            mode: this.currentMode,
            colors: themes[this.currentTheme][this.currentMode]
        };
    }
}

// Create global theme manager
window.themeManager = new ThemeManager();

// Apply saved theme on load
document.addEventListener('DOMContentLoaded', function() {
    window.themeManager.applyTheme();
});
