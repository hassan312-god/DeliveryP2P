/**
 * Configuration globale de l'application LivraisonP2P
 * Version 1.2.0 - Mise à jour avec intégration PHP
 */

const CONFIG = {
    // Configuration Supabase
    SUPABASE: {
        URL: 'https://syamapjohtlbjlyhlhsi.supabase.co',
        ANON_KEY: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5YW1hcGpvaHRsYmpseWhsaHNpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTAzNzAxNjksImV4cCI6MjA2NTk0NjE2OX0._Sl5uiiUKK58wPa-lJG-TPe7K9rdem6Uc2V4epyhx_M',
        SERVICE_KEY: '' // Clé service pour les opérations admin
    },

    // Configuration de l'application
    APP: {
        NAME: 'LivraisonP2P',
        VERSION: '1.2.0',
        ENV: 'development', // development, staging, production
        BASE_URL: window.location.origin,
        API_BASE_URL: window.location.origin + '/php',
        SUPPORT_EMAIL: 'support@livraisonp2p.com',
        SUPPORT_PHONE: '+221 77 123 45 67'
    },

    // Configuration des prix (XOF)
    PRICING: {
        BASE_PRICE_PER_KM: 100,
        MINIMUM_PRICE: 500,
        URGENT_MULTIPLIER: 1.5,
        NIGHT_MULTIPLIER: 1.2,
        WEEKEND_MULTIPLIER: 1.1,
        CURRENCY: 'XOF',
        CURRENCY_SYMBOL: 'CFA'
    },

    // Configuration de la géolocalisation
    geolocation: {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000,
        defaultLocation: {
            latitude: 14.7167, // Dakar
            longitude: -17.4677
        }
    },

    // Configuration des zones de couverture (Dakar)
    COVERAGE_ZONES: {
        'Dakar Centre': { lat: 14.7167, lng: -17.4677, radius: 5 },
        'Plateau': { lat: 14.7247, lng: -17.4441, radius: 3 },
        'Médina': { lat: 14.7167, lng: -17.4500, radius: 4 },
        'Fann': { lat: 14.7167, lng: -17.4500, radius: 3 },
        'Almadies': { lat: 14.7167, lng: -17.4500, radius: 6 },
        'Yoff': { lat: 14.7167, lng: -17.4500, radius: 4 },
        'Ouakam': { lat: 14.7167, lng: -17.4500, radius: 5 },
        'Mermoz': { lat: 14.7167, lng: -17.4500, radius: 3 },
        'Sacré-Cœur': { lat: 14.7167, lng: -17.4500, radius: 4 },
        'Point E': { lat: 14.7167, lng: -17.4500, radius: 3 }
    },

    // Configuration des statuts de livraison
    DELIVERY_STATUSES: {
        pending: { label: 'En attente', color: 'yellow', icon: 'fas fa-clock' },
        accepted: { label: 'Acceptée', color: 'blue', icon: 'fas fa-check' },
        picked_up: { label: 'Récupérée', color: 'purple', icon: 'fas fa-box' },
        in_transit: { label: 'En cours', color: 'orange', icon: 'fas fa-truck' },
        delivered: { label: 'Livrée', color: 'green', icon: 'fas fa-check-circle' },
        cancelled: { label: 'Annulée', color: 'red', icon: 'fas fa-times-circle' }
    },

    // Configuration des rôles utilisateur
    USER_ROLES: {
        client: { 
            label: 'Client', 
            icon: 'fas fa-user',
            permissions: ['create_delivery', 'view_own_deliveries', 'rate_courier', 'generate_qr_codes', 'view_qr_history']
        },
        livreur: { 
            label: 'Livreur', 
            icon: 'fas fa-truck',
            permissions: ['accept_delivery', 'view_available_deliveries', 'update_delivery_status', 'generate_qr_codes', 'view_earnings']
        },
        admin: { 
            label: 'Administrateur', 
            icon: 'fas fa-crown',
            permissions: ['view_all_deliveries', 'manage_users', 'view_analytics', 'manage_qr_codes', 'access_admin_panel']
        }
    },

    // Configuration des méthodes de paiement
    PAYMENT_METHODS: {
        mobile_money: { label: 'Mobile Money', icon: 'fas fa-mobile-alt', color: 'green' },
        card: { label: 'Carte bancaire', icon: 'fas fa-credit-card', color: 'blue' },
        cash: { label: 'Espèces', icon: 'fas fa-money-bill-wave', color: 'yellow' }
    },

    // Configuration des types de QR codes
    QR_CODE_TYPES: {
        delivery: { label: 'Livraison', icon: 'fas fa-truck', color: 'blue', description: 'QR code pour suivre une livraison' },
        user: { label: 'Utilisateur', icon: 'fas fa-user', color: 'green', description: 'QR code pour identifier un utilisateur' },
        payment: { label: 'Paiement', icon: 'fas fa-credit-card', color: 'purple', description: 'QR code pour effectuer un paiement' },
        location: { label: 'Localisation', icon: 'fas fa-map-marker-alt', color: 'orange', description: 'QR code pour partager une localisation' },
        custom: { label: 'Personnalisé', icon: 'fas fa-edit', color: 'gray', description: 'QR code avec contenu personnalisé' }
    },

    // Configuration des notifications
    NOTIFICATIONS: {
        TYPES: {
            delivery_update: { icon: 'fas fa-truck', color: 'blue' },
            payment_received: { icon: 'fas fa-credit-card', color: 'green' },
            qr_code_created: { icon: 'fas fa-qrcode', color: 'purple' },
            system_alert: { icon: 'fas fa-exclamation-triangle', color: 'red' },
            info: { icon: 'fas fa-info-circle', color: 'blue' }
        },
        AUTO_HIDE_DELAY: 5000,
        MAX_VISIBLE: 5
    },

    // Configuration des API endpoints
    API_ENDPOINTS: {
        // Supabase endpoints
        SUPABASE: {
            AUTH: '/auth/v1',
            REST: '/rest/v1',
            RPC: '/rest/v1/rpc'
        },
        // PHP endpoints
        PHP: {
            ADMIN_DASHBOARD: '/admin-dashboard.php',
            QR_CODE_GENERATOR: '/qr-code-generator.php',
            BACKUP_MANAGER: '/backup-manager.php',
            EMAIL_SERVICE: '/email-service.php',
            SUPABASE_API: '/supabase-api.php'
        },
        // External APIs
        EXTERNAL: {
            MAPS: 'https://maps.googleapis.com/maps/api',
            WEATHER: 'https://api.openweathermap.org/data/2.5',
            SMS: 'https://api.twilio.com/2010-04-01'
        }
    },

    // Configuration des limites
    LIMITS: {
        MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
        MAX_QR_CODES_PER_USER: 100,
        MAX_DELIVERIES_PER_DAY: 50,
        MAX_NOTIFICATIONS: 1000,
        SESSION_TIMEOUT: 24 * 60 * 60 * 1000, // 24 heures
        API_RATE_LIMIT: 100 // requêtes par minute
    },

    // Configuration des fonctionnalités
    FEATURES: {
        QR_CODE_GENERATION: true,
        QR_CODE_SCANNING: true,
        REAL_TIME_TRACKING: true,
        PUSH_NOTIFICATIONS: true,
        EMAIL_NOTIFICATIONS: true,
        ADMIN_PANEL: true,
        BACKUP_SYSTEM: true,
        ANALYTICS: true,
        MULTI_LANGUAGE: false,
        DARK_MODE: true
    },

    // Configuration des thèmes
    THEMES: {
        light: {
            primary: '#3B82F6',
            secondary: '#6B7280',
            success: '#10B981',
            warning: '#F59E0B',
            error: '#EF4444',
            background: '#F9FAFB',
            surface: '#FFFFFF',
            text: '#1F2937'
        },
        dark: {
            primary: '#60A5FA',
            secondary: '#9CA3AF',
            success: '#34D399',
            warning: '#FBBF24',
            error: '#F87171',
            background: '#111827',
            surface: '#1F2937',
            text: '#F9FAFB'
        }
    },

    // Configuration des langues
    LANGUAGES: {
        fr: { name: 'Français', flag: '🇫🇷', default: true },
        en: { name: 'English', flag: '🇺🇸', default: false },
        ar: { name: 'العربية', flag: '🇸🇦', default: false }
    },

    // Configuration des erreurs
    ERRORS: {
        NETWORK_ERROR: 'Erreur de connexion. Vérifiez votre connexion internet.',
        AUTH_ERROR: 'Erreur d\'authentification. Veuillez vous reconnecter.',
        VALIDATION_ERROR: 'Données invalides. Vérifiez vos informations.',
        SERVER_ERROR: 'Erreur serveur. Veuillez réessayer plus tard.',
        PERMISSION_ERROR: 'Vous n\'avez pas les permissions nécessaires.',
        NOT_FOUND_ERROR: 'Ressource introuvable.',
        RATE_LIMIT_ERROR: 'Trop de requêtes. Veuillez patienter.'
    },

    // Configuration des messages de succès
    SUCCESS_MESSAGES: {
        DELIVERY_CREATED: 'Livraison créée avec succès !',
        DELIVERY_UPDATED: 'Livraison mise à jour avec succès !',
        PAYMENT_SUCCESS: 'Paiement effectué avec succès !',
        QR_CODE_GENERATED: 'QR code généré avec succès !',
        PROFILE_UPDATED: 'Profil mis à jour avec succès !',
        EMAIL_SENT: 'Email envoyé avec succès !',
        BACKUP_CREATED: 'Sauvegarde créée avec succès !'
    },

    // Configuration des validations
    VALIDATIONS: {
        EMAIL: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        PHONE: /^(\+221|221)?[0-9]{9}$/,
        PASSWORD: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
        NAME: /^[a-zA-ZÀ-ÿ\s]{2,50}$/,
        ADDRESS: /^.{10,200}$/,
        DESCRIPTION: /^.{10,500}$/
    },

    // Configuration des timeouts
    TIMEOUTS: {
        API_REQUEST: 30000, // 30 secondes
        TOAST_DISPLAY: 5000, // 5 secondes
        SESSION_CHECK: 60000, // 1 minute
        LOCATION_UPDATE: 30000, // 30 secondes
        PUSH_CHECK: 60000 // 1 minute
    },

    // Configuration des caches
    CACHE: {
        USER_PROFILE: 300000, // 5 minutes
        DELIVERY_LIST: 60000, // 1 minute
        QR_CODES: 300000, // 5 minutes
        COVERAGE_ZONES: 3600000, // 1 heure
        APP_CONFIG: 86400000 // 24 heures
    }
};

// Fonctions utilitaires de configuration
const ConfigUtils = {
    /**
     * Obtenir une valeur de configuration
     */
    get(key, defaultValue = null) {
        const keys = key.split('.');
        let value = CONFIG;
        
        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return defaultValue;
            }
        }
        
        return value;
    },

    /**
     * Vérifier si une fonctionnalité est activée
     */
    isFeatureEnabled(feature) {
        return CONFIG.FEATURES[feature] === true;
    },

    /**
     * Obtenir l'URL d'un endpoint
     */
    getApiUrl(endpoint) {
        if (endpoint.startsWith('SUPABASE.')) {
            return CONFIG.SUPABASE.URL + CONFIG.API_ENDPOINTS.SUPABASE[endpoint.split('.')[1]];
        } else if (endpoint.startsWith('PHP.')) {
            return CONFIG.APP.API_BASE_URL + CONFIG.API_ENDPOINTS.PHP[endpoint.split('.')[1]];
        } else if (endpoint.startsWith('EXTERNAL.')) {
            return CONFIG.API_ENDPOINTS.EXTERNAL[endpoint.split('.')[1]];
        }
        return endpoint;
    },

    /**
     * Formater un montant en XOF
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'XOF',
            minimumFractionDigits: 0
        }).format(amount);
    },

    /**
     * Formater une date
     */
    formatDate(date, format = 'short') {
        const d = new Date(date);
        const now = new Date();
        const diff = now - d;
        const oneDay = 24 * 60 * 60 * 1000;

        if (format === 'relative') {
            if (diff < oneDay) {
                return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            } else if (diff < 7 * oneDay) {
                return d.toLocaleDateString('fr-FR', { weekday: 'long' });
            } else {
                return d.toLocaleDateString('fr-FR');
            }
        } else if (format === 'short') {
            return d.toLocaleDateString('fr-FR');
        } else {
            return d.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    },

    /**
     * Calculer la distance entre deux points
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Rayon de la Terre en km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    },

    /**
     * Calculer le prix d'une livraison
     */
    calculateDeliveryPrice(distance, options = {}) {
        const { isUrgent = false, isNight = false, isWeekend = false } = options;
        
        let basePrice = Math.max(CONFIG.PRICING.BASE_PRICE_PER_KM * distance, CONFIG.PRICING.MINIMUM_PRICE);
        let multiplier = 1.0;

        if (isUrgent) multiplier *= CONFIG.PRICING.URGENT_MULTIPLIER;
        if (isNight) multiplier *= CONFIG.PRICING.NIGHT_MULTIPLIER;
        if (isWeekend) multiplier *= CONFIG.PRICING.WEEKEND_MULTIPLIER;

        return Math.round(basePrice * multiplier);
    },

    /**
     * Vérifier les permissions d'un utilisateur
     */
    hasPermission(userRole, permission) {
        const role = CONFIG.USER_ROLES[userRole];
        return role && role.permissions && role.permissions.includes(permission);
    },

    /**
     * Obtenir le statut de livraison
     */
    getDeliveryStatus(status) {
        return CONFIG.DELIVERY_STATUSES[status] || CONFIG.DELIVERY_STATUSES.pending;
    },

    /**
     * Obtenir le type de QR code
     */
    getQRCodeType(type) {
        return CONFIG.QR_CODE_TYPES[type] || CONFIG.QR_CODE_TYPES.custom;
    },

    /**
     * Obtenir la méthode de paiement
     */
    getPaymentMethod(method) {
        return CONFIG.PAYMENT_METHODS[method] || CONFIG.PAYMENT_METHODS.cash;
    },

    /**
     * Vérifier si on est en mode développement
     */
    isDevelopment() {
        return CONFIG.APP.ENV === 'development';
    },

    /**
     * Vérifier si on est en mode production
     */
    isProduction() {
        return CONFIG.APP.ENV === 'production';
    },

    /**
     * Logger un message
     */
    log(level, message, data = {}) {
        if (this.isDevelopment()) {
            console.log(`[${level.toUpperCase()}] ${message}`, data);
        }
        
        // En production, envoyer au serveur de logs
        if (this.isProduction()) {
            // Implémenter l'envoi au serveur de logs
        }
    },

    /**
     * Générer un ID unique
     */
    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    },

    /**
     * Valider un email
     */
    validateEmail(email) {
        return CONFIG.VALIDATIONS.EMAIL.test(email);
    },

    /**
     * Valider un numéro de téléphone sénégalais (optionnel)
     */
    validatePhone(phone) {
        // Si le téléphone est vide ou null, c'est valide (optionnel)
        if (!phone || phone.trim() === '') {
            return true;
        }
        
        return CONFIG.VALIDATIONS.PHONE.test(phone);
    },

    /**
     * Valider un mot de passe
     */
    validatePassword(password) {
        return CONFIG.VALIDATIONS.PASSWORD.test(password);
    },

    /**
     * Obtenir le thème actuel
     */
    getCurrentTheme() {
        return localStorage.getItem('theme') || 'light';
    },

    /**
     * Définir le thème
     */
    setTheme(theme) {
        localStorage.setItem('theme', theme);
        document.documentElement.setAttribute('data-theme', theme);
    },

    /**
     * Basculer le thème
     */
    toggleTheme() {
        const currentTheme = this.getCurrentTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
        return newTheme;
    }
};

// Initialisation de la configuration
document.addEventListener('DOMContentLoaded', function() {
    // Appliquer le thème
    const theme = ConfigUtils.getCurrentTheme();
    ConfigUtils.setTheme(theme);
    
    // Ajouter les classes CSS pour le thème
    document.body.classList.add(`theme-${theme}`);
    
    // Logger le démarrage
    ConfigUtils.log('info', 'Configuration initialisée', {
        version: CONFIG.APP.VERSION,
        environment: CONFIG.APP.ENV,
        theme: theme
    });
});

// Exporter la configuration
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CONFIG, ConfigUtils };
} else {
    window.CONFIG = CONFIG;
    window.ConfigUtils = ConfigUtils;
} 