/**
 * Popup Forms Manager
 * Handles popup form triggers and display logic
 */
class PopupFormsManager {
    constructor() {
        this.popupForms = [];
        this.triggeredForms = new Set();
        this.scrollThreshold = 50; // 50% scroll
        this.exitIntentTriggered = false;
        
        this.init();
    }
    
    init() {
        // Load popup forms from server
        this.loadPopupForms();
        
        // Setup event listeners
        this.setupEventListeners();
    }
    
    async loadPopupForms() {
        try {
            const base = (typeof window.MYNAK_BASE === 'string' ? window.MYNAK_BASE : '');
            const response = await fetch(base + '/ajax/get_popup_forms.php');
            const data = await response.json();
            
            if (data.success && data.forms) {
                this.popupForms = data.forms;
                this.setupTriggers();
            }
        } catch (error) {
            console.error('Error loading popup forms:', error);
        }
    }
    
    setupTriggers() {
        this.popupForms.forEach(form => {
            const settings = form.settings;
            
            switch (settings.popup_trigger) {
                case 'timer':
                    this.setupTimerTrigger(form, settings.popup_delay || 5);
                    break;
                case 'scroll':
                    this.setupScrollTrigger(form);
                    break;
                case 'exit':
                    this.setupExitIntentTrigger(form);
                    break;
                case 'button':
                    this.setupButtonTrigger(form, settings.popup_button_text || 'Form Doldur');
                    break;
            }
        });
    }
    
    setupEventListeners() {
        // Scroll event for scroll triggers
        window.addEventListener('scroll', this.handleScroll.bind(this));
        
        // Mouse leave event for exit intent
        document.addEventListener('mouseleave', this.handleExitIntent.bind(this));
        
        // Button click events for popup forms
        document.addEventListener('click', (e) => {
            if (e.target.closest('.open-popup-form')) {
                e.preventDefault();
                const button = e.target.closest('.open-popup-form');
                const formId = button.getAttribute('data-form-id');
                if (formId) {
                    this.openFormById(formId);
                }
            }
        });
        
        // Storage event to prevent showing same popup multiple times
        this.loadTriggeredForms();
    }
    
    setupTimerTrigger(form, delay) {
        if (this.isFormTriggered(form.id)) return;
        
        setTimeout(() => {
            this.showPopupForm(form);
        }, delay * 1000);
    }
    
    setupScrollTrigger(form) {
        // Will be handled by scroll event listener
    }
    
    setupExitIntentTrigger(form) {
        // Will be handled by mouseleave event listener
    }
    
    setupButtonTrigger(form, buttonText) {
        // Create trigger button
        const button = document.createElement('button');
        button.className = 'popup-form-trigger-btn';
        button.textContent = buttonText;
        button.onclick = () => this.showPopupForm(form);
        
        // Add to page (you can customize where to place it)
        const targetContainer = document.querySelector('.popup-trigger-container') || document.body;
        targetContainer.appendChild(button);
    }
    
    handleScroll() {
        const scrollPercent = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
        
        if (scrollPercent >= this.scrollThreshold) {
            this.popupForms.forEach(form => {
                if (form.settings.popup_trigger === 'scroll' && !this.isFormTriggered(form.id)) {
                    this.showPopupForm(form);
                }
            });
        }
    }
    
    handleExitIntent(e) {
        if (this.exitIntentTriggered) return;
        if (e.clientY <= 0) {
            this.exitIntentTriggered = true;
            
            this.popupForms.forEach(form => {
                if (form.settings.popup_trigger === 'exit' && !this.isFormTriggered(form.id)) {
                    this.showPopupForm(form);
                }
            });
        }
    }
    
    showPopupForm(form) {
        if (this.isFormTriggered(form.id)) return;
        
        // Mark as triggered
        this.markFormAsTriggered(form.id);
        
        // Open popup form in new window/iframe or modal
        const popupUrl = `/mynakliyat/popup_form.php?id=${form.id}`;
        
        // Create iframe modal
        this.createIframeModal(popupUrl, form.settings);
    }
    
    openFormById(formId) {
        // Open popup form directly by ID (for button clicks)
        const popupUrl = `/mynakliyat/popup_form.php?id=${formId}`;
        
        // Use default settings for button-triggered forms
        const defaultSettings = {
            popup_width: 'medium'
        };
        
        // Create iframe modal
        this.createIframeModal(popupUrl, defaultSettings);
    }
    
    createIframeModal(url, settings) {
        // Remove existing modal if any
        const existingModal = document.querySelector('.popup-form-overlay');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Create modal overlay
        const overlay = document.createElement('div');
        overlay.className = 'popup-form-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        // Create iframe container
        const container = document.createElement('div');
        container.className = 'popup-form-container';
        
        let width = '600px';
        let height = '500px';
        
        switch (settings.popup_width) {
            case 'small':
                width = '400px';
                height = '400px';
                break;
            case 'medium':
                width = '600px';
                height = '500px';
                break;
            case 'large':
                width = '800px';
                height = '600px';
                break;
            case 'full':
                width = '90%';
                height = '90%';
                break;
        }
        
        container.style.cssText = `
            position: relative;
            width: ${width};
            height: ${height};
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        `;
        
        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = `
            position: absolute;
            top: 10px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            z-index: 10000;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        closeBtn.onclick = () => overlay.remove();
        
        // Create iframe
        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.style.cssText = `
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 15px;
        `;
        
        // Assemble modal
        container.appendChild(closeBtn);
        container.appendChild(iframe);
        overlay.appendChild(container);
        document.body.appendChild(overlay);
        
        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
            }
        });
        
        // Listen for messages from iframe
        window.addEventListener('message', (e) => {
            if (e.data === 'popup_form_success') {
                setTimeout(() => {
                    overlay.remove();
                }, 2000);
            }
        });
    }
    
    isFormTriggered(formId) {
        return this.triggeredForms.has(formId) || localStorage.getItem(`popup_form_${formId}`) === 'triggered';
    }
    
    markFormAsTriggered(formId) {
        this.triggeredForms.add(formId);
        localStorage.setItem(`popup_form_${formId}`, 'triggered');
        
        // Set expiry (24 hours)
        const expiry = Date.now() + (24 * 60 * 60 * 1000);
        localStorage.setItem(`popup_form_${formId}_expiry`, expiry.toString());
    }
    
    loadTriggeredForms() {
        // Load triggered forms from localStorage and check expiry
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.startsWith('popup_form_') && key.endsWith('_expiry')) {
                const expiry = parseInt(localStorage.getItem(key));
                const formKey = key.replace('_expiry', '');
                
                if (Date.now() > expiry) {
                    // Expired, remove from storage
                    localStorage.removeItem(key);
                    localStorage.removeItem(formKey);
                } else {
                    // Still valid, add to triggered set
                    const formId = formKey.replace('popup_form_', '');
                    this.triggeredForms.add(formId);
                }
            }
        }
    }
}

// Additional CSS for trigger buttons
const popupFormStyles = `
    .popup-form-trigger-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 500;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
        z-index: 1000;
    }
    
    .popup-form-trigger-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .popup-form-overlay {
        animation: fadeIn 0.3s ease;
    }
    
    .popup-form-container {
        animation: slideInUp 0.4s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @media (max-width: 768px) {
        .popup-form-container {
            width: 95% !important;
            height: 85% !important;
            margin: 10px;
        }
        
        .popup-form-trigger-btn {
            bottom: 10px;
            right: 10px;
            padding: 10px 16px;
            font-size: 14px;
        }
    }
`;

// Inject styles
const styleSheet = document.createElement('style');
styleSheet.textContent = popupFormStyles;
document.head.appendChild(styleSheet);

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new PopupFormsManager();
    });
} else {
    new PopupFormsManager();
}
