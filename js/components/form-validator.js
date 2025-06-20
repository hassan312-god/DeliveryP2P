/**
 * Validateur de formulaires pour LivraisonP2P
 * Version 1.2.0 - Support des champs optionnels
 */

class FormValidator {
    constructor(formId, rules) {
        this.form = document.getElementById(formId);
        this.rules = rules;
        this.errors = new Map();
        
        if (this.form) {
            this.init();
        }
    }

    init() {
        // Ajouter les écouteurs d'événements
        this.form.addEventListener('submit', this.handleSubmit.bind(this));
        
        // Validation en temps réel
        Object.keys(this.rules).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('blur', () => this.validateField(fieldName));
                field.addEventListener('input', () => this.clearFieldError(fieldName));
            }
        });
    }

    handleSubmit(event) {
        event.preventDefault();
        
        // Valider tous les champs
        const isValid = this.validateAll();
        
        if (isValid) {
            // Soumettre le formulaire
            this.submitForm();
        } else {
            // Afficher les erreurs
            this.showErrors();
        }
    }

    validateAll() {
        let isValid = true;
        
        Object.keys(this.rules).forEach(fieldName => {
            if (!this.validateField(fieldName)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    validateField(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return true;

        const value = field.value.trim();
        const rule = this.rules[fieldName];
        const errors = [];

        // Vérifier si le champ est requis
        if (rule.required && !value) {
            errors.push(this.getErrorMessage(fieldName, 'required'));
        }

        // Si le champ n'est pas requis et est vide, on passe les autres validations
        if (!rule.required && !value) {
            this.clearFieldError(fieldName);
            return true;
        }

        // Validations supplémentaires seulement si le champ a une valeur
        if (value) {
            // Longueur minimale
            if (rule.minLength && value.length < rule.minLength) {
                errors.push(this.getErrorMessage(fieldName, 'minLength', rule.minLength));
            }

            // Longueur maximale
            if (rule.maxLength && value.length > rule.maxLength) {
                errors.push(this.getErrorMessage(fieldName, 'maxLength', rule.maxLength));
            }

            // Validation email
            if (rule.email && !this.isValidEmail(value)) {
                errors.push(this.getErrorMessage(fieldName, 'email'));
            }

            // Validation téléphone
            if (rule.phone && !this.isValidPhone(value)) {
                errors.push(this.getErrorMessage(fieldName, 'phone'));
            }

            // Validation pattern
            if (rule.pattern && !rule.pattern.test(value)) {
                errors.push(this.getErrorMessage(fieldName, 'pattern'));
            }

            // Validation de correspondance
            if (rule.match) {
                const matchField = this.form.querySelector(`[name="${rule.match}"]`);
                if (matchField && value !== matchField.value) {
                    errors.push(this.getErrorMessage(fieldName, 'match', rule.match));
                }
            }

            // Validation personnalisée
            if (rule.custom && typeof rule.custom === 'function') {
                const customError = rule.custom(value, field);
                if (customError) {
                    errors.push(customError);
                }
            }
        }

        // Stocker les erreurs
        if (errors.length > 0) {
            this.errors.set(fieldName, errors);
            this.showFieldError(fieldName, errors[0]);
            return false;
        } else {
            this.errors.delete(fieldName);
            this.clearFieldError(fieldName);
            return true;
        }
    }

    showFieldError(fieldName, message) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        // Ajouter la classe d'erreur au champ
        field.classList.add('border-red-500');
        field.classList.remove('border-gray-300');

        // Afficher le message d'erreur
        let errorElement = field.parentNode.querySelector('.error-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message text-red-500 text-sm mt-1';
            field.parentNode.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
    }

    clearFieldError(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        // Retirer la classe d'erreur du champ
        field.classList.remove('border-red-500');
        field.classList.add('border-gray-300');

        // Masquer le message d'erreur
        const errorElement = field.parentNode.querySelector('.error-message');
        if (errorElement) {
            errorElement.classList.add('hidden');
        }
    }

    showErrors() {
        // Afficher un toast avec les erreurs
        if (window.toast) {
            const errorMessages = Array.from(this.errors.values()).flat();
            window.toast.error(errorMessages.join(', '));
        }
    }

    submitForm() {
        // Émettre un événement de soumission réussie
        const event = new CustomEvent('form:validated', {
            detail: {
                form: this.form,
                data: this.getFormData()
            }
        });
        document.dispatchEvent(event);
    }

    getFormData() {
        const formData = new FormData(this.form);
        const data = {};
        
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    }

    isValidEmail(email) {
        return CONFIG.VALIDATIONS.EMAIL.test(email);
    }

    isValidPhone(phone) {
        // Téléphone optionnel - si vide, c'est valide
        if (!phone || phone.trim() === '') {
            return true;
        }
        return CONFIG.VALIDATIONS.PHONE.test(phone);
    }

    getErrorMessage(fieldName, type, param = null) {
        const fieldLabels = {
            prenom: 'Prénom',
            nom: 'Nom',
            email: 'Adresse email',
            telephone: 'Numéro de téléphone',
            role: 'Type de compte',
            mot_de_passe: 'Mot de passe',
            mot_de_passe_confirmation: 'Confirmation du mot de passe',
            terms: 'Conditions d\'utilisation'
        };

        const fieldLabel = fieldLabels[fieldName] || fieldName;

        const messages = {
            required: `${fieldLabel} est requis`,
            minLength: `${fieldLabel} doit contenir au moins ${param} caractères`,
            maxLength: `${fieldLabel} ne peut pas dépasser ${param} caractères`,
            email: `${fieldLabel} n'est pas valide`,
            phone: `${fieldLabel} n'est pas valide`,
            pattern: `${fieldLabel} n'est pas au bon format`,
            match: `${fieldLabel} ne correspond pas`
        };

        return messages[type] || `${fieldLabel} n'est pas valide`;
    }

    // Méthodes utilitaires
    reset() {
        this.errors.clear();
        this.form.reset();
        
        // Nettoyer tous les messages d'erreur
        Object.keys(this.rules).forEach(fieldName => {
            this.clearFieldError(fieldName);
        });
    }

    setFieldValue(fieldName, value) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.value = value;
        }
    }

    getFieldValue(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        return field ? field.value : null;
    }

    addRule(fieldName, rule) {
        this.rules[fieldName] = { ...this.rules[fieldName], ...rule };
    }

    removeRule(fieldName) {
        delete this.rules[fieldName];
    }
}

// Instance globale
window.FormValidator = FormValidator;

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormValidator;
} 