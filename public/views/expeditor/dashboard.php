<?php $page_title = 'Tableau de bord Expéditeur - LivraisonP2P'; ?>

<div class="container-fluid">
    <!-- En-tête du tableau de bord -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="fas fa-tachometer-alt me-2 text-primary"></i>
                        Tableau de bord Expéditeur
                    </h1>
                    <p class="text-muted mb-0">Gérez vos annonces et suivez vos livraisons</p>
                </div>
                <a href="/expeditor/create-ad" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>
                    Nouvelle annonce
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Annonces actives</h6>
                            <h3 class="mb-0" id="activeAdsCount">0</h3>
                        </div>
                        <i class="fas fa-box fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
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
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">En cours</h6>
                            <h3 class="mb-0" id="inProgressCount">0</h3>
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
                            <h6 class="card-title mb-0">Note moyenne</h6>
                            <h3 class="mb-0" id="averageRating">0.0</h3>
                        </div>
                        <i class="fas fa-star fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label for="statusFilter" class="form-label">Filtrer par statut</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">Tous les statuts</option>
                                <option value="pending">En attente</option>
                                <option value="assigned">Attribuée</option>
                                <option value="picked_up">Ramassée</option>
                                <option value="in_transit">En transit</option>
                                <option value="delivered">Livrée</option>
                                <option value="cancelled">Annulée</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label for="searchInput" class="form-label">Rechercher</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Rechercher dans vos annonces...">
                        </div>
                        <div class="col-md-4">
                            <label for="sortBy" class="form-label">Trier par</label>
                            <select class="form-select" id="sortBy">
                                <option value="created_at_desc">Plus récentes</option>
                                <option value="created_at_asc">Plus anciennes</option>
                                <option value="title_asc">Titre A-Z</option>
                                <option value="title_desc">Titre Z-A</option>
                                <option value="amount_desc">Prix décroissant</option>
                                <option value="amount_asc">Prix croissant</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des annonces -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Mes annonces
                    </h5>
                </div>
                <div class="card-body p-0">
                    <!-- État de chargement -->
                    <div id="loadingState" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-3 text-muted">Chargement de vos annonces...</p>
                    </div>

                    <!-- État vide -->
                    <div id="emptyState" class="text-center py-5" style="display: none;">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5>Aucune annonce trouvée</h5>
                        <p class="text-muted">Vous n'avez pas encore créé d'annonce ou aucun résultat ne correspond à vos critères.</p>
                        <a href="/expeditor/create-ad" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Créer votre première annonce
                        </a>
                    </div>

                    <!-- Liste des annonces -->
                    <div id="adsList" class="list-group list-group-flush">
                        <!-- Les annonces seront injectées ici dynamiquement -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template pour une annonce -->
<template id="adTemplate">
    <div class="list-group-item list-group-item-action">
        <div class="row align-items-center">
            <div class="col-md-2 col-4">
                <img src="" alt="Photo du colis" class="img-fluid rounded ad-image" style="max-height: 80px; object-fit: cover;">
            </div>
            <div class="col-md-4 col-8">
                <h6 class="mb-1 ad-title"></h6>
                <p class="mb-1 text-muted ad-description"></p>
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    <span class="ad-date"></span>
                </small>
            </div>
            <div class="col-md-2">
                <div class="d-flex flex-column">
                    <span class="fw-bold text-primary ad-amount"></span>
                    <small class="text-muted">
                        <i class="fas fa-weight-hanging me-1"></i>
                        <span class="ad-weight"></span>
                    </small>
                </div>
            </div>
            <div class="col-md-2">
                <span class="badge ad-status"></span>
                <div class="mt-1">
                    <small class="text-muted ad-courier"></small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="btn-group-vertical btn-group-sm w-100">
                    <button class="btn btn-outline-primary btn-sm ad-details-btn">
                        <i class="fas fa-eye me-1"></i>Détails
                    </button>
                    <button class="btn btn-outline-secondary btn-sm ad-chat-btn" style="display: none;">
                        <i class="fas fa-comments me-1"></i>Chat
                    </button>
                    <button class="btn btn-outline-warning btn-sm ad-edit-btn" style="display: none;">
                        <i class="fas fa-edit me-1"></i>Modifier
                    </button>
                    <button class="btn btn-outline-danger btn-sm ad-cancel-btn" style="display: none;">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let ads = [];
    let filteredAds = [];
    
    // Éléments DOM
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const adsList = document.getElementById('adsList');
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    const sortBy = document.getElementById('sortBy');
    
    // Charger les annonces au démarrage
    loadAds();
    
    // Écouteurs d'événements pour les filtres
    statusFilter.addEventListener('change', filterAds);
    searchInput.addEventListener('input', filterAds);
    sortBy.addEventListener('change', filterAds);
    
    // Fonction pour charger les annonces
    async function loadAds() {
        try {
            showLoading();
            
            const response = await ApiService.get('/ads/mine');
            
            if (response.success) {
                ads = response.data;
                updateStatistics();
                filterAds();
            } else {
                ToastManager.show('Erreur lors du chargement des annonces', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            ToastManager.show('Erreur de connexion au serveur', 'error');
        } finally {
            hideLoading();
        }
    }
    
    // Fonction pour filtrer et trier les annonces
    function filterAds() {
        const statusFilterValue = statusFilter.value;
        const searchValue = searchInput.value.toLowerCase();
        const sortValue = sortBy.value;
        
        // Filtrer
        filteredAds = ads.filter(ad => {
            const matchesStatus = !statusFilterValue || ad.status === statusFilterValue;
            const matchesSearch = !searchValue || 
                ad.title.toLowerCase().includes(searchValue) ||
                ad.description.toLowerCase().includes(searchValue) ||
                ad.pickup_address.toLowerCase().includes(searchValue) ||
                ad.delivery_address.toLowerCase().includes(searchValue);
            
            return matchesStatus && matchesSearch;
        });
        
        // Trier
        filteredAds.sort((a, b) => {
            switch (sortValue) {
                case 'created_at_desc':
                    return new Date(b.created_at) - new Date(a.created_at);
                case 'created_at_asc':
                    return new Date(a.created_at) - new Date(b.created_at);
                case 'title_asc':
                    return a.title.localeCompare(b.title);
                case 'title_desc':
                    return b.title.localeCompare(a.title);
                case 'amount_desc':
                    return b.amount - a.amount;
                case 'amount_asc':
                    return a.amount - b.amount;
                default:
                    return 0;
            }
        });
        
        renderAds();
    }
    
    // Fonction pour afficher les annonces
    function renderAds() {
        if (filteredAds.length === 0) {
            loadingState.style.display = 'none';
            adsList.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }
        
        loadingState.style.display = 'none';
        emptyState.style.display = 'none';
        adsList.style.display = 'block';
        
        adsList.innerHTML = '';
        
        filteredAds.forEach(ad => {
            const adElement = createAdElement(ad);
            adsList.appendChild(adElement);
        });
    }
    
    // Fonction pour créer un élément d'annonce
    function createAdElement(ad) {
        const template = document.getElementById('adTemplate');
        const clone = template.content.cloneNode(true);
        
        // Remplir les données
        const image = clone.querySelector('.ad-image');
        image.src = ad.image_url || '/assets/images/default-package.jpg';
        image.alt = ad.title;
        
        clone.querySelector('.ad-title').textContent = ad.title;
        clone.querySelector('.ad-description').textContent = ad.description.substring(0, 100) + (ad.description.length > 100 ? '...' : '');
        clone.querySelector('.ad-date').textContent = DateTimeManager.formatDate(ad.created_at, { day: 'numeric', month: 'short', year: 'numeric' });
        clone.querySelector('.ad-amount').textContent = ad.amount + ' €';
        clone.querySelector('.ad-weight').textContent = ad.weight + ' kg';
        
        // Statut
        const statusBadge = clone.querySelector('.ad-status');
        statusBadge.textContent = getStatusText(ad.status);
        statusBadge.className = 'badge ' + getStatusClass(ad.status);
        
        // Livreur
        const courierElement = clone.querySelector('.ad-courier');
        if (ad.courier_name) {
            courierElement.textContent = 'Livreur: ' + ad.courier_name;
        } else {
            courierElement.textContent = 'Aucun livreur';
        }
        
        // Boutons d'action
        const detailsBtn = clone.querySelector('.ad-details-btn');
        const chatBtn = clone.querySelector('.ad-chat-btn');
        const editBtn = clone.querySelector('.ad-edit-btn');
        const cancelBtn = clone.querySelector('.ad-cancel-btn');
        
        detailsBtn.addEventListener('click', () => viewAdDetails(ad.id));
        
        // Afficher les boutons selon le statut
        if (ad.status === 'assigned' || ad.status === 'picked_up' || ad.status === 'in_transit') {
            chatBtn.style.display = 'block';
            chatBtn.addEventListener('click', () => openChat(ad.id));
            // Ajout du bouton Suivre mon colis
            let trackBtn = document.createElement('button');
            trackBtn.className = 'btn btn-outline-primary btn-sm ms-2 ad-track-btn';
            trackBtn.innerHTML = '<i class="fas fa-map-marker-alt me-1"></i> Suivre mon colis';
            trackBtn.onclick = () => window.location.href = `/views/tracking.php?delivery_id=${ad.id}`;
            detailsBtn.parentNode.appendChild(trackBtn);
        }
        
        if (ad.status === 'pending') {
            editBtn.style.display = 'block';
            cancelBtn.style.display = 'block';
            editBtn.addEventListener('click', () => editAd(ad.id));
            cancelBtn.addEventListener('click', () => cancelAd(ad.id));
        }
        
        return clone;
    }
    
    // Fonction pour obtenir le texte du statut
    function getStatusText(status) {
        const statusTexts = {
            'pending': 'En attente',
            'assigned': 'Attribuée',
            'picked_up': 'Ramassée',
            'in_transit': 'En transit',
            'delivered': 'Livrée',
            'cancelled': 'Annulée'
        };
        return statusTexts[status] || status;
    }
    
    // Fonction pour obtenir la classe CSS du statut
    function getStatusClass(status) {
        const statusClasses = {
            'pending': 'bg-warning',
            'assigned': 'bg-info',
            'picked_up': 'bg-primary',
            'in_transit': 'bg-primary',
            'delivered': 'bg-success',
            'cancelled': 'bg-danger'
        };
        return statusClasses[status] || 'bg-secondary';
    }
    
    // Fonction pour mettre à jour les statistiques
    function updateStatistics() {
        const activeAds = ads.filter(ad => ad.status === 'pending').length;
        const completedDeliveries = ads.filter(ad => ad.status === 'delivered').length;
        const inProgress = ads.filter(ad => ['assigned', 'picked_up', 'in_transit'].includes(ad.status)).length;
        
        // Calculer la note moyenne
        const ratedAds = ads.filter(ad => ad.rating);
        const averageRating = ratedAds.length > 0 
            ? (ratedAds.reduce((sum, ad) => sum + ad.rating, 0) / ratedAds.length).toFixed(1)
            : '0.0';
        
        document.getElementById('activeAdsCount').textContent = activeAds;
        document.getElementById('completedDeliveriesCount').textContent = completedDeliveries;
        document.getElementById('inProgressCount').textContent = inProgress;
        document.getElementById('averageRating').textContent = averageRating;
    }
    
    // Fonctions d'action
    function viewAdDetails(adId) {
        window.location.href = `/ad-details?id=${adId}`;
    }
    
    function openChat(adId) {
        window.location.href = `/chat?ad_id=${adId}`;
    }
    
    function editAd(adId) {
        window.location.href = `/expeditor/edit-ad?id=${adId}`;
    }
    
    async function cancelAd(adId) {
        if (!confirm('Êtes-vous sûr de vouloir annuler cette annonce ?')) {
            return;
        }
        
        try {
            const response = await ApiService.put(`/ads/${adId}/cancel`);
            
            if (response.success) {
                ToastManager.show('Annonce annulée avec succès', 'success');
                loadAds(); // Recharger les annonces
            } else {
                ToastManager.show(response.message || 'Erreur lors de l\'annulation', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            ToastManager.show('Erreur de connexion au serveur', 'error');
        }
    }
    
    // Écouter les mises à jour en temps réel via Supabase
    if (typeof supabase !== 'undefined') {
        supabase
            .channel('ads_updates')
            .on('postgres_changes', {
                event: '*',
                schema: 'public',
                table: 'ads',
                filter: `expeditor_id=eq.${currentUser.id}`
            }, (payload) => {
                // Mettre à jour la liste des annonces
                loadAds();
            })
            .subscribe();
    }
});
</script> 