<?php $page_title = 'Inscription - LivraisonP2P'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <!-- En-tête -->
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">
                            <i class="fas fa-user-plus me-2"></i>
                            Créer un compte
                        </h2>
                        <p class="text-muted">Rejoignez la communauté LivraisonP2P</p>
                    </div>
                    
                    <!-- Formulaire d'inscription -->
                    <form id="registerForm" novalidate>
                        <!-- Informations personnelles -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label">
                                        <i class="fas fa-user me-1"></i>
                                        Prénom *
                                    </label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="firstName" 
                                           name="first_name" 
                                           placeholder="Votre prénom"
                                           required>
                                    <div class="invalid-feedback">
                                        Le prénom est requis.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lastName" class="form-label">
                                        <i class="fas fa-user me-1"></i>
                                        Nom *
                                    </label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="lastName" 
                                           name="last_name" 
                                           placeholder="Votre nom"
                                           required>
                                    <div class="invalid-feedback">
                                        Le nom est requis.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>
                                Adresse email *
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
                        
                        <!-- Téléphone -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone me-1"></i>
                                Numéro de téléphone *
                            </label>
                            <input type="tel" 
                                   class="form-control form-control-lg" 
                                   id="phone" 
                                   name="phone" 
                                   placeholder="06 12 34 56 78"
                                   required>
                            <div class="invalid-feedback">
                                Veuillez saisir un numéro de téléphone valide.
                            </div>
                        </div>
                        
                        <!-- Rôle -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-users me-1"></i>
                                Je souhaite *
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check border rounded p-3 h-100">
                                        <input class="form-check-input" type="radio" name="role" id="roleExpeditor" value="expeditor" required>
                                        <label class="form-check-label" for="roleExpeditor">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-box fa-2x text-primary me-3"></i>
                                                <div>
                                                    <strong>Expédier des colis</strong>
                                                    <br>
                                                    <small class="text-muted">Créer des annonces de livraison</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check border rounded p-3 h-100">
                                        <input class="form-check-input" type="radio" name="role" id="roleCourier" value="courier" required>
                                        <label class="form-check-label" for="roleCourier">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-truck fa-2x text-success me-3"></i>
                                                <div>
                                                    <strong>Livrer des colis</strong>
                                                    <br>
                                                    <small class="text-muted">Accepter des livraisons</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="invalid-feedback">
                                Veuillez sélectionner un rôle.
                            </div>
                        </div>
                        
                        <!-- Mot de passe -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>
                                Mot de passe *
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
                            <div class="password-strength mt-2" id="passwordStrength">
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar" id="strengthBar" role="progressbar"></div>
                                </div>
                                <small class="text-muted" id="strengthText">Force du mot de passe</small>
                            </div>
                            <div class="invalid-feedback">
                                Le mot de passe doit contenir au moins 8 caractères.
                            </div>
                        </div>
                        
                        <!-- Confirmation mot de passe -->
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">
                                <i class="fas fa-lock me-1"></i>
                                Confirmer le mot de passe *
                            </label>
                            <input type="password" 
                                   class="form-control form-control-lg" 
                                   id="confirmPassword" 
                                   name="confirm_password" 
                                   placeholder="Confirmez votre mot de passe"
                                   required>
                            <div class="invalid-feedback">
                                Les mots de passe ne correspondent pas.
                            </div>
                        </div>
                        
                        <!-- Conditions -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="acceptTerms" name="accept_terms" required>
                                <label class="form-check-label" for="acceptTerms">
                                    J'accepte les 
                                    <a href="/terms" target="_blank" class="text-decoration-none">conditions d'utilisation</a> 
                                    et la 
                                    <a href="/privacy" target="_blank" class="text-decoration-none">politique de confidentialité</a> *
                                </label>
                                <div class="invalid-feedback">
                                    Vous devez accepter les conditions d'utilisation.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bouton d'inscription -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3" id="registerBtn">
                            <span class="spinner-border spinner-border-sm me-2" id="registerSpinner" style="display: none;"></span>
                            <i class="fas fa-user-plus me-2" id="registerIcon"></i>
                            Créer mon compte
                        </button>
                        
                        <!-- Lien de connexion -->
                        <div class="text-center">
                            <p class="mb-0">
                                Déjà un compte ? 
                                <a href="/login" class="text-decoration-none fw-bold">
                                    Se connecter
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');
    const registerBtn = document.getElementById('registerBtn');
    const registerSpinner = document.getElementById('registerSpinner');
    const registerIcon = document.getElementById('registerIcon');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    // Toggle password visibility
    togglePasswordBtn.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        passwordIcon.classList.toggle('fa-eye');
        passwordIcon.classList.toggle('fa-eye-slash');
    });
    
    // Validation en temps réel
    firstNameInput.addEventListener('input', function() {
        validateRequired(this, 'Le prénom est requis.');
    });
    
    lastNameInput.addEventListener('input', function() {
        validateRequired(this, 'Le nom est requis.');
    });
    
    emailInput.addEventListener('input', function() {
        validateEmail(this);
    });
    
    phoneInput.addEventListener('input', function() {
        validatePhone(this);
    });
    
    passwordInput.addEventListener('input', function() {
        validatePassword(this);
        checkPasswordStrength(this.value);
    });
    
    confirmPasswordInput.addEventListener('input', function() {
        validateConfirmPassword(this);
    });
    
    // Soumission du formulaire
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation complète
        const validations = [
            validateRequired(firstNameInput, 'Le prénom est requis.'),
            validateRequired(lastNameInput, 'Le nom est requis.'),
            validateEmail(emailInput),
            validatePhone(phoneInput),
            validatePassword(passwordInput),
            validateConfirmPassword(confirmPasswordInput),
            validateRole(),
            validateTerms()
        ];
        
        if (validations.some(v => !v)) {
            showToast('Veuillez corriger les erreurs dans le formulaire.', 'error');
            return;
        }
        
        // Préparer les données
        const formData = {
            first_name: firstNameInput.value.trim(),
            last_name: lastNameInput.value.trim(),
            email: emailInput.value.trim(),
            phone: phoneInput.value.trim(),
            password: passwordInput.value,
            role: document.querySelector('input[name="role"]:checked').value,
            accept_terms: document.getElementById('acceptTerms').checked
        };
        
        // Afficher le chargement
        setLoadingState(true);
        
        // Envoyer la requête
        fetch('/api/auth/register', {
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
                showToast('Inscription réussie ! Redirection vers la connexion...', 'success');
                
                // Rediriger vers la page de connexion
                setTimeout(() => {
                    window.location.href = '/login?email=' + encodeURIComponent(emailInput.value.trim());
                }, 2000);
            } else {
                showToast(data.message || 'Erreur lors de l\'inscription.', 'error');
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
    function validateRequired(input, message) {
        const value = input.value.trim();
        if (!value) {
            input.classList.add('is-invalid');
            input.nextElementSibling.textContent = message;
            return false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        }
    }
    
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
    
    function validatePhone(input) {
        const phone = input.value.trim();
        const phoneRegex = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
        
        if (!phone) {
            input.classList.add('is-invalid');
            return false;
        } else if (!phoneRegex.test(phone)) {
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
        } else if (password.length < 8) {
            input.classList.add('is-invalid');
            return false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        }
    }
    
    function validateConfirmPassword(input) {
        const confirmPassword = input.value;
        const password = passwordInput.value;
        
        if (!confirmPassword) {
            input.classList.add('is-invalid');
            return false;
        } else if (confirmPassword !== password) {
            input.classList.add('is-invalid');
            return false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        }
    }
    
    function validateRole() {
        const selectedRole = document.querySelector('input[name="role"]:checked');
        const roleContainer = document.querySelector('.form-check.border');
        
        if (!selectedRole) {
            roleContainer.classList.add('border-danger');
            return false;
        } else {
            roleContainer.classList.remove('border-danger');
            return true;
        }
    }
    
    function validateTerms() {
        const acceptTerms = document.getElementById('acceptTerms');
        
        if (!acceptTerms.checked) {
            acceptTerms.classList.add('is-invalid');
            return false;
        } else {
            acceptTerms.classList.remove('is-invalid');
            return true;
        }
    }
    
    // Fonction pour vérifier la force du mot de passe
    function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = '';
        
        if (password.length >= 8) strength += 25;
        if (/[a-z]/.test(password)) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 25;
        
        if (strength < 25) {
            feedback = 'Très faible';
            strengthBar.className = 'progress-bar bg-danger';
        } else if (strength < 50) {
            feedback = 'Faible';
            strengthBar.className = 'progress-bar bg-warning';
        } else if (strength < 75) {
            feedback = 'Moyen';
            strengthBar.className = 'progress-bar bg-info';
        } else {
            feedback = 'Fort';
            strengthBar.className = 'progress-bar bg-success';
        }
        
        strengthBar.style.width = strength + '%';
        strengthText.textContent = feedback;
    }
    
    // Fonction pour gérer l'état de chargement
    function setLoadingState(loading) {
        if (loading) {
            registerBtn.disabled = true;
            registerSpinner.style.display = 'inline-block';
            registerIcon.style.display = 'none';
        } else {
            registerBtn.disabled = false;
            registerSpinner.style.display = 'none';
            registerIcon.style.display = 'inline-block';
        }
    }
    
    // Pré-sélectionner le rôle si présent dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const roleParam = urlParams.get('role');
    if (roleParam && (roleParam === 'expeditor' || roleParam === 'courier')) {
        document.getElementById('role' + roleParam.charAt(0).toUpperCase() + roleParam.slice(1)).checked = true;
    }
});
</script> 