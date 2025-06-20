/**
 * Service Supabase pour LivraisonP2P
 * Gère la connexion à Supabase et les opérations de base
 */

// Configuration Supabase
const SUPABASE_CONFIG = {
    url: window.SUPABASE_URL || 'https://your-project.supabase.co',
    anonKey: window.SUPABASE_ANON_KEY || 'your-anon-key',
    serviceRoleKey: window.SUPABASE_SERVICE_ROLE_KEY || 'your-service-role-key'
};

// Initialisation du client Supabase
let supabaseClient = null;

/**
 * Initialiser le client Supabase
 */
function initializeSupabase() {
    try {
        if (typeof createClient !== 'undefined') {
            supabaseClient = createClient(SUPABASE_CONFIG.url, SUPABASE_CONFIG.anonKey, {
                auth: {
                    autoRefreshToken: true,
                    persistSession: true,
                    detectSessionInUrl: true,
                    flowType: 'pkce'
                },
                realtime: {
                    params: {
                        eventsPerSecond: 10
                    }
                },
                global: {
                    headers: {
                        'X-Client-Info': 'livraisonp2p-web'
                    }
                }
            });

            // Configurer les listeners d'authentification
            setupAuthListeners();
            
            console.log('Client Supabase initialisé avec succès');
            return supabaseClient;
        } else {
            console.error('Supabase client non disponible');
            return null;
        }
    } catch (error) {
        console.error('Erreur lors de l\'initialisation de Supabase:', error);
        return null;
    }
}

/**
 * Configurer les listeners d'authentification
 */
function setupAuthListeners() {
    if (!supabaseClient) return;

    // Écouter les changements d'état d'authentification
    supabaseClient.auth.onAuthStateChange((event, session) => {
        console.log('Changement d\'état auth Supabase:', event, session);
        
        // Envoyer un événement personnalisé
        const authEvent = new CustomEvent('supabaseAuthStateChange', {
            detail: { event, session }
        });
        document.dispatchEvent(authEvent);
    });

    // Écouter les erreurs d'authentification
    supabaseClient.auth.onError((error) => {
        console.error('Erreur d\'authentification Supabase:', error);
        
        const errorEvent = new CustomEvent('supabaseAuthError', {
            detail: { error }
        });
        document.dispatchEvent(errorEvent);
    });
}

/**
 * Obtenir le client Supabase
 */
function getSupabaseClient() {
    if (!supabaseClient) {
        supabaseClient = initializeSupabase();
    }
    return supabaseClient;
}

/**
 * Service d'authentification Supabase
 */
class SupabaseAuthService {
    constructor() {
        this.client = getSupabaseClient();
    }

    /**
     * Inscription avec email de confirmation
     */
    async signUp(email, password, userData = {}) {
        try {
            const { data, error } = await this.client.auth.signUp({
                email,
                password,
                options: {
                    data: userData,
                    emailRedirectTo: `${window.location.origin}/auth/email-confirmation.html`
                }
            });

            if (error) {
                console.error('Erreur inscription Supabase:', error);
                return { success: false, error };
            }

            // Créer le profil utilisateur
            if (data.user) {
                await this.createUserProfile(data.user, userData);
            }

            return { success: true, data };
        } catch (error) {
            console.error('Erreur inscription:', error);
            return { success: false, error };
        }
    }

    /**
     * Connexion
     */
    async signIn(email, password) {
        try {
            const { data, error } = await this.client.auth.signInWithPassword({
                email,
                password
            });

            if (error) {
                console.error('Erreur connexion Supabase:', error);
                return { success: false, error };
            }

            return { success: true, data };
        } catch (error) {
            console.error('Erreur connexion:', error);
            return { success: false, error };
        }
    }

    /**
     * Déconnexion
     */
    async signOut() {
        try {
            const { error } = await this.client.auth.signOut();
            
            if (error) {
                console.error('Erreur déconnexion Supabase:', error);
                return { success: false, error };
            }

            return { success: true };
        } catch (error) {
            console.error('Erreur déconnexion:', error);
            return { success: false, error };
        }
    }

    /**
     * Connexion sociale
     */
    async signInWithOAuth(provider, options = {}) {
        try {
            const { data, error } = await this.client.auth.signInWithOAuth({
                provider,
                options: {
                    redirectTo: `${window.location.origin}/auth/callback.html`,
                    ...options
                }
            });

            if (error) {
                console.error('Erreur connexion sociale Supabase:', error);
                return { success: false, error };
            }

            return { success: true, data };
        } catch (error) {
            console.error('Erreur connexion sociale:', error);
            return { success: false, error };
        }
    }

    /**
     * Envoyer email de confirmation
     */
    async resendConfirmationEmail(email) {
        try {
            const { error } = await this.client.auth.resend({
                type: 'signup',
                email,
                options: {
                    emailRedirectTo: `${window.location.origin}/auth/email-confirmation.html`
                }
            });

            if (error) {
                console.error('Erreur envoi email confirmation Supabase:', error);
                return { success: false, error };
            }

            return { success: true };
        } catch (error) {
            console.error('Erreur envoi email confirmation:', error);
            return { success: false, error };
        }
    }

    /**
     * Envoyer email de réinitialisation
     */
    async sendPasswordResetEmail(email) {
        try {
            const { error } = await this.client.auth.resetPasswordForEmail(email, {
                redirectTo: `${window.location.origin}/auth/reset-password.html`
            });

            if (error) {
                console.error('Erreur envoi email réinitialisation Supabase:', error);
                return { success: false, error };
            }

            return { success: true };
        } catch (error) {
            console.error('Erreur envoi email réinitialisation:', error);
            return { success: false, error };
        }
    }

    /**
     * Réinitialiser le mot de passe
     */
    async resetPassword(newPassword) {
        try {
            const { error } = await this.client.auth.updateUser({
                password: newPassword
            });

            if (error) {
                console.error('Erreur réinitialisation mot de passe Supabase:', error);
                return { success: false, error };
            }

            return { success: true };
        } catch (error) {
            console.error('Erreur réinitialisation mot de passe:', error);
            return { success: false, error };
        }
    }

    /**
     * Vérifier le token OTP
     */
    async verifyOtp(token, type) {
        try {
            const { data, error } = await this.client.auth.verifyOtp({
                token_hash: token,
                type: type
            });

            if (error) {
                console.error('Erreur vérification OTP Supabase:', error);
                return { success: false, error };
            }

            return { success: true, data };
        } catch (error) {
            console.error('Erreur vérification OTP:', error);
            return { success: false, error };
        }
    }

    /**
     * Obtenir la session actuelle
     */
    async getSession() {
        try {
            const { data, error } = await this.client.auth.getSession();
            
            if (error) {
                console.error('Erreur récupération session Supabase:', error);
                return { success: false, error };
            }

            return { success: true, data };
        } catch (error) {
            console.error('Erreur récupération session:', error);
            return { success: false, error };
        }
    }

    /**
     * Obtenir l'utilisateur actuel
     */
    async getUser() {
        try {
            const { data, error } = await this.client.auth.getUser();
            
            if (error) {
                console.error('Erreur récupération utilisateur Supabase:', error);
                return { success: false, error };
            }

            return { success: true, data };
        } catch (error) {
            console.error('Erreur récupération utilisateur:', error);
            return { success: false, error };
        }
    }

    /**
     * Créer le profil utilisateur
     */
    async createUserProfile(user, userData = {}) {
        try {
            const profileData = {
                id: user.id,
                email: user.email,
                nom: userData.nom || user.user_metadata?.nom || 'Utilisateur',
                prenom: userData.prenom || user.user_metadata?.prenom || 'Nouveau',
                telephone: userData.telephone || user.user_metadata?.telephone || null,
                role: userData.role || 'client',
                date_inscription: new Date().toISOString(),
                statut: 'en_attente_confirmation',
                email_confirme: false,
                provider: user.app_metadata?.provider || 'email'
            };

            const { error } = await this.client
                .from('profiles')
                .insert([profileData]);

            if (error) {
                console.error('Erreur création profil Supabase:', error);
                return { success: false, error };
            }

            return { success: true };
        } catch (error) {
            console.error('Erreur création profil:', error);
            return { success: false, error };
        }
    }

    /**
     * Mettre à jour le profil utilisateur
     */
    async updateUserProfile(userId, profileData) {
        try {
            const { error } = await this.client
                .from('profiles')
                .update(profileData)
                .eq('id', userId);

            if (error) {
                console.error('Erreur mise à jour profil Supabase:', error);
                return { success: false, error };
            }

            return { success: true };
        } catch (error) {
            console.error('Erreur mise à jour profil:', error);
            return { success: false, error };
        }
    }

    /**
     * Obtenir le profil utilisateur
     */
    async getUserProfile(userId) {
        try {
            const { data, error } = await this.client
                .from('profiles')
                .select('*')
                .eq('id', userId)
                .single();

            if (error) {
                console.error('Erreur récupération profil Supabase:', error);
                return { success: false, error };
            }

            return { success: true, data };
        } catch (error) {
            console.error('Erreur récupération profil:', error);
            return { success: false, error };
        }
    }
}

/**
 * Service de base de données Supabase
 */
class SupabaseDatabaseService {
    constructor() {
        this.client = getSupabaseClient();
    }

    /**
     * Exécuter une requête SQL
     */
    async executeQuery(query, params = []) {
        try {
            const { data, error } = await this.client.rpc('execute_sql', {
                query_text: query,
                query_params: params
            });

            if (error) {
                console.error('Erreur exécution requête Supabase:', error);
                return { success: false, error };
            }

            return { success: true, data };
        } catch (error) {
            console.error('Erreur exécution requête:', error);
            return { success: false, error };
        }
    }

    /**
     * Insérer des données
     */
    async insert(table, data) {
        try {
            const { data: result, error } = await this.client
                .from(table)
                .insert(data)
                .select();

            if (error) {
                console.error(`Erreur insertion ${table} Supabase:`, error);
                return { success: false, error };
            }

            return { success: true, data: result };
        } catch (error) {
            console.error(`Erreur insertion ${table}:`, error);
            return { success: false, error };
        }
    }

    /**
     * Mettre à jour des données
     */
    async update(table, data, conditions) {
        try {
            let query = this.client.from(table).update(data);
            
            // Appliquer les conditions
            Object.keys(conditions).forEach(key => {
                query = query.eq(key, conditions[key]);
            });

            const { data: result, error } = await query.select();

            if (error) {
                console.error(`Erreur mise à jour ${table} Supabase:`, error);
                return { success: false, error };
            }

            return { success: true, data: result };
        } catch (error) {
            console.error(`Erreur mise à jour ${table}:`, error);
            return { success: false, error };
        }
    }

    /**
     * Supprimer des données
     */
    async delete(table, conditions) {
        try {
            let query = this.client.from(table).delete();
            
            // Appliquer les conditions
            Object.keys(conditions).forEach(key => {
                query = query.eq(key, conditions[key]);
            });

            const { error } = await query;

            if (error) {
                console.error(`Erreur suppression ${table} Supabase:`, error);
                return { success: false, error };
            }

            return { success: true };
        } catch (error) {
            console.error(`Erreur suppression ${table}:`, error);
            return { success: false, error };
        }
    }

    /**
     * Sélectionner des données
     */
    async select(table, columns = '*', conditions = {}) {
        try {
            let query = this.client.from(table).select(columns);
            
            // Appliquer les conditions
            Object.keys(conditions).forEach(key => {
                query = query.eq(key, conditions[key]);
            });

            const { data, error } = await query;

            if (error) {
                console.error(`Erreur sélection ${table} Supabase:`, error);
                return { success: false, error };
            }

            return { success: true, data };
        } catch (error) {
            console.error(`Erreur sélection ${table}:`, error);
            return { success: false, error };
        }
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function() {
    initializeSupabase();
});

// Exporter les services
window.supabaseClient = getSupabaseClient();
window.SupabaseAuthService = SupabaseAuthService;
window.SupabaseDatabaseService = SupabaseDatabaseService;

// Export pour Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeSupabase,
        getSupabaseClient,
        SupabaseAuthService,
        SupabaseDatabaseService
    };
} 