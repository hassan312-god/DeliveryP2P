/**
 * Module d'authentification pour LivraisonP2P
 * Gère l'authentification, l'inscription, la connexion et les emails de confirmation
 */
class AuthModule {
    constructor() {
        this.currentUser = null;
        this.currentProfile = null;
        this.isAuthenticated = false;
        this.session = null;
        
        // Initialiser le service Supabase
        this.authService = null;
        this.databaseService = null;
        
        // Initialiser l'état d'authentification
        this.initAuth();
        
        // Écouter les changements d'authentification
        document.addEventListener('supabaseAuthStateChange', (event) => {
            this.handleAuthStateChange(event.detail.event, event.detail.session);
        });
    }

    /**
     * Initialiser l'état d'authentification
     */
    async initAuth() {
        try {
            // Initialiser les services Supabase
            if (window.SupabaseAuthService) {
                this.authService = new window.SupabaseAuthService();
                this.databaseService = new window.SupabaseDatabaseService();
            }
            
            // Vérifier la session actuelle
            if (this.authService) {
                const { success, session } = await this.authService.getSession();
                if (success && session) {
                    await this.handleAuthStateChange('SIGNED_IN', session);
                }
            }
        } catch (error) {
            console.error('Erreur lors de l\'initialisation de l\'auth:', error);
        }
    }

    /**
     * Gérer les changements d'état d'authentification
     */
    async handleAuthStateChange(event, session) {
        console.log('Changement d\'état auth:', event, session);
        
        this.session = session;
        
        if (event === 'SIGNED_IN' && session) {
            this.currentUser = session.user;
            this.isAuthenticated = true;
            
            // Charger le profil utilisateur
            await this.loadUserProfile();
            
            // Envoyer l'événement de connexion
            this.dispatchAuthEvent('login', this.currentUser);
            
        } else if (event === 'SIGNED_OUT') {
            this.currentUser = null;
            this.currentProfile = null;
            this.isAuthenticated = false;
            
            // Envoyer l'événement de déconnexion
            this.dispatchAuthEvent('logout');
            
        } else if (event === 'TOKEN_REFRESHED') {
            this.session = session;
        }
    }

    /**
     * Charger le profil utilisateur
     */
    async loadUserProfile() {
        if (!this.currentUser || !this.databaseService) return;
        
        try {
            const { success, profile } = await this.databaseService.getUserProfile(this.currentUser.id);
            
            if (success) {
                this.currentProfile = profile;
            } else {
                console.error('Erreur lors du chargement du profil');
            }
        } catch (error) {
            console.error('Erreur lors du chargement du profil:', error);
        }
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    async register(userData) {
        try {
            // Validation des données
            const validation = this.validateRegistrationData(userData);
            if (!validation.isValid) {
                return { success: false, error: validation.error };
            }

            if (!this.authService) {
                return { success: false, error: 'Service d\'authentification non disponible' };
            }

            // Créer le compte utilisateur
            const { success, data, error } = await this.authService.signUp(
                userData.email,
                userData.mot_de_passe,
                {
                    nom: userData.nom,
                    prenom: userData.prenom,
                    telephone: userData.telephone || null,
                    role: userData.role || 'client'
                }
            );

            if (!success) {
                console.error('Erreur lors de l\'inscription:', error);
                return { 
                    success: false, 
                    error: this.translateAuthError(error?.message || 'Erreur lors de l\'inscription') 
                };
            }

            // Envoyer l'email de confirmation
            await this.sendConfirmationEmail(userData.email);

            return {
                success: true,
                user: data.user,
                message: 'Inscription réussie ! Veuillez vérifier votre email pour confirmer votre compte.',
                requiresConfirmation: true
            };

        } catch (error) {
            console.error('Erreur lors de l\'inscription:', error);
            return { 
                success: false, 
                error: 'Erreur serveur lors de l\'inscription' 
            };
        }
    }

    /**
     * Connexion utilisateur
     */
    async login(email, password) {
        try {
            if (!this.authService) {
                return { success: false, error: 'Service d\'authentification non disponible' };
            }

            const { success, data, error } = await this.authService.signIn(email, password);
            
            if (!success) {
                console.error('Erreur lors de la connexion:', error);
                return { success: false, error: this.translateAuthError(error?.message || 'Erreur lors de la connexion') };
            }

            // Vérifier si l'email est confirmé
            if (!data.user.email_confirmed_at) {
                await this.authService.signOut();
                return {
                    success: false,
                    error: 'Veuillez confirmer votre email avant de vous connecter. Vérifiez votre boîte de réception.',
                    requiresConfirmation: true
                };
            }

            return { success: true, user: data.user };

        } catch (error) {
            console.error('Erreur lors de la connexion:', error);
            return { success: false, error: 'Erreur serveur lors de la connexion' };
        }
    }

    /**
     * Déconnexion utilisateur
     */
    async logout() {
        try {
            if (!this.authService) {
                return { success: false, error: 'Service d\'authentification non disponible' };
            }

            const { success, error } = await this.authService.signOut();
            
            if (!success) {
                console.error('Erreur lors de la déconnexion:', error);
                return { success: false, error: 'Erreur lors de la déconnexion' };
            }

            // Réinitialiser l'état local
            this.currentUser = null;
            this.currentProfile = null;
            this.isAuthenticated = false;
            this.session = null;

            // Envoyer l'événement de déconnexion
            this.dispatchAuthEvent('logout');

            return { success: true };

        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
            return { success: false, error: 'Erreur serveur lors de la déconnexion' };
        }
    }

    /**
     * Envoyer l'email de confirmation
     */
    async sendConfirmationEmail(email) {
        try {
            if (!this.authService) {
                return { success: false, error: 'Service d\'authentification non disponible' };
            }

            const { success, error } = await this.authService.resendConfirmationEmail(email);
            
            if (!success) {
                console.error('Erreur lors de l\'envoi de l\'email de confirmation:', error);
                return { success: false, error: 'Erreur lors de l\'envoi de l\'email de confirmation' };
            }

            return { success: true };

        } catch (error) {
            console.error('Erreur lors de l\'envoi de l\'email de confirmation:', error);
            return { success: false, error: 'Erreur serveur lors de l\'envoi de l\'email' };
        }
    }

    /**
     * Envoyer l'email de réinitialisation de mot de passe
     */
    async sendPasswordResetEmail(email) {
        try {
            if (!this.authService) {
                return { success: false, error: 'Service d\'authentification non disponible' };
            }

            const { success, error } = await this.authService.sendPasswordResetEmail(email);
            
            if (!success) {
                console.error('Erreur lors de l\'envoi de l\'email de réinitialisation:', error);
                return { success: false, error: 'Erreur lors de l\'envoi de l\'email de réinitialisation' };
            }

            return { success: true };

        } catch (error) {
            console.error('Erreur lors de l\'envoi de l\'email de réinitialisation:', error);
            return { success: false, error: 'Erreur serveur lors de l\'envoi de l\'email' };
        }
    }

    /**
     * Réinitialiser le mot de passe
     */
    async resetPassword(newPassword) {
        try {
            if (!this.authService) {
                return { success: false, error: 'Service d\'authentification non disponible' };
            }

            const { success, error } = await this.authService.resetPassword(newPassword);
            
            if (!success) {
                console.error('Erreur lors de la réinitialisation du mot de passe:', error);
                return { success: false, error: 'Erreur lors de la réinitialisation du mot de passe' };
            }

            return { success: true };

        } catch (error) {
            console.error('Erreur lors de la réinitialisation du mot de passe:', error);
            return { success: false, error: 'Erreur serveur lors de la réinitialisation' };
        }
    }

    /**
     * Connexion sociale
     */
    async socialLogin(provider) {
        try {
            if (!this.authService) {
                return { success: false, error: 'Service d\'authentification non disponible' };
            }

            const { success, data, error } = await this.authService.signInWithOAuth(provider);
            
            if (!success) {
                console.error('Erreur lors de la connexion sociale:', error);
                return { success: false, error: 'Erreur lors de la connexion sociale' };
            }

            return { success: true, data };

        } catch (error) {
            console.error('Erreur lors de la connexion sociale:', error);
            return { success: false, error: 'Erreur serveur lors de la connexion sociale' };
        }
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    isLoggedIn() {
        return this.isAuthenticated && this.currentUser !== null;
    }

    /**
     * Obtenir l'utilisateur actuel
     */
    getCurrentUser() {
        return this.currentUser;
    }

    /**
     * Obtenir le profil actuel
     */
    getCurrentProfile() {
        return this.currentProfile;
    }

    /**
     * Mettre à jour le profil utilisateur
     */
    async updateProfile(profileData) {
        try {
            if (!this.currentUser || !this.databaseService) {
                return { success: false, error: 'Utilisateur non connecté ou service non disponible' };
            }

            const { success, error } = await this.databaseService.updateUserProfile(this.currentUser.id, profileData);
            
            if (!success) {
                console.error('Erreur lors de la mise à jour du profil:', error);
                return { success: false, error: 'Erreur lors de la mise à jour du profil' };
            }

            // Recharger le profil
            await this.loadUserProfile();

            return { success: true };

        } catch (error) {
            console.error('Erreur lors de la mise à jour du profil:', error);
            return { success: false, error: 'Erreur serveur lors de la mise à jour' };
        }
    }

    /**
     * Valider les données d'inscription
     */
    validateRegistrationData(data) {
        if (!data.email || !this.isValidEmail(data.email)) {
            return { isValid: false, error: 'Adresse email invalide' };
        }

        if (!data.mot_de_passe || data.mot_de_passe.length < 6) {
            return { isValid: false, error: 'Le mot de passe doit contenir au moins 6 caractères' };
        }

        if (!data.nom || data.nom.trim().length < 2) {
            return { isValid: false, error: 'Le nom doit contenir au moins 2 caractères' };
        }

        if (!data.prenom || data.prenom.trim().length < 2) {
            return { isValid: false, error: 'Le prénom doit contenir au moins 2 caractères' };
        }

        if (data.telephone && !this.isValidPhone(data.telephone)) {
            return { isValid: false, error: 'Numéro de téléphone invalide' };
        }

        return { isValid: true };
    }

    /**
     * Valider un email
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Valider un numéro de téléphone
     */
    isValidPhone(phone) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,15}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Traduire les erreurs d'authentification
     */
    translateAuthError(errorMessage) {
        const errorTranslations = {
            'Invalid login credentials': 'Email ou mot de passe incorrect',
            'Email not confirmed': 'Email non confirmé. Vérifiez votre boîte de réception.',
            'User already registered': 'Un compte existe déjà avec cet email',
            'Password should be at least 6 characters': 'Le mot de passe doit contenir au moins 6 caractères',
            'Unable to validate email address: invalid format': 'Format d\'email invalide',
            'Signup is disabled': 'L\'inscription est temporairement désactivée',
            'User not found': 'Utilisateur non trouvé',
            'Too many requests': 'Trop de tentatives. Veuillez réessayer plus tard.'
        };

        return errorTranslations[errorMessage] || errorMessage || 'Une erreur est survenue';
    }

    /**
     * Envoyer un événement d'authentification
     */
    dispatchAuthEvent(type, data = null) {
        const event = new CustomEvent('authStateChange', {
            detail: { type, data, user: this.currentUser, profile: this.currentProfile }
        });
        document.dispatchEvent(event);
    }

    /**
     * Vérifier la confirmation d'email
     */
    async checkEmailConfirmation() {
        try {
            if (!this.currentUser) {
                return { success: false, error: 'Aucun utilisateur connecté' };
            }

            // Vérifier si l'email est confirmé
            if (this.currentUser.email_confirmed_at) {
                return { success: true, confirmed: true };
            }

            // Vérifier la session actuelle
            if (this.authService) {
                const { success, user } = await this.authService.getUser();
                if (success && user && user.email_confirmed_at) {
                    this.currentUser = user;
                    return { success: true, confirmed: true };
                }
            }

            return { success: true, confirmed: false };

        } catch (error) {
            console.error('Erreur lors de la vérification de l\'email:', error);
            return { success: false, error: 'Erreur lors de la vérification' };
        }
    }

    /**
     * Obtenir la session actuelle
     */
    getSession() {
        return this.session;
    }

    /**
     * Rafraîchir la session
     */
    async refreshSession() {
        try {
            if (!this.authService) {
                return { success: false, error: 'Service d\'authentification non disponible' };
            }

            const { success, session } = await this.authService.getSession();
            
            if (success && session) {
                this.session = session;
                return { success: true, session };
            }

            return { success: false, error: 'Aucune session valide' };

        } catch (error) {
            console.error('Erreur lors du rafraîchissement de la session:', error);
            return { success: false, error: 'Erreur lors du rafraîchissement' };
        }
    }

    /**
     * Désactiver le compte
     */
    async deactivateAccount() {
        try {
            if (!this.currentUser || !this.databaseService) {
                return { success: false, error: 'Utilisateur non connecté ou service non disponible' };
            }

            // Mettre à jour le profil pour désactiver le compte
            const { success, error } = await this.databaseService.updateUserProfile(this.currentUser.id, {
                is_active: false
            });

            if (!success) {
                console.error('Erreur lors de la désactivation du compte:', error);
                return { success: false, error: 'Erreur lors de la désactivation du compte' };
            }

            // Déconnecter l'utilisateur
            await this.logout();

            return { success: true };

        } catch (error) {
            console.error('Erreur lors de la désactivation du compte:', error);
            return { success: false, error: 'Erreur serveur lors de la désactivation' };
        }
    }
}

// Exporter le module
window.AuthModule = AuthModule; 