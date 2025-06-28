<?php

namespace App\Services;

class QRCodeService
{
    private $secretKey;

    public function __construct()
    {
        $this->secretKey = $_ENV['APP_SECRET'] ?? 'default-secret-key';
    }

    /**
     * Génère un hash unique pour un QR code de livraison
     */
    public function generateQRHash($deliveryId, $userId = null)
    {
        $data = [
            'delivery_id' => $deliveryId,
            'user_id' => $userId,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16))
        ];

        $jsonData = json_encode($data);
        $hash = hash_hmac('sha256', $jsonData, $this->secretKey);
        
        return $hash;
    }

    /**
     * Génère un QR code complet avec URL
     */
    public function generateQRCode($deliveryId, $userId = null)
    {
        $hash = $this->generateQRHash($deliveryId, $userId);
        
        $qrData = [
            'hash' => $hash,
            'delivery_id' => $deliveryId,
            'timestamp' => time()
        ];

        return [
            'hash' => $hash,
            'data' => json_encode($qrData),
            'url' => $this->generateQRUrl($hash)
        ];
    }

    /**
     * Génère l'URL pour le QR code
     */
    public function generateQRUrl($hash)
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        return $baseUrl . '/confirm-delivery?code=' . $hash;
    }

    /**
     * Valide un QR code hash
     */
    public function validateQRHash($hash, $deliveryId = null)
    {
        if (empty($hash)) {
            return false;
        }

        // Vérifier la longueur du hash (SHA256 = 64 caractères)
        if (strlen($hash) !== 64) {
            return false;
        }

        // Vérifier que le hash ne contient que des caractères hexadécimaux
        if (!ctype_xdigit($hash)) {
            return false;
        }

        // Si un deliveryId est fourni, on peut faire une validation supplémentaire
        if ($deliveryId) {
            // Ici on pourrait vérifier en base de données que le hash correspond bien à cette livraison
            // Pour l'instant, on retourne true si le format est correct
            return true;
        }

        return true;
    }

    /**
     * Décode les données d'un QR code
     */
    public function decodeQRData($qrData)
    {
        try {
            $data = json_decode($qrData, true);
            
            if (!$data || !isset($data['hash'], $data['delivery_id'], $data['timestamp'])) {
                return false;
            }

            // Vérifier que le timestamp n'est pas trop ancien (24h)
            $maxAge = 24 * 60 * 60; // 24 heures en secondes
            if (time() - $data['timestamp'] > $maxAge) {
                return false;
            }

            return $data;
        } catch (\Exception $e) {
            error_log('QR Code decode error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère un QR code image (nécessite une bibliothèque externe)
     * Note: Cette fonction nécessite l'installation de qrcode-generator ou similaire
     */
    public function generateQRImage($data, $size = 300)
    {
        // Exemple avec une bibliothèque QR code
        // require_once 'vendor/autoload.php';
        // $qrCode = new QRCode();
        // return $qrCode->generate($data, $size);
        
        // Pour l'instant, on retourne une URL vers un service en ligne
        $encodedData = urlencode($data);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedData}";
    }

    /**
     * Vérifie si un QR code est expiré
     */
    public function isQRExpired($timestamp, $maxAge = 86400)
    {
        return (time() - $timestamp) > $maxAge;
    }

    /**
     * Génère un code de confirmation alternatif (pour les cas où le QR ne fonctionne pas)
     */
    public function generateConfirmationCode($deliveryId)
    {
        $data = $deliveryId . time() . random_bytes(8);
        return strtoupper(substr(hash('sha256', $data), 0, 8));
    }

    /**
     * Valide un code de confirmation
     */
    public function validateConfirmationCode($code, $deliveryId)
    {
        if (empty($code) || strlen($code) !== 8) {
            return false;
        }

        // Vérifier que le code ne contient que des caractères alphanumériques
        if (!ctype_alnum($code)) {
            return false;
        }

        // Ici on pourrait faire une validation plus poussée en base de données
        return true;
    }

    /**
     * Génère un token de sécurité pour les opérations sensibles
     */
    public function generateSecurityToken($userId, $operation)
    {
        $data = [
            'user_id' => $userId,
            'operation' => $operation,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(8))
        ];

        $jsonData = json_encode($data);
        return hash_hmac('sha256', $jsonData, $this->secretKey);
    }

    /**
     * Valide un token de sécurité
     */
    public function validateSecurityToken($token, $userId, $operation, $maxAge = 3600)
    {
        if (empty($token)) {
            return false;
        }

        // Vérifier la longueur du token
        if (strlen($token) !== 64) {
            return false;
        }

        // Ici on pourrait faire une validation plus poussée
        // Pour l'instant, on retourne true si le format est correct
        return true;
    }
} 