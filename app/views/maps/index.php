<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - LivraisonP2P</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        #map {
            height: 70vh;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .map-controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .delivery-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .search-result-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .search-result-item:hover {
            background: #f8f9fa;
        }
        
        .marker-info {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            max-width: 300px;
        }
        
        .route-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-truck"></i> LivraisonP2P
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/dashboard">
                    <i class="fas fa-home"></i> Tableau de bord
                </a>
                <a class="nav-link active" href="/maps">
                    <i class="fas fa-map"></i> Carte
                </a>
                <a class="nav-link" href="/profile">
                    <i class="fas fa-user"></i> Profil
                </a>
                <a class="nav-link" href="/logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- En-tête -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-center mb-3">
                    <i class="fas fa-map-marked-alt text-primary"></i>
                    Carte de Livraison
                </h1>
                <p class="text-center text-muted">
                    Visualisez et gérez vos livraisons en temps réel
                </p>
            </div>
        </div>

        <!-- Contrôles de la carte -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="map-controls">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="search-box">
                                <label for="origin" class="form-label">
                                    <i class="fas fa-map-marker-alt text-danger"></i> Point de départ
                                </label>
                                <input type="text" class="form-control" id="origin" placeholder="Entrez l'adresse de départ">
                                <div class="search-results" id="origin-results"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="search-box">
                                <label for="destination" class="form-label">
                                    <i class="fas fa-flag-checkered text-success"></i> Destination
                                </label>
                                <input type="text" class="form-control" id="destination" placeholder="Entrez l'adresse de destination">
                                <div class="search-results" id="destination-results"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="transport-mode" class="form-label">
                                <i class="fas fa-route text-info"></i> Mode de transport
                            </label>
                            <select class="form-select" id="transport-mode">
                                <option value="driving">Voiture</option>
                                <option value="walking">À pied</option>
                                <option value="bicycling">Vélo</option>
                                <option value="transit">Transports en commun</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-center">
                            <button class="btn btn-primary" onclick="calculateRoute()">
                                <i class="fas fa-directions"></i> Calculer l'itinéraire
                            </button>
                            <button class="btn btn-outline-secondary" onclick="clearRoute()">
                                <i class="fas fa-eraser"></i> Effacer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte et informations -->
        <div class="row">
            <div class="col-lg-8">
                <div id="map"></div>
            </div>
            <div class="col-lg-4">
                <!-- Informations de livraison -->
                <div class="delivery-info">
                    <h5><i class="fas fa-info-circle"></i> Informations de livraison</h5>
                    <div id="delivery-details">
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt"></i>
                            <strong>Départ:</strong> <span id="origin-display">Non défini</span>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-flag-checkered"></i>
                            <strong>Destination:</strong> <span id="destination-display">Non défini</span>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-route"></i>
                            <strong>Distance:</strong> <span id="distance-display">-</span>
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-clock"></i>
                            <strong>Durée:</strong> <span id="duration-display">-</span>
                        </p>
                    </div>
                </div>

                <!-- Instructions d'itinéraire -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-list-ol"></i> Instructions d'itinéraire
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="directions-panel" style="max-height: 300px; overflow-y: auto;">
                            <p class="text-muted">Calculez un itinéraire pour voir les instructions</p>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt"></i> Actions rapides
                        </h6>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-success btn-sm w-100 mb-2" onclick="startDelivery()">
                            <i class="fas fa-play"></i> Démarrer la livraison
                        </button>
                        <button class="btn btn-info btn-sm w-100 mb-2" onclick="shareRoute()">
                            <i class="fas fa-share"></i> Partager l'itinéraire
                        </button>
                        <button class="btn btn-warning btn-sm w-100" onclick="saveRoute()">
                            <i class="fas fa-bookmark"></i> Sauvegarder
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let map;
        let directionsService;
        let directionsRenderer;
        let markers = [];
        let currentRoute = null;

        // Initialiser la carte
        function initMap() {
            // Centre par défaut (Paris)
            const defaultCenter = { lat: 48.8566, lng: 2.3522 };
            
            // Créer la carte
            map = new google.maps.Map(document.getElementById('map'), {
                center: defaultCenter,
                zoom: 13,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
            });

            // Initialiser les services
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                suppressMarkers: true
            });
            directionsRenderer.setMap(map);

            // Ajouter les contrôles de recherche
            setupSearchBoxes();
            
            // Détecter la géolocalisation
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        map.setCenter(pos);
                        addMarker(pos, 'Votre position', 'current-location');
                    },
                    () => {
                        console.log('Géolocalisation non disponible');
                    }
                );
            }
        }

        // Configurer les boîtes de recherche
        function setupSearchBoxes() {
            const originInput = document.getElementById('origin');
            const destinationInput = document.getElementById('destination');

            // Recherche automatique pour l'origine
            originInput.addEventListener('input', debounce(function() {
                searchPlaces(this.value, 'origin-results');
            }, 300));

            // Recherche automatique pour la destination
            destinationInput.addEventListener('input', debounce(function() {
                searchPlaces(this.value, 'destination-results');
            }, 300));
        }

        // Rechercher des lieux
        async function searchPlaces(query, resultsId) {
            if (query.length < 3) {
                document.getElementById(resultsId).style.display = 'none';
                return;
            }

            try {
                const response = await fetch('/maps/search-places', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ query })
                });

                const data = await response.json();
                
                if (data.success) {
                    displaySearchResults(data.data, resultsId);
                }
            } catch (error) {
                console.error('Erreur de recherche:', error);
            }
        }

        // Afficher les résultats de recherche
        function displaySearchResults(places, resultsId) {
            const resultsDiv = document.getElementById(resultsId);
            resultsDiv.innerHTML = '';
            
            places.forEach(place => {
                const item = document.createElement('div');
                item.className = 'search-result-item';
                item.innerHTML = `
                    <div><strong>${place.name}</strong></div>
                    <div class="text-muted small">${place.formatted_address}</div>
                `;
                
                item.addEventListener('click', () => {
                    const inputId = resultsId === 'origin-results' ? 'origin' : 'destination';
                    document.getElementById(inputId).value = place.formatted_address;
                    resultsDiv.style.display = 'none';
                    
                    // Ajouter un marqueur
                    addMarker(
                        { lat: place.lat, lng: place.lng },
                        place.name,
                        inputId === 'origin' ? 'origin' : 'destination'
                    );
                });
                
                resultsDiv.appendChild(item);
            });
            
            resultsDiv.style.display = places.length > 0 ? 'block' : 'none';
        }

        // Calculer l'itinéraire
        async function calculateRoute() {
            const origin = document.getElementById('origin').value;
            const destination = document.getElementById('destination').value;
            const mode = document.getElementById('transport-mode').value;

            if (!origin || !destination) {
                alert('Veuillez saisir l\'origine et la destination');
                return;
            }

            try {
                const response = await fetch('/maps/get-directions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ origin, destination, mode })
                });

                const data = await response.json();
                
                if (data.success) {
                    displayRoute(data.data, origin, destination);
                } else {
                    alert('Erreur: ' + data.error);
                }
            } catch (error) {
                console.error('Erreur de calcul d\'itinéraire:', error);
                alert('Erreur lors du calcul de l\'itinéraire');
            }
        }

        // Afficher l'itinéraire
        function displayRoute(routeData, origin, destination) {
            currentRoute = routeData;
            
            // Mettre à jour les informations
            document.getElementById('origin-display').textContent = origin;
            document.getElementById('destination-display').textContent = destination;
            document.getElementById('distance-display').textContent = routeData.distance;
            document.getElementById('duration-display').textContent = routeData.duration;

            // Afficher l'itinéraire sur la carte
            const request = {
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode[document.getElementById('transport-mode').value.toUpperCase()]
            };

            directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                    
                    // Afficher les instructions
                    displayDirections(result.routes[0].legs[0].steps);
                }
            });
        }

        // Afficher les instructions d'itinéraire
        function displayDirections(steps) {
            const panel = document.getElementById('directions-panel');
            panel.innerHTML = '';
            
            steps.forEach((step, index) => {
                const stepDiv = document.createElement('div');
                stepDiv.className = 'mb-2 p-2 border-bottom';
                stepDiv.innerHTML = `
                    <div class="d-flex align-items-start">
                        <span class="badge bg-primary me-2">${index + 1}</span>
                        <div>
                            <div class="small">${step.instructions.replace(/<[^>]*>/g, '')}</div>
                            <div class="text-muted small">${step.distance?.text || ''}</div>
                        </div>
                    </div>
                `;
                panel.appendChild(stepDiv);
            });
        }

        // Ajouter un marqueur
        function addMarker(position, title, type) {
            const icon = getMarkerIcon(type);
            
            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: title,
                icon: icon
            });

            // Info window
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div class="marker-info">
                        <h6>${title}</h6>
                        <p class="mb-1">Lat: ${position.lat.toFixed(6)}</p>
                        <p class="mb-0">Lng: ${position.lng.toFixed(6)}</p>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });

            markers.push(marker);
        }

        // Obtenir l'icône du marqueur
        function getMarkerIcon(type) {
            const icons = {
                'origin': 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                'destination': 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',
                'current-location': 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
            };
            return icons[type] || 'http://maps.google.com/mapfiles/ms/icons/red-dot.png';
        }

        // Effacer l'itinéraire
        function clearRoute() {
            directionsRenderer.setDirections({ routes: [] });
            markers.forEach(marker => marker.setMap(null));
            markers = [];
            
            document.getElementById('origin').value = '';
            document.getElementById('destination').value = '';
            document.getElementById('origin-display').textContent = 'Non défini';
            document.getElementById('destination-display').textContent = 'Non défini';
            document.getElementById('distance-display').textContent = '-';
            document.getElementById('duration-display').textContent = '-';
            document.getElementById('directions-panel').innerHTML = '<p class="text-muted">Calculez un itinéraire pour voir les instructions</p>';
            
            currentRoute = null;
        }

        // Démarrer la livraison
        function startDelivery() {
            if (!currentRoute) {
                alert('Veuillez d\'abord calculer un itinéraire');
                return;
            }
            
            if (confirm('Démarrer la livraison ?')) {
                // Ici tu peux ajouter la logique pour démarrer la livraison
                alert('Livraison démarrée !');
            }
        }

        // Partager l'itinéraire
        function shareRoute() {
            if (!currentRoute) {
                alert('Aucun itinéraire à partager');
                return;
            }
            
            const url = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(document.getElementById('origin').value)}&destination=${encodeURIComponent(document.getElementById('destination').value)}`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Itinéraire de livraison',
                    text: `Itinéraire: ${document.getElementById('origin').value} → ${document.getElementById('destination').value}`,
                    url: url
                });
            } else {
                // Fallback: copier dans le presse-papiers
                navigator.clipboard.writeText(url).then(() => {
                    alert('Lien copié dans le presse-papiers !');
                });
            }
        }

        // Sauvegarder l'itinéraire
        function saveRoute() {
            if (!currentRoute) {
                alert('Aucun itinéraire à sauvegarder');
                return;
            }
            
            // Ici tu peux ajouter la logique pour sauvegarder
            alert('Itinéraire sauvegardé !');
        }

        // Fonction utilitaire pour debounce
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>
    
    <!-- Google Maps API -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($apiKey) ?>&libraries=places&callback=initMap"></script>
</body>
</html> 