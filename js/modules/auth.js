/**
 * Module d'authentification pour LivraisonP2P
 * Gère l'authentification, l'inscription, la connexion et les emails de confirmation
 */
class AuthModule {
    constructor() {
        this.supabase = window.supabaseClient;
        this.currentUser = null;
        this.currentProfile = null;
        this.isAuthenticated = false;
        this.session = null;
        
        // Initialiser l'état d'authentification
        this.initAuth();
        
        // Écouter les changements d'authentification
        this.supabase.auth.onAuthStateChange((event, session) => {
            this.handleAuthStateChange(event, session);
        });
    }

    /**
     * Initialiser l'état d'authentification
     */
    async initAuth() {
        try {
            const { data: { session } } = await this.supabase.auth.getSession();
            if (session) {
                await this.handleAuthStateChange('SIGNED_IN', session);
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
        if (!this.currentUser) return;
        
        try {
            const { data, error } = await this.supabase
                .from('profiles')
                .select('*')
                .eq('id', this.currentUser.id)
                .single();
            
            if (error) {
                console.error('Erreur lors du chargement du profil:', error);
                return;
            }
            
            this.currentProfile = data;
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

            // Créer le compte utilisateur
            const { data: authData, error: authError } = await this.supabase.auth.signUp({
                email: userData.email,
                password: userData.mot_de_passe,
                options: {
                    data: {
                        nom: userData.nom,
                        prenom: userData.prenom,
                        telephone: userData.telephone || null,
                        role: userData.role || 'client'
                    }
                }
            });

            if (authError) {
                console.error('Erreur lors de l\'inscription:', authError);
                return { 
                    success: false, 
                    error: this.translateAuthError(authError.message) 
                };
            }

            // Créer le profil utilisateur
            const profileData = {
                id: authData.user.id,
                email: userData.email,
                nom: userData.nom,
                prenom: userData.prenom,
                telephone: userData.telephone || null,
                role: userData.role || 'client',
                date_inscription: new Date().toISOString(),
                statut: 'en_attente_confirmation'
            };

            const { error: profileError } = await this.supabase
                .from('profiles')
                .insert([profileData]);

            if (profileError) {
                console.error('Erreur lors de la création du profil:', profileError);
                // Supprimer le compte auth si le profil n'a pas pu être créé
                await this.supabase.auth.admin.deleteUser(authData.user.id);
                return { 
                    success: false, 
                    error: 'Erreur lors de la création du profil utilisateur' 
                };
            }

            // Envoyer l'email de confirmation
            await this.sendConfirmationEmail(userData.email);

            return {
                success: true,
                user: authData.user,
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
            const { data, error } = await this.supabase.auth.signInWithPassword({
                email: email,
                password: password
            });

            if (error) {
                console.error('Erreur lors de la connexion:', error);
                return { 
                    success: false, 
                    error: this.translateAuthError(error.message) 
                };
            }

            // Vérifier si l'email est confirmé
            if (!data.user.email_confirmed_at) {
                // Déconnecter l'utilisateur
                await this.supabase.auth.signOut();
                
                return {
                    success: false,
                    error: 'Veuillez confirmer votre email avant de vous connecter. Vérifiez votre boîte de réception.',
                    requiresConfirmation: true
                };
            }

            // Charger le profil utilisateur
            await this.loadUserProfile();

            return {
                success: true,
                user: data.user,
                profile: this.currentProfile
            };

        } catch (error) {
            console.error('Erreur lors de la connexion:', error);
            return { 
                success: false, 
                error: 'Erreur serveur lors de la connexion' 
            };
        }
    }

    /**
     * Déconnexion utilisateur
     */
    async logout() {
        try {
            const { error } = await this.supabase.auth.signOut();
            
            if (error) {
                console.error('Erreur lors de la déconnexion:', error);
                return { success: false, error: 'Erreur lors de la déconnexion' };
            }

            return { success: true };
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
            return { success: false, error: 'Erreur serveur lors de la déconnexion' };
        }
    }

    /**
     * Envoyer un email de confirmation
     */
    async sendConfirmationEmail(email) {
        try {
            const { error } = await this.supabase.auth.resend({
                type: 'signup',
                email: email
            });

            if (error) {
                console.error('Erreur lors de l\'envoi de l\'email de confirmation:', error);
                return { success: false, error: 'Erreur lors de l\'envoi de l\'email' };
            }

            return { success: true };
        } catch (error) {
            console.error('Erreur lors de l\'envoi de l\'email de confirmation:', error);
            return { success: false, error: 'Erreur serveur' };
        }
    }

    /**
     * Envoyer un email de réinitialisation de mot de passe
     */
    async sendPasswordResetEmail(email) {
        try {
            const { error } = await this.supabase.auth.resetPasswordForEmail(email, {
                redirectTo: `${window.location.origin}/auth/reset-password.html`
            });

            if (error) {
                console.error('Erreur lors de l\'envoi de l\'email de réinitialisation:', error);
                return { success: false, error: 'Erreur lors de l\'envoi de l\'email' };
            }

            return { success: true };
        } catch (error) {
            console.error('Erreur lors de l\'envoi de l\'email de réinitialisation:', error);
            return { success: false, error: 'Erreur serveur' };
        }
    }

    /**
     * Réinitialiser le mot de passe
     */
    async resetPassword(newPassword) {
        try {
            const { error } = await this.supabase.auth.updateUser({
                password: newPassword
            });

            if (error) {
                console.error('Erreur lors de la réinitialisation du mot de passe:', error);
                return { success: false, error: this.translateAuthError(error.message) };
            }

            return { success: true };
        } catch (error) {
            console.error('Erreur lors de la réinitialisation du mot de passe:', error);
            return { success: false, error: 'Erreur serveur' };
        }
    }

    /**
     * Connexion sociale
     */
    async socialLogin(provider) {
        try {
            const { data, error } = await this.supabase.auth.signInWithOAuth({
                provider: provider,
                options: {
                    redirectTo: `${window.location.origin}/auth/callback.html`
                }
            });

            if (error) {
                console.error('Erreur lors de la connexion sociale:', error);
                return { success: false, error: this.translateAuthError(error.message) };
            }

            return { success: true, data };
        } catch (error) {
            console.error('Erreur lors de la connexion sociale:', error);
            return { success: false, error: 'Erreur serveur' };
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
        if (!this.currentUser) {
            return { success: false, error: 'Utilisateur non connecté' };
        }

        try {
            const { error } = await this.supabase
                .from('profiles')
                .update(profileData)
                .eq('id', this.currentUser.id);

            if (error) {
                console.error('Erreur lors de la mise à jour du profil:', error);
                return { success: false, error: 'Erreur lors de la mise à jour du profil' };
            }

            // Recharger le profil
            await this.loadUserProfile();

            return { success: true, profile: this.currentProfile };
        } catch (error) {
            console.error('Erreur lors de la mise à jour du profil:', error);
            return { success: false, error: 'Erreur serveur' };
        }
    }

    /**
     * Validation des données d'inscription
     */
    validateRegistrationData(data) {
        // Email
        if (!data.email || !this.isValidEmail(data.email)) {
            return { isValid: false, error: 'Adresse email invalide' };
        }

        // Mot de passe
        if (!data.mot_de_passe || data.mot_de_passe.length < 6) {
            return { isValid: false, error: 'Le mot de passe doit contenir au moins 6 caractères' };
        }

        // Nom et prénom
        if (!data.nom || data.nom.trim().length < 2) {
            return { isValid: false, error: 'Le nom doit contenir au moins 2 caractères' };
        }

        if (!data.prenom || data.prenom.trim().length < 2) {
            return { isValid: false, error: 'Le prénom doit contenir au moins 2 caractères' };
        }

        // Téléphone (optionnel mais validé si fourni)
        if (data.telephone && !this.isValidPhone(data.telephone)) {
            return { isValid: false, error: 'Numéro de téléphone invalide' };
        }

        return { isValid: true };
    }

    /**
     * Validation d'email
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validation de téléphone
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
            'Email not confirmed': 'Veuillez confirmer votre email avant de vous connecter',
            'User already registered': 'Un compte existe déjà avec cette adresse email',
            'Password should be at least 6 characters': 'Le mot de passe doit contenir au moins 6 caractères',
            'Unable to validate email address: invalid format': 'Format d\'email invalide',
            'Signup is disabled': 'L\'inscription est temporairement désactivée',
            'Too many requests': 'Trop de tentatives. Veuillez réessayer plus tard',
            'User not found': 'Aucun compte trouvé avec cette adresse email',
            'Email rate limit exceeded': 'Trop de demandes d\'email. Veuillez réessayer plus tard'
        };

        return errorTranslations[errorMessage] || errorMessage;
    }

    /**
     * Envoyer un événement d'authentification
     */
    dispatchAuthEvent(type, data = null) {
        const event = new CustomEvent('authStateChanged', {
            detail: { type, data, user: this.currentUser, profile: this.currentProfile }
        });
        document.dispatchEvent(event);
    }

    /**
     * Vérifier le statut de confirmation de l'email
     */
    async checkEmailConfirmation() {
        if (!this.currentUser) {
            return { confirmed: false, error: 'Utilisateur non connecté' };
        }

        try {
            const { data: { user }, error } = await this.supabase.auth.getUser();
            
            if (error) {
                return { confirmed: false, error: 'Erreur lors de la vérification' };
            }

            return { 
                confirmed: !!user.email_confirmed_at,
                confirmedAt: user.email_confirmed_at 
            };
        } catch (error) {
            console.error('Erreur lors de la vérification de confirmation:', error);
            return { confirmed: false, error: 'Erreur serveur' };
        }
    }

    /**
     * Obtenir les informations de session
     */
    getSession() {
        return this.session;
    }

    /**
     * Rafraîchir la session
     */
    async refreshSession() {
        try {
            const { data, error } = await this.supabase.auth.refreshSession();
            
            if (error) {
                console.error('Erreur lors du rafraîchissement de session:', error);
                return { success: false, error };
            }

            return { success: true, session: data.session };
        } catch (error) {
            console.error('Erreur lors du rafraîchissement de session:', error);
            return { success: false, error };
        }
    }
}

// Exporter le module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthModule;
} else {
    window.AuthModule = AuthModule;
} 