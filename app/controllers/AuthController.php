<?php

namespace App\Controllers;

use App\Core\Session;
use App\Services\SupabaseService;
use App\Services\QRCodeService;
use App\Models\User;

class AuthController
{
    private $session;
    private $supabaseService;
    private $qrCodeService;
    private $userModel;

    public function __construct()
    {
        $this->session = Session::getInstance();
        $this->supabaseService = new SupabaseService();
        $this->qrCodeService = new QRCodeService();
        $this->userModel = new User();
    }

    /**
     * Page de connexion
     */
    public function showLogin()
    {
        // Si déjà connecté, rediriger vers le tableau de bord approprié
        if ($this->session->isAuthenticated()) {
            $this->redirectToDashboard();
        }

        return include __DIR__ . '/../../public/views/auth/login.php';
    }

    /**
     * Traitement de la connexion
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }
        
        // Récupérer les données du formulaire
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Validation des données
        if (empty($email) || empty($password)) {
            flash('error', 'Email et mot de passe requis');
            redirect('/login');
            return;
        }
        
        try {
            // Authentification via Supabase
            $authResult = $this->supabaseService->signIn($email, $password);
            
            if (isset($authResult['error'])) {
                flash('error', 'Email ou mot de passe incorrect');
                redirect('/login');
                return;
            }
            
            // Récupérer les informations de l'utilisateur
            $user = $this->userModel->findByEmail($email);
            
            if (!$user) {
                flash('error', 'Utilisateur non trouvé');
                redirect('/login');
                return;
            }
            
            // Créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_first_name'] = $user['first_name'];
            $_SESSION['user_last_name'] = $user['last_name'];
            $_SESSION['access_token'] = $authResult['access_token'] ?? null;
            $_SESSION['refresh_token'] = $authResult['refresh_token'] ?? null;
            
            // Rediriger selon le rôle
            if ($user['role'] === 'admin') {
                redirect('/admin/dashboard');
            } elseif ($user['role'] === 'courier') {
                redirect('/courier/dashboard');
            } else {
                redirect('/expeditor/dashboard');
            }
            
        } catch (Exception $e) {
            error_log('Erreur de connexion: ' . $e->getMessage());
            flash('error', 'Erreur lors de la connexion');
            redirect('/login');
        }
    }

    /**
     * API de connexion (pour AJAX)
     */
    public function apiLogin()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            return [
                'success' => false,
                'error' => 'Email et mot de passe requis'
            ];
        }

        // Authentification via Supabase
        $authResult = $this->supabaseService->authenticateUser($email, $password);

        if (!$authResult || isset($authResult['error'])) {
            return [
                'success' => false,
                'error' => 'Email ou mot de passe incorrect'
            ];
        }

        // Récupérer les données utilisateur
        $user = $this->supabaseService->getUserByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Utilisateur non trouvé'
            ];
        }

        $userData = $user[0];

        // Vérifier le mot de passe
        if (!password_verify($password, $userData['password_hash'])) {
            return [
                'success' => false,
                'error' => 'Email ou mot de passe incorrect'
            ];
        }

        // Créer la session utilisateur
        $this->session->setUser($userData);

        return [
            'success' => true,
            'user' => [
                'id' => $userData['id'],
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ],
            'redirect' => $this->getDashboardUrl($userData['role'])
        ];
    }

    /**
     * Page d'inscription
     */
    public function showRegister()
    {
        // Si déjà connecté, rediriger vers le tableau de bord approprié
        if ($this->session->isAuthenticated()) {
            $this->redirectToDashboard();
        }

        return include __DIR__ . '/../../public/views/auth/register.php';
    }

    /**
     * Traitement de l'inscription
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }
        
        // Récupérer les données du formulaire
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $phoneNumber = $_POST['phone_number'] ?? '';
        $role = $_POST['role'] ?? 'expeditor';
        
        // Validation des données
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            flash('error', 'Tous les champs obligatoires doivent être remplis');
            redirect('/register');
            return;
        }
        
        if ($password !== $confirmPassword) {
            flash('error', 'Les mots de passe ne correspondent pas');
            redirect('/register');
            return;
        }
        
        if (strlen($password) < 8) {
            flash('error', 'Le mot de passe doit contenir au moins 8 caractères');
            redirect('/register');
            return;
        }
        
        if (!in_array($role, ['expeditor', 'courier'])) {
            flash('error', 'Rôle invalide');
            redirect('/register');
            return;
        }
        
        try {
            // Vérifier si l'utilisateur existe déjà
            if ($this->userModel->exists($email)) {
                flash('error', 'Un compte avec cet email existe déjà');
                redirect('/register');
                return;
            }
            
            // Créer l'utilisateur via Supabase Auth
            $authResult = $this->supabaseService->signUp($email, $password, [
                'data' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'role' => $role
                ]
            ]);
            
            if (isset($authResult['error'])) {
                flash('error', 'Erreur lors de la création du compte');
                redirect('/register');
                return;
            }
            
            // Créer l'utilisateur dans la table users
            $userData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'phone_number' => $phoneNumber,
                'role' => $role,
                'is_available' => $role === 'courier' ? true : null,
            ];
            
            $userResult = $this->userModel->create($userData);
            
            if (isset($userResult['error'])) {
                flash('error', 'Erreur lors de la création du profil utilisateur');
                redirect('/register');
                return;
            }
            
            flash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
            redirect('/login');
            
        } catch (Exception $e) {
            error_log('Erreur d\'inscription: ' . $e->getMessage());
            flash('error', 'Erreur lors de l\'inscription');
            redirect('/register');
        }
    }

    /**
     * API d'inscription (pour AJAX)
     */
    public function apiRegister()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $firstName = filter_var($input['first_name'] ?? '', FILTER_SANITIZE_STRING);
        $lastName = filter_var($input['last_name'] ?? '', FILTER_SANITIZE_STRING);
        $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone = filter_var($input['phone_number'] ?? '', FILTER_SANITIZE_STRING);
        $password = $input['password'] ?? '';
        $passwordConfirm = $input['password_confirm'] ?? '';
        $role = filter_var($input['role'] ?? '', FILTER_SANITIZE_STRING);

        // Validation
        $errors = $this->validateRegistrationData($firstName, $lastName, $email, $password, $passwordConfirm, $role);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Vérifier si l'email existe déjà
        $existingUser = $this->supabaseService->getUserByEmail($email);
        if ($existingUser) {
            return [
                'success' => false,
                'errors' => ['email' => 'Cet email est déjà utilisé']
            ];
        }

        // Hacher le mot de passe
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Créer l'utilisateur dans Supabase
        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone_number' => $phone,
            'password_hash' => $passwordHash,
            'role' => $role
        ];

        $result = $this->supabaseService->post('users', $userData);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la création du compte'
            ];
        }

        // Créer la session utilisateur
        $userData['id'] = $result[0]['id'];
        $this->session->setUser($userData);

        return [
            'success' => true,
            'user' => [
                'id' => $userData['id'],
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ],
            'redirect' => $this->getDashboardUrl($userData['role'])
        ];
    }

    /**
     * Déconnexion
     */
    public function logout()
    {
        // Déconnecter de Supabase si un token existe
        if (isset($_SESSION['access_token'])) {
            try {
                $this->supabaseService->signOut($_SESSION['access_token']);
            } catch (Exception $e) {
                error_log('Erreur lors de la déconnexion Supabase: ' . $e->getMessage());
            }
        }
        
        // Détruire la session
        session_destroy();
        
        // Rediriger vers la page d'accueil
        redirect('/');
    }

    /**
     * API de déconnexion
     */
    public function apiLogout()
    {
        $this->session->logout();
        return [
            'success' => true,
            'message' => 'Déconnexion réussie'
        ];
    }

    /**
     * Validation des données d'inscription
     */
    private function validateRegistrationData($firstName, $lastName, $email, $password, $passwordConfirm, $role)
    {
        $errors = [];

        // Validation du prénom
        if (empty($firstName) || strlen($firstName) < 2) {
            $errors['first_name'] = 'Le prénom doit contenir au moins 2 caractères';
        }

        // Validation du nom
        if (empty($lastName) || strlen($lastName) < 2) {
            $errors['last_name'] = 'Le nom doit contenir au moins 2 caractères';
        }

        // Validation de l'email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide';
        }

        // Validation du mot de passe
        if (empty($password) || strlen($password) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        }

        // Validation de la confirmation du mot de passe
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas';
        }

        // Validation du rôle
        $validRoles = ['expeditor', 'courier'];
        if (!in_array($role, $validRoles)) {
            $errors['role'] = 'Rôle invalide';
        }

        return $errors;
    }

    /**
     * Rediriger vers le tableau de bord approprié
     */
    private function redirectToDashboard()
    {
        $role = $this->session->getUserRole();
        $dashboardUrl = $this->getDashboardUrl($role);
        header('Location: ' . $dashboardUrl);
        exit;
    }

    /**
     * Obtenir l'URL du tableau de bord selon le rôle
     */
    private function getDashboardUrl($role)
    {
        switch ($role) {
            case 'expeditor':
                return '/expeditor/dashboard';
            case 'courier':
                return '/courier/dashboard';
            case 'admin':
                return '/admin/dashboard';
            default:
                return '/';
        }
    }
} 