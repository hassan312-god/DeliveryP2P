<?php $page_title = 'Connexion - LivraisonP2P'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <!-- En-tête -->
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Connexion
                        </h2>
                        <p class="text-muted">Accédez à votre compte LivraisonP2P</p>
                    </div>
                    
                    <!-- Formulaire de connexion -->
                    <form id="loginForm" novalidate>
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>
                                Adresse email
                            </label>
                            <input type="email" 
                                   class="form-control form-control-lg" 
                                   id="email" 
                                   name="email" 
                                   placeholder="votre@email.com"
                                   required>
                            <div class="invalid-feedback">
                                Veuillez saisir une adresse email valide.
                            </div>
                        </div>
                        
                        <!-- Mot de passe -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>
                                Mot de passe
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control form-control-lg" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Votre mot de passe"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="togglePassword">
                                    <i class="fas fa-eye" id="passwordIcon"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Le mot de passe est requis.
                            </div>
                        </div>
                        
                        <!-- Options -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                                <label class="form-check-label" for="rememberMe">
                                    Se souvenir de moi
                                </label>
                            </div>
                            <a href="/forgot-password" class="text-decoration-none">
                                Mot de passe oublié ?
                            </a>
                        </div>
                        
                        <!-- Bouton de connexion -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="loginBtn">
                            <span class="spinner-border spinner-border-sm me-2" id="loginSpinner" style="display: none;"></span>
                            <i class="fas fa-sign-in-alt me-2" id="loginIcon"></i>
                            Se connecter
                        </button>
                        
                        <!-- Séparateur -->
                        <div class="text-center mb-3">
                            <span class="text-muted">ou</span>
                        </div>
                        
                        <!-- Lien d'inscription -->
                        <div class="text-center">
                            <p class="mb-0">
                                Pas encore de compte ? 
                                <a href="/register" class="text-decoration-none fw-bold">
                                    S'inscrire maintenant
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Informations supplémentaires -->
            <div class="text-center mt-4">
                <p class="text-muted small">
                    En vous connectant, vous acceptez nos 
                    <a href="/terms" class="text-decoration-none">conditions d'utilisation</a> 
                    et notre 
                    <a href="/privacy" class="text-decoration-none">politique de confidentialité</a>.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');
    const loginBtn = document.getElementById('loginBtn');
    const loginSpinner = document.getElementById('loginSpinner');
    const loginIcon = document.getElementById('loginIcon');
    
    // Toggle password visibility
    togglePasswordBtn.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        passwordIcon.classList.toggle('fa-eye');
        passwordIcon.classList.toggle('fa-eye-slash');
    });
    
    // Validation en temps réel
    emailInput.addEventListener('input', function() {
        validateEmail(this);
    });
    
    passwordInput.addEventListener('input', function() {
        validatePassword(this);
    });
    
    // Soumission du formulaire
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation
        const isEmailValid = validateEmail(emailInput);
        const isPasswordValid = validatePassword(passwordInput);
        
        if (!isEmailValid || !isPasswordValid) {
            showToast('Veuillez corriger les erreurs dans le formulaire.', 'error');
            return;
        }
        
        // Préparer les données
        const formData = {
            email: emailInput.value.trim(),
            password: passwordInput.value,
            remember_me: document.getElementById('rememberMe').checked
        };
        
        // Afficher le chargement
        setLoadingState(true);
        
        // Envoyer la requête
        fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Connexion réussie ! Redirection...', 'success');
                
                // Rediriger selon le rôle
                setTimeout(() => {
                    if (data.user.role === 'admin') {
                        window.location.href = '/admin/dashboard';
                    } else if (data.user.role === 'courier') {
                        window.location.href = '/courier/dashboard';
                    } else {
                        window.location.href = '/expeditor/dashboard';
                    }
                }, 1500);
            } else {
                showToast(data.message || 'Erreur lors de la connexion.', 'error');
                setLoadingState(false);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur de connexion au serveur.', 'error');
            setLoadingState(false);
        });
    });
    
    // Fonctions de validation
    function validateEmail(input) {
        const email = input.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!email) {
            input.classList.add('is-invalid');
            return false;
        } else if (!emailRegex.test(email)) {
            input.classList.add('is-invalid');
            return false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        }
    }
    
    function validatePassword(input) {
        const password = input.value;
        
        if (!password) {
            input.classList.add('is-invalid');
            return false;
        } else if (password.length < 6) {
            input.classList.add('is-invalid');
            return false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        }
    }
    
    // Fonction pour gérer l'état de chargement
    function setLoadingState(loading) {
        if (loading) {
            loginBtn.disabled = true;
            loginSpinner.style.display = 'inline-block';
            loginIcon.style.display = 'none';
        } else {
            loginBtn.disabled = false;
            loginSpinner.style.display = 'none';
            loginIcon.style.display = 'inline-block';
        }
    }
    
    // Pré-remplir l'email si présent dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const emailParam = urlParams.get('email');
    if (emailParam) {
        emailInput.value = emailParam;
        validateEmail(emailInput);
    }
});
</script> 