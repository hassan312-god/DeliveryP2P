<?php
/**
 * Service QR Code Sécurisé pour LivraisonP2P
 * Génération et validation de QR codes avec cryptographie AES-256
 * 
 * @author LivraisonP2P Team
 * @version 1.0.0
 */

declare(strict_types=1);

namespace DeliveryP2P\Services;

use DeliveryP2P\Utils\Security;
use DeliveryP2P\Utils\Logger;
use DeliveryP2P\Core\Exceptions\ApiException;

class QRCodeService
{
    private Security $security;
    private Logger $logger;
    private string $qrSecret;
    private string $encryptionKey;

    public function __construct()
    {
        $this->security = new Security();
        $this->logger = new Logger();
        $this->qrSecret = QR_CODE_SECRET;
        $this->encryptionKey = ENCRYPTION_KEY;
    }

    /**
     * Génère un QR code sécurisé pour une livraison
     */
    public function generateQRCode(array $deliveryData): array
    {
        try {
            // Validation des données
            $this->validateDeliveryData($deliveryData);

            // Création du payload sécurisé
            $payload = $this->createSecurePayload($deliveryData);

            // Génération du QR code unique
            $qrCode = $this->generateUniqueQRCode($payload);

            // Stockage sécurisé
            $this->storeQRCode($qrCode, $deliveryData);

            $this->logger->info('QR Code generated', [
                'delivery_id' => $deliveryData['id'],
                'qr_code' => $qrCode['code']
            ]);

            return [
                'success' => true,
                'qr_code' => $qrCode['code'],
                'qr_image' => $qrCode['image_url'],
                'expires_at' => $qrCode['expires_at'],
                'delivery_id' => $deliveryData['id']
            ];

        } catch (Exception $e) {
            $this->logger->error('QR Code generation failed', [
                'error' => $e->getMessage(),
                'delivery_data' => $deliveryData
            ]);
            throw new ApiException('Erreur lors de la génération du QR code', 500);
        }
    }

    /**
     * Valide un QR code scanné
     */
    public function validateQRCode(string $qrCode, array $scanData): array
    {
        try {
            // Décodage et validation du QR code
            $payload = $this->decodeQRCode($qrCode);

            // Vérification de l'expiration
            if ($this->isExpired($payload)) {
                throw new ApiException('QR code expiré', 400);
            }

            // Validation géolocalisée
            $geolocationValid = $this->validateGeolocation($payload, $scanData);

            // Validation temporelle
            $temporalValid = $this->validateTemporalWindow($payload, $scanData);

            // Enregistrement du scan
            $this->recordScan($qrCode, $scanData, $payload);

            $this->logger->info('QR Code validated', [
                'qr_code' => $qrCode,
                'delivery_id' => $payload['delivery_id'],
                'geolocation_valid' => $geolocationValid,
                'temporal_valid' => $temporalValid
            ]);

            return [
                'success' => true,
                'valid' => $geolocationValid && $temporalValid,
                'delivery_id' => $payload['delivery_id'],
                'delivery_status' => $payload['status'],
                'geolocation_valid' => $geolocationValid,
                'temporal_valid' => $temporalValid,
                'message' => $this->getValidationMessage($payload, $geolocationValid, $temporalValid)
            ];

        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('QR Code validation failed', [
                'error' => $e->getMessage(),
                'qr_code' => $qrCode
            ]);
            throw new ApiException('QR code invalide', 400);
        }
    }

    /**
     * Crée un payload sécurisé pour le QR code
     */
    private function createSecurePayload(array $deliveryData): array
    {
        $payload = [
            'delivery_id' => $deliveryData['id'],
            'user_id' => $deliveryData['user_id'],
            'courier_id' => $deliveryData['courier_id'] ?? null,
            'status' => $deliveryData['status'],
            'type' => $deliveryData['type'] ?? 'delivery',
            'created_at' => time(),
            'expires_at' => time() + (DELIVERY_TIMEOUT_MINUTES * 60),
            'nonce' => $this->security->generateNonce(),
            'version' => '1.0'
        ];

        // Ajout de données spécifiques selon le type
        if ($payload['type'] === 'pickup') {
            $payload['pickup_location'] = $deliveryData['pickup_location'];
        } elseif ($payload['type'] === 'delivery') {
            $payload['delivery_location'] = $deliveryData['delivery_location'];
        }

        return $payload;
    }

    /**
     * Génère un QR code unique avec cryptographie
     */
    private function generateUniqueQRCode(array $payload): array
    {
        // Chiffrement AES-256 du payload
        $encryptedData = $this->security->encryptAES256(
            json_encode($payload),
            $this->encryptionKey
        );

        // Génération d'un code unique
        $uniqueCode = $this->generateUniqueCode($payload['delivery_id']);

        // Création de l'URL du QR code
        $qrData = $this->qrSecret . ':' . $uniqueCode . ':' . $encryptedData;
        $qrImageUrl = $this->generateQRImage($qrData);

        return [
            'code' => $uniqueCode,
            'data' => $qrData,
            'image_url' => $qrImageUrl,
            'expires_at' => $payload['expires_at'],
            'payload' => $payload
        ];
    }

    /**
     * Génère un code unique pour le QR code
     */
    private function generateUniqueCode(string $deliveryId): string
    {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        $hash = hash_hmac('sha256', $deliveryId . $timestamp . $random, $this->qrSecret);
        
        return substr($hash, 0, 16); // 16 caractères hexadécimaux
    }

    /**
     * Génère l'image QR code
     */
    private function generateQRImage(string $data): string
    {
        // Utilisation de l'API QR Server pour la génération
        $size = QR_CODE_SIZE;
        $margin = QR_CODE_MARGIN;
        $errorCorrection = QR_CODE_ERROR_CORRECTION;
        
        $url = "https://api.qrserver.com/v1/create-qr-code/";
        $params = http_build_query([
            'size' => $size . 'x' . $size,
            'data' => $data,
            'margin' => $margin,
            'ecc' => $errorCorrection,
            'format' => 'png'
        ]);

        return $url . '?' . $params;
    }

    /**
     * Décode et valide un QR code
     */
    private function decodeQRCode(string $qrCode): array
    {
        // Récupération des données du QR code depuis la base
        $qrData = $this->getQRCodeData($qrCode);
        
        if (!$qrData) {
            throw new ApiException('QR code non trouvé', 404);
        }

        // Décryptage des données
        $decryptedData = $this->security->decryptAES256(
            $qrData['encrypted_data'],
            $this->encryptionKey
        );

        $payload = json_decode($decryptedData, true);
        
        if (!$payload) {
            throw new ApiException('Données QR code corrompues', 400);
        }

        return $payload;
    }

    /**
     * Valide la géolocalisation du scan
     */
    private function validateGeolocation(array $payload, array $scanData): bool
    {
        if (!isset($scanData['latitude']) || !isset($scanData['longitude'])) {
            return false;
        }

        $scanLocation = [
            'lat' => (float) $scanData['latitude'],
            'lng' => (float) $scanData['longitude']
        ];

        // Récupération de la localisation cible
        $targetLocation = $this->getTargetLocation($payload);
        
        if (!$targetLocation) {
            return false;
        }

        // Calcul de la distance
        $distance = $this->calculateDistance($scanLocation, $targetLocation);
        $maxDistance = DELIVERY_RADIUS_KM * 1000; // Conversion en mètres

        return $distance <= $maxDistance;
    }

    /**
     * Valide la fenêtre temporelle
     */
    private function validateTemporalWindow(array $payload, array $scanData): bool
    {
        $currentTime = time();
        $expiresAt = $payload['expires_at'];

        // Vérification de l'expiration
        if ($currentTime > $expiresAt) {
            return false;
        }

        // Vérification de la fenêtre de livraison si spécifiée
        if (isset($payload['delivery_window'])) {
            $windowStart = strtotime($payload['delivery_window']['start']);
            $windowEnd = strtotime($payload['delivery_window']['end']);
            
            return $currentTime >= $windowStart && $currentTime <= $windowEnd;
        }

        return true;
    }

    /**
     * Enregistre un scan de QR code
     */
    private function recordScan(string $qrCode, array $scanData, array $payload): void
    {
        $scanRecord = [
            'qr_code' => $qrCode,
            'delivery_id' => $payload['delivery_id'],
            'scanned_by' => $scanData['user_id'] ?? null,
            'scanned_at' => date('c'),
            'latitude' => $scanData['latitude'] ?? null,
            'longitude' => $scanData['longitude'] ?? null,
            'device_info' => $scanData['device_info'] ?? null,
            'ip_address' => $scanData['ip_address'] ?? null,
            'user_agent' => $scanData['user_agent'] ?? null
        ];

        // Stockage en base de données
        $this->storeScanRecord($scanRecord);
    }

    /**
     * Récupère les données d'un QR code depuis la base
     */
    private function getQRCodeData(string $qrCode): ?array
    {
        // Implémentation de la récupération depuis Supabase
        // À implémenter selon votre structure de base de données
        return null; // Placeholder
    }

    /**
     * Stocke un QR code en base
     */
    private function storeQRCode(array $qrCode, array $deliveryData): void
    {
        // Implémentation du stockage dans Supabase
        // À implémenter selon votre structure de base de données
    }

    /**
     * Stocke un enregistrement de scan
     */
    private function storeScanRecord(array $scanRecord): void
    {
        // Implémentation du stockage dans Supabase
        // À implémenter selon votre structure de base de données
    }

    /**
     * Récupère la localisation cible selon le type
     */
    private function getTargetLocation(array $payload): ?array
    {
        if ($payload['type'] === 'pickup' && isset($payload['pickup_location'])) {
            return $payload['pickup_location'];
        } elseif ($payload['type'] === 'delivery' && isset($payload['delivery_location'])) {
            return $payload['delivery_location'];
        }

        return null;
    }

    /**
     * Calcule la distance entre deux points (formule de Haversine)
     */
    private function calculateDistance(array $point1, array $point2): float
    {
        $lat1 = deg2rad($point1['lat']);
        $lon1 = deg2rad($point1['lng']);
        $lat2 = deg2rad($point2['lat']);
        $lon2 = deg2rad($point2['lng']);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return 6371000 * $c; // Rayon de la Terre en mètres
    }

    /**
     * Vérifie si un QR code est expiré
     */
    private function isExpired(array $payload): bool
    {
        return time() > $payload['expires_at'];
    }

    /**
     * Valide les données de livraison
     */
    private function validateDeliveryData(array $deliveryData): void
    {
        $required = ['id', 'user_id', 'status'];
        
        foreach ($required as $field) {
            if (!isset($deliveryData[$field])) {
                throw new ApiException("Champ requis manquant: {$field}", 400);
            }
        }
    }

    /**
     * Génère un message de validation personnalisé
     */
    private function getValidationMessage(array $payload, bool $geolocationValid, bool $temporalValid): string
    {
        if (!$geolocationValid && !$temporalValid) {
            return 'QR code invalide : localisation et horaire incorrects';
        } elseif (!$geolocationValid) {
            return 'QR code invalide : vous devez être à l\'adresse de livraison';
        } elseif (!$temporalValid) {
            return 'QR code expiré ou hors de la fenêtre de livraison';
        }

        return 'QR code validé avec succès';
    }
} 