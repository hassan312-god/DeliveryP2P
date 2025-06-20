/**
 * Module QR Code pour LivraisonP2P
 * Gestion complète des QR codes avec backend Supabase
 */
class QRCodeModule {
    constructor() {
        this.supabase = window.supabase;
        this.currentQRCode = null;
        this.scanner = null;
        this.isScanning = false;
        
        // Initialiser les événements
        this.initEvents();
    }

    /**
     * Initialiser les événements
     */
    initEvents() {
        // Événements pour les boutons d'action
        document.addEventListener('click', (e) => {
            if (e.target.matches('#download-qr')) {
                this.downloadQRCode();
            } else if (e.target.matches('#share-qr')) {
                this.shareQRCode();
            } else if (e.target.matches('#save-qr')) {
                this.saveQRCode();
            } else if (e.target.matches('[data-action="scan-qr"]')) {
                this.startScanning();
            } else if (e.target.matches('[data-action="stop-scan"]')) {
                this.stopScanning();
            } else if (e.target.matches('[data-action="switch-camera"]')) {
                this.switchCamera();
            }
        });

        // Événements pour les filtres d'historique
        document.addEventListener('change', (e) => {
            if (e.target.matches('#history-filter') || e.target.matches('#history-date')) {
                this.filterHistory();
            }
        });

        // Événement pour effacer l'historique
        document.addEventListener('click', (e) => {
            if (e.target.matches('#clear-history')) {
                this.clearHistory();
            }
        });
    }

    /**
     * Générer un QR code
     * @param {string} data - Données à encoder
     * @param {object} metadata - Métadonnées du QR code
     */
    async generateQRCode(data, metadata = {}) {
        try {
            // Vérifier l'authentification
            const user = this.supabase.auth.user();
            if (!user) {
                throw new Error('Utilisateur non authentifié');
            }

            // Générer le QR code
            const qrCodeDataURL = await QRCode.toDataURL(data, {
                width: 256,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            });

            // Sauvegarder dans la base de données
            const qrRecord = await this.saveQRCodeToDatabase(data, metadata, qrCodeDataURL);

            // Afficher le QR code
            this.displayQRCode(qrCodeDataURL, metadata, qrRecord.id);

            // Afficher un toast de succès
            if (window.toast) {
                window.toast.success('QR code généré avec succès');
            }

            return { success: true, data: qrRecord };

        } catch (error) {
            console.error('Erreur lors de la génération du QR code:', error);
            
            if (window.toast) {
                window.toast.error('Erreur lors de la génération du QR code');
            }
            
            return { success: false, error: error.message };
        }
    }

    /**
     * Sauvegarder le QR code dans la base de données
     */
    async saveQRCodeToDatabase(data, metadata, qrCodeDataURL) {
        const user = this.supabase.auth.user();
        
        const qrData = {
            user_id: user.id,
            content: data,
            qr_code_data: qrCodeDataURL,
            type: metadata.type || 'custom',
            title: metadata.title || 'QR Code',
            description: metadata.description || '',
            metadata: metadata,
            created_at: new Date().toISOString()
        };

        const { data: qrRecord, error } = await this.supabase
            .from('qr_codes')
            .insert([qrData])
            .single();

        if (error) {
            throw new Error(`Erreur lors de la sauvegarde: ${error.message}`);
        }

        return qrRecord;
    }

    /**
     * Afficher le QR code généré
     */
    displayQRCode(qrCodeDataURL, metadata, qrId) {
        const container = document.getElementById('qrcode-container');
        const display = document.getElementById('qrcode-display');
        const info = document.getElementById('qr-info');
        const placeholder = document.getElementById('qr-placeholder');

        // Masquer le placeholder
        placeholder.classList.add('hidden');
        
        // Afficher le conteneur
        container.classList.remove('hidden');

        // Afficher le QR code
        display.innerHTML = `
            <img src="${qrCodeDataURL}" alt="QR Code" class="w-64 h-64">
        `;

        // Afficher les informations
        info.innerHTML = `
            <h4 class="font-semibold text-gray-900">${metadata.title || 'QR Code'}</h4>
            <p class="text-sm text-gray-600">${metadata.description || ''}</p>
            <p class="text-xs text-gray-500 mt-1">Généré le ${new Date().toLocaleString()}</p>
        `;

        // Stocker les données du QR code actuel
        this.currentQRCode = {
            id: qrId,
            dataURL: qrCodeDataURL,
            metadata: metadata
        };
    }

    /**
     * Télécharger le QR code
     */
    downloadQRCode() {
        if (!this.currentQRCode) {
            if (window.toast) {
                window.toast.error('Aucun QR code à télécharger');
            }
            return;
        }

        const link = document.createElement('a');
        link.download = `qrcode-${this.currentQRCode.metadata.type}-${Date.now()}.png`;
        link.href = this.currentQRCode.dataURL;
        link.click();

        if (window.toast) {
            window.toast.success('QR code téléchargé');
        }
    }

    /**
     * Partager le QR code
     */
    async shareQRCode() {
        if (!this.currentQRCode) {
            if (window.toast) {
                window.toast.error('Aucun QR code à partager');
            }
            return;
        }

        try {
            // Convertir le data URL en blob
            const response = await fetch(this.currentQRCode.dataURL);
            const blob = await response.blob();
            const file = new File([blob], `qrcode-${Date.now()}.png`, { type: 'image/png' });

            if (navigator.share) {
                await navigator.share({
                    title: this.currentQRCode.metadata.title,
                    text: this.currentQRCode.metadata.description,
                    files: [file]
                });
            } else {
                // Fallback pour les navigateurs qui ne supportent pas l'API Share
                this.copyToClipboard(this.currentQRCode.dataURL);
            }

            if (window.toast) {
                window.toast.success('QR code partagé');
            }
        } catch (error) {
            console.error('Erreur lors du partage:', error);
            if (window.toast) {
                window.toast.error('Erreur lors du partage');
            }
        }
    }

    /**
     * Copier dans le presse-papiers
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            if (window.toast) {
                window.toast.success('Lien copié dans le presse-papiers');
            }
        } catch (error) {
            console.error('Erreur lors de la copie:', error);
            if (window.toast) {
                window.toast.error('Erreur lors de la copie');
            }
        }
    }

    /**
     * Sauvegarder le QR code
     */
    async saveQRCode() {
        if (!this.currentQRCode) {
            if (window.toast) {
                window.toast.error('Aucun QR code à sauvegarder');
            }
            return;
        }

        try {
            // Marquer comme favori dans la base de données
            const { error } = await this.supabase
                .from('qr_codes')
                .update({ is_favorite: true })
                .eq('id', this.currentQRCode.id);

            if (error) {
                throw error;
            }

            if (window.toast) {
                window.toast.success('QR code sauvegardé dans les favoris');
            }
        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error);
            if (window.toast) {
                window.toast.error('Erreur lors de la sauvegarde');
            }
        }
    }

    /**
     * Démarrer le scan
     */
    async startScanning() {
        try {
            const startButton = document.getElementById('scan-start');
            const scannerInterface = document.getElementById('qr-scanner-interface');
            const scannerContainer = document.getElementById('qr-scanner-container');

            // Masquer le bouton de démarrage
            startButton.classList.add('hidden');
            
            // Afficher l'interface de scan
            scannerInterface.classList.remove('hidden');

            // Initialiser le scanner
            this.scanner = new Html5Qrcode("qr-scanner-container");
            
            const cameras = await Html5Qrcode.getCameras();
            if (cameras && cameras.length) {
                await this.scanner.start(
                    { deviceId: cameras[0].id },
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 }
                    },
                    this.onScanSuccess.bind(this),
                    this.onScanError.bind(this)
                );
                
                this.isScanning = true;
            } else {
                throw new Error('Aucune caméra disponible');
            }

        } catch (error) {
            console.error('Erreur lors du démarrage du scan:', error);
            
            if (window.toast) {
                window.toast.error('Erreur lors du démarrage du scan');
            }
            
            // Revenir à l'état initial
            this.resetScanInterface();
        }
    }

    /**
     * Arrêter le scan
     */
    async stopScanning() {
        if (this.scanner && this.isScanning) {
            try {
                await this.scanner.stop();
                this.isScanning = false;
                this.resetScanInterface();
                
                if (window.toast) {
                    window.toast.success('Scan arrêté');
                }
            } catch (error) {
                console.error('Erreur lors de l\'arrêt du scan:', error);
            }
        }
    }

    /**
     * Changer de caméra
     */
    async switchCamera() {
        if (!this.scanner || !this.isScanning) return;

        try {
            const cameras = await Html5Qrcode.getCameras();
            const currentCamera = this.scanner.getActiveDeviceId();
            const currentIndex = cameras.findIndex(cam => cam.id === currentCamera);
            const nextIndex = (currentIndex + 1) % cameras.length;
            
            await this.scanner.stop();
            await this.scanner.start(
                { deviceId: cameras[nextIndex].id },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                this.onScanSuccess.bind(this),
                this.onScanError.bind(this)
            );

            if (window.toast) {
                window.toast.success('Caméra changée');
            }
        } catch (error) {
            console.error('Erreur lors du changement de caméra:', error);
            if (window.toast) {
                window.toast.error('Erreur lors du changement de caméra');
            }
        }
    }

    /**
     * Réinitialiser l'interface de scan
     */
    resetScanInterface() {
        const startButton = document.getElementById('scan-start');
        const scannerInterface = document.getElementById('qr-scanner-interface');
        const scanResult = document.getElementById('qr-scan-result');

        startButton.classList.remove('hidden');
        scannerInterface.classList.add('hidden');
        scanResult.classList.add('hidden');
    }

    /**
     * Callback de succès du scan
     */
    async onScanSuccess(decodedText, decodedResult) {
        try {
            // Arrêter le scan
            await this.stopScanning();

            // Traiter le contenu scanné
            await this.processScannedContent(decodedText);

        } catch (error) {
            console.error('Erreur lors du traitement du scan:', error);
            if (window.toast) {
                window.toast.error('Erreur lors du traitement du scan');
            }
        }
    }

    /**
     * Callback d'erreur du scan
     */
    onScanError(error) {
        // Erreurs de scan normales, pas besoin de les afficher
        console.debug('Erreur de scan:', error);
    }

    /**
     * Traiter le contenu scanné
     */
    async processScannedContent(content) {
        try {
            let parsedContent;
            let contentType = 'custom';

            // Essayer de parser le JSON
            try {
                parsedContent = JSON.parse(content);
                contentType = parsedContent.type || 'custom';
            } catch (e) {
                parsedContent = { content: content };
                contentType = 'custom';
            }

            // Afficher le résultat
            this.displayScanResult(parsedContent, contentType);

            // Traiter selon le type
            switch (contentType) {
                case 'delivery':
                    await this.handleDeliveryQR(parsedContent);
                    break;
                case 'user':
                    await this.handleUserQR(parsedContent);
                    break;
                case 'payment':
                    await this.handlePaymentQR(parsedContent);
                    break;
                case 'location':
                    await this.handleLocationQR(parsedContent);
                    break;
                default:
                    // Contenu personnalisé
                    break;
            }

        } catch (error) {
            console.error('Erreur lors du traitement du contenu:', error);
            if (window.toast) {
                window.toast.error('Erreur lors du traitement du contenu');
            }
        }
    }

    /**
     * Afficher le résultat du scan
     */
    displayScanResult(content, type) {
        const resultContainer = document.getElementById('qr-scan-result');
        const modal = document.getElementById('qr-content-modal');
        const modalContent = document.getElementById('modal-content');

        let html = '';

        switch (type) {
            case 'delivery':
                html = `
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-blue-900 mb-2">
                            <i class="fas fa-truck mr-2"></i>Livraison #${content.delivery_id?.slice(0, 8)}
                        </h4>
                        <p class="text-blue-700 mb-2">
                            <strong>Départ:</strong> ${content.pickup_address}
                        </p>
                        <p class="text-blue-700 mb-2">
                            <strong>Arrivée:</strong> ${content.delivery_address}
                        </p>
                        <p class="text-blue-700">
                            <strong>Statut:</strong> ${content.status}
                        </p>
                        <button onclick="window.qrCodeManager.trackDelivery('${content.delivery_id}')" 
                                class="mt-3 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                            Suivre la livraison
                        </button>
                    </div>
                `;
                break;

            case 'user':
                html = `
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-green-900 mb-2">
                            <i class="fas fa-user mr-2"></i>${content.prenom} ${content.nom}
                        </h4>
                        <p class="text-green-700 mb-2">
                            <strong>Email:</strong> ${content.email}
                        </p>
                        <p class="text-green-700 mb-2">
                            <strong>Rôle:</strong> ${content.role}
                        </p>
                        <button onclick="window.qrCodeManager.contactUser('${content.user_id}')" 
                                class="mt-3 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                            Contacter
                        </button>
                    </div>
                `;
                break;

            case 'payment':
                html = `
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-purple-900 mb-2">
                            <i class="fas fa-credit-card mr-2"></i>Paiement
                        </h4>
                        <p class="text-purple-700 mb-2">
                            <strong>Montant:</strong> ${content.amount} ${content.currency || 'XOF'}
                        </p>
                        <p class="text-purple-700 mb-2">
                            <strong>Description:</strong> ${content.description || 'Aucune description'}
                        </p>
                        <button onclick="window.qrCodeManager.processPayment(${content.amount}, '${content.description || ''}')" 
                                class="mt-3 bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600">
                            Effectuer le paiement
                        </button>
                    </div>
                `;
                break;

            case 'location':
                html = `
                    <div class="bg-orange-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-orange-900 mb-2">
                            <i class="fas fa-map-marker-alt mr-2"></i>Localisation
                        </h4>
                        <p class="text-orange-700 mb-2">
                            <strong>Latitude:</strong> ${content.latitude}
                        </p>
                        <p class="text-orange-700 mb-2">
                            <strong>Longitude:</strong> ${content.longitude}
                        </p>
                        <p class="text-orange-700 mb-2">
                            <strong>Précision:</strong> ${content.accuracy}m
                        </p>
                        <button onclick="window.qrCodeManager.openLocation(${content.latitude}, ${content.longitude})" 
                                class="mt-3 bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600">
                            Ouvrir sur la carte
                        </button>
                    </div>
                `;
                break;

            default:
                html = `
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-2">
                            <i class="fas fa-qrcode mr-2"></i>Contenu personnalisé
                        </h4>
                        <p class="text-gray-700 whitespace-pre-wrap">${content.content || content}</p>
                    </div>
                `;
                break;
        }

        // Afficher dans le modal
        modalContent.innerHTML = html;
        modal.classList.remove('hidden');

        // Afficher aussi dans le conteneur de résultat
        resultContainer.innerHTML = html;
        resultContainer.classList.remove('hidden');
    }

    /**
     * Gérer un QR code de livraison
     */
    async handleDeliveryQR(content) {
        try {
            // Rediriger vers la page de suivi
            window.location.href = `/client/track-delivery.html?id=${content.delivery_id}`;
        } catch (error) {
            console.error('Erreur lors du traitement de la livraison:', error);
        }
    }

    /**
     * Gérer un QR code utilisateur
     */
    async handleUserQR(content) {
        try {
            // Ouvrir le chat avec l'utilisateur
            window.location.href = `/chat.html?user=${content.user_id}`;
        } catch (error) {
            console.error('Erreur lors du traitement de l\'utilisateur:', error);
        }
    }

    /**
     * Gérer un QR code de paiement
     */
    async handlePaymentQR(content) {
        try {
            // Rediriger vers la page de paiement
            window.location.href = `/payment.html?amount=${content.amount}&description=${encodeURIComponent(content.description || '')}`;
        } catch (error) {
            console.error('Erreur lors du traitement du paiement:', error);
        }
    }

    /**
     * Gérer un QR code de localisation
     */
    async handleLocationQR(content) {
        try {
            // Ouvrir la carte avec la localisation
            const url = `https://www.google.com/maps?q=${content.latitude},${content.longitude}`;
            window.open(url, '_blank');
        } catch (error) {
            console.error('Erreur lors du traitement de la localisation:', error);
        }
    }

    /**
     * Récupérer l'historique des QR codes
     */
    async getHistory(filters = {}) {
        try {
            const user = this.supabase.auth.user();
            if (!user) {
                throw new Error('Utilisateur non authentifié');
            }

            let query = this.supabase
                .from('qr_codes')
                .select('*')
                .eq('user_id', user.id)
                .order('created_at', { ascending: false });

            // Appliquer les filtres
            if (filters.type && filters.type !== 'all') {
                query = query.eq('type', filters.type);
            }

            if (filters.date) {
                const startDate = new Date(filters.date);
                const endDate = new Date(filters.date);
                endDate.setDate(endDate.getDate() + 1);
                
                query = query.gte('created_at', startDate.toISOString())
                           .lt('created_at', endDate.toISOString());
            }

            const { data, error } = await query;

            if (error) {
                throw error;
            }

            return data || [];

        } catch (error) {
            console.error('Erreur lors de la récupération de l\'historique:', error);
            return [];
        }
    }

    /**
     * Filtrer l'historique
     */
    async filterHistory() {
        const typeFilter = document.getElementById('history-filter').value;
        const dateFilter = document.getElementById('history-date').value;

        const filters = {};
        if (typeFilter) filters.type = typeFilter;
        if (dateFilter) filters.date = dateFilter;

        const history = await this.getHistory(filters);
        this.displayHistory(history);
    }

    /**
     * Afficher l'historique
     */
    displayHistory(history) {
        const container = document.getElementById('history-list');
        container.innerHTML = '';

        if (history.length === 0) {
            container.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-history text-4xl mb-4"></i>
                    <p>Aucun QR code trouvé</p>
                </div>
            `;
            return;
        }

        history.forEach(item => {
            const card = document.createElement('div');
            card.className = 'bg-white p-4 rounded-lg shadow border hover:shadow-md transition-shadow';
            card.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-qrcode text-blue-600"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">${item.title}</h4>
                            <p class="text-sm text-gray-600">${item.description}</p>
                            <p class="text-xs text-gray-500">${new Date(item.created_at).toLocaleString()}</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="window.qrCodeManager.viewQRCode('${item.id}')" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="window.qrCodeManager.deleteQRCode('${item.id}')" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    /**
     * Voir un QR code
     */
    async viewQRCode(qrId) {
        try {
            const { data, error } = await this.supabase
                .from('qr_codes')
                .select('*')
                .eq('id', qrId)
                .single();

            if (error) {
                throw error;
            }

            // Afficher le QR code
            this.displayQRCode(data.qr_code_data, {
                title: data.title,
                description: data.description,
                type: data.type
            }, data.id);

            // Basculer vers l'onglet de génération
            document.getElementById('tab-generate').click();

        } catch (error) {
            console.error('Erreur lors de la récupération du QR code:', error);
            if (window.toast) {
                window.toast.error('Erreur lors de la récupération du QR code');
            }
        }
    }

    /**
     * Supprimer un QR code
     */
    async deleteQRCode(qrId) {
        try {
            const { error } = await this.supabase
                .from('qr_codes')
                .delete()
                .eq('id', qrId);

            if (error) {
                throw error;
            }

            if (window.toast) {
                window.toast.success('QR code supprimé');
            }

            // Recharger l'historique
            await this.loadQRHistory();

        } catch (error) {
            console.error('Erreur lors de la suppression du QR code:', error);
            if (window.toast) {
                window.toast.error('Erreur lors de la suppression');
            }
        }
    }

    /**
     * Effacer l'historique
     */
    async clearHistory() {
        try {
            const user = this.supabase.auth.user();
            if (!user) {
                throw new Error('Utilisateur non authentifié');
            }

            const { error } = await this.supabase
                .from('qr_codes')
                .delete()
                .eq('user_id', user.id);

            if (error) {
                throw error;
            }

            if (window.toast) {
                window.toast.success('Historique effacé');
            }

            // Recharger l'historique
            await this.loadQRHistory();

        } catch (error) {
            console.error('Erreur lors de l\'effacement de l\'historique:', error);
            if (window.toast) {
                window.toast.error('Erreur lors de l\'effacement');
            }
        }
    }

    /**
     * Charger l'historique des QR codes
     */
    async loadQRHistory() {
        const history = await this.getHistory();
        this.displayHistory(history);
    }

    // Méthodes utilitaires pour les actions du scan
    trackDelivery(deliveryId) {
        window.location.href = `/client/track-delivery.html?id=${deliveryId}`;
    }

    contactUser(userId) {
        window.location.href = `/chat.html?user=${userId}`;
    }

    processPayment(amount, description) {
        window.location.href = `/payment.html?amount=${amount}&description=${encodeURIComponent(description)}`;
    }

    openLocation(latitude, longitude) {
        const url = `https://www.google.com/maps?q=${latitude},${longitude}`;
        window.open(url, '_blank');
    }
}

// Exporter le module
window.QRCodeModule = QRCodeModule; 