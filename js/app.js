/**
 * Application principale LivraisonP2P
 * Version 1.2.0 - Intégration complète avec PHP et nouvelles fonctionnalités
 */

class LivraisonP2PApp {
    constructor() {
        this.currentUser = null;
        this.currentProfile = null;
        this.isInitialized = false;
        this.theme = ConfigUtils.getCurrentTheme();
        this.language = localStorage.getItem('language') || 'fr';
        this.notifications = [];
        this.offlineQueue = [];
        this.isOnline = navigator.onLine;
        
        this.init();
    }

    /**
     * Initialiser l'application
     */
    async init() {
        try {
            // Configuration initiale
            this.setupEventListeners();
            this.setupServiceWorker();
            this.setupOfflineSupport();
            this.setupTheme();
            this.setupLanguage();
            
            // Vérifier l'authentification
            await this.checkAuth();
            
            // Initialiser les composants
            this.initComponents();
            
            // Charger les données initiales
            await this.loadInitialData();
            
            // Démarrer les services en arrière-plan
            this.startBackgroundServices();
            
            this.isInitialized = true;
            ConfigUtils.log('info', 'Application initialisée avec succès');
            
            // Émettre l'événement d'initialisation
            this.emit('app:initialized');
            
        } catch (error) {
            console.error('Erreur d\'initialisation:', error);
            this.showError('Erreur lors de l\'initialisation de l\'application');
        }
    }

    /**
     * Configurer les écouteurs d'événements
     */
    setupEventListeners() {
        // Événements de navigation
        document.addEventListener('click', this.handleNavigation.bind(this));
        
        // Événements de formulaire
        document.addEventListener('submit', this.handleFormSubmit.bind(this));
        
        // Événements de thème
        document.addEventListener('theme:change', this.handleThemeChange.bind(this));
        
        // Événements de langue
        document.addEventListener('language:change', this.handleLanguageChange.bind(this));
        
        // Événements en ligne/hors ligne
        window.addEventListener('online', this.handleOnline.bind(this));
        window.addEventListener('offline', this.handleOffline.bind(this));
        
        // Événements de visibilité
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
        
        // Événements de stockage
        window.addEventListener('storage', this.handleStorageChange.bind(this));
        
        // Événements de géolocalisation
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(
                this.handleLocationUpdate.bind(this),
                this.handleLocationError.bind(this),
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        }
    }

    /**
     * Configurer le Service Worker
     */
    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('Service Worker enregistré:', registration);
                    
                    // Écouter les mises à jour
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                this.showUpdateNotification();
                            }
                        });
                    });
                })
                .catch(error => {
                    console.error('Erreur Service Worker:', error);
                });
        }
    }

    /**
     * Configurer le support hors ligne
     */
    setupOfflineSupport() {
        // Stocker les requêtes en attente
        this.offlineQueue = JSON.parse(localStorage.getItem('offlineQueue') || '[]');
        
        // Traiter la file d'attente quand on revient en ligne
        if (this.isOnline && this.offlineQueue.length > 0) {
            this.processOfflineQueue();
        }
    }

    /**
     * Configurer le thème
     */
    setupTheme() {
        ConfigUtils.setTheme(this.theme);
        document.body.classList.add(`theme-${this.theme}`);
    }

    /**
     * Configurer la langue
     */
    setupLanguage() {
        document.documentElement.lang = this.language;
        document.documentElement.setAttribute('data-lang', this.language);
    }

    /**
     * Vérifier l'authentification
     */
    async checkAuth() {
        try {
            const { data: { user } } = await window.apiService.supabase.auth.getUser();
            
            if (user) {
                this.currentUser = user;
                this.currentProfile = await window.apiService.getUserProfile(user.id);
                
                // Mettre à jour l'interface
                this.updateAuthUI();
                
                // Charger les notifications
                this.loadNotifications();
                
                ConfigUtils.log('info', 'Utilisateur authentifié', { userId: user.id });
            } else {
                this.currentUser = null;
                this.currentProfile = null;
                this.updateAuthUI();
            }
        } catch (error) {
            console.error('Erreur vérification auth:', error);
            this.currentUser = null;
            this.currentProfile = null;
        }
    }

    /**
     * Initialiser les composants
     */
    initComponents() {
        // Initialiser les toasts
        if (window.Toast) {
            window.toast = new window.Toast();
        }

        // Initialiser le validateur de formulaires
        if (window.FormValidator) {
            this.formValidator = new window.FormValidator();
        }

        // Initialiser l'authentification
        if (window.AuthModule) {
            window.auth = new window.AuthModule();
        }

        // Initialiser les QR codes
        if (window.QRCodeModule) {
            window.qrCode = new window.QRCodeModule();
        }

        // Initialiser les notifications push
        this.initPushNotifications();
    }

    /**
     * Charger les données initiales
     */
    async loadInitialData() {
        if (!this.currentUser) return;

        try {
            // Charger les données en parallèle
            const [deliveries, qrCodes, payments] = await Promise.allSettled([
                window.apiService.getDeliveries({ user_id: this.currentUser.id }),
                window.apiService.getQRCodes({ user_id: this.currentUser.id }),
                window.apiService.getPayments({ user_id: this.currentUser.id })
            ]);

            // Mettre à jour les compteurs
            this.updateCounters({
                deliveries: deliveries.value?.data?.length || 0,
                qrCodes: qrCodes.value?.data?.length || 0,
                payments: payments.value?.data?.length || 0
            });

        } catch (error) {
            console.error('Erreur chargement données initiales:', error);
        }
    }

    /**
     * Démarrer les services en arrière-plan
     */
    startBackgroundServices() {
        // Vérification périodique des notifications
        setInterval(() => {
            if (this.currentUser) {
                this.loadNotifications();
            }
        }, CONFIG.TIMEOUTS.PUSH_CHECK);

        // Vérification de la santé de l'API
        setInterval(() => {
            this.checkApiHealth();
        }, 5 * 60 * 1000); // Toutes les 5 minutes

        // Synchronisation des données hors ligne
        setInterval(() => {
            if (this.isOnline && this.offlineQueue.length > 0) {
                this.processOfflineQueue();
            }
        }, 30 * 1000); // Toutes les 30 secondes
    }

    // ===== GESTION DE L'AUTHENTIFICATION =====

    /**
     * Mettre à jour l'interface d'authentification
     */
    updateAuthUI() {
        const authElements = document.querySelectorAll('[data-auth]');
        
        authElements.forEach(element => {
            const authType = element.dataset.auth;
            
            if (authType === 'logged-in' && this.currentUser) {
                element.style.display = '';
            } else if (authType === 'logged-out' && !this.currentUser) {
                element.style.display = '';
            } else {
                element.style.display = 'none';
            }
        });

        // Mettre à jour les informations utilisateur
        if (this.currentProfile) {
            const userNameElements = document.querySelectorAll('[data-user-name]');
            userNameElements.forEach(element => {
                element.textContent = `${this.currentProfile.prenom} ${this.currentProfile.nom}`;
            });

            const userRoleElements = document.querySelectorAll('[data-user-role]');
            userRoleElements.forEach(element => {
                element.textContent = CONFIG.USER_ROLES[this.currentProfile.role]?.label || this.currentProfile.role;
            });
        }
    }

    /**
     * Rediriger selon le rôle
     */
    redirectByRole() {
        if (!this.currentProfile) return;

        const role = this.currentProfile.role;
        const lastSection = localStorage.getItem('last_section');

        let redirectUrl = '/';
        
        switch (role) {
            case 'client':
                redirectUrl = lastSection && lastSection.includes('/client/') ? lastSection : '/client/dashboard.html';
                break;
            case 'livreur':
                redirectUrl = lastSection && lastSection.includes('/courier/') ? lastSection : '/courier/dashboard.html';
                break;
            case 'admin':
                redirectUrl = '/php/admin-dashboard.php';
                break;
        }

        if (window.location.pathname !== redirectUrl) {
            window.location.href = redirectUrl;
        }
    }

    // ===== GESTION DES NOTIFICATIONS =====

    /**
     * Initialiser les notifications push
     */
    async initPushNotifications() {
        if (!('Notification' in window) || !this.currentUser) return;

        try {
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                // Enregistrer pour les notifications push
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: CONFIG.PUSH_PUBLIC_KEY
                });

                // Envoyer la subscription au serveur
                await window.apiService.createNotification({
                    user_id: this.currentUser.id,
                    type: 'push_subscription',
                    title: 'Notifications activées',
                    content: 'Vous recevrez maintenant des notifications push',
                    data: subscription
                });
            }
        } catch (error) {
            console.error('Erreur notifications push:', error);
        }
    }

    /**
     * Charger les notifications
     */
    async loadNotifications() {
        if (!this.currentUser) return;

        try {
            const result = await window.apiService.getNotifications({
                user_id: this.currentUser.id,
                read: false
            });

            if (result.success) {
                this.notifications = result.data;
                this.updateNotificationBadge();
                this.displayNotifications();
            }
        } catch (error) {
            console.error('Erreur chargement notifications:', error);
        }
    }

    /**
     * Mettre à jour le badge de notifications
     */
    updateNotificationBadge() {
        const badges = document.querySelectorAll('[data-notification-badge]');
        const count = this.notifications.length;
        
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? '' : 'none';
        });
    }

    /**
     * Afficher les notifications
     */
    displayNotifications() {
        const container = document.getElementById('notificationsContainer');
        if (!container) return;

        container.innerHTML = '';

        this.notifications.forEach(notification => {
            const element = this.createNotificationElement(notification);
            container.appendChild(element);
        });
    }

    /**
     * Créer un élément de notification
     */
    createNotificationElement(notification) {
        const div = document.createElement('div');
        div.className = 'notification-item';
        div.innerHTML = `
            <div class="notification-content">
                <h4>${notification.title}</h4>
                <p>${notification.content}</p>
                <small>${ConfigUtils.formatDate(notification.created_at, 'relative')}</small>
            </div>
            <button onclick="app.markNotificationAsRead('${notification.id}')" class="mark-read-btn">
                <i class="fas fa-check"></i>
            </button>
        `;
        return div;
    }

    /**
     * Marquer une notification comme lue
     */
    async markNotificationAsRead(notificationId) {
        try {
            const result = await window.apiService.markNotificationAsRead(notificationId);
            
            if (result.success) {
                // Retirer de la liste
                this.notifications = this.notifications.filter(n => n.id !== notificationId);
                this.updateNotificationBadge();
                this.displayNotifications();
            }
        } catch (error) {
            console.error('Erreur marquage notification:', error);
        }
    }

    // ===== GESTION DES QR CODES =====

    /**
     * Générer un QR code
     */
    async generateQRCode(data) {
        try {
            const result = await window.apiService.createQRCode({
                user_id: this.currentUser.id,
                ...data
            });

            if (result.success) {
                this.showSuccess(CONFIG.SUCCESS_MESSAGES.QR_CODE_GENERATED);
                this.emit('qr:generated', result.data);
                return result.data;
            } else {
                this.showError(result.error);
                return null;
            }
        } catch (error) {
            console.error('Erreur génération QR code:', error);
            this.showError(CONFIG.ERRORS.SERVER_ERROR);
            return null;
        }
    }

    /**
     * Scanner un QR code
     */
    async scanQRCode(qrData) {
        try {
            // Traiter le contenu du QR code selon son type
            const type = qrData.type || 'custom';
            
            switch (type) {
                case 'delivery':
                    return this.handleDeliveryQR(qrData);
                case 'user':
                    return this.handleUserQR(qrData);
                case 'payment':
                    return this.handlePaymentQR(qrData);
                case 'location':
                    return this.handleLocationQR(qrData);
                default:
                    return this.handleCustomQR(qrData);
            }
        } catch (error) {
            console.error('Erreur scan QR code:', error);
            this.showError('Erreur lors du scan du QR code');
        }
    }

    /**
     * Traiter un QR code de livraison
     */
    async handleDeliveryQR(qrData) {
        const deliveryId = qrData.delivery_id;
        
        if (!deliveryId) {
            this.showError('QR code de livraison invalide');
            return;
        }

        // Rediriger vers le suivi de livraison
        window.location.href = `/client/track-delivery.html?id=${deliveryId}`;
    }

    /**
     * Traiter un QR code utilisateur
     */
    async handleUserQR(qrData) {
        const userId = qrData.user_id;
        
        if (!userId) {
            this.showError('QR code utilisateur invalide');
            return;
        }

        // Afficher les informations de l'utilisateur
        this.showUserInfo(userId);
    }

    /**
     * Traiter un QR code de paiement
     */
    async handlePaymentQR(qrData) {
        const paymentId = qrData.payment_id;
        
        if (!paymentId) {
            this.showError('QR code de paiement invalide');
            return;
        }

        // Rediriger vers la page de paiement
        window.location.href = `/payment.html?id=${paymentId}`;
    }

    /**
     * Traiter un QR code de localisation
     */
    async handleLocationQR(qrData) {
        const { lat, lng, address } = qrData;
        
        if (!lat || !lng) {
            this.showError('QR code de localisation invalide');
            return;
        }

        // Ouvrir dans Google Maps
        const url = `https://www.google.com/maps?q=${lat},${lng}`;
        window.open(url, '_blank');
    }

    /**
     * Traiter un QR code personnalisé
     */
    async handleCustomQR(qrData) {
        const content = qrData.content;
        
        if (!content) {
            this.showError('QR code invalide');
            return;
        }

        // Afficher le contenu
        this.showCustomContent(content);
    }

    // ===== GESTION DES LIVRAISONS =====

    /**
     * Créer une livraison
     */
    async createDelivery(deliveryData) {
        try {
            // Ajouter l'ID utilisateur
            deliveryData.user_id = this.currentUser.id;
            
            const result = await window.apiService.createDelivery(deliveryData);
            
            if (result.success) {
                this.showSuccess(CONFIG.SUCCESS_MESSAGES.DELIVERY_CREATED);
                this.emit('delivery:created', result.data);
                return result.data;
            } else {
                this.showError(result.error);
                return null;
            }
        } catch (error) {
            console.error('Erreur création livraison:', error);
            this.showError(CONFIG.ERRORS.SERVER_ERROR);
            return null;
        }
    }

    /**
     * Suivre une livraison
     */
    async trackDelivery(deliveryId) {
        try {
            const result = await window.apiService.getDeliveries({
                user_id: this.currentUser.id
            });

            if (result.success) {
                const delivery = result.data.find(d => d.id === deliveryId);
                if (delivery) {
                    this.showDeliveryTracking(delivery);
                } else {
                    this.showError('Livraison introuvable');
                }
            }
        } catch (error) {
            console.error('Erreur suivi livraison:', error);
            this.showError(CONFIG.ERRORS.SERVER_ERROR);
        }
    }

    // ===== GESTION DES PAIEMENTS =====

    /**
     * Effectuer un paiement
     */
    async processPayment(paymentData) {
        try {
            paymentData.user_id = this.currentUser.id;
            paymentData.status = 'pending';
            
            const result = await window.apiService.createPayment(paymentData);
            
            if (result.success) {
                this.showSuccess(CONFIG.SUCCESS_MESSAGES.PAYMENT_SUCCESS);
                this.emit('payment:processed', result.data);
                return result.data;
            } else {
                this.showError(result.error);
                return null;
            }
        } catch (error) {
            console.error('Erreur paiement:', error);
            this.showError(CONFIG.ERRORS.SERVER_ERROR);
            return null;
        }
    }

    // ===== GESTION DES ÉVÉNEMENTS =====

    /**
     * Gérer la navigation
     */
    handleNavigation(event) {
        const link = event.target.closest('a[data-nav]');
        if (!link) return;

        event.preventDefault();
        
        const navType = link.dataset.nav;
        const href = link.href;

        switch (navType) {
            case 'internal':
                this.navigateTo(href);
                break;
            case 'external':
                window.open(href, '_blank');
                break;
            case 'modal':
                this.openModal(href);
                break;
        }
    }

    /**
     * Gérer la soumission de formulaires
     */
    handleFormSubmit(event) {
        const form = event.target;
        const formType = form.dataset.formType;

        if (formType === 'auth') {
            this.handleAuthForm(form);
        } else if (formType === 'delivery') {
            this.handleDeliveryForm(form);
        } else if (formType === 'qr-code') {
            this.handleQRCodeForm(form);
        }
    }

    /**
     * Gérer le changement de thème
     */
    handleThemeChange(event) {
        const newTheme = event.detail.theme;
        this.theme = newTheme;
        ConfigUtils.setTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    }

    /**
     * Gérer le changement de langue
     */
    handleLanguageChange(event) {
        const newLanguage = event.detail.language;
        this.language = newLanguage;
        localStorage.setItem('language', newLanguage);
        this.setupLanguage();
        this.loadTranslations();
    }

    /**
     * Gérer la connexion en ligne
     */
    handleOnline() {
        this.isOnline = true;
        this.showSuccess('Connexion rétablie');
        this.processOfflineQueue();
    }

    /**
     * Gérer la déconnexion
     */
    handleOffline() {
        this.isOnline = false;
        this.showWarning('Vous êtes hors ligne. Certaines fonctionnalités peuvent être limitées.');
    }

    /**
     * Gérer le changement de visibilité
     */
    handleVisibilityChange() {
        if (!document.hidden && this.currentUser) {
            this.loadNotifications();
        }
    }

    /**
     * Gérer le changement de stockage
     */
    handleStorageChange(event) {
        if (event.key === 'theme') {
            this.handleThemeChange({ detail: { theme: event.newValue } });
        } else if (event.key === 'language') {
            this.handleLanguageChange({ detail: { language: event.newValue } });
        }
    }

    /**
     * Gérer la mise à jour de localisation
     */
    handleLocationUpdate(position) {
        const location = {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            accuracy: position.coords.accuracy,
            timestamp: position.timestamp
        };

        // Mettre à jour la localisation si l'utilisateur est connecté
        if (this.currentUser) {
            this.updateUserLocation(location);
        }

        this.emit('location:updated', location);
    }

    /**
     * Gérer l'erreur de localisation
     */
    handleLocationError(error) {
        console.error('Erreur géolocalisation:', error);
        this.emit('location:error', error);
    }

    // ===== UTILITAIRES =====

    /**
     * Naviguer vers une page
     */
    navigateTo(url) {
        // Sauvegarder la section actuelle
        localStorage.setItem('last_section', window.location.pathname);
        
        // Naviguer
        window.location.href = url;
    }

    /**
     * Ouvrir une modal
     */
    openModal(url) {
        // Implémenter l'ouverture de modal
        console.log('Ouverture modal:', url);
    }

    /**
     * Mettre à jour les compteurs
     */
    updateCounters(counts) {
        Object.entries(counts).forEach(([key, value]) => {
            const elements = document.querySelectorAll(`[data-counter="${key}"]`);
            elements.forEach(element => {
                element.textContent = value;
            });
        });
    }

    /**
     * Traiter la file d'attente hors ligne
     */
    async processOfflineQueue() {
        if (this.offlineQueue.length === 0) return;

        const queue = [...this.offlineQueue];
        this.offlineQueue = [];

        for (const request of queue) {
            try {
                await window.apiService.request(request.url, request.options);
            } catch (error) {
                console.error('Erreur traitement requête hors ligne:', error);
                this.offlineQueue.push(request);
            }
        }

        localStorage.setItem('offlineQueue', JSON.stringify(this.offlineQueue));
    }

    /**
     * Vérifier la santé de l'API
     */
    async checkApiHealth() {
        try {
            const result = await window.apiService.healthCheck();
            if (!result.success) {
                this.showWarning('Problème de connectivité avec le serveur');
            }
        } catch (error) {
            console.error('Erreur health check:', error);
        }
    }

    /**
     * Mettre à jour la localisation utilisateur
     */
    async updateUserLocation(location) {
        try {
            await window.apiService.supabase
                .from('user_locations')
                .upsert({
                    user_id: this.currentUser.id,
                    latitude: location.lat,
                    longitude: location.lng,
                    accuracy: location.accuracy,
                    updated_at: new Date().toISOString()
                });
        } catch (error) {
            console.error('Erreur mise à jour localisation:', error);
        }
    }

    /**
     * Charger les traductions
     */
    async loadTranslations() {
        // Implémenter le chargement des traductions
        console.log('Chargement traductions pour:', this.language);
    }

    /**
     * Afficher une notification de mise à jour
     */
    showUpdateNotification() {
        if (window.toast) {
            window.toast.info('Une nouvelle version est disponible. Rechargez la page pour l\'installer.', {
                duration: 0,
                actions: [
                    {
                        label: 'Recharger',
                        action: () => window.location.reload()
                    }
                ]
            });
        }
    }

    // ===== GESTION DES ERREURS ET SUCCÈS =====

    /**
     * Afficher une erreur
     */
    showError(message) {
        if (window.toast) {
            window.toast.error(message);
        } else {
            alert(message);
        }
    }

    /**
     * Afficher un succès
     */
    showSuccess(message) {
        if (window.toast) {
            window.toast.success(message);
        }
    }

    /**
     * Afficher un avertissement
     */
    showWarning(message) {
        if (window.toast) {
            window.toast.warning(message);
        }
    }

    /**
     * Afficher une information
     */
    showInfo(message) {
        if (window.toast) {
            window.toast.info(message);
        }
    }

    // ===== SYSTÈME D'ÉVÉNEMENTS =====

    /**
     * Émettre un événement
     */
    emit(eventName, data = {}) {
        const event = new CustomEvent(eventName, { detail: data });
        document.dispatchEvent(event);
    }

    /**
     * Écouter un événement
     */
    on(eventName, callback) {
        document.addEventListener(eventName, (event) => {
            callback(event.detail);
        });
    }

    /**
     * Arrêter d'écouter un événement
     */
    off(eventName, callback) {
        document.removeEventListener(eventName, callback);
    }
}

// Initialiser l'application quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    window.app = new LivraisonP2PApp();
});

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LivraisonP2PApp;
} 