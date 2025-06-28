/**
 * Service API pour LivraisonP2P
 * Gestion des requêtes vers Supabase et les services PHP
 * Version 1.2.0 - Intégration PHP
 */

class ApiService {
    constructor() {
        this.baseUrl = CONFIG.APP.API_BASE_URL;
        this.cache = new Map();
        this.requestQueue = [];
        this.isProcessingQueue = false;
        this.rateLimitCount = 0;
        this.rateLimitReset = Date.now();
        
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
    }

    /**
     * Configurer les intercepteurs
     */
    setupInterceptors() {
        // Intercepteur pour les requêtes
        this.interceptors = {
            request: [],
            response: [],
            error: []
        };
    }

    /**
     * Ajouter un intercepteur
     */
    addInterceptor(type, handler) {
        if (this.interceptors[type]) {
            this.interceptors[type].push(handler);
        }
    }

    /**
     * Exécuter les intercepteurs
     */
    async executeInterceptors(type, data) {
        for (const interceptor of this.interceptors[type]) {
            try {
                data = await interceptor(data);
            } catch (error) {
                console.error(`Erreur dans l'intercepteur ${type}:`, error);
            }
        }
        return data;
    }

    /**
     * Vérifier le rate limiting
     */
    checkRateLimit() {
        const now = Date.now();
        if (now > this.rateLimitReset) {
            this.rateLimitCount = 0;
            this.rateLimitReset = now + 60000; // 1 minute
        }

        if (this.rateLimitCount >= CONFIG.LIMITS.API_RATE_LIMIT) {
            throw new Error(CONFIG.ERRORS.RATE_LIMIT_ERROR);
        }

        this.rateLimitCount++;
    }

    /**
     * Effectuer une requête HTTP
     */
    async request(url, options = {}) {
        try {
            this.checkRateLimit();

            const defaultOptions = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                timeout: CONFIG.TIMEOUTS.API_REQUEST
            };

            const finalOptions = { ...defaultOptions, ...options };

            // Exécuter les intercepteurs de requête
            const interceptedOptions = await this.executeInterceptors('request', finalOptions);

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), CONFIG.TIMEOUTS.API_REQUEST);

            const response = await fetch(url, {
                ...interceptedOptions,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            // Exécuter les intercepteurs de réponse
            const interceptedResponse = await this.executeInterceptors('response', response);

            if (!interceptedResponse.ok) {
                throw new Error(`HTTP ${interceptedResponse.status}: ${interceptedResponse.statusText}`);
            }

            const data = await interceptedResponse.json();
            return { success: true, data };

        } catch (error) {
            // Exécuter les intercepteurs d'erreur
            const interceptedError = await this.executeInterceptors('error', error);
            
            console.error('Erreur API:', interceptedError);
            
            if (interceptedError.name === 'AbortError') {
                throw new Error(CONFIG.ERRORS.NETWORK_ERROR);
            }

            throw interceptedError;
        }
    }

    /**
     * Requête vers Supabase
     */
    async supabaseRequest(endpoint, options = {}) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Requête vers l'API PHP
     */
    async phpRequest(endpoint, options = {}) {
        let url = endpoint;
        if (!endpoint.startsWith('http')) {
            url = `/backend${endpoint}`;
        }
        return this.request(url, options);
    }

    /**
     * Gestion du cache
     */
    getCached(key) {
        const cached = this.cache.get(key);
        if (cached && Date.now() < cached.expiry) {
            return cached.data;
        }
        this.cache.delete(key);
        return null;
    }

    setCached(key, data, ttl = CONFIG.CACHE.USER_PROFILE) {
        this.cache.set(key, {
            data,
            expiry: Date.now() + ttl
        });
    }

    clearCache(pattern = null) {
        if (pattern) {
            for (const key of this.cache.keys()) {
                if (key.includes(pattern)) {
                    this.cache.delete(key);
                }
            }
        } else {
            this.cache.clear();
        }
    }

    // ===== AUTHENTIFICATION =====

    /**
     * S'inscrire
     */
    async register(userData) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Se connecter
     */
    async login(email, password) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Se déconnecter
     */
    async logout() {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Récupérer le profil utilisateur
     */
    async getUserProfile(userId) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    // ===== LIVRAISONS =====

    /**
     * Créer une livraison
     */
    async createDelivery(deliveryData) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Récupérer les livraisons
     */
    async getDeliveries(filters = {}) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Mettre à jour le statut d'une livraison
     */
    async updateDeliveryStatus(deliveryId, status, additionalData = {}) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    // ===== QR CODES =====

    /**
     * Créer un QR code
     */
    async createQRCode(qrCodeData) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Récupérer les QR codes
     */
    async getQRCodes(filters = {}) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Rechercher dans les QR codes
     */
    async searchQRCodes(userId, searchTerm, type = null) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Obtenir les statistiques des QR codes
     */
    async getQRCodeStats(userId) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    // ===== PAIEMENTS =====

    /**
     * Créer un paiement
     */
    async createPayment(paymentData) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Récupérer les paiements
     */
    async getPayments(filters = {}) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    // ===== NOTIFICATIONS =====

    /**
     * Créer une notification
     */
    async createNotification(notificationData) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Récupérer les notifications
     */
    async getNotifications(filters = {}) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Marquer une notification comme lue
     */
    async markNotificationAsRead(notificationId) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    // ===== ADMIN =====

    /**
     * Obtenir les statistiques admin
     */
    async getAdminStats() {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Créer une sauvegarde
     */
    async createBackup() {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Lister les sauvegardes
     */
    async listBackups() {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    // ===== UTILITAIRES =====

    /**
     * Vérifier si c'est la nuit
     */
    isNightTime() {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Vérifier si c'est le weekend
     */
    isWeekend() {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Obtenir la géolocalisation
     */
    async getCurrentLocation() {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Uploader un fichier
     */
    async uploadFile(file, folder = 'uploads') {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Envoyer un email
     */
    async sendEmail(emailData) {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }

    /**
     * Vérifier la santé de l'API
     */
    async healthCheck() {
        // Toute la logique d'initialisation et d'utilisation du client Supabase est supprimée ou commentée.
        throw new Error('Client Supabase non initialisé');
    }
}

// Gestion explicite des erreurs d'accès refusé (permission denied)
function handleApiError(error) {
    if (error && error.message && error.message.toLowerCase().includes('permission denied')) {
        if (window.toast) window.toast.error('Vous navez pas les droits pour effectuer cette action.');
        return { success: false, error: 'Accès refusé.' };
    }
    return { success: false, error: error.message || 'Erreur inconnue.' };
}

// Instance globale
window.apiService = new ApiService();

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ApiService;
} 