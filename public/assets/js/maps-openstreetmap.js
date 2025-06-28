// Configuration OpenStreetMap + Leaflet (Alternative gratuite à Google Maps)

class OpenStreetMapService {
    constructor() {
        this.map = null;
        this.markers = [];
    }

    // Initialiser la carte
    initMap(elementId, center = [48.8566, 2.3522]) { // Paris par défaut
        this.map = L.map(elementId).setView(center, 13);
        
        // Ajouter la couche de tuiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);

        return this.map;
    }

    // Géocodage gratuit avec Nominatim
    async geocodeAddress(address) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`
            );
            const data = await response.json();
            
            if (data.length > 0) {
                return {
                    lat: parseFloat(data[0].lat),
                    lng: parseFloat(data[0].lon),
                    display_name: data[0].display_name
                };
            }
            return null;
        } catch (error) {
            console.error('Erreur de géocodage:', error);
            return null;
        }
    }

    // Géocodage inverse (coordonnées vers adresse)
    async reverseGeocode(lat, lng) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`
            );
            const data = await response.json();
            
            return {
                address: data.display_name,
                street: data.address?.road || '',
                city: data.address?.city || data.address?.town || '',
                postcode: data.address?.postcode || ''
            };
        } catch (error) {
            console.error('Erreur de géocodage inverse:', error);
            return null;
        }
    }

    // Ajouter un marqueur
    addMarker(lat, lng, title = '', popup = '') {
        const marker = L.marker([lat, lng])
            .addTo(this.map)
            .bindPopup(popup || title);
        
        this.markers.push(marker);
        return marker;
    }

    // Calculer la distance entre deux points
    calculateDistance(lat1, lng1, lat2, lng2) {
        return this.map.distance([lat1, lng1], [lat2, lng2]) / 1000; // en km
    }

    // Centrer la carte sur une adresse
    async centerOnAddress(address) {
        const coords = await this.geocodeAddress(address);
        if (coords) {
            this.map.setView([coords.lat, coords.lng], 15);
            return coords;
        }
        return null;
    }

    // Effacer tous les marqueurs
    clearMarkers() {
        this.markers.forEach(marker => this.map.removeLayer(marker));
        this.markers = [];
    }
}

// Exemple d'utilisation
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la carte
    const mapService = new OpenStreetMapService();
    const map = mapService.initMap('map');
    
    // Exemple : centrer sur une adresse
    document.getElementById('search-address')?.addEventListener('click', async function() {
        const address = document.getElementById('address-input').value;
        const coords = await mapService.centerOnAddress(address);
        
        if (coords) {
            mapService.addMarker(coords.lat, coords.lng, 'Adresse trouvée', coords.display_name);
        }
    });
}); 