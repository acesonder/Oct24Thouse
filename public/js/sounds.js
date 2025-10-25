/**
 * Sound Notifications System
 * Generates and plays notification sounds
 */

class NotificationSounds {
    constructor() {
        this.audioContext = null;
        this.enabled = localStorage.getItem('soundsEnabled') !== 'false';
        this.volume = parseFloat(localStorage.getItem('soundVolume') || '0.5');
    }

    init() {
        // Create AudioContext on first user interaction (required by browsers)
        if (!this.audioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
    }

    setEnabled(enabled) {
        this.enabled = enabled;
        localStorage.setItem('soundsEnabled', enabled);
    }

    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
        localStorage.setItem('soundVolume', this.volume);
    }

    // Play message received sound
    playMessageSound() {
        if (!this.enabled) return;
        this.init();
        
        const now = this.audioContext.currentTime;
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        // Create a pleasant "pop" sound
        oscillator.frequency.setValueAtTime(800, now);
        oscillator.frequency.exponentialRampToValueAtTime(600, now + 0.1);
        
        gainNode.gain.setValueAtTime(this.volume * 0.3, now);
        gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
        
        oscillator.start(now);
        oscillator.stop(now + 0.1);
    }

    // Play notification sound
    playNotificationSound() {
        if (!this.enabled) return;
        this.init();
        
        const now = this.audioContext.currentTime;
        const oscillator1 = this.audioContext.createOscillator();
        const oscillator2 = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator1.connect(gainNode);
        oscillator2.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        // Create a two-tone notification sound
        oscillator1.frequency.setValueAtTime(523.25, now); // C5
        oscillator2.frequency.setValueAtTime(659.25, now); // E5
        
        gainNode.gain.setValueAtTime(this.volume * 0.3, now);
        gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.3);
        
        oscillator1.start(now);
        oscillator2.start(now);
        oscillator1.stop(now + 0.3);
        oscillator2.stop(now + 0.3);
    }

    // Play success sound
    playSuccessSound() {
        if (!this.enabled) return;
        this.init();
        
        const now = this.audioContext.currentTime;
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        // Create an ascending melody
        oscillator.frequency.setValueAtTime(523.25, now); // C5
        oscillator.frequency.setValueAtTime(659.25, now + 0.1); // E5
        oscillator.frequency.setValueAtTime(783.99, now + 0.2); // G5
        
        gainNode.gain.setValueAtTime(this.volume * 0.3, now);
        gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.4);
        
        oscillator.start(now);
        oscillator.stop(now + 0.4);
    }

    // Play error sound
    playErrorSound() {
        if (!this.enabled) return;
        this.init();
        
        const now = this.audioContext.currentTime;
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        // Create a descending error sound
        oscillator.frequency.setValueAtTime(400, now);
        oscillator.frequency.exponentialRampToValueAtTime(200, now + 0.2);
        
        gainNode.gain.setValueAtTime(this.volume * 0.3, now);
        gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.2);
        
        oscillator.start(now);
        oscillator.stop(now + 0.2);
    }

    // Play alert sound (for urgent notifications)
    playAlertSound() {
        if (!this.enabled) return;
        this.init();
        
        const now = this.audioContext.currentTime;
        
        // Play three quick beeps
        for (let i = 0; i < 3; i++) {
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            const startTime = now + (i * 0.2);
            oscillator.frequency.setValueAtTime(800, startTime);
            
            gainNode.gain.setValueAtTime(this.volume * 0.4, startTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, startTime + 0.1);
            
            oscillator.start(startTime);
            oscillator.stop(startTime + 0.1);
        }
    }

    // Play typing indicator sound (subtle)
    playTypingSound() {
        if (!this.enabled) return;
        this.init();
        
        const now = this.audioContext.currentTime;
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        oscillator.frequency.setValueAtTime(1000, now);
        
        gainNode.gain.setValueAtTime(this.volume * 0.1, now);
        gainNode.gain.exponentialRampToValueAtTime(0.01, now + 0.05);
        
        oscillator.start(now);
        oscillator.stop(now + 0.05);
    }
}

// Create global instance
window.notificationSounds = new NotificationSounds();

// Initialize on first user interaction
document.addEventListener('click', function initSounds() {
    window.notificationSounds.init();
    document.removeEventListener('click', initSounds);
}, { once: true });
