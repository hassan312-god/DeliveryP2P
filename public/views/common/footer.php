    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-truck me-2"></i>LivraisonP2P</h5>
                    <p class="mb-0">La plateforme de livraison entre particuliers qui connecte expéditeurs et livreurs pour des livraisons sécurisées et rapides.</p>
                </div>
                <div class="col-md-3">
                    <h6>Liens utiles</h6>
                    <ul class="list-unstyled">
                        <li><a href="/about" class="text-light text-decoration-none">À propos</a></li>
                        <li><a href="/help" class="text-light text-decoration-none">Aide</a></li>
                        <li><a href="/contact" class="text-light text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Suivez-nous</h6>
                    <div class="d-flex gap-2">
                        <a href="#" class="text-light fs-5"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light fs-5"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light fs-5"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light fs-5"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> LivraisonP2P. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="/privacy" class="text-light text-decoration-none me-3">Confidentialité</a>
                    <a href="/terms" class="text-light text-decoration-none">Conditions d'utilisation</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/main.js"></script>
    
    <!-- Scripts spécifiques à la page -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Toast container pour les notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer">
        <!-- Les toasts seront injectés ici dynamiquement -->
    </div>
    
    <!-- Modal de chargement -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mb-0" id="loadingMessage">Chargement en cours...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script d'initialisation -->
    <script>
        // Initialisation globale
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser les tooltips Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerList);
            });
            
            // Initialiser les popovers Bootstrap
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Initialiser les notifications si l'utilisateur est connecté
            if (currentUser.id) {
                initializeNotifications();
            }
        });
        
        // Fonction de déconnexion
        function logout() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '/login';
                    } else {
                        showToast('Erreur lors de la déconnexion', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showToast('Erreur lors de la déconnexion', 'error');
                });
            }
        }
        
        // Fonction pour afficher un toast
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHtml = `
                <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
                    <div class="toast-header">
                        <i class="fas fa-${type === 'success' ? 'check-circle text-success' : type === 'error' ? 'exclamation-circle text-danger' : 'info-circle text-info'} me-2"></i>
                        <strong class="me-auto">Notification</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Supprimer le toast du DOM après qu'il soit caché
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
        
        // Fonction pour afficher/masquer le modal de chargement
        function showLoading(message = 'Chargement en cours...') {
            document.getElementById('loadingMessage').textContent = message;
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();
        }
        
        function hideLoading() {
            const loadingModal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
            if (loadingModal) {
                loadingModal.hide();
            }
        }
        
        // Fonction pour initialiser les notifications
        function initializeNotifications() {
            // Charger les notifications non lues
            loadUnreadNotifications();
            
            // Écouter les nouvelles notifications via Supabase Realtime
            if (supabase) {
                supabase
                    .channel('notifications')
                    .on('postgres_changes', {
                        event: 'INSERT',
                        schema: 'public',
                        table: 'notifications',
                        filter: `user_id=eq.${currentUser.id}`
                    }, (payload) => {
                        // Nouvelle notification reçue
                        updateNotificationBadge();
                        showToast('Nouvelle notification reçue', 'info');
                    })
                    .subscribe();
            }
        }
        
        // Fonction pour charger les notifications non lues
        function loadUnreadNotifications() {
            fetch('/api/notifications/unread')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateNotificationBadge(data.count);
                        updateNotificationsList(data.notifications);
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des notifications:', error);
                });
        }
        
        // Fonction pour mettre à jour le badge de notifications
        function updateNotificationBadge(count = 0) {
            const badge = document.getElementById('notificationCount');
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Fonction pour mettre à jour la liste des notifications
        function updateNotificationsList(notifications) {
            const list = document.getElementById('notificationsList');
            const noNotifications = document.getElementById('noNotifications');
            
            if (notifications && notifications.length > 0) {
                noNotifications.style.display = 'none';
                
                notifications.forEach(notification => {
                    const item = document.createElement('li');
                    item.innerHTML = `
                        <a class="dropdown-item" href="${notification.link || '#'}">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${notification.title}</h6>
                                <small>${notification.created_at}</small>
                            </div>
                            <p class="mb-1">${notification.message}</p>
                        </a>
                    `;
                    list.appendChild(item);
                });
            } else {
                noNotifications.style.display = 'block';
            }
        }
    </script>
</body>
</html> 