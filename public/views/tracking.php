<?php $page_title = 'Suivi de mon colis - LivraisonP2P'; ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Illustration SVG -->
            <div class="text-center mb-4">
                <img src="/assets/images/tracking-illustration.svg" alt="Suivi colis" style="max-height: 180px;">
            </div>

            <!-- Illustration SVG personnalisée -->
            <div class="text-center mb-4">
                <svg width="180" height="120" viewBox="0 0 360 240" fill="none" xmlns="http://www.w3.org/2000/svg" style="max-width:100%;height:auto;">
                    <rect width="360" height="240" rx="24" fill="#F3F6FB"/>
                    <ellipse cx="180" cy="180" rx="120" ry="32" fill="#E0E7EF"/>
                    <rect x="80" y="120" width="200" height="40" rx="12" fill="#2563eb"/>
                    <rect x="120" y="100" width="120" height="24" rx="8" fill="#fff"/>
                    <rect x="160" y="80" width="40" height="16" rx="6" fill="#2563eb"/>
                    <rect x="170" y="60" width="20" height="20" rx="6" fill="#fff"/>
                    <rect x="140" y="140" width="80" height="24" rx="8" fill="#fff"/>
                    <rect x="180" y="160" width="40" height="16" rx="6" fill="#2563eb"/>
                    <circle cx="110" cy="160" r="8" fill="#2563eb"/>
                    <circle cx="250" cy="160" r="8" fill="#2563eb"/>
                    <rect x="140" y="120" width="80" height="8" rx="4" fill="#fff"/>
                    <rect x="180" y="180" width="40" height="8" rx="4" fill="#fff"/>
                    <rect x="100" y="180" width="40" height="8" rx="4" fill="#fff"/>
                    <rect x="220" y="180" width="40" height="8" rx="4" fill="#fff"/>
                    <rect x="170" y="200" width="20" height="8" rx="4" fill="#2563eb"/>
                    <rect x="90" y="200" width="40" height="8" rx="4" fill="#2563eb"/>
                    <rect x="230" y="200" width="40" height="8" rx="4" fill="#2563eb"/>
                    <rect x="150" y="210" width="60" height="8" rx="4" fill="#2563eb"/>
                    <rect x="170" y="220" width="20" height="8" rx="4" fill="#2563eb"/>
                    <circle cx="180" cy="120" r="12" fill="#fff" stroke="#2563eb" stroke-width="4"/>
                    <rect x="170" y="110" width="20" height="8" rx="4" fill="#2563eb"/>
                    <rect x="180" y="100" width="8" height="8" rx="4" fill="#2563eb"/>
                </svg>
            </div>

            <!-- Carte en temps réel -->
            <div class="card shadow mb-4">
                <div class="card-body p-0" style="height: 320px;">
                    <div id="trackingMap" style="width: 100%; height: 100%; border-radius: 12px;"></div>
                </div>
            </div>

            <!-- Timeline animée -->
            <div class="timeline mb-4">
                <div class="timeline-step active" id="step-created">
                    <div class="timeline-dot"></div>
                    <span>Créée</span>
                </div>
                <div class="timeline-step" id="step-picked">
                    <div class="timeline-dot"></div>
                    <span>Ramassée</span>
                </div>
                <div class="timeline-step" id="step-transit">
                    <div class="timeline-dot"></div>
                    <span>En transit</span>
                </div>
                <div class="timeline-step" id="step-delivered">
                    <div class="timeline-dot"></div>
                    <span>Livrée</span>
                </div>
            </div>

            <!-- Statut et infos colis/livreur -->
            <div class="card mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-center gap-3">
                    <img id="packageImage" src="/assets/images/default-package.jpg" alt="Colis" class="rounded shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                    <div class="flex-fill">
                        <h5 id="packageTitle" class="mb-1">Titre du colis</h5>
                        <div class="text-muted mb-1" id="packageDesc">Description du colis...</div>
                        <div class="small text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i> <span id="pickupAddress">Départ</span> → <span id="deliveryAddress">Arrivée</span>
                        </div>
                    </div>
                    <div class="text-center">
                        <span class="badge bg-primary" id="deliveryStatus">En transit</span>
                        <div class="mt-2 fw-bold" id="packageAmount">15 €</div>
                    </div>
                </div>
            </div>

            <!-- Infos livreur et actions -->
            <div class="card mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-center gap-3">
                    <img id="courierImage" src="/assets/images/default-avatar.png" alt="Livreur" class="rounded-circle shadow-sm" style="width: 64px; height: 64px; object-fit: cover;">
                    <div class="flex-fill">
                        <div class="fw-bold" id="courierName">Nom du livreur</div>
                        <div class="small text-muted" id="courierVehicle">Véhicule</div>
                        <div class="small text-muted" id="courierPlate">Plaque : --</div>
                        <div class="small text-muted" id="courierStats">4.9 ★ | 120 livraisons</div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button class="btn btn-outline-primary position-relative" id="chatBtn">
                            <i class="fas fa-comments me-1"></i> Chat
                            <span id="chatBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">Nouveau</span>
                        </button>
                        <a class="btn btn-outline-success" id="callBtn" href="#" target="_blank">
                            <i class="fas fa-phone me-1"></i> Appeler
                        </a>
                    </div>
                </div>
            </div>

            <!-- QR code de livraison -->
            <div class="text-center mb-4">
                <div id="qrCodeContainer">
                    <img src="/assets/images/qr-placeholder.svg" alt="QR Code" style="width: 120px;">
                    <div class="small text-muted mt-2">À présenter à la livraison</div>
                </div>
            </div>

            <!-- Statut et ETA -->
            <div class="text-center mb-3">
                <span id="deliveryStatus" class="badge bg-primary fs-5">En transit</span>
                <span id="eta" class="ms-2 text-muted"></span>
            </div>
        </div>
    </div>
</div>

<!-- Toast notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
  <div id="toastContainer"></div>
</div>

<!-- Timeline CSS -->
<style>
.timeline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}
.timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}
.timeline-dot {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #e0e7ef;
    border: 3px solid #2563eb;
    margin-bottom: 6px;
    transition: background 0.3s, border 0.3s;
}
.timeline-step.active .timeline-dot {
    background: #2563eb;
    border-color: #2563eb;
}
.timeline-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 8px;
    right: -50%;
    width: 100%;
    height: 4px;
    background: #e0e7ef;
    z-index: 0;
}
.timeline-step.active:not(:last-child)::after {
    background: #2563eb;
}
.timeline-step span {
    font-size: 0.95rem;
    color: #2563eb;
    font-weight: 500;
}
.pulse {
  animation: pulse 1.2s infinite;
}
@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(37,99,235,0.5); }
  70% { box-shadow: 0 0 0 10px rgba(37,99,235,0); }
  100% { box-shadow: 0 0 0 0 rgba(37,99,235,0); }
}
</style>

<!-- Google Maps JS (remplacer VOTRE_CLE_API) -->
<script src="https://maps.googleapis.com/maps/api/js?key=VOTRE_CLE_API"></script>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js/dist/umd/supabase.min.js"></script>
<script>
// Config Supabase (remplace par tes vraies clés)
const SUPABASE_URL = 'https://YOUR_PROJECT.supabase.co';
const SUPABASE_KEY = 'YOUR_PUBLIC_ANON_KEY';
const supabase = supabase.createClient(SUPABASE_URL, SUPABASE_KEY);

// Récupérer l'ID de la livraison depuis l'URL
const urlParams = new URLSearchParams(window.location.search);
const deliveryId = urlParams.get('delivery_id');

let map, courierMarker, routePolyline;
let lastStatus = null;
let lastMessageCount = 0;

async function fetchDelivery() {
    // Récupérer la livraison depuis Supabase
    const { data, error } = await supabase
        .from('deliveries')
        .select('*')
        .eq('id', deliveryId)
        .single();
    if (error) {
        alert('Erreur lors du chargement de la livraison');
        return;
    }
    updateTrackingUI(data);
    return data;
}

function updateTrackingUI(delivery) {
    // Mettre à jour les infos colis/livreur/statut
    document.getElementById('packageTitle').textContent = delivery.title;
    document.getElementById('packageDesc').textContent = delivery.description;
    document.getElementById('pickupAddress').textContent = delivery.pickup_address;
    document.getElementById('deliveryAddress').textContent = delivery.delivery_address;
    document.getElementById('packageAmount').textContent = delivery.amount + ' €';
    document.getElementById('deliveryStatus').textContent = getStatusText(delivery.status);
    document.getElementById('packageImage').src = delivery.image_url || '/assets/images/default-package.jpg';
    document.getElementById('courierName').textContent = delivery.courier_name || 'Livreur';
    document.getElementById('courierVehicle').textContent = delivery.courier_vehicle || '';
    document.getElementById('courierImage').src = delivery.courier_avatar || '/assets/images/default-avatar.png';
    // Champs dynamiques supplémentaires
    document.getElementById('courierStats').textContent =
      (delivery.courier_rating ? delivery.courier_rating + ' ★ | ' : '') +
      (delivery.courier_deliveries ? delivery.courier_deliveries + ' livraisons' : '');
    document.getElementById('courierPlate').textContent = delivery.courier_plate ? 'Plaque : ' + delivery.courier_plate : '';
    window.currentCourierPhone = delivery.courier_phone || '';
    // QR code (optionnel)
    if (delivery.qr_url) {
        document.querySelector('#qrCodeContainer img').src = delivery.qr_url;
    }
    // Timeline
    updateTimeline(delivery.status);
    // Carte
    updateTrackingMap(delivery);
    // ETA
    document.getElementById('eta').textContent = delivery.eta ? 'Arrivée estimée : ' + delivery.eta : '';
    // Statut badge couleur
    const statusBadge = document.getElementById('deliveryStatus');
    statusBadge.textContent = getStatusText(delivery.status);
    statusBadge.className = 'badge fs-5 ' + getStatusClass(delivery.status);
    // Toast notification si changement de statut
    if (lastStatus && lastStatus !== delivery.status) {
      showToast('Statut de la livraison : ' + getStatusText(delivery.status), 'primary');
    }
    lastStatus = delivery.status;
}

function getStatusText(status) {
    const statusTexts = {
        'assigned': 'Attribuée',
        'picked_up': 'Ramassée',
        'in_transit': 'En transit',
        'delivered': 'Livrée'
    };
    return statusTexts[status] || status;
}

function getStatusClass(status) {
  switch(status) {
    case 'assigned': return 'bg-info';
    case 'picked_up': return 'bg-warning text-dark';
    case 'in_transit': return 'bg-primary';
    case 'delivered': return 'bg-success';
    default: return 'bg-secondary';
  }
}

function updateTrackingMap(delivery) {
    // Initialiser la carte si besoin
    if (!map) {
        map = new google.maps.Map(document.getElementById('trackingMap'), {
            center: { lat: delivery.pickup_lat, lng: delivery.pickup_lng },
            zoom: 12,
            disableDefaultUI: true,
        });
        // Marqueur départ
        new google.maps.Marker({
            position: { lat: delivery.pickup_lat, lng: delivery.pickup_lng },
            map,
            icon: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',
            title: 'Départ'
        });
        // Marqueur arrivée
        new google.maps.Marker({
            position: { lat: delivery.delivery_lat, lng: delivery.delivery_lng },
            map,
            icon: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
            title: 'Arrivée'
        });
    }
    // Marqueur livreur
    if (!courierMarker) {
        courierMarker = new google.maps.Marker({
            position: { lat: delivery.courier_lat, lng: delivery.courier_lng },
            map,
            icon: 'https://maps.google.com/mapfiles/ms/icons/truck.png',
            title: 'Livreur'
        });
        // Animation pulse
        setTimeout(() => {
          if (courierMarker && courierMarker.getIcon()) {
            const markerEl = document.querySelector('img[alt="Livreur"]');
            if (markerEl) markerEl.classList.add('pulse');
          }
        }, 1000);
    } else {
        courierMarker.setPosition({ lat: delivery.courier_lat, lng: delivery.courier_lng });
    }
    // Tracé du trajet
    const path = [
        { lat: delivery.pickup_lat, lng: delivery.pickup_lng },
        { lat: delivery.courier_lat, lng: delivery.courier_lng },
        { lat: delivery.delivery_lat, lng: delivery.delivery_lng }
    ];
    if (!routePolyline) {
        routePolyline = new google.maps.Polyline({
            path,
            geodesic: true,
            strokeColor: '#2563eb',
            strokeOpacity: 0.8,
            strokeWeight: 4,
            map
        });
    } else {
        routePolyline.setPath(path);
    }
    // Centrer la carte sur le livreur
    map.setCenter({ lat: delivery.courier_lat, lng: delivery.courier_lng });
}

// Abonnement en temps réel Supabase
function subscribeToDeliveryRealtime() {
    supabase
        .channel('public:deliveries')
        .on('postgres_changes', { event: 'UPDATE', schema: 'public', table: 'deliveries', filter: `id=eq.${deliveryId}` }, payload => {
            if (payload.new) {
                updateTrackingUI(payload.new);
            }
        })
        .subscribe();
}

function showToast(message, type = 'info') {
  const toastId = 'toast-' + Date.now();
  const toast = document.createElement('div');
  toast.className = `toast align-items-center text-bg-${type} border-0 show`;
  toast.id = toastId;
  toast.role = 'alert';
  toast.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
  document.getElementById('toastContainer').appendChild(toast);
  setTimeout(() => toast.remove(), 5000);
}

// Badge nouveau message (Supabase)
async function checkNewMessages() {
  const { data, error } = await supabase
    .from('messages')
    .select('id,read,delivery_id')
    .eq('delivery_id', deliveryId)
    .order('id', { ascending: false });
  if (!error && data) {
    const unread = data.filter(m => !m.read).length;
    const badge = document.getElementById('chatBadge');
    if (unread > 0) {
      badge.style.display = 'inline-block';
      badge.textContent = unread > 9 ? '9+' : 'Nouveau';
      showToast('Nouveau message reçu', 'danger');
    } else {
      badge.style.display = 'none';
    }
    lastMessageCount = unread;
  }
}

// Abonnement temps réel messages
function subscribeToMessagesRealtime() {
  supabase
    .channel('public:messages')
    .on('postgres_changes', { event: 'INSERT', schema: 'public', table: 'messages', filter: `delivery_id=eq.${deliveryId}` }, payload => {
      checkNewMessages();
    })
    .subscribe();
}

window.addEventListener('DOMContentLoaded', async function() {
    if (!deliveryId) {
        alert('Aucune livraison sélectionnée');
        return;
    }
    await fetchDelivery();
    subscribeToDeliveryRealtime();
    await checkNewMessages();
    subscribeToMessagesRealtime();
});

// Timeline dynamique (exemple, à brancher sur le vrai statut)
function updateTimeline(status) {
    const steps = ['created', 'picked', 'transit', 'delivered'];
    steps.forEach((step, idx) => {
        const el = document.getElementById('step-' + step);
        if (el) {
            if (steps.indexOf(status) >= idx) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        }
    });
}
// Exemple d'appel : updateTimeline('transit');

// Boutons chat/appel (exemple)
document.getElementById('chatBtn').addEventListener('click', function() {
    window.location.href = '/chat?delivery_id=' + deliveryId;
});
document.getElementById('callBtn').addEventListener('click', function(e) {
    const phone = window.currentCourierPhone || '';
    if (phone) {
        this.href = 'tel:' + phone;
    } else {
        e.preventDefault();
        alert('Numéro non disponible');
    }
});
</script> 