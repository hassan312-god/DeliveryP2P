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
        .test-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .test-result {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 0.9em;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .success {
            border-left: 4px solid #28a745;
        }
        
        .error {
            border-left: 4px solid #dc3545;
        }
        
        .loading {
            border-left: 4px solid #ffc107;
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
                <a class="nav-link" href="/maps">
                    <i class="fas fa-map"></i> Carte
                </a>
                <a class="nav-link active" href="/maps/test">
                    <i class="fas fa-vial"></i> Test API
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
                    <i class="fas fa-vial text-warning"></i>
                    Test Google Maps API
                </h1>
                <p class="text-center text-muted">
                    Vérifiez que toutes les fonctionnalités de l'API fonctionnent correctement
                </p>
            </div>
        </div>

        <!-- Tests -->
        <div class="row">
            <!-- Test de géocodage -->
            <div class="col-md-6">
                <div class="card test-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt text-danger"></i>
                            Test de Géocodage
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Convertir une adresse en coordonnées GPS</p>
                        
                        <div class="mb-3">
                            <label for="geocode-address" class="form-label">Adresse à géocoder</label>
                            <input type="text" class="form-control" id="geocode-address" 
                                   value="Tour Eiffel, Paris" placeholder="Entrez une adresse">
                        </div>
                        
                        <button class="btn btn-primary" onclick="testGeocode()">
                            <i class="fas fa-search"></i> Tester le géocodage
                        </button>
                        
                        <div id="geocode-result" class="test-result" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- Test de géocodage inverse -->
            <div class="col-md-6">
                <div class="card test-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-map-pin text-success"></i>
                            Test de Géocodage Inverse
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Convertir des coordonnées GPS en adresse</p>
                        
                        <div class="row">
                            <div class="col-6">
                                <label for="reverse-lat" class="form-label">Latitude</label>
                                <input type="number" step="any" class="form-control" id="reverse-lat" 
                                       value="48.8584" placeholder="Latitude">
                            </div>
                            <div class="col-6">
                                <label for="reverse-lng" class="form-label">Longitude</label>
                                <input type="number" step="any" class="form-control" id="reverse-lng" 
                                       value="2.2945" placeholder="Longitude">
                            </div>
                        </div>
                        
                        <button class="btn btn-success mt-3" onclick="testReverseGeocode()">
                            <i class="fas fa-search"></i> Tester le géocodage inverse
                        </button>
                        
                        <div id="reverse-result" class="test-result" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- Test de calcul de distance -->
            <div class="col-md-6">
                <div class="card test-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-route text-info"></i>
                            Test de Calcul de Distance
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Calculer la distance entre deux points</p>
                        
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Point A</label>
                                <input type="text" class="form-control mb-2" id="point-a" 
                                       value="Tour Eiffel, Paris" placeholder="Adresse du point A">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Point B</label>
                                <input type="text" class="form-control mb-2" id="point-b" 
                                       value="Arc de Triomphe, Paris" placeholder="Adresse du point B">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="distance-mode" class="form-label">Mode de transport</label>
                            <select class="form-select" id="distance-mode">
                                <option value="driving">Voiture</option>
                                <option value="walking">À pied</option>
                                <option value="bicycling">Vélo</option>
                                <option value="transit">Transports en commun</option>
                            </select>
                        </div>
                        
                        <button class="btn btn-info" onclick="testDistance()">
                            <i class="fas fa-calculator"></i> Calculer la distance
                        </button>
                        
                        <div id="distance-result" class="test-result" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- Test de recherche de lieux -->
            <div class="col-md-6">
                <div class="card test-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-search text-warning"></i>
                            Test de Recherche de Lieux
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Rechercher des lieux par nom ou type</p>
                        
                        <div class="mb-3">
                            <label for="places-query" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="places-query" 
                                   value="restaurant" placeholder="Ex: restaurant, hôtel, musée...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="places-location" class="form-label">Localisation (optionnel)</label>
                            <input type="text" class="form-control" id="places-location" 
                                   value="Paris" placeholder="Ville ou région">
                        </div>
                        
                        <button class="btn btn-warning" onclick="testPlaces()">
                            <i class="fas fa-search"></i> Rechercher des lieux
                        </button>
                        
                        <div id="places-result" class="test-result" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- Test de validation d'adresse -->
            <div class="col-md-6">
                <div class="card test-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle text-success"></i>
                            Test de Validation d'Adresse
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Vérifier si une adresse est valide</p>
                        
                        <div class="mb-3">
                            <label for="validate-address" class="form-label">Adresse à valider</label>
                            <input type="text" class="form-control" id="validate-address" 
                                   value="123 Rue de la Paix, Paris" placeholder="Entrez une adresse">
                        </div>
                        
                        <button class="btn btn-success" onclick="testValidation()">
                            <i class="fas fa-check"></i> Valider l'adresse
                        </button>
                        
                        <div id="validation-result" class="test-result" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- Test de l'API Key -->
            <div class="col-md-6">
                <div class="card test-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-key text-primary"></i>
                            Test de la Clé API
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Vérifier que la clé API est valide</p>
                        
                        <div class="mb-3">
                            <label class="form-label">Clé API actuelle</label>
                            <input type="text" class="form-control" value="<?= substr(htmlspecialchars($apiKey), 0, 20) ?>..." readonly>
                            <small class="text-muted">Clé masquée pour la sécurité</small>
                        </div>
                        
                        <button class="btn btn-primary" onclick="testApiKey()">
                            <i class="fas fa-key"></i> Tester la clé API
                        </button>
                        
                        <div id="apikey-result" class="test-result" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Résumé des tests -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-list"></i>
                            Résumé des Tests
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-success" id="success-count">0</div>
                                    <div class="text-muted">Tests réussis</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-danger" id="error-count">0</div>
                                    <div class="text-muted">Tests échoués</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-warning" id="pending-count">6</div>
                                    <div class="text-muted">Tests en attente</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <button class="btn btn-outline-primary" onclick="runAllTests()">
                                        <i class="fas fa-play"></i> Lancer tous les tests
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let testResults = {
            success: 0,
            error: 0,
            pending: 6
        };

        // Mettre à jour le compteur
        function updateCounters() {
            document.getElementById('success-count').textContent = testResults.success;
            document.getElementById('error-count').textContent = testResults.error;
            document.getElementById('pending-count').textContent = testResults.pending;
        }

        // Afficher un résultat de test
        function showResult(elementId, data, isSuccess = true) {
            const element = document.getElementById(elementId);
            element.style.display = 'block';
            element.className = `test-result ${isSuccess ? 'success' : 'error'}`;
            element.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            
            if (isSuccess) {
                testResults.success++;
                testResults.pending--;
            } else {
                testResults.error++;
                testResults.pending--;
            }
            updateCounters();
        }

        // Test de géocodage
        async function testGeocode() {
            const address = document.getElementById('geocode-address').value;
            if (!address) {
                alert('Veuillez saisir une adresse');
                return;
            }

            try {
                const response = await fetch('/maps/geocode', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ address })
                });

                const data = await response.json();
                showResult('geocode-result', data, data.success);
            } catch (error) {
                showResult('geocode-result', { error: error.message }, false);
            }
        }

        // Test de géocodage inverse
        async function testReverseGeocode() {
            const lat = document.getElementById('reverse-lat').value;
            const lng = document.getElementById('reverse-lng').value;
            
            if (!lat || !lng) {
                alert('Veuillez saisir les coordonnées');
                return;
            }

            try {
                // Pour le géocodage inverse, on utilise directement l'API Google
                const response = await fetch(`https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=<?= htmlspecialchars($apiKey) ?>`);
                const data = await response.json();
                showResult('reverse-result', data, data.status === 'OK');
            } catch (error) {
                showResult('reverse-result', { error: error.message }, false);
            }
        }

        // Test de calcul de distance
        async function testDistance() {
            const pointA = document.getElementById('point-a').value;
            const pointB = document.getElementById('point-b').value;
            const mode = document.getElementById('distance-mode').value;
            
            if (!pointA || !pointB) {
                alert('Veuillez saisir les deux points');
                return;
            }

            try {
                // D'abord géocoder les deux points
                const [coordA, coordB] = await Promise.all([
                    fetch('/maps/geocode', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ address: pointA })
                    }).then(r => r.json()),
                    fetch('/maps/geocode', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ address: pointB })
                    }).then(r => r.json())
                ]);

                if (!coordA.success || !coordB.success) {
                    throw new Error('Impossible de géocoder une des adresses');
                }

                // Calculer la distance
                const response = await fetch('/maps/calculate-distance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        lat1: coordA.data.lat,
                        lng1: coordA.data.lng,
                        lat2: coordB.data.lat,
                        lng2: coordB.data.lng,
                        mode
                    })
                });

                const data = await response.json();
                showResult('distance-result', data, data.success);
            } catch (error) {
                showResult('distance-result', { error: error.message }, false);
            }
        }

        // Test de recherche de lieux
        async function testPlaces() {
            const query = document.getElementById('places-query').value;
            const location = document.getElementById('places-location').value;
            
            if (!query) {
                alert('Veuillez saisir une requête de recherche');
                return;
            }

            try {
                const response = await fetch('/maps/search-places', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        query,
                        location: location ? { lat: 48.8566, lng: 2.3522 } : null,
                        radius: 5000
                    })
                });

                const data = await response.json();
                showResult('places-result', data, data.success);
            } catch (error) {
                showResult('places-result', { error: error.message }, false);
            }
        }

        // Test de validation d'adresse
        async function testValidation() {
            const address = document.getElementById('validate-address').value;
            
            if (!address) {
                alert('Veuillez saisir une adresse');
                return;
            }

            try {
                const response = await fetch('/maps/validate-address', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ address })
                });

                const data = await response.json();
                showResult('validation-result', data, data.success);
            } catch (error) {
                showResult('validation-result', { error: error.message }, false);
            }
        }

        // Test de la clé API
        async function testApiKey() {
            try {
                // Test simple avec l'API Geocoding
                const response = await fetch(`https://maps.googleapis.com/maps/api/geocode/json?address=Paris&key=<?= htmlspecialchars($apiKey) ?>`);
                const data = await response.json();
                
                const isSuccess = data.status === 'OK' || data.status === 'ZERO_RESULTS';
                showResult('apikey-result', {
                    status: data.status,
                    message: isSuccess ? 'Clé API valide' : 'Erreur avec la clé API',
                    results_count: data.results?.length || 0
                }, isSuccess);
            } catch (error) {
                showResult('apikey-result', { error: error.message }, false);
            }
        }

        // Lancer tous les tests
        async function runAllTests() {
            // Réinitialiser les compteurs
            testResults = { success: 0, error: 0, pending: 6 };
            updateCounters();
            
            // Masquer tous les résultats
            document.querySelectorAll('.test-result').forEach(el => el.style.display = 'none');
            
            // Lancer les tests dans l'ordre
            await testApiKey();
            await new Promise(resolve => setTimeout(resolve, 500));
            await testGeocode();
            await new Promise(resolve => setTimeout(resolve, 500));
            await testReverseGeocode();
            await new Promise(resolve => setTimeout(resolve, 500));
            await testDistance();
            await new Promise(resolve => setTimeout(resolve, 500));
            await testPlaces();
            await new Promise(resolve => setTimeout(resolve, 500));
            await testValidation();
        }

        // Initialiser les compteurs
        updateCounters();
    </script>
</body>
</html> 