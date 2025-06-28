<?php $page_title = 'Créer une annonce - LivraisonP2P'; ?>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <!-- Formulaire de création d'annonce -->
            <div class="card shadow-lg">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-plus me-2 text-primary"></i>
                        Créer une nouvelle annonce
                    </h4>
                </div>
                <div class="card-body">
                    <form id="createAdForm" enctype="multipart/form-data" novalidate>
                        <!-- Informations du colis -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-box me-2"></i>
                                    Informations du colis
                                </h5>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-tag me-1"></i>
                                        Titre de l'annonce *
                                    </label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="title" 
                                           name="title" 
                                           placeholder="Ex: Livraison d'un colis fragile"
                                           required>
                                    <div class="invalid-feedback">
                                        Le titre est requis.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">
                                        <i class="fas fa-euro-sign me-1"></i>
                                        Montant proposé (€) *
                                    </label>
                                    <input type="number" 
                                           class="form-control form-control-lg" 
                                           id="amount" 
                                           name="amount" 
                                           min="1" 
                                           max="1000" 
                                           placeholder="25"
                                           required>
                                    <div class="invalid-feedback">
                                        Le montant doit être entre 1 et 1000 €.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>
                                        Description détaillée *
                                    </label>
                                    <textarea class="form-control" 
                                              id="description" 
                                              name="description" 
                                              rows="4" 
                                              placeholder="Décrivez votre colis, sa nature, sa fragilité, etc."
                                              required></textarea>
                                    <div class="invalid-feedback">
                                        La description est requise.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Caractéristiques du colis -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-ruler-combined me-2"></i>
                                    Caractéristiques du colis
                                </h5>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="weight" class="form-label">
                                        <i class="fas fa-weight-hanging me-1"></i>
                                        Poids (kg) *
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="weight" 
                                           name="weight" 
                                           min="0.1" 
                                           max="100" 
                                           step="0.1" 
                                           placeholder="2.5"
                                           required>
                                    <div class="invalid-feedback">
                                        Le poids doit être entre 0.1 et 100 kg.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="length" class="form-label">
                                        <i class="fas fa-arrows-alt-h me-1"></i>
                                        Longueur (cm) *
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="length" 
                                           name="length" 
                                           min="1" 
                                           max="200" 
                                           placeholder="30"
                                           required>
                                    <div class="invalid-feedback">
                                        La longueur doit être entre 1 et 200 cm.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="width" class="form-label">
                                        <i class="fas fa-arrows-alt-v me-1"></i>
                                        Largeur (cm) *
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="width" 
                                           name="width" 
                                           min="1" 
                                           max="200" 
                                           placeholder="20"
                                           required>
                                    <div class="invalid-feedback">
                                        La largeur doit être entre 1 et 200 cm.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="height" class="form-label">
                                        <i class="fas fa-arrows-alt-v me-1"></i>
                                        Hauteur (cm) *
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="height" 
                                           name="height" 
                                           min="1" 
                                           max="200" 
                                           placeholder="15"
                                           required>
                                    <div class="invalid-feedback">
                                        La hauteur doit être entre 1 et 200 cm.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Adresses -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    Adresses de livraison
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="pickupAddress" class="form-label">
                                        <i class="fas fa-map-marker me-1"></i>
                                        Adresse de départ *
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="pickupAddress" 
                                           name="pickup_address" 
                                           placeholder="Entrez l'adresse de départ"
                                           required>
                                    <div class="invalid-feedback">
                                        L'adresse de départ est requise.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deliveryAddress" class="form-label">
                                        <i class="fas fa-flag-checkered me-1"></i>
                                        Adresse de destination *
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="deliveryAddress" 
                                           name="delivery_address" 
                                           placeholder="Entrez l'adresse de destination"
                                           required>
                                    <div class="invalid-feedback">
                                        L'adresse de destination est requise.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Coordonnées cachées -->
                            <input type="hidden" id="pickupLat" name="pickup_lat">
                            <input type="hidden" id="pickupLng" name="pickup_lng">
                            <input type="hidden" id="deliveryLat" name="delivery_lat">
                            <input type="hidden" id="deliveryLng" name="delivery_lng">
                        </div>

                        <!-- Photo du colis -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-camera me-2"></i>
                                    Photo du colis
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="packageImage" class="form-label">
                                        <i class="fas fa-upload me-1"></i>
                                        Photo du colis
                                    </label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="packageImage" 
                                           name="image" 
                                           accept="image/*">
                                    <div class="form-text">
                                        Formats acceptés : JPG, PNG, GIF (max 5MB)
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Aperçu</label>
                                    <div id="imagePreview" class="border rounded p-3 text-center" style="min-height: 150px;">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                        <p class="text-muted mt-2">Aperçu de l'image</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Options supplémentaires -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-cogs me-2"></i>
                                    Options supplémentaires
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deadline" class="form-label">
                                        <i class="fas fa-clock me-1"></i>
                                        Date limite de livraison
                                    </label>
                                    <input type="datetime-local" 
                                           class="form-control" 
                                           id="deadline" 
                                           name="deadline">
                                    <div class="form-text">
                                        Laissez vide pour aucune limite
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fragile" class="form-label">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Type de colis
                                    </label>
                                    <select class="form-select" id="fragile" name="fragile">
                                        <option value="0">Normal</option>
                                        <option value="1">Fragile</option>
                                        <option value="2">Très fragile</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-3">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <span class="spinner-border spinner-border-sm me-2" id="submitSpinner" style="display: none;"></span>
                                        <i class="fas fa-check me-2" id="submitIcon"></i>
                                        Créer l'annonce
                                    </button>
                                    <a href="/expeditor/dashboard" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-times me-2"></i>
                                        Annuler
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Carte interactive -->
            <div class="card shadow-lg sticky-top" style="top: 100px;">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map me-2"></i>
                        Carte de livraison
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div id="map" style="height: 400px; width: 100%;"></div>
                </div>
                <div class="card-footer">
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted">Distance estimée</small>
                            <div class="fw-bold" id="estimatedDistance">-- km</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Temps estimé</small>
                            <div class="fw-bold" id="estimatedTime">-- min</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let map;
    let pickupMarker;
    let deliveryMarker;
    let directionsService;
    let directionsRenderer;
    let currentLocation = null;
    
    // Éléments DOM
    const form = document.getElementById('createAdForm');
    const pickupAddressInput = document.getElementById('pickupAddress');
    const deliveryAddressInput = document.getElementById('deliveryAddress');
    const imageInput = document.getElementById('packageImage');
    const imagePreview = document.getElementById('imagePreview');
    const submitBtn = document.getElementById('submitBtn');
    const submitSpinner = document.getElementById('submitSpinner');
    const submitIcon = document.getElementById('submitIcon');
    
    // Initialisation
    initializeMap();
    initializeForm();
    
    // Fonction d'initialisation de la carte
    function initializeMap() {
        // Centrer la carte sur la France par défaut
        const defaultCenter = { lat: 46.603354, lng: 1.888334 };
        
        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 6,
            center: defaultCenter,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });
        
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            suppressMarkers: true,
            polylineOptions: {
                strokeColor: '#007bff',
                strokeWeight: 4
            }
        });
        directionsRenderer.setMap(map);
        
        // Obtenir la position actuelle
        getCurrentLocation();
    }
    
    // Fonction pour obtenir la position actuelle
    async function getCurrentLocation() {
        try {
            currentLocation = await GeolocationManager.getCurrentPosition();
            
            // Centrer la carte sur la position actuelle
            map.setCenter({ lat: currentLocation.latitude, lng: currentLocation.longitude });
            map.setZoom(12);
            
            // Ajouter un marqueur pour la position actuelle
            new google.maps.Marker({
                position: { lat: currentLocation.latitude, lng: currentLocation.longitude },
                map: map,
                title: 'Votre position',
                icon: {
                    url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                }
            });
            
        } catch (error) {
            console.error('Erreur de géolocalisation:', error);
            ToastManager.show('Impossible d\'obtenir votre position', 'warning');
        }
    }
    
    // Fonction d'initialisation du formulaire
    function initializeForm() {
        // Autocomplétion des adresses
        const pickupAutocomplete = new google.maps.places.Autocomplete(pickupAddressInput);
        const deliveryAutocomplete = new google.maps.places.Autocomplete(deliveryAddressInput);
        
        // Écouter les changements d'adresse
        pickupAutocomplete.addListener('place_changed', () => {
            const place = pickupAutocomplete.getPlace();
            if (place.geometry) {
                updatePickupMarker(place.geometry.location);
                updateRoute();
            }
        });
        
        deliveryAutocomplete.addListener('place_changed', () => {
            const place = deliveryAutocomplete.getPlace();
            if (place.geometry) {
                updateDeliveryMarker(place.geometry.location);
                updateRoute();
            }
        });
        
        // Prévisualisation d'image
        imageInput.addEventListener('change', handleImagePreview);
        
        // Soumission du formulaire
        form.addEventListener('submit', handleFormSubmit);
        
        // Validation en temps réel
        form.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('input', validateField);
            field.addEventListener('blur', validateField);
        });
    }
    
    // Fonction pour mettre à jour le marqueur de départ
    function updatePickupMarker(position) {
        if (pickupMarker) {
            pickupMarker.setMap(null);
        }
        
        pickupMarker = new google.maps.Marker({
            position: position,
            map: map,
            title: 'Point de départ',
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
            }
        });
        
        // Mettre à jour les coordonnées cachées
        document.getElementById('pickupLat').value = position.lat();
        document.getElementById('pickupLng').value = position.lng();
    }
    
    // Fonction pour mettre à jour le marqueur de destination
    function updateDeliveryMarker(position) {
        if (deliveryMarker) {
            deliveryMarker.setMap(null);
        }
        
        deliveryMarker = new google.maps.Marker({
            position: position,
            map: map,
            title: 'Point de destination',
            icon: {
                url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png'
            }
        });
        
        // Mettre à jour les coordonnées cachées
        document.getElementById('deliveryLat').value = position.lat();
        document.getElementById('deliveryLng').value = position.lng();
    }
    
    // Fonction pour mettre à jour l'itinéraire
    function updateRoute() {
        if (!pickupMarker || !deliveryMarker) {
            return;
        }
        
        const request = {
            origin: pickupMarker.getPosition(),
            destination: deliveryMarker.getPosition(),
            travelMode: google.maps.TravelMode.DRIVING
        };
        
        directionsService.route(request, (result, status) => {
            if (status === 'OK') {
                directionsRenderer.setDirections(result);
                
                const route = result.routes[0];
                const leg = route.legs[0];
                
                // Mettre à jour les informations de distance et temps
                document.getElementById('estimatedDistance').textContent = leg.distance.text;
                document.getElementById('estimatedTime').textContent = leg.duration.text;
                
                // Ajuster la carte pour afficher tout l'itinéraire
                const bounds = new google.maps.LatLngBounds();
                bounds.extend(pickupMarker.getPosition());
                bounds.extend(deliveryMarker.getPosition());
                map.fitBounds(bounds);
            }
        });
    }
    
    // Fonction pour gérer la prévisualisation d'image
    function handleImagePreview(event) {
        const file = event.target.files[0];
        
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                ToastManager.show('L\'image est trop volumineuse (max 5MB)', 'error');
                imageInput.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.innerHTML = `
                    <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 120px;">
                    <p class="text-muted mt-2 mb-0">${file.name}</p>
                `;
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.innerHTML = `
                <i class="fas fa-image fa-3x text-muted"></i>
                <p class="text-muted mt-2">Aperçu de l'image</p>
            `;
        }
    }
    
    // Fonction de validation de champ
    function validateField(event) {
        const field = event.target;
        const value = field.value.trim();
        
        // Supprimer les classes de validation existantes
        field.classList.remove('is-valid', 'is-invalid');
        
        // Validation selon le type de champ
        let isValid = true;
        
        if (field.hasAttribute('required') && !value) {
            isValid = false;
        } else if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            isValid = emailRegex.test(value);
        } else if (field.type === 'number' && value) {
            const min = field.getAttribute('min');
            const max = field.getAttribute('max');
            const numValue = parseFloat(value);
            
            if (min && numValue < parseFloat(min)) isValid = false;
            if (max && numValue > parseFloat(max)) isValid = false;
        }
        
        // Appliquer les classes de validation
        if (isValid && value) {
            field.classList.add('is-valid');
        } else if (!isValid) {
            field.classList.add('is-invalid');
        }
    }
    
    // Fonction de soumission du formulaire
    async function handleFormSubmit(event) {
        event.preventDefault();
        
        // Validation complète du formulaire
        const validation = FormManager.validateForm(form);
        
        if (!validation.isValid) {
            FormManager.showValidationErrors(validation.errors);
            return;
        }
        
        // Vérifier que les coordonnées sont définies
        if (!document.getElementById('pickupLat').value || !document.getElementById('deliveryLat').value) {
            ToastManager.show('Veuillez sélectionner des adresses valides sur la carte', 'error');
            return;
        }
        
        // Afficher le chargement
        setLoadingState(true);
        
        try {
            // Préparer les données du formulaire
            const formData = new FormData(form);
            
            // Envoyer la requête
            const response = await ApiService.upload('/ads', formData);
            
            if (response.success) {
                ToastManager.show('Annonce créée avec succès !', 'success');
                
                // Rediriger vers le tableau de bord
                setTimeout(() => {
                    window.location.href = '/expeditor/dashboard';
                }, 1500);
            } else {
                ToastManager.show(response.message || 'Erreur lors de la création de l\'annonce', 'error');
                setLoadingState(false);
            }
        } catch (error) {
            console.error('Erreur:', error);
            ToastManager.show('Erreur de connexion au serveur', 'error');
            setLoadingState(false);
        }
    }
    
    // Fonction pour gérer l'état de chargement
    function setLoadingState(loading) {
        if (loading) {
            submitBtn.disabled = true;
            submitSpinner.style.display = 'inline-block';
            submitIcon.style.display = 'none';
        } else {
            submitBtn.disabled = false;
            submitSpinner.style.display = 'none';
            submitIcon.style.display = 'inline-block';
        }
    }
});
</script> 