<?php

namespace App\Core;

class Session
{
    private static $instance = null;

    private function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Démarre une session sécurisée
     */
    public function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuration sécurisée de la session
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Régénérer l'ID de session pour éviter la fixation de session
            if (!isset($_SESSION['initialized'])) {
                session_regenerate_id(true);
                $_SESSION['initialized'] = true;
            }
        }
    }

    /**
     * Définit une valeur en session
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Récupère une valeur de session
     */
    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Vérifie si une clé existe en session
     */
    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Supprime une valeur de session
     */
    public function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Récupère et supprime une valeur de session
     */
    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }

    /**
     * Définit un message flash (disponible pour la prochaine requête)
     */
    public function flash($key, $value)
    {
        $_SESSION['flash'][$key] = $value;
    }

    /**
     * Récupère un message flash
     */
    public function getFlash($key, $default = null)
    {
        $value = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    /**
     * Vérifie s'il y a des messages flash
     */
    public function hasFlash($key = null)
    {
        if ($key === null) {
            return !empty($_SESSION['flash']);
        }
        return isset($_SESSION['flash'][$key]);
    }

    /**
     * Définit les données utilisateur en session
     */
    public function setUser($userData)
    {
        $_SESSION['user'] = $userData;
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_role'] = $userData['role'];
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
    }

    /**
     * Récupère les données utilisateur
     */
    public function getUser()
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Récupère l'ID utilisateur
     */
    public function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Récupère le rôle utilisateur
     */
    public function getUserRole()
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Vérifie si l'utilisateur est authentifié
     */
    public function isAuthenticated()
    {
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public function hasRole($role)
    {
        return $this->getUserRole() === $role;
    }

    /**
     * Vérifie si l'utilisateur a un des rôles spécifiés
     */
    public function hasAnyRole($roles)
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array($this->getUserRole(), $roles);
    }

    /**
     * Déconnecte l'utilisateur
     */
    public function logout()
    {
        // Supprimer toutes les données de session
        session_unset();
        session_destroy();
        
        // Supprimer le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
    }

    /**
     * Régénère l'ID de session
     */
    public function regenerate()
    {
        session_regenerate_id(true);
    }

    /**
     * Vérifie si la session a expiré
     */
    public function isExpired($maxLifetime = 3600)
    {
        if (!isset($_SESSION['login_time'])) {
            return true;
        }
        
        return (time() - $_SESSION['login_time']) > $maxLifetime;
    }

    /**
     * Met à jour le temps de connexion
     */
    public function updateLoginTime()
    {
        $_SESSION['login_time'] = time();
    }

    /**
     * Définit un token CSRF
     */
    public function setCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Récupère le token CSRF
     */
    public function getCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $this->setCSRFToken();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valide un token CSRF
     */
    public function validateCSRFToken($token)
    {
        return hash_equals($this->getCSRFToken(), $token);
    }

    /**
     * Définit une erreur
     */
    public function setError($key, $message)
    {
        $_SESSION['errors'][$key] = $message;
    }

    /**
     * Récupère une erreur
     */
    public function getError($key)
    {
        $error = $_SESSION['errors'][$key] ?? null;
        unset($_SESSION['errors'][$key]);
        return $error;
    }

    /**
     * Récupère toutes les erreurs
     */
    public function getErrors()
    {
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);
        return $errors;
    }

    /**
     * Vérifie s'il y a des erreurs
     */
    public function hasErrors()
    {
        return !empty($_SESSION['errors']);
    }

    /**
     * Définit une donnée d'ancien formulaire
     */
    public function setOld($key, $value)
    {
        $_SESSION['old'][$key] = $value;
    }

    /**
     * Récupère une donnée d'ancien formulaire
     */
    public function getOld($key, $default = '')
    {
        $value = $_SESSION['old'][$key] ?? $default;
        unset($_SESSION['old'][$key]);
        return $value;
    }

    /**
     * Récupère toutes les données d'ancien formulaire
     */
    public function getOldData()
    {
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        return $old;
    }

    /**
     * Définit des données d'ancien formulaire depuis un tableau
     */
    public function setOldData($data)
    {
        $_SESSION['old'] = $data;
    }

    /**
     * Nettoie les données temporaires de session
     */
    public function cleanup()
    {
        // Supprimer les messages flash
        unset($_SESSION['flash']);
        
        // Supprimer les erreurs
        unset($_SESSION['errors']);
        
        // Supprimer les données d'ancien formulaire
        unset($_SESSION['old']);
    }

    /**
     * Récupère toutes les données de session (pour debug)
     */
    public function all()
    {
        return $_SESSION;
    }

    /**
     * Supprime toutes les données de session
     */
    public function clear()
    {
        session_unset();
    }
} 