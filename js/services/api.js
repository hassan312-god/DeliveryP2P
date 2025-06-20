/**
 * Service API pour LivraisonP2P
 * Gestion des requêtes vers Supabase et les services PHP
 * Version 1.2.0 - Intégration PHP
 */

class ApiService {
    constructor() {
        this.supabase = null;
        this.baseUrl = CONFIG.APP.API_BASE_URL;
        this.cache = new Map();
        this.requestQueue = [];
        this.isProcessingQueue = false;
        this.rateLimitCount = 0;
        this.rateLimitReset = Date.now();
        
        this.initSupabase();
        this.setupInterceptors();
    }

    /**
     * Initialiser Supabase
     */
    initSupabase() {
        if (typeof supabase !== 'undefined') {
            this.supabase = supabase.createClient(CONFIG.SUPABASE.URL, CONFIG.SUPABASE.ANON_KEY);
        } else {
            console.warn('Supabase client non disponible');
        }
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
        if (!this.supabase) {
            throw new Error('Client Supabase non initialisé');
        }

        try {
            const { data, error } = await this.supabase
                .from(endpoint)
                .select(options.select || '*')
                .eq(options.eq?.column, options.eq?.value)
                .order(options.order?.column, { ascending: options.order?.ascending ?? true })
                .limit(options.limit);

            if (error) throw error;
            return { success: true, data };

        } catch (error) {
            console.error('Erreur Supabase:', error);
            throw error;
        }
    }

    /**
     * Requête vers l'API PHP
     */
    async phpRequest(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
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
        try {
            const { data, error } = await this.supabase.auth.signUp({
                email: userData.email,
                password: userData.mot_de_passe
            });

            if (error) throw error;

            // Créer le profil utilisateur
            const profileData = {
                id: data.user.id,
                prenom: userData.prenom,
                nom: userData.nom,
                email: userData.email,
                telephone: userData.telephone,
                role: userData.role,
                created_at: new Date().toISOString()
            };

            const profileResult = await this.supabaseRequest('profiles', {
                options: { method: 'POST', body: profileData }
            });

            return { success: true, user: data.user, profile: profileResult.data };

        } catch (error) {
            console.error('Erreur d\'inscription:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Se connecter
     */
    async login(email, password) {
        try {
            const { data, error } = await this.supabase.auth.signInWithPassword({
                email,
                password
            });

            if (error) throw error;

            // Récupérer le profil utilisateur
            const profile = await this.getUserProfile(data.user.id);

            return { success: true, user: data.user, profile };

        } catch (error) {
            console.error('Erreur de connexion:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Se déconnecter
     */
    async logout() {
        try {
            const { error } = await this.supabase.auth.signOut();
            if (error) throw error;

            this.clearCache();
            return { success: true };

        } catch (error) {
            console.error('Erreur de déconnexion:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Récupérer le profil utilisateur
     */
    async getUserProfile(userId) {
        const cacheKey = `profile_${userId}`;
        const cached = this.getCached(cacheKey);

        if (cached) return cached;

        try {
            const result = await this.supabaseRequest('profiles', {
                eq: { column: 'id', value: userId }
            });

            if (result.success && result.data.length > 0) {
                const profile = result.data[0];
                this.setCached(cacheKey, profile, CONFIG.CACHE.USER_PROFILE);
                return profile;
            }

            return null;

        } catch (error) {
            console.error('Erreur récupération profil:', error);
            return null;
        }
    }

    // ===== LIVRAISONS =====

    /**
     * Créer une livraison
     */
    async createDelivery(deliveryData) {
        try {
            // Calculer le prix automatiquement
            if (!deliveryData.amount) {
                const distance = ConfigUtils.calculateDistance(
                    deliveryData.pickup_lat, deliveryData.pickup_lng,
                    deliveryData.delivery_lat, deliveryData.delivery_lng
                );
                deliveryData.amount = ConfigUtils.calculateDeliveryPrice(distance, {
                    isUrgent: deliveryData.is_urgent,
                    isNight: this.isNightTime(),
                    isWeekend: this.isWeekend()
                });
            }

            const result = await this.supabaseRequest('deliveries', {
                options: { method: 'POST', body: deliveryData }
            });

            this.clearCache('deliveries');
            return result;

        } catch (error) {
            console.error('Erreur création livraison:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Récupérer les livraisons
     */
    async getDeliveries(filters = {}) {
        const cacheKey = `deliveries_${JSON.stringify(filters)}`;
        const cached = this.getCached(cacheKey);

        if (cached) return cached;

        try {
            let query = this.supabase.from('deliveries').select('*');

            if (filters.user_id) {
                query = query.eq('user_id', filters.user_id);
            }
            if (filters.status) {
                query = query.eq('status', filters.status);
            }
            if (filters.courier_id) {
                query = query.eq('courier_id', filters.courier_id);
            }

            query = query.order('created_at', { ascending: false });

            const { data, error } = await query;

            if (error) throw error;

            const result = { success: true, data };
            this.setCached(cacheKey, result, CONFIG.CACHE.DELIVERY_LIST);
            return result;

        } catch (error) {
            console.error('Erreur récupération livraisons:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Mettre à jour le statut d'une livraison
     */
    async updateDeliveryStatus(deliveryId, status, additionalData = {}) {
        try {
            const updateData = { status, ...additionalData };
            
            const { data, error } = await this.supabase
                .from('deliveries')
                .update(updateData)
                .eq('id', deliveryId)
                .select();

            if (error) throw error;

            this.clearCache('deliveries');
            return { success: true, data: data[0] };

        } catch (error) {
            console.error('Erreur mise à jour livraison:', error);
            return { success: false, error: error.message };
        }
    }

    // ===== QR CODES =====

    /**
     * Créer un QR code
     */
    async createQRCode(qrCodeData) {
        try {
            const result = await this.phpRequest('/qr-code-generator.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'create',
                    ...qrCodeData
                })
            });

            this.clearCache('qr_codes');
            return result;

        } catch (error) {
            console.error('Erreur création QR code:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Récupérer les QR codes
     */
    async getQRCodes(filters = {}) {
        const cacheKey = `qr_codes_${JSON.stringify(filters)}`;
        const cached = this.getCached(cacheKey);

        if (cached) return cached;

        try {
            const params = new URLSearchParams();
            params.append('action', 'list');
            
            if (filters.user_id) params.append('user_id', filters.user_id);
            if (filters.type) params.append('type', filters.type);

            const result = await this.phpRequest(`/qr-code-generator.php?${params}`);
            
            if (result.success) {
                this.setCached(cacheKey, result, CONFIG.CACHE.QR_CODES);
            }

            return result;

        } catch (error) {
            console.error('Erreur récupération QR codes:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Rechercher dans les QR codes
     */
    async searchQRCodes(userId, searchTerm, type = null) {
        try {
            const params = new URLSearchParams({
                action: 'search',
                user_id: userId,
                q: searchTerm
            });

            if (type) params.append('type', type);

            return await this.phpRequest(`/qr-code-generator.php?${params}`);

        } catch (error) {
            console.error('Erreur recherche QR codes:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Obtenir les statistiques des QR codes
     */
    async getQRCodeStats(userId) {
        try {
            const params = new URLSearchParams({
                action: 'stats',
                user_id: userId
            });

            return await this.phpRequest(`/qr-code-generator.php?${params}`);

        } catch (error) {
            console.error('Erreur statistiques QR codes:', error);
            return { success: false, error: error.message };
        }
    }

    // ===== PAIEMENTS =====

    /**
     * Créer un paiement
     */
    async createPayment(paymentData) {
        try {
            const result = await this.supabaseRequest('payments', {
                options: { method: 'POST', body: paymentData }
            });

            this.clearCache('payments');
            return result;

        } catch (error) {
            console.error('Erreur création paiement:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Récupérer les paiements
     */
    async getPayments(filters = {}) {
        try {
            let query = this.supabase.from('payments').select('*');

            if (filters.user_id) {
                query = query.eq('user_id', filters.user_id);
            }
            if (filters.status) {
                query = query.eq('status', filters.status);
            }

            query = query.order('created_at', { ascending: false });

            const { data, error } = await query;

            if (error) throw error;

            return { success: true, data };

        } catch (error) {
            console.error('Erreur récupération paiements:', error);
            return { success: false, error: error.message };
        }
    }

    // ===== NOTIFICATIONS =====

    /**
     * Créer une notification
     */
    async createNotification(notificationData) {
        try {
            const result = await this.supabaseRequest('notifications', {
                options: { method: 'POST', body: notificationData }
            });

            this.clearCache('notifications');
            return result;

        } catch (error) {
            console.error('Erreur création notification:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Récupérer les notifications
     */
    async getNotifications(filters = {}) {
        try {
            let query = this.supabase.from('notifications').select('*');

            if (filters.user_id) {
                query = query.eq('user_id', filters.user_id);
            }
            if (filters.read === false) {
                query = query.eq('read', false);
            }

            query = query.order('created_at', { ascending: false });

            const { data, error } = await query;

            if (error) throw error;

            return { success: true, data };

        } catch (error) {
            console.error('Erreur récupération notifications:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Marquer une notification comme lue
     */
    async markNotificationAsRead(notificationId) {
        try {
            const { data, error } = await this.supabase
                .from('notifications')
                .update({ read: true, read_at: new Date().toISOString() })
                .eq('id', notificationId)
                .select();

            if (error) throw error;

            this.clearCache('notifications');
            return { success: true, data: data[0] };

        } catch (error) {
            console.error('Erreur marquage notification:', error);
            return { success: false, error: error.message };
        }
    }

    // ===== ADMIN =====

    /**
     * Obtenir les statistiques admin
     */
    async getAdminStats() {
        try {
            return await this.phpRequest('/admin-dashboard.php?action=stats');

        } catch (error) {
            console.error('Erreur statistiques admin:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Créer une sauvegarde
     */
    async createBackup() {
        try {
            return await this.phpRequest('/backup-manager.php?action=create_full');

        } catch (error) {
            console.error('Erreur création sauvegarde:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Lister les sauvegardes
     */
    async listBackups() {
        try {
            return await this.phpRequest('/backup-manager.php?action=list');

        } catch (error) {
            console.error('Erreur liste sauvegardes:', error);
            return { success: false, error: error.message };
        }
    }

    // ===== UTILITAIRES =====

    /**
     * Vérifier si c'est la nuit
     */
    isNightTime() {
        const hour = new Date().getHours();
        return hour >= 22 || hour < 6;
    }

    /**
     * Vérifier si c'est le weekend
     */
    isWeekend() {
        const day = new Date().getDay();
        return day === 0 || day === 6; // Dimanche = 0, Samedi = 6
    }

    /**
     * Obtenir la géolocalisation
     */
    async getCurrentLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Géolocalisation non supportée'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                },
                (error) => {
                    reject(new Error(`Erreur géolocalisation: ${error.message}`));
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
     * Uploader un fichier
     */
    async uploadFile(file, folder = 'uploads') {
        try {
            if (file.size > CONFIG.LIMITS.MAX_FILE_SIZE) {
                throw new Error('Fichier trop volumineux');
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('folder', folder);

            const response = await fetch(`${this.baseUrl}/upload.php`, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Erreur upload');
            }

            const result = await response.json();
            return result;

        } catch (error) {
            console.error('Erreur upload fichier:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Envoyer un email
     */
    async sendEmail(emailData) {
        try {
            return await this.phpRequest('/email-service.php', {
                method: 'POST',
                body: JSON.stringify(emailData)
            });

        } catch (error) {
            console.error('Erreur envoi email:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Vérifier la santé de l'API
     */
    async healthCheck() {
        try {
            return await this.phpRequest('/admin-dashboard.php?action=health');

        } catch (error) {
            console.error('Erreur health check:', error);
            return { success: false, error: error.message };
        }
    }
}

// Instance globale
window.apiService = new ApiService();

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ApiService;
} 