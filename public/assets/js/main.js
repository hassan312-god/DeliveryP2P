/**
 * LivraisonP2P - JavaScript Principal
 * Fonctions utilitaires et gestion des API
 */

// Configuration globale
const API_BASE_URL = '/api';
const TOAST_DURATION = 5000;

/**
 * Fonctions utilitaires pour les requêtes API
 */
class ApiService {
    /**
     * Effectue une requête fetch avec gestion d'erreurs
     */
    static async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };

        const finalOptions = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, finalOptions);
            
            // Vérifier si la réponse est ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Essayer de parser la réponse JSON
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }

    /**
     * Requête GET
     */
    static async get(endpoint, params = {}) {
        const url = new URL(API_BASE_URL + endpoint, window.location.origin);
        
        // Ajouter les paramètres à l'URL
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });

        return this.request(url.toString(), { method: 'GET' });
    }

    /**
     * Requête POST
     */
    static async post(endpoint, data = {}) {
        return this.request(API_BASE_URL + endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * Requête PUT
     */
    static async put(endpoint, data = {}) {
        return this.request(API_BASE_URL + endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * Requête DELETE
     */
    static async delete(endpoint) {
        return this.request(API_BASE_URL + endpoint, {
            method: 'DELETE'
        });
    }

    /**
     * Upload de fichier
     */
    static async upload(endpoint, formData) {
        return this.request(API_BASE_URL + endpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
    }
}

/**
 * Gestionnaire de notifications toast
 */
class ToastManager {
    static show(message, type = 'info', duration = TOAST_DURATION) {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            console.warn('Toast container not found');
            return;
        }

        const toastId = 'toast-' + Date.now();
        const iconClass = this.getIconClass(type);
        const bgClass = this.getBgClass(type);

        const toastHtml = `
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
                <div class="toast-header ${bgClass} text-white">
                    <i class="fas ${iconClass} me-2"></i>
                    <strong class="me-auto">Notification</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: duration });
        toast.show();

        // Supprimer le toast du DOM après qu'il soit caché
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    }

    static getIconClass(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    static getBgClass(type) {
        const bgClasses = {
            success: 'bg-success',
            error: 'bg-danger',
            warning: 'bg-warning',
            info: 'bg-info'
        };
        return bgClasses[type] || bgClasses.info;
    }
}

/**
 * Gestionnaire de modals de chargement
 */
class LoadingManager {
    static show(message = 'Chargement en cours...') {
        const loadingMessage = document.getElementById('loadingMessage');
        if (loadingMessage) {
            loadingMessage.textContent = message;
        }
        
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
    }

    static hide() {
        const loadingModal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
        if (loadingModal) {
            loadingModal.hide();
        }
    }
}

/**
 * Gestionnaire de formulaires
 */
class FormManager {
    /**
     * Valide un formulaire et retourne les données
     */
    static validateForm(formElement) {
        const formData = new FormData(formElement);
        const data = {};
        const errors = [];

        // Convertir FormData en objet
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        // Validation basique
        const requiredFields = formElement.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                errors.push(`Le champ "${field.name}" est requis`);
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });

        // Validation email
        const emailFields = formElement.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                errors.push(`L'email "${field.value}" n'est pas valide`);
                field.classList.add('is-invalid');
            }
        });

        return { data, errors, isValid: errors.length === 0 };
    }

    /**
     * Valide un email
     */
    static isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Réinitialise un formulaire
     */
    static resetForm(formElement) {
        formElement.reset();
        formElement.querySelectorAll('.is-valid, .is-invalid').forEach(field => {
            field.classList.remove('is-valid', 'is-invalid');
        });
    }

    /**
     * Affiche les erreurs de validation
     */
    static showValidationErrors(errors) {
        if (errors.length > 0) {
            ToastManager.show(errors.join('<br>'), 'error');
        }
    }
}

/**
 * Gestionnaire de géolocalisation
 */
class GeolocationManager {
    static async getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Géolocalisation non supportée'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                },
                (error) => {
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        });
    }

    /**
     * Calcule la distance entre deux points (formule de Haversine)
     */
    static calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Rayon de la Terre en km
        const dLat = this.deg2rad(lat2 - lat1);
        const dLon = this.deg2rad(lon2 - lon1);
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(this.deg2rad(lat1)) * Math.cos(this.deg2rad(lat2)) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const distance = R * c; // Distance en km
        return distance;
    }

    static deg2rad(deg) {
        return deg * (Math.PI/180);
    }
}

/**
 * Gestionnaire de dates et heures
 */
class DateTimeManager {
    /**
     * Formate une date en format français
     */
    static formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };

        return new Intl.DateTimeFormat('fr-FR', { ...defaultOptions, ...options }).format(new Date(date));
    }

    /**
     * Calcule le temps écoulé depuis une date
     */
    static timeAgo(date) {
        const now = new Date();
        const past = new Date(date);
        const diffInSeconds = Math.floor((now - past) / 1000);

        if (diffInSeconds < 60) {
            return 'À l\'instant';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `Il y a ${days} jour${days > 1 ? 's' : ''}`;
        }
    }
}

/**
 * Gestionnaire de stockage local
 */
class StorageManager {
    static set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error('Erreur lors du stockage:', error);
        }
    }

    static get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('Erreur lors de la récupération:', error);
            return defaultValue;
        }
    }

    static remove(key) {
        try {
            localStorage.removeItem(key);
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
        }
    }

    static clear() {
        try {
            localStorage.clear();
        } catch (error) {
            console.error('Erreur lors du nettoyage:', error);
        }
    }
}

/**
 * Gestionnaire de navigation
 */
class NavigationManager {
    /**
     * Navigue vers une URL avec gestion des paramètres
     */
    static navigate(url, params = {}) {
        const urlObj = new URL(url, window.location.origin);
        
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                urlObj.searchParams.set(key, params[key]);
            }
        });

        window.location.href = urlObj.toString();
    }

    /**
     * Redirige vers une URL
     */
    static redirect(url) {
        window.location.href = url;
    }

    /**
     * Recharge la page actuelle
     */
    static reload() {
        window.location.reload();
    }
}

/**
 * Gestionnaire de débogage
 */
class DebugManager {
    static log(message, data = null) {
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.log(`[LivraisonP2P] ${message}`, data);
        }
    }

    static error(message, error = null) {
        console.error(`[LivraisonP2P] ${message}`, error);
    }
}

/**
 * Fonctions globales pour compatibilité
 */
window.showToast = ToastManager.show;
window.showLoading = LoadingManager.show;
window.hideLoading = LoadingManager.hide;

/**
 * Initialisation globale
 */
document.addEventListener('DOMContentLoaded', function() {
    DebugManager.log('Application initialisée');
    
    // Initialiser les tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialiser les popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Initialiser les notifications si l'utilisateur est connecté
    if (typeof currentUser !== 'undefined' && currentUser.id) {
        initializeNotifications();
    }
});

/**
 * Gestion des erreurs globales
 */
window.addEventListener('error', function(event) {
    DebugManager.error('Erreur JavaScript:', event.error);
});

window.addEventListener('unhandledrejection', function(event) {
    DebugManager.error('Promesse rejetée:', event.reason);
});

// Export des classes pour utilisation dans d'autres modules
window.ApiService = ApiService;
window.ToastManager = ToastManager;
window.LoadingManager = LoadingManager;
window.FormManager = FormManager;
window.GeolocationManager = GeolocationManager;
window.DateTimeManager = DateTimeManager;
window.StorageManager = StorageManager;
window.NavigationManager = NavigationManager;
window.DebugManager = DebugManager; 