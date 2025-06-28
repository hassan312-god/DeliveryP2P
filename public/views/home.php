<?php $page_title = 'Accueil - LivraisonP2P'; ?>

<!-- Hero Section -->
<section class="hero-section bg-gradient-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    Livraison entre particuliers
                    <span class="text-warning">simplifiée</span>
                </h1>
                <p class="lead mb-4">
                    Connectez-vous avec des livreurs de confiance pour vos envois. 
                    Rapide, sécurisé et économique.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <?php if (!$is_logged_in): ?>
                        <a href="/register" class="btn btn-warning btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Commencer maintenant
                        </a>
                        <a href="/login" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Se connecter
                        </a>
                    <?php else: ?>
                        <?php if ($user_role === 'expeditor'): ?>
                            <a href="/expeditor/create-ad" class="btn btn-warning btn-lg">
                                <i class="fas fa-plus me-2"></i>
                                Créer une annonce
                            </a>
                        <?php elseif ($user_role === 'courier'): ?>
                            <a href="/courier/dashboard" class="btn btn-warning btn-lg">
                                <i class="fas fa-truck me-2"></i>
                                Voir les livraisons
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="/assets/images/hero-illustration.svg" alt="Livraison" class="img-fluid" style="max-height: 400px;">
            </div>
        </div>
    </div>
</section>

<!-- Fonctionnalités principales -->
<section class="features-section py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="display-5 fw-bold mb-3">Comment ça marche ?</h2>
                <p class="lead text-muted">Trois étapes simples pour vos livraisons</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card text-center p-4 h-100">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-edit fa-3x text-primary"></i>
                    </div>
                    <h4>1. Créez votre annonce</h4>
                    <p class="text-muted">
                        Décrivez votre colis, définissez les adresses de départ et d'arrivée, 
                        et fixez votre budget.
                    </p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card text-center p-4 h-100">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-handshake fa-3x text-success"></i>
                    </div>
                    <h4>2. Un livreur accepte</h4>
                    <p class="text-muted">
                        Un livreur disponible à proximité accepte votre livraison 
                        et vous contacte pour organiser le ramassage.
                    </p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card text-center p-4 h-100">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-check-circle fa-3x text-warning"></i>
                    </div>
                    <h4>3. Livraison sécurisée</h4>
                    <p class="text-muted">
                        Suivez votre colis en temps réel et confirmez la réception 
                        par scan QR code.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistiques -->
<section class="stats-section bg-light py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold text-primary" id="totalDeliveries">0</h3>
                    <p class="text-muted">Livraisons effectuées</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold text-success" id="activeCouriers">0</h3>
                    <p class="text-muted">Livreurs actifs</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold text-warning" id="satisfactionRate">0%</h3>
                    <p class="text-muted">Satisfaction client</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold text-info" id="avgDeliveryTime">0h</h3>
                    <p class="text-muted">Temps moyen de livraison</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Avantages -->
<section class="benefits-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="display-5 fw-bold mb-4">Pourquoi choisir LivraisonP2P ?</h2>
                
                <div class="benefit-item d-flex align-items-start mb-4">
                    <div class="benefit-icon me-3">
                        <i class="fas fa-euro-sign fa-2x text-success"></i>
                    </div>
                    <div>
                        <h5>Économique</h5>
                        <p class="text-muted mb-0">Jusqu'à 50% moins cher que les services traditionnels</p>
                    </div>
                </div>
                
                <div class="benefit-item d-flex align-items-start mb-4">
                    <div class="benefit-icon me-3">
                        <i class="fas fa-shield-alt fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h5>Sécurisé</h5>
                        <p class="text-muted mb-0">Confirmation QR code et suivi en temps réel</p>
                    </div>
                </div>
                
                <div class="benefit-item d-flex align-items-start mb-4">
                    <div class="benefit-icon me-3">
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                    <div>
                        <h5>Rapide</h5>
                        <p class="text-muted mb-0">Livraison en quelques heures dans la même ville</p>
                    </div>
                </div>
                
                <div class="benefit-item d-flex align-items-start">
                    <div class="benefit-icon me-3">
                        <i class="fas fa-users fa-2x text-info"></i>
                    </div>
                    <div>
                        <h5>Communautaire</h5>
                        <p class="text-muted mb-0">Système d'évaluation et de confiance mutuelle</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-4">Témoignages clients</h4>
                        
                        <div class="testimonial mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="stars me-2">
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                </div>
                                <small class="text-muted">Marie L.</small>
                            </div>
                            <p class="mb-0">"Service excellent ! Ma livraison a été effectuée en moins de 2 heures. Le livreur était très professionnel."</p>
                        </div>
                        
                        <div class="testimonial mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="stars me-2">
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                </div>
                                <small class="text-muted">Pierre D.</small>
                            </div>
                            <p class="mb-0">"En tant que livreur, j'apprécie la flexibilité et la simplicité de la plateforme. Les paiements sont rapides."</p>
                        </div>
                        
                        <div class="testimonial">
                            <div class="d-flex align-items-center mb-2">
                                <div class="stars me-2">
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                </div>
                                <small class="text-muted">Sophie M.</small>
                            </div>
                            <p class="mb-0">"Le système de QR code pour confirmer la réception est génial. Je me sens en sécurité."</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section bg-primary text-white py-5">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4">Prêt à commencer ?</h2>
        <p class="lead mb-4">Rejoignez notre communauté de plus de 10 000 utilisateurs</p>
        
        <?php if (!$is_logged_in): ?>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/register?role=expeditor" class="btn btn-warning btn-lg">
                    <i class="fas fa-box me-2"></i>
                    Je veux expédier
                </a>
                <a href="/register?role=courier" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-truck me-2"></i>
                    Je veux livrer
                </a>
            </div>
        <?php else: ?>
            <a href="/<?php echo $user_role; ?>/dashboard" class="btn btn-warning btn-lg">
                <i class="fas fa-tachometer-alt me-2"></i>
                Accéder à mon tableau de bord
            </a>
        <?php endif; ?>
    </div>
</section>

<script>
// Charger les statistiques
document.addEventListener('DOMContentLoaded', function() {
    // Simuler le chargement des statistiques
    setTimeout(() => {
        document.getElementById('totalDeliveries').textContent = '1,247';
        document.getElementById('activeCouriers').textContent = '156';
        document.getElementById('satisfactionRate').textContent = '98%';
        document.getElementById('avgDeliveryTime').textContent = '2.5h';
    }, 1000);
});
</script> 