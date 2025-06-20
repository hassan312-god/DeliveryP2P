/**
 * Composant Toast pour LivraisonP2P
 * Version 1.2.0 - Interface améliorée et nouvelles fonctionnalités
 */

class Toast {
    constructor(options = {}) {
        this.container = null;
        this.toasts = new Map();
        this.counter = 0;
        this.defaultOptions = {
            duration: 5000,
            position: 'top-right',
            theme: 'light',
            maxVisible: 5,
            animation: true,
            sound: false,
            ...options
        };
        
        this.init();
    }

    /**
     * Initialiser le composant
     */
    init() {
        this.createContainer();
        this.setupEventListeners();
        this.loadTheme();
    }

    /**
     * Créer le conteneur de toasts
     */
    createContainer() {
        this.container = document.getElementById('toastContainer');
        
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toastContainer';
            this.container.className = `fixed z-50 ${this.getPositionClasses()}`;
            document.body.appendChild(this.container);
        }

        // Appliquer le thème
        this.container.setAttribute('data-theme', this.defaultOptions.theme);
    }

    /**
     * Configurer les écouteurs d'événements
     */
    setupEventListeners() {
        // Écouter les changements de thème
        document.addEventListener('theme:change', (event) => {
            this.updateTheme(event.detail.theme);
        });

        // Écouter les changements de langue
        document.addEventListener('language:change', (event) => {
            this.updateLanguage(event.detail.language);
        });
    }

    /**
     * Charger le thème actuel
     */
    loadTheme() {
        const currentTheme = ConfigUtils.getCurrentTheme();
        this.updateTheme(currentTheme);
    }

    /**
     * Obtenir les classes de position
     */
    getPositionClasses() {
        const positions = {
            'top-left': 'top-4 left-4',
            'top-right': 'top-4 right-4',
            'top-center': 'top-4 left-1/2 transform -translate-x-1/2',
            'bottom-left': 'bottom-4 left-4',
            'bottom-right': 'bottom-4 right-4',
            'bottom-center': 'bottom-4 left-1/2 transform -translate-x-1/2'
        };
        
        return positions[this.defaultOptions.position] || positions['top-right'];
    }

    /**
     * Afficher un toast de succès
     */
    success(message, options = {}) {
        return this.show(message, {
            type: 'success',
            icon: 'fas fa-check-circle',
            color: 'green',
            ...options
        });
    }

    /**
     * Afficher un toast d'erreur
     */
    error(message, options = {}) {
        return this.show(message, {
            type: 'error',
            icon: 'fas fa-exclamation-circle',
            color: 'red',
            ...options
        });
    }

    /**
     * Afficher un toast d'avertissement
     */
    warning(message, options = {}) {
        return this.show(message, {
            type: 'warning',
            icon: 'fas fa-exclamation-triangle',
            color: 'yellow',
            ...options
        });
    }

    /**
     * Afficher un toast d'information
     */
    info(message, options = {}) {
        return this.show(message, {
            type: 'info',
            icon: 'fas fa-info-circle',
            color: 'blue',
            ...options
        });
    }

    /**
     * Afficher un toast de chargement
     */
    loading(message, options = {}) {
        return this.show(message, {
            type: 'loading',
            icon: 'fas fa-spinner fa-spin',
            color: 'blue',
            duration: 0, // Pas de fermeture automatique
            ...options
        });
    }

    /**
     * Afficher un toast personnalisé
     */
    show(message, options = {}) {
        const id = `toast-${++this.counter}`;
        const finalOptions = { ...this.defaultOptions, ...options };

        // Créer l'élément toast
        const toastElement = this.createToastElement(id, message, finalOptions);
        
        // Ajouter au conteneur
        this.container.appendChild(toastElement);
        this.toasts.set(id, { element: toastElement, options: finalOptions });

        // Limiter le nombre de toasts visibles
        this.limitVisibleToasts();

        // Animation d'entrée
        if (finalOptions.animation) {
            this.animateIn(toastElement);
        }

        // Son de notification
        if (finalOptions.sound) {
            this.playSound(finalOptions.type);
        }

        // Fermeture automatique
        if (finalOptions.duration > 0) {
            setTimeout(() => {
                this.hide(id);
            }, finalOptions.duration);
        }

        // Retourner l'ID pour manipulation ultérieure
        return id;
    }

    /**
     * Créer un élément toast
     */
    createToastElement(id, message, options) {
        const toast = document.createElement('div');
        toast.id = id;
        toast.className = `toast-item mb-4 max-w-sm w-full bg-white rounded-lg shadow-lg border-l-4 border-${options.color}-500 transform transition-all duration-300 ease-in-out`;
        toast.setAttribute('data-type', options.type);
        toast.setAttribute('data-theme', this.defaultOptions.theme);

        const iconClass = options.icon || 'fas fa-info-circle';
        const iconColor = `text-${options.color}-500`;

        toast.innerHTML = `
            <div class="flex items-start p-4">
                <div class="flex-shrink-0">
                    <i class="${iconClass} ${iconColor} text-lg"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900 toast-message">${this.escapeHtml(message)}</p>
                    ${options.description ? `<p class="mt-1 text-sm text-gray-500">${this.escapeHtml(options.description)}</p>` : ''}
                    ${options.actions ? this.createActions(options.actions, id) : ''}
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button class="toast-close bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="sr-only">Fermer</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            ${options.progress ? '<div class="toast-progress h-1 bg-gray-200 rounded-b-lg"><div class="toast-progress-bar h-full bg-blue-500 rounded-b-lg transition-all duration-300"></div></div>' : ''}
        `;

        // Ajouter les écouteurs d'événements
        this.addToastEventListeners(toast, id, options);

        return toast;
    }

    /**
     * Créer les actions du toast
     */
    createActions(actions, toastId) {
        const actionsHtml = actions.map(action => {
            const buttonClass = action.primary 
                ? 'bg-blue-600 text-white hover:bg-blue-700' 
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200';
            
            return `
                <button class="toast-action ml-2 px-3 py-1 text-xs font-medium rounded-md transition-colors ${buttonClass}" 
                        data-action="${action.action}" 
                        data-toast-id="${toastId}">
                    ${action.label}
                </button>
            `;
        }).join('');

        return `<div class="mt-2 flex flex-wrap gap-2">${actionsHtml}</div>`;
    }

    /**
     * Ajouter les écouteurs d'événements au toast
     */
    addToastEventListeners(toast, id, options) {
        // Bouton de fermeture
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => {
            this.hide(id);
        });

        // Actions personnalisées
        const actionBtns = toast.querySelectorAll('.toast-action');
        actionBtns.forEach(btn => {
            btn.addEventListener('click', (event) => {
                const action = event.target.dataset.action;
                const actionConfig = options.actions.find(a => a.action === action);
                
                if (actionConfig && actionConfig.callback) {
                    actionConfig.callback(id);
                }
                
                // Fermer le toast si l'action le demande
                if (actionConfig && actionConfig.closeOnClick !== false) {
                    this.hide(id);
                }
            });
        });

        // Pause sur hover pour les toasts avec durée
        if (options.duration > 0) {
            toast.addEventListener('mouseenter', () => {
                this.pauseToast(id);
            });

            toast.addEventListener('mouseleave', () => {
                this.resumeToast(id);
            });
        }
    }

    /**
     * Masquer un toast
     */
    hide(id) {
        const toastData = this.toasts.get(id);
        if (!toastData) return;

        const { element, options } = toastData;

        // Animation de sortie
        if (options.animation) {
            this.animateOut(element, () => {
                this.removeToast(id);
            });
        } else {
            this.removeToast(id);
        }
    }

    /**
     * Supprimer un toast
     */
    removeToast(id) {
        const toastData = this.toasts.get(id);
        if (!toastData) return;

        const { element } = toastData;
        
        if (element.parentNode) {
            element.parentNode.removeChild(element);
        }
        
        this.toasts.delete(id);
    }

    /**
     * Mettre à jour un toast de chargement
     */
    updateLoading(id, message, type = 'success') {
        const toastData = this.toasts.get(id);
        if (!toastData) return;

        const { element, options } = toastData;
        const messageElement = element.querySelector('.toast-message');
        const iconElement = element.querySelector('i');

        if (messageElement) {
            messageElement.textContent = message;
        }

        if (iconElement) {
            const newIcon = type === 'success' ? 'fas fa-check-circle' : 
                           type === 'error' ? 'fas fa-exclamation-circle' : 
                           type === 'warning' ? 'fas fa-exclamation-triangle' : 
                           'fas fa-info-circle';
            
            iconElement.className = `${newIcon} text-${options.color}-500 text-lg`;
        }

        // Fermer automatiquement après mise à jour
        setTimeout(() => {
            this.hide(id);
        }, 2000);
    }

    /**
     * Masquer tous les toasts
     */
    hideAll() {
        this.toasts.forEach((toastData, id) => {
            this.hide(id);
        });
    }

    /**
     * Limiter le nombre de toasts visibles
     */
    limitVisibleToasts() {
        const visibleToasts = Array.from(this.toasts.keys());
        
        if (visibleToasts.length > this.defaultOptions.maxVisible) {
            const toastsToRemove = visibleToasts.slice(0, visibleToasts.length - this.defaultOptions.maxVisible);
            toastsToRemove.forEach(id => {
                this.hide(id);
            });
        }
    }

    /**
     * Animer l'entrée d'un toast
     */
    animateIn(element) {
        element.style.transform = 'translateX(100%)';
        element.style.opacity = '0';
        
        requestAnimationFrame(() => {
            element.style.transform = 'translateX(0)';
            element.style.opacity = '1';
        });
    }

    /**
     * Animer la sortie d'un toast
     */
    animateOut(element, callback) {
        element.style.transform = 'translateX(100%)';
        element.style.opacity = '0';
        
        setTimeout(callback, 300);
    }

    /**
     * Mettre en pause un toast
     */
    pauseToast(id) {
        const toastData = this.toasts.get(id);
        if (toastData && toastData.timeout) {
            clearTimeout(toastData.timeout);
        }
    }

    /**
     * Reprendre un toast
     */
    resumeToast(id) {
        const toastData = this.toasts.get(id);
        if (toastData && toastData.options.duration > 0) {
            toastData.timeout = setTimeout(() => {
                this.hide(id);
            }, toastData.options.duration);
        }
    }

    /**
     * Jouer un son
     */
    playSound(type) {
        const sounds = {
            success: '/assets/sounds/success.mp3',
            error: '/assets/sounds/error.mp3',
            warning: '/assets/sounds/warning.mp3',
            info: '/assets/sounds/info.mp3'
        };

        const soundUrl = sounds[type];
        if (soundUrl) {
            try {
                const audio = new Audio(soundUrl);
                audio.volume = 0.3;
                audio.play().catch(() => {
                    // Ignorer les erreurs de lecture audio
                });
            } catch (error) {
                // Ignorer les erreurs audio
            }
        }
    }

    /**
     * Mettre à jour le thème
     */
    updateTheme(theme) {
        this.defaultOptions.theme = theme;
        
        if (this.container) {
            this.container.setAttribute('data-theme', theme);
        }

        // Mettre à jour tous les toasts existants
        this.toasts.forEach((toastData, id) => {
            const { element } = toastData;
            element.setAttribute('data-theme', theme);
            
            // Appliquer les styles du thème
            if (theme === 'dark') {
                element.classList.add('dark-theme');
            } else {
                element.classList.remove('dark-theme');
            }
        });
    }

    /**
     * Mettre à jour la langue
     */
    updateLanguage(language) {
        // Traduire les messages existants si nécessaire
        this.toasts.forEach((toastData, id) => {
            const { element } = toastData;
            const messageElement = element.querySelector('.toast-message');
            
            if (messageElement && messageElement.dataset.translateKey) {
                const translatedMessage = this.translate(messageElement.dataset.translateKey, language);
                messageElement.textContent = translatedMessage;
            }
        });
    }

    /**
     * Traduire un message
     */
    translate(key, language) {
        const translations = {
            fr: {
                'success': 'Succès',
                'error': 'Erreur',
                'warning': 'Avertissement',
                'info': 'Information',
                'loading': 'Chargement...'
            },
            en: {
                'success': 'Success',
                'error': 'Error',
                'warning': 'Warning',
                'info': 'Information',
                'loading': 'Loading...'
            }
        };

        return translations[language]?.[key] || key;
    }

    /**
     * Échapper le HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Obtenir les statistiques
     */
    getStats() {
        return {
            total: this.toasts.size,
            visible: this.container.children.length,
            types: this.getTypeStats()
        };
    }

    /**
     * Obtenir les statistiques par type
     */
    getTypeStats() {
        const stats = {};
        this.toasts.forEach((toastData, id) => {
            const type = toastData.options.type;
            stats[type] = (stats[type] || 0) + 1;
        });
        return stats;
    }

    /**
     * Configurer les options par défaut
     */
    configure(options) {
        this.defaultOptions = { ...this.defaultOptions, ...options };
        
        // Mettre à jour la position du conteneur
        if (options.position) {
            this.container.className = `fixed z-50 ${this.getPositionClasses()}`;
        }
    }
}

// Styles CSS pour les thèmes
const toastStyles = `
<style>
    .toast-item {
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .toast-item[data-theme="dark"] {
        background-color: rgba(31, 41, 55, 0.95);
        color: #F9FAFB;
    }

    .toast-item[data-theme="dark"] .toast-message {
        color: #F9FAFB;
    }

    .toast-item[data-theme="dark"] .toast-close {
        background-color: rgba(31, 41, 55, 0.95);
        color: #9CA3AF;
    }

    .toast-item[data-theme="dark"] .toast-close:hover {
        color: #F9FAFB;
    }

    .toast-progress-bar {
        animation: progress-shrink linear forwards;
    }

    @keyframes progress-shrink {
        from { width: 100%; }
        to { width: 0%; }
    }

    .toast-item[data-type="loading"] .toast-progress-bar {
        animation: progress-indeterminate 2s infinite;
    }

    @keyframes progress-indeterminate {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .toast-item[data-type="success"] {
        border-left-color: #10B981;
    }

    .toast-item[data-type="error"] {
        border-left-color: #EF4444;
    }

    .toast-item[data-type="warning"] {
        border-left-color: #F59E0B;
    }

    .toast-item[data-type="info"] {
        border-left-color: #3B82F6;
    }

    .toast-item[data-type="loading"] {
        border-left-color: #3B82F6;
    }
</style>
`;

// Ajouter les styles au document
if (!document.getElementById('toast-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'toast-styles';
    styleElement.innerHTML = toastStyles;
    document.head.appendChild(styleElement);
}

// Instance globale
window.Toast = Toast;

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Toast;
} 