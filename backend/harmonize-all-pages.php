<?php
/**
 * Script d'harmonisation automatique de toutes les pages
 * Applique le design harmonisé et lie tous les boutons au backend
 */

class PageHarmonizer {
    private $pages = [
        // Pages d'authentification
        'auth/login.html',
        'auth/register.html',
        'auth/forgot-password.html',
        'auth/reset-password.html',
        'auth/email-confirmation.html',
        'auth/callback.html',
        
        // Pages client
        'client/dashboard.html',
        'client/create-request.html',
        'client/track-delivery.html',
        'client/history.html',
        'client/profile.html',
        
        // Pages livreur
        'courier/dashboard.html',
        'courier/available-requests.html',
        'courier/active-deliveries.html',
        'courier/earnings.html',
        'courier/profile.html',
        
        // Pages admin
        'admin/dashboard.html',
        'admin/users.html',
        'admin/livraisons.html',
        'admin/paiements.html',
        'admin/profile.html',
        
        // Pages principales
        'index.html',
        'chat.html',
        'call.html',
        'notifications.html',
        'qrcode.html'
    ];
    
    public function harmonize() {
        echo "🎨 Harmonisation automatique de toutes les pages\n";
        echo "================================================\n\n";
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($this->pages as $page) {
            if (file_exists($page)) {
                echo "📄 Traitement de $page...\n";
                
                if ($this->harmonizePage($page)) {
                    echo "   ✅ Harmonisé avec succès\n";
                    $successCount++;
                } else {
                    echo "   ❌ Erreur lors de l'harmonisation\n";
                    $errorCount++;
                }
            } else {
                echo "⚠️  Page $page non trouvée\n";
            }
        }
        
        echo "\n📊 Résumé de l'harmonisation :\n";
        echo "   ✅ Pages harmonisées : $successCount\n";
        echo "   ❌ Erreurs : $errorCount\n";
        echo "   📄 Total traitées : " . count($this->pages) . "\n";
        
        return $errorCount === 0;
    }
    
    private function harmonizePage($pagePath) {
        try {
            $content = file_get_contents($pagePath);
            
            // Appliquer les transformations
            $content = $this->addHarmonizedStyles($content);
            $content = $this->updateCSSClasses($content);
            $content = $this->addEventHandlers($content);
            $content = $this->addFormValidation($content);
            $content = $this->addErrorHandling($content);
            $content = $this->addAnimations($content);
            
            // Sauvegarder la page
            file_put_contents($pagePath, $content);
            
            return true;
        } catch (Exception $e) {
            echo "   Erreur: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function addHarmonizedStyles($content) {
        $harmonizedCSS = '
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="/css/app-styles.css" rel="stylesheet">
        ';
        
        // Supprimer les anciens styles
        $content = preg_replace('/<link[^>]*tailwind[^>]*>/', '', $content);
        $content = preg_replace('/<style[^>]*>.*?<\/style>/s', '', $content);
        
        // Ajouter les nouveaux styles après le head
        $content = preg_replace('/<head>/', '<head>' . $harmonizedCSS, $content);
        
        return $content;
    }
    
    private function updateCSSClasses($content) {
        $replacements = [
            'bg-blue-600' => 'btn-primary',
            'bg-gray-600' => 'btn-secondary',
            'bg-green-600' => 'btn-success',
            'bg-red-600' => 'btn-error',
            'bg-yellow-600' => 'btn-warning',
            'rounded-lg' => 'auth-card',
            'form-input' => 'form-input',
            'btn' => 'btn',
            'container' => 'app-container',
            'max-w-7xl' => 'app-container',
            'mx-auto' => '',
            'px-4' => '',
            'py-8' => 'p-8',
            'text-center' => 'text-center',
            'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3' => 'grid grid-cols-3',
            'space-y-4' => 'space-y-4',
            'flex items-center justify-between' => 'flex items-center justify-between'
        ];
        
        foreach ($replacements as $old => $new) {
            $content = str_replace($old, $new, $content);
        }
        
        return $content;
    }
    
    private function addEventHandlers($content) {
        $handlers = '
        <script>
        // Gestionnaires d\'événements pour tous les boutons
        document.addEventListener("DOMContentLoaded", function() {
            // Boutons de soumission de formulaire
            document.querySelectorAll("form").forEach(form => {
                form.addEventListener("submit", handleFormSubmit);
            });
            
            // Boutons d\'action
            document.querySelectorAll(".btn").forEach(btn => {
                btn.addEventListener("click", handleButtonClick);
            });
            
            // Liens de navigation
            document.querySelectorAll("a[href]").forEach(link => {
                link.addEventListener("click", handleLinkClick);
            });
        });
        
        // Gestionnaire de soumission de formulaire
        async function handleFormSubmit(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const action = form.getAttribute("action") || form.dataset.action;
            
            try {
                setLoadingState(form, true);
                
                const response = await fetch(action, {
                    method: "POST",
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message || "Opération réussie");
                    if (result.redirect) {
                        setTimeout(() => window.location.href = result.redirect, 1500);
                    }
                } else {
                    showError(result.error || "Erreur lors de l\'opération");
                }
            } catch (error) {
                console.error("Erreur:", error);
                showError("Erreur de connexion");
            } finally {
                setLoadingState(form, false);
            }
        }
        
        // Gestionnaire de clic sur bouton
        async function handleButtonClick(e) {
            const btn = e.target.closest(".btn");
            if (!btn) return;
            
            const action = btn.dataset.action;
            if (!action) return;
            
            e.preventDefault();
            
            try {
                setLoadingState(btn, true);
                
                const response = await fetch(action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(btn.dataset)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess(result.message || "Action réussie");
                    if (result.redirect) {
                        setTimeout(() => window.location.href = result.redirect, 1500);
                    }
                } else {
                    showError(result.error || "Erreur lors de l\'action");
                }
            } catch (error) {
                console.error("Erreur:", error);
                showError("Erreur de connexion");
            } finally {
                setLoadingState(btn, false);
            }
        }
        
        // Gestionnaire de clic sur lien
        function handleLinkClick(e) {
            const link = e.target.closest("a");
            if (!link) return;
            
            const href = link.getAttribute("href");
            if (href && !href.startsWith("#") && !href.startsWith("javascript:")) {
                localStorage.setItem("last_page", window.location.pathname);
            }
        }
        
        // État de chargement
        function setLoadingState(element, loading) {
            if (loading) {
                element.classList.add("btn-loading");
                element.disabled = true;
            } else {
                element.classList.remove("btn-loading");
                element.disabled = false;
            }
        }
        
        // Affichage des messages
        function showSuccess(message) {
            if (window.Toast) {
                window.Toast.success(message);
            } else {
                alert(message);
            }
        }
        
        function showError(message) {
            if (window.Toast) {
                window.Toast.error(message);
            } else {
                alert(message);
            }
        }
        </script>
        ';
        
        $content = str_replace('</body>', $handlers . '</body>', $content);
        
        return $content;
    }
    
    private function addFormValidation($content) {
        $validation = '
        <script>
        // Validation en temps réel
        document.addEventListener("input", function(e) {
            const input = e.target;
            validateField(input);
        });
        
        // Validation d\'un champ
        function validateField(input) {
            const value = input.value.trim();
            const type = input.type;
            const name = input.name;
            
            let isValid = true;
            let errorMessage = "";
            
            // Validation selon le type
            switch (type) {
                case "email":
                    isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                    errorMessage = "Adresse email invalide";
                    break;
                    
                case "password":
                    isValid = value.length >= 6;
                    errorMessage = "Le mot de passe doit contenir au moins 6 caractères";
                    break;
                    
                case "tel":
                    if (value) {
                        isValid = /^[\+]?[0-9\s\-\(\)]{8,15}$/.test(value);
                        errorMessage = "Numéro de téléphone invalide";
                    }
                    break;
            }
            
            // Validation des champs requis
            if (input.required && !value) {
                isValid = false;
                errorMessage = "Ce champ est requis";
            }
            
            // Afficher l\'erreur
            showFieldError(input, isValid ? "" : errorMessage);
            
            return isValid;
        }
        
        // Afficher une erreur de champ
        function showFieldError(input, message) {
            const errorElement = input.parentNode.querySelector(".error-message");
            
            if (errorElement) {
                if (message) {
                    errorElement.textContent = message;
                    errorElement.classList.add("show");
                    input.classList.add("error");
                } else {
                    errorElement.classList.remove("show");
                    input.classList.remove("error");
                }
            }
        }
        </script>
        ';
        
        $content = str_replace('</body>', $validation . '</body>', $content);
        
        return $content;
    }
    
    private function addErrorHandling($content) {
        $errorHandling = '
        <script>
        // Gestion globale des erreurs
        window.addEventListener("error", function(e) {
            console.error("Erreur JavaScript:", e.error);
            showError("Une erreur est survenue");
        });
        
        // Gestion des erreurs de réseau
        window.addEventListener("online", function() {
            showSuccess("Connexion rétablie");
        });
        
        window.addEventListener("offline", function() {
            showError("Connexion perdue");
        });
        
        // Gestion des erreurs de fetch
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            return originalFetch.apply(this, args).catch(error => {
                console.error("Erreur fetch:", error);
                showError("Erreur de connexion au serveur");
                throw error;
            });
        };
        </script>
        ';
        
        $content = str_replace('</body>', $errorHandling . '</body>', $content);
        
        return $content;
    }
    
    private function addAnimations($content) {
        $animations = '
        <script>
        // Animations d\'entrée
        document.addEventListener("DOMContentLoaded", function() {
            const elements = document.querySelectorAll(".card, .btn, .form-group");
            elements.forEach((el, index) => {
                el.style.animationDelay = (index * 0.1) + "s";
                el.classList.add("animate-fade-in");
            });
        });
        </script>
        ';
        
        $content = str_replace('</body>', $animations . '</body>', $content);
        
        return $content;
    }
}

// Exécution du script
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $harmonizer = new PageHarmonizer();
    
    if ($harmonizer->harmonize()) {
        echo "\n🎉 Harmonisation terminée avec succès !\n";
        echo "🚀 Toutes les pages sont maintenant harmonisées et connectées au backend.\n";
    } else {
        echo "\n❌ Erreurs lors de l'harmonisation\n";
        exit(1);
    }
}
?> 