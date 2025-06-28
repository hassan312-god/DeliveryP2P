<?php

declare(strict_types=1);

namespace DeliveryP2P\Services;

use DeliveryP2P\Utils\Logger;
use DeliveryP2P\Utils\Database;
use DeliveryP2P\Utils\Cache;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Ramsey\Uuid\Uuid;

/**
 * Service QR Code Sécurisé de Nouvelle Génération
 * Cryptographie AES-256 + Signatures numériques
 */
class QRCodeService
{
    private Logger $logger;
    private Database $database;
    private Cache $cache;
    private string $encryptionKey;
    private string $qrSecret;
    private int $qrSize;
    private int $qrMargin;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->database = Database::getInstance();
        $this->cache = new Cache();
        
        // Configuration depuis les variables d'environnement
        $this->encryptionKey = $_ENV['QR_ENCRYPTION_KEY'] ?? $_ENV['ENCRYPTION_KEY'] ?? '';
        $this->qrSecret = $_ENV['QR_CODE_SECRET'] ?? '';
        $this->qrSize = (int) ($_ENV['QR_CODE_SIZE'] ?? 300);
        $this->qrMargin = (int) ($_ENV['QR_CODE_MARGIN'] ?? 10);
        
        if (empty($this->encryptionKey) || empty($this->qrSecret)) {
            throw new \Exception('Clés de chiffrement QR manquantes dans les variables d\'environnement');
        }
    }

    /**
     * Génère un QR code sécurisé pour une livraison
     */
    public function generateSecureQR(string $deliveryId, string $type = 'delivery', array $metadata = []): array
    {
        try {
            $qrId = Uuid::uuid4()->toString();
            $timestamp = time();
            $expiry = $timestamp + (24 * 60 * 60); // 24 heures
            
            // Données à encoder dans le QR
            $qrData = [
                'qr_id' => $qrId,
                'delivery_id' => $deliveryId,
                'type' => $type,
                'timestamp' => $timestamp,
                'expiry' => $expiry,
                'metadata' => $metadata
            ];
            
            // Chiffrement AES-256 des données sensibles
            $encryptedData = $this->encryptData($qrData);
            
            // Signature numérique pour anti-fraude
            $signature = $this->generateSignature($qrData);
            
            // Données finales du QR
            $finalData = [
                'data' => $encryptedData,
                'sig' => $signature,
                'v' => '2.0' // Version du format QR
            ];
            
            // Encodage JSON et génération QR
            $jsonData = json_encode($finalData);
            $qrCode = $this->createQRCode($jsonData);
            
            // Stockage en base de données
            $this->storeQRCode($qrId, $deliveryId, $type, $qrData, $expiry);
            
            // Cache pour validation rapide
            $this->cache->set("qr:{$qrId}", $qrData, 24 * 60 * 60);
            
            $this->logger->info('Secure QR code generated', [
                'qr_id' => $qrId,
                'delivery_id' => $deliveryId,
                'type' => $type,
                'expires_at' => date('c', $expiry)
            ]);
            
            return [
                'success' => true,
                'qr_id' => $qrId,
                'qr_code' => $qrCode,
                'expires_at' => date('c', $expiry),
                'type' => $type
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('QR generation failed', [
                'delivery_id' => $deliveryId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Valide un QR code avec vérifications multi-niveaux
     */
    public function validateQR(string $qrData, array $context = []): array
    {
        try {
            // Décodage des données QR
            $decoded = json_decode($qrData, true);
            
            if (!$decoded || !isset($decoded['data']) || !isset($decoded['sig'])) {
                return $this->errorResponse('Format QR invalide');
            }
            
            // Vérification de la version
            if (($decoded['v'] ?? '1.0') !== '2.0') {
                return $this->errorResponse('Version QR non supportée');
            }
            
            // Déchiffrement des données
            $data = $this->decryptData($decoded['data']);
            
            if (!$data) {
                return $this->errorResponse('Données QR corrompues');
            }
            
            // Vérification de la signature
            if (!$this->verifySignature($data, $decoded['sig'])) {
                return $this->errorResponse('Signature QR invalide');
            }
            
            // Vérification de l'expiration
            if (time() > ($data['expiry'] ?? 0)) {
                return $this->errorResponse('QR code expiré');
            }
            
            // Vérification géolocalisée si contexte fourni
            if (isset($context['latitude']) && isset($context['longitude'])) {
                $geoValidation = $this->validateGeolocation($data, $context);
                if (!$geoValidation['valid']) {
                    return $this->errorResponse($geoValidation['message']);
                }
            }
            
            // Vérification temporelle (fenêtres de livraison)
            $timeValidation = $this->validateTimeWindow($data);
            if (!$timeValidation['valid']) {
                return $this->errorResponse($timeValidation['message']);
            }
            
            // Enregistrement du scan
            $this->recordScan($data['qr_id'], $context);
            
            // Récupération des informations de livraison
            $deliveryInfo = $this->getDeliveryInfo($data['delivery_id']);
            
            $this->logger->info('QR code validated successfully', [
                'qr_id' => $data['qr_id'],
                'delivery_id' => $data['delivery_id'],
                'type' => $data['type'],
                'scanner_ip' => $context['ip'] ?? 'unknown'
            ]);
            
            return [
                'success' => true,
                'qr_id' => $data['qr_id'],
                'delivery_id' => $data['delivery_id'],
                'type' => $data['type'],
                'metadata' => $data['metadata'] ?? [],
                'delivery_info' => $deliveryInfo,
                'validated_at' => date('c')
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('QR validation failed', [
                'error' => $e->getMessage(),
                'context' => $context
            ]);
            
            return $this->errorResponse('Erreur de validation QR');
        }
    }

    /**
     * Chiffre les données avec AES-256
     */
    private function encryptData(array $data): string
    {
        $jsonData = json_encode($data);
        $iv = random_bytes(16);
        
        $encrypted = openssl_encrypt(
            $jsonData,
            'AES-256-CBC',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($encrypted === false) {
            throw new \Exception('Échec du chiffrement AES-256');
        }
        
        // Combinaison IV + données chiffrées en base64
        return base64_encode($iv . $encrypted);
    }

    /**
     * Déchiffre les données AES-256
     */
    private function decryptData(string $encryptedData): ?array
    {
        try {
            $data = base64_decode($encryptedData);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            
            $decrypted = openssl_decrypt(
                $encrypted,
                'AES-256-CBC',
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted === false) {
                return null;
            }
            
            return json_decode($decrypted, true);
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Génère une signature numérique HMAC-SHA256
     */
    private function generateSignature(array $data): string
    {
        $jsonData = json_encode($data);
        return hash_hmac('sha256', $jsonData, $this->qrSecret);
    }

    /**
     * Vérifie la signature numérique
     */
    private function verifySignature(array $data, string $signature): bool
    {
        $expectedSignature = $this->generateSignature($data);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Crée le QR code physique
     */
    private function createQRCode(string $data): string
    {
        $qrCode = new QrCode($data);
        $qrCode->setSize($this->qrSize);
        $qrCode->setMargin($this->qrMargin);
        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());
        $qrCode->setForegroundColor(new Color(0, 0, 0));
        $qrCode->setBackgroundColor(new Color(255, 255, 255));
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return base64_encode($result->getString());
    }

    /**
     * Stocke les informations du QR en base
     */
    private function storeQRCode(string $qrId, string $deliveryId, string $type, array $data, int $expiry): void
    {
        $qrData = [
            'id' => $qrId,
            'delivery_id' => $deliveryId,
            'type' => $type,
            'data' => json_encode($data),
            'expires_at' => date('c', $expiry),
            'created_at' => date('c'),
            'status' => 'active'
        ];
        
        $this->database->post('qr_codes', $qrData);
    }

    /**
     * Valide la géolocalisation
     */
    private function validateGeolocation(array $qrData, array $context): array
    {
        // Récupération des coordonnées de livraison
        $deliveryInfo = $this->getDeliveryInfo($qrData['delivery_id']);
        
        if (!$deliveryInfo) {
            return ['valid' => false, 'message' => 'Informations de livraison introuvables'];
        }
        
        $deliveryLat = $deliveryInfo['delivery_latitude'] ?? null;
        $deliveryLng = $deliveryInfo['delivery_longitude'] ?? null;
        
        if (!$deliveryLat || !$deliveryLng) {
            return ['valid' => true]; // Pas de géolocalisation requise
        }
        
        $scannerLat = $context['latitude'] ?? null;
        $scannerLng = $context['longitude'] ?? null;
        
        if (!$scannerLat || !$scannerLng) {
            return ['valid' => false, 'message' => 'Géolocalisation requise'];
        }
        
        // Calcul de la distance
        $distance = $this->calculateDistance($deliveryLat, $deliveryLng, $scannerLat, $scannerLng);
        $maxDistance = (float) ($_ENV['DELIVERY_RADIUS_KM'] ?? 0.5); // 500m par défaut
        
        if ($distance > $maxDistance) {
            return [
                'valid' => false, 
                'message' => "Distance trop importante ({$distance}km > {$maxDistance}km)"
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * Valide la fenêtre temporelle
     */
    private function validateTimeWindow(array $qrData): array
    {
        $now = time();
        $expiry = $qrData['expiry'] ?? 0;
        
        if ($now > $expiry) {
            return ['valid' => false, 'message' => 'QR code expiré'];
        }
        
        // Vérification des heures de livraison (optionnel)
        $hour = (int) date('H', $now);
        if ($hour < 6 || $hour > 22) {
            return ['valid' => false, 'message' => 'Hors des heures de livraison (6h-22h)'];
        }
        
        return ['valid' => true];
    }

    /**
     * Enregistre un scan de QR
     */
    private function recordScan(string $qrId, array $context): void
    {
        $scanData = [
            'qr_id' => $qrId,
            'scanned_at' => date('c'),
            'ip_address' => $context['ip'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'latitude' => $context['latitude'] ?? null,
            'longitude' => $context['longitude'] ?? null,
            'device_info' => json_encode($context['device'] ?? [])
        ];
        
        $this->database->post('qr_scans', $scanData);
    }

    /**
     * Récupère les informations de livraison
     */
    private function getDeliveryInfo(string $deliveryId): ?array
    {
        $result = $this->database->get('deliveries', ['id' => $deliveryId]);
        
        if ($result['success'] && !empty($result['data'])) {
            return $result['data'][0] ?? null;
        }
        
        return null;
    }

    /**
     * Calcule la distance entre deux points (formule de Haversine)
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en km
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Génère une réponse d'erreur standardisée
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'validated_at' => date('c')
        ];
    }

    /**
     * Récupère l'historique des scans d'un QR
     */
    public function getScanHistory(string $qrId): array
    {
        $result = $this->database->get('qr_scans', ['qr_id' => $qrId], ['order' => 'scanned_at.desc']);
        
        if ($result['success']) {
            return $result['data'] ?? [];
        }
        
        return [];
    }

    /**
     * Révoque un QR code
     */
    public function revokeQR(string $qrId): bool
    {
        try {
            $this->database->patch('qr_codes', ['id' => $qrId], ['status' => 'revoked']);
            $this->cache->delete("qr:{$qrId}");
            
            $this->logger->info('QR code revoked', ['qr_id' => $qrId]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('QR revocation failed', [
                'qr_id' => $qrId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
} 