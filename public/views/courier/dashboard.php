<?php $page_title = 'Tableau de bord Livreur - LivraisonP2P'; ?>

<div class="container-fluid">
    <!-- En-tête du tableau de bord -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="fas fa-tachometer-alt me-2 text-success"></i>
                        Tableau de bord Livreur
                    </h1>
                    <p class="text-muted mb-0">Trouvez des livraisons et gérez vos missions</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-success btn-lg" id="toggleAvailabilityBtn">
                        <i class="fas fa-toggle-on me-2" id="availabilityIcon"></i>
                        <span id="availabilityText">Disponible</span>
                    </button>
                    <button class="btn btn-primary btn-lg" onclick="refreshLocation()">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Actualiser position
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Livraisons terminées</h6>
                            <h3 class="mb-0" id="completedDeliveriesCount">0</h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">En cours</h6>
                            <h3 class="mb-0" id="activeDeliveriesCount">0</h3>
                        </div>
                        <i class="fas fa-truck fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Gains totaux</h6>
                            <h3 class="mb-0" id="totalEarnings">0 €</h3>
                        </div>
                        <i class="fas fa-euro-sign fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Note moyenne</h6>
                            <h3 class="mb-0" id="averageRating">0.0</h3>
                        </div>
                        <i class="fas fa-star fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Onglets -->
    <ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="available-tab" data-bs-toggle="tab" data-bs-target="#available" type="button" role="tab">
                <i class="fas fa-list me-2"></i>
                Annonces disponibles
                <span class="badge bg-primary ms-2" id="availableCount">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
                <i class="fas fa-truck me-2"></i>
                Mes livraisons
                <span class="badge bg-success ms-2" id="activeCount">0</span>
            </button>
        </li>
    </ul>

    <!-- Contenu des onglets -->
    <div class="tab-content" id="dashboardTabContent">
        <!-- Onglet Annonces disponibles -->
        <div class="tab-pane fade show active" id="available" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Annonces disponibles à proximité
                                </h5>
                                <div class="d-flex gap-2">
                                    <input type="range" class="form-range" id="distanceFilter" min="1" max="50" value="10" style="width: 150px;">
                                    <span class="text-muted" id="distanceValue">10 km</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- État de chargement -->
                            <div id="availableLoadingState" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-3 text-muted">Recherche d'annonces à proximité...</p>
                            </div>

                            <!-- État vide -->
                            <div id="availableEmptyState" class="text-center py-5" style="display: none;">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5>Aucune annonce disponible</h5>
                                <p class="text-muted">Aucune annonce ne correspond à vos critères dans votre zone.</p>
                                <button class="btn btn-primary" onclick="refreshAvailableAds()">
                                    <i class="fas fa-refresh me-2"></i>
                                    Actualiser
                                </button>
                            </div>

                            <!-- Liste des annonces disponibles -->
                            <div id="availableAdsList" class="row g-3 p-3">
                                <!-- Les annonces seront injectées ici dynamiquement -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Mes livraisons -->
        <div class="tab-pane fade" id="active" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-truck me-2"></i>
                                Mes livraisons en cours
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <!-- État de chargement -->
                            <div id="activeLoadingState" class="text-center py-5" style="display: none;">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-3 text-muted">Chargement de vos livraisons...</p>
                            </div>

                            <!-- État vide -->
                            <div id="activeEmptyState" class="text-center py-5" style="display: none;">
                                <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                                <h5>Aucune livraison en cours</h5>
                                <p class="text-muted">Vous n'avez pas de livraison active pour le moment.</p>
                                <a href="#available" class="btn btn-success" data-bs-toggle="tab">
                                    <i class="fas fa-search me-2"></i>
                                    Voir les annonces disponibles
                                </a>
                            </div>

                            <!-- Liste des livraisons actives -->
                            <div id="activeDeliveriesList" class="list-group list-group-flush">
                                <!-- Les livraisons seront injectées ici dynamiquement -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template pour une annonce disponible -->
<template id="availableAdTemplate">
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm">
            <img src="" alt="Photo du colis" class="card-img-top ad-image" style="height: 200px; object-fit: cover;">
            <div class="card-body">
                <h6 class="card-title ad-title"></h6>
                <p class="card-text text-muted ad-description"></p>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted">
                            <i class="fas fa-weight-hanging me-1"></i>
                            <span class="ad-weight"></span>
                        </small>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">
                            <i class="fas fa-ruler-combined me-1"></i>
                            <span class="ad-dimensions"></span>
                        </small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <span class="ad-distance"></span>
                        </small>
                    </div>
                    <div class="col-6">
                        <span class="fw-bold text-success ad-amount"></span>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button class="btn btn-primary btn-sm ad-accept-btn">
                        <i class="fas fa-handshake me-1"></i>
                        Accepter
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Template pour une livraison active -->
<template id="activeDeliveryTemplate">
    <div class="list-group-item">
        <div class="row align-items-center">
            <div class="col-md-2 col-4">
                <img src="" alt="Photo du colis" class="img-fluid rounded delivery-image" style="max-height: 80px; object-fit: cover;">
            </div>
            <div class="col-md-4 col-8">
                <h6 class="mb-1 delivery-title"></h6>
                <p class="mb-1 text-muted delivery-description"></p>
                <small class="text-muted">
                    <i class="fas fa-user me-1"></i>
                    <span class="delivery-expeditor"></span>
                </small>
            </div>
            <div class="col-md-2">
                <span class="badge delivery-status"></span>
                <div class="mt-1">
                    <small class="text-muted delivery-amount"></small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="d-flex flex-column">
                    <small class="text-muted">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <span class="delivery-pickup"></span>
                    </small>
                    <small class="text-muted">
                        <i class="fas fa-flag-checkered me-1"></i>
                        <span class="delivery-destination"></span>
                    </small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="btn-group-vertical btn-group-sm w-100">
                    <button class="btn btn-outline-primary btn-sm delivery-details-btn">
                        <i class="fas fa-eye me-1"></i>Détails
                    </button>
                    <button class="btn btn-outline-secondary btn-sm delivery-chat-btn">
                        <i class="fas fa-comments me-1"></i>Chat
                    </button>
                    <button class="btn btn-outline-success btn-sm delivery-action-btn">
                        <i class="fas fa-check me-1"></i>Action
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let isAvailable = true;
    let currentLocation = null;
    let availableAds = [];
    let activeDeliveries = [];
    
    // Éléments DOM
    const toggleAvailabilityBtn = document.getElementById('toggleAvailabilityBtn');
    const availabilityIcon = document.getElementById('availabilityIcon');
    const availabilityText = document.getElementById('availabilityText');
    const distanceFilter = document.getElementById('distanceFilter');
    const distanceValue = document.getElementById('distanceValue');
    
    // Initialisation
    initializeDashboard();
    
    // Écouteurs d'événements
    toggleAvailabilityBtn.addEventListener('click', toggleAvailability);
    distanceFilter.addEventListener('input', updateDistanceFilter);
    
    // Fonction d'initialisation
    async function initializeDashboard() {
        try {
            // Obtenir la position actuelle
            await getCurrentLocation();
            
            // Charger les données
            await Promise.all([
                loadAvailableAds(),
                loadActiveDeliveries(),
                updateStatistics()
            ]);
            
            // Mettre à jour l'état de disponibilité
            updateAvailabilityStatus();
            
        } catch (error) {
            console.error('Erreur d\'initialisation:', error);
            ToastManager.show('Erreur lors de l\'initialisation du tableau de bord', 'error');
        }
    }
    
    // Fonction pour obtenir la position actuelle
    async function getCurrentLocation() {
        try {
            currentLocation = await GeolocationManager.getCurrentPosition();
            console.log('Position obtenue:', currentLocation);
        } catch (error) {
            console.error('Erreur de géolocalisation:', error);
            ToastManager.show('Impossible d\'obtenir votre position. Veuillez autoriser la géolocalisation.', 'warning');
        }
    }
    
    // Fonction pour actualiser la position
    window.refreshLocation = async function() {
        try {
            await getCurrentLocation();
            await loadAvailableAds();
            ToastManager.show('Position actualisée', 'success');
        } catch (error) {
            console.error('Erreur lors de l\'actualisation:', error);
            ToastManager.show('Erreur lors de l\'actualisation de la position', 'error');
        }
    };
    
    // Fonction pour basculer la disponibilité
    async function toggleAvailability() {
        try {
            const newStatus = !isAvailable;
            
            const response = await ApiService.post('/users/set-availability', {
                is_available: newStatus
            });
            
            if (response.success) {
                isAvailable = newStatus;
                updateAvailabilityStatus();
                ToastManager.show(
                    newStatus ? 'Vous êtes maintenant disponible' : 'Vous n\'êtes plus disponible',
                    'success'
                );
            } else {
                ToastManager.show('Erreur lors de la mise à jour du statut', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            ToastManager.show('Erreur de connexion au serveur', 'error');
        }
    }
    
    // Fonction pour mettre à jour l'affichage de la disponibilité
    function updateAvailabilityStatus() {
        if (isAvailable) {
            availabilityIcon.className = 'fas fa-toggle-on me-2';
            availabilityText.textContent = 'Disponible';
            toggleAvailabilityBtn.className = 'btn btn-outline-success btn-lg';
        } else {
            availabilityIcon.className = 'fas fa-toggle-off me-2';
            availabilityText.textContent = 'Indisponible';
            toggleAvailabilityBtn.className = 'btn btn-outline-secondary btn-lg';
        }
    }
    
    // Fonction pour mettre à jour le filtre de distance
    function updateDistanceFilter() {
        const distance = distanceFilter.value;
        distanceValue.textContent = distance + ' km';
        loadAvailableAds();
    }
    
    // Fonction pour charger les annonces disponibles
    async function loadAvailableAds() {
        try {
            document.getElementById('availableLoadingState').style.display = 'block';
            document.getElementById('availableEmptyState').style.display = 'none';
            document.getElementById('availableAdsList').style.display = 'none';
            
            const params = {
                distance: distanceFilter.value,
                available: true
            };
            
            if (currentLocation) {
                params.lat = currentLocation.latitude;
                params.lng = currentLocation.longitude;
            }
            
            const response = await ApiService.get('/ads/available', params);
            
            if (response.success) {
                availableAds = response.data;
                renderAvailableAds();
                document.getElementById('availableCount').textContent = availableAds.length;
            } else {
                ToastManager.show('Erreur lors du chargement des annonces', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            ToastManager.show('Erreur de connexion au serveur', 'error');
        } finally {
            document.getElementById('availableLoadingState').style.display = 'none';
        }
    }
    
    // Fonction pour afficher les annonces disponibles
    function renderAvailableAds() {
        const container = document.getElementById('availableAdsList');
        
        if (availableAds.length === 0) {
            document.getElementById('availableEmptyState').style.display = 'block';
            container.style.display = 'none';
            return;
        }
        
        document.getElementById('availableEmptyState').style.display = 'none';
        container.style.display = 'flex';
        container.innerHTML = '';
        
        availableAds.forEach(ad => {
            const adElement = createAvailableAdElement(ad);
            container.appendChild(adElement);
        });
    }
    
    // Fonction pour créer un élément d'annonce disponible
    function createAvailableAdElement(ad) {
        const template = document.getElementById('availableAdTemplate');
        const clone = template.content.cloneNode(true);
        
        // Remplir les données
        const image = clone.querySelector('.ad-image');
        image.src = ad.image_url || '/assets/images/default-package.jpg';
        image.alt = ad.title;
        
        clone.querySelector('.ad-title').textContent = ad.title;
        clone.querySelector('.ad-description').textContent = ad.description.substring(0, 80) + (ad.description.length > 80 ? '...' : '');
        clone.querySelector('.ad-weight').textContent = ad.weight + ' kg';
        clone.querySelector('.ad-dimensions').textContent = ad.length + 'x' + ad.width + 'x' + ad.height + ' cm';
        clone.querySelector('.ad-distance').textContent = ad.distance ? ad.distance.toFixed(1) + ' km' : 'N/A';
        clone.querySelector('.ad-amount').textContent = ad.amount + ' €';
        
        // Bouton d'acceptation
        const acceptBtn = clone.querySelector('.ad-accept-btn');
        acceptBtn.addEventListener('click', () => acceptDelivery(ad.id));
        
        return clone;
    }
    
    // Fonction pour accepter une livraison
    async function acceptDelivery(adId) {
        if (!confirm('Êtes-vous sûr de vouloir accepter cette livraison ?')) {
            return;
        }
        
        try {
            const response = await ApiService.post(`/deliveries/${adId}/accept`);
            
            if (response.success) {
                ToastManager.show('Livraison acceptée avec succès !', 'success');
                await Promise.all([
                    loadAvailableAds(),
                    loadActiveDeliveries(),
                    updateStatistics()
                ]);
            } else {
                ToastManager.show(response.message || 'Erreur lors de l\'acceptation', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            ToastManager.show('Erreur de connexion au serveur', 'error');
        }
    }
    
    // Fonction pour charger les livraisons actives
    async function loadActiveDeliveries() {
        try {
            document.getElementById('activeLoadingState').style.display = 'block';
            document.getElementById('activeEmptyState').style.display = 'none';
            document.getElementById('activeDeliveriesList').style.display = 'none';
            
            const response = await ApiService.get('/deliveries/active');
            
            if (response.success) {
                activeDeliveries = response.data;
                renderActiveDeliveries();
                document.getElementById('activeCount').textContent = activeDeliveries.length;
            } else {
                ToastManager.show('Erreur lors du chargement des livraisons', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            ToastManager.show('Erreur de connexion au serveur', 'error');
        } finally {
            document.getElementById('activeLoadingState').style.display = 'none';
        }
    }
    
    // Fonction pour afficher les livraisons actives
    function renderActiveDeliveries() {
        const container = document.getElementById('activeDeliveriesList');
        
        if (activeDeliveries.length === 0) {
            document.getElementById('activeEmptyState').style.display = 'block';
            container.style.display = 'none';
            return;
        }
        
        document.getElementById('activeEmptyState').style.display = 'none';
        container.style.display = 'block';
        container.innerHTML = '';
        
        activeDeliveries.forEach(delivery => {
            const deliveryElement = createActiveDeliveryElement(delivery);
            container.appendChild(deliveryElement);
        });
    }
    
    // Fonction pour créer un élément de livraison active
    function createActiveDeliveryElement(delivery) {
        const template = document.getElementById('activeDeliveryTemplate');
        const clone = template.content.cloneNode(true);
        
        // Remplir les données
        const image = clone.querySelector('.delivery-image');
        image.src = delivery.image_url || '/assets/images/default-package.jpg';
        image.alt = delivery.title;
        
        clone.querySelector('.delivery-title').textContent = delivery.title;
        clone.querySelector('.delivery-description').textContent = delivery.description.substring(0, 100) + (delivery.description.length > 100 ? '...' : '');
        clone.querySelector('.delivery-expeditor').textContent = delivery.expeditor_name;
        clone.querySelector('.delivery-amount').textContent = delivery.amount + ' €';
        clone.querySelector('.delivery-pickup').textContent = delivery.pickup_address;
        clone.querySelector('.delivery-destination').textContent = delivery.delivery_address;
        
        // Statut
        const statusBadge = clone.querySelector('.delivery-status');
        statusBadge.textContent = getStatusText(delivery.status);
        statusBadge.className = 'badge ' + getStatusClass(delivery.status);
        
        // Boutons d'action
        const detailsBtn = clone.querySelector('.delivery-details-btn');
        const chatBtn = clone.querySelector('.delivery-chat-btn');
        const actionBtn = clone.querySelector('.delivery-action-btn');
        
        detailsBtn.addEventListener('click', () => viewDeliveryDetails(delivery.id));
        chatBtn.addEventListener('click', () => openChat(delivery.id));
        
        // Ajout du bouton Suivi
        let trackBtn = document.createElement('button');
        trackBtn.className = 'btn btn-outline-primary btn-sm mt-1 delivery-track-btn';
        trackBtn.innerHTML = '<i class="fas fa-map-marker-alt me-1"></i> Suivi';
        trackBtn.onclick = () => window.location.href = `/views/tracking.php?delivery_id=${delivery.id}`;
        actionBtn.parentNode.appendChild(trackBtn);
        
        // Configurer le bouton d'action selon le statut
        configureActionButton(actionBtn, delivery);
        
        return clone;
    }
    
    // Fonction pour configurer le bouton d'action
    function configureActionButton(button, delivery) {
        switch (delivery.status) {
            case 'assigned':
                button.innerHTML = '<i class="fas fa-box me-1"></i>Ramasser';
                button.className = 'btn btn-outline-success btn-sm delivery-action-btn';
                button.addEventListener('click', () => updateDeliveryStatus(delivery.id, 'picked_up'));
                break;
            case 'picked_up':
                button.innerHTML = '<i class="fas fa-truck me-1"></i>En transit';
                button.className = 'btn btn-outline-primary btn-sm delivery-action-btn';
                button.addEventListener('click', () => updateDeliveryStatus(delivery.id, 'in_transit'));
                break;
            case 'in_transit':
                button.innerHTML = '<i class="fas fa-qrcode me-1"></i>Livrer';
                button.className = 'btn btn-outline-warning btn-sm delivery-action-btn';
                button.addEventListener('click', () => showQRCode(delivery.id));
                break;
            default:
                button.style.display = 'none';
        }
    }
    
    // Fonction pour obtenir le texte du statut
    function getStatusText(status) {
        const statusTexts = {
            'assigned': 'Attribuée',
            'picked_up': 'Ramassée',
            'in_transit': 'En transit',
            'delivered': 'Livrée'
        };
        return statusTexts[status] || status;
    }
    
    // Fonction pour obtenir la classe CSS du statut
    function getStatusClass(status) {
        const statusClasses = {
            'assigned': 'bg-info',
            'picked_up': 'bg-primary',
            'in_transit': 'bg-warning',
            'delivered': 'bg-success'
        };
        return statusClasses[status] || 'bg-secondary';
    }
    
    // Fonction pour mettre à jour le statut d'une livraison
    async function updateDeliveryStatus(deliveryId, newStatus) {
        try {
            const response = await ApiService.put(`/deliveries/${deliveryId}/status`, {
                status: newStatus
            });
            
            if (response.success) {
                ToastManager.show('Statut mis à jour avec succès', 'success');
                await loadActiveDeliveries();
                await updateStatistics();
            } else {
                ToastManager.show(response.message || 'Erreur lors de la mise à jour', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            ToastManager.show('Erreur de connexion au serveur', 'error');
        }
    }
    
    // Fonction pour afficher le QR code
    function showQRCode(deliveryId) {
        window.location.href = `/qrcode-display?delivery_id=${deliveryId}`;
    }
    
    // Fonction pour voir les détails d'une livraison
    function viewDeliveryDetails(deliveryId) {
        window.location.href = `/ad-details?id=${deliveryId}`;
    }
    
    // Fonction pour ouvrir le chat
    function openChat(deliveryId) {
        window.location.href = `/chat?delivery_id=${deliveryId}`;
    }
    
    // Fonction pour actualiser les annonces disponibles
    window.refreshAvailableAds = loadAvailableAds;
    
    // Fonction pour mettre à jour les statistiques
    async function updateStatistics() {
        try {
            const response = await ApiService.get('/courier/statistics');
            
            if (response.success) {
                const stats = response.data;
                document.getElementById('completedDeliveriesCount').textContent = stats.completed_deliveries;
                document.getElementById('activeDeliveriesCount').textContent = stats.active_deliveries;
                document.getElementById('totalEarnings').textContent = stats.total_earnings + ' €';
                document.getElementById('averageRating').textContent = stats.average_rating.toFixed(1);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des statistiques:', error);
        }
    }
    
    // Écouter les mises à jour en temps réel via Supabase
    if (typeof supabase !== 'undefined') {
        supabase
            .channel('courier_updates')
            .on('postgres_changes', {
                event: '*',
                schema: 'public',
                table: 'deliveries',
                filter: `courier_id=eq.${currentUser.id}`
            }, (payload) => {
                // Mettre à jour les données
                loadActiveDeliveries();
                updateStatistics();
            })
            .subscribe();
    }
});
</script> 