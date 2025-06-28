<?php $page_title = 'Page non trouvée - LivraisonP2P'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="error-page">
                <!-- Icône d'erreur -->
                <div class="error-icon mb-4">
                    <i class="fas fa-exclamation-triangle fa-5x text-warning"></i>
                </div>
                
                <!-- Message d'erreur -->
                <h1 class="display-1 fw-bold text-muted mb-3">404</h1>
                <h2 class="h3 mb-4">Page non trouvée</h2>
                <p class="lead text-muted mb-5">
                    Désolé, la page que vous recherchez n'existe pas ou a été déplacée.
                </p>
                
                <!-- Actions -->
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="/" class="btn btn-primary btn-lg">
                        <i class="fas fa-home me-2"></i>
                        Retour à l'accueil
                    </a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        Page précédente
                    </a>
                </div>
                
                <!-- Recherche -->
                <div class="mt-5">
                    <h5 class="mb-3">Rechercher quelque chose ?</h5>
                    <form class="d-flex justify-content-center" action="/search" method="GET">
                        <div class="input-group" style="max-width: 400px;">
                            <input type="text" 
                                   class="form-control" 
                                   name="q" 
                                   placeholder="Rechercher..."
                                   aria-label="Rechercher">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    padding: 4rem 0;
}

.error-icon {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}
</style> 