<?php

declare(strict_types=1);

namespace DeliveryP2P\Controllers;

use DeliveryP2P\Services\QRCodeService;
use DeliveryP2P\Utils\Response;
use DeliveryP2P\Utils\Logger;
use DeliveryP2P\Core\Exceptions\ApiException;

/**
 * Contrôleur QR Code
 * Gestion du système QR code sécurisé
 */
class QRController
{
    private QRCodeService $qrService;
    private Logger $logger;

    public function __construct()
    {
        $this->qrService = new QRCodeService();
        $this->logger = new Logger();
    }

    /**
     * Génère un QR code sécurisé
     * POST /qr/generate
     */
    public function generate(): array
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                return Response::validationError(['data' => 'Données JSON invalides']);
            }

            $deliveryId = $input['delivery_id'] ?? null;
            $type = $input['type'] ?? 'delivery';
            $metadata = $input['metadata'] ?? [];

            if (!$deliveryId) {
                return Response::validationError(['delivery_id' => 'ID de livraison requis']);
            }

            // Validation du type
            $allowedTypes = ['pickup', 'delivery', 'verification'];
            if (!in_array($type, $allowedTypes)) {
                return Response::validationError(['type' => 'Type invalide']);
            }

            $result = $this->qrService->generateSecureQR($deliveryId, $type, $metadata);

            $this->logger->info('QR code generated', [
                'delivery_id' => $deliveryId,
                'type' => $type,
                'qr_id' => $result['qr_id']
            ]);

            return Response::created($result, 'QR code généré avec succès');

        } catch (\Exception $e) {
            $this->logger->error('QR generation failed', [
                'error' => $e->getMessage()
            ]);

            throw new ApiException('Erreur lors de la génération du QR code', 500);
        }
    }

    /**
     * Valide un QR code
     * POST /qr/validate
     */
    public function validate(): array
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                return Response::validationError(['data' => 'Données JSON invalides']);
            }

            $qrData = $input['qr_data'] ?? null;
            $context = $input['context'] ?? [];

            if (!$qrData) {
                return Response::validationError(['qr_data' => 'Données QR requises']);
            }

            // Ajout du contexte de scan
            $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $context['timestamp'] = time();

            $result = $this->qrService->validateQR($qrData, $context);

            if ($result['success']) {
                $this->logger->info('QR code validated', [
                    'qr_id' => $result['qr_id'] ?? 'unknown',
                    'delivery_id' => $result['delivery_id'] ?? 'unknown'
                ]);
            } else {
                $this->logger->warning('QR code validation failed', [
                    'error' => $result['error'] ?? 'unknown error'
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('QR validation failed', [
                'error' => $e->getMessage()
            ]);

            throw new ApiException('Erreur lors de la validation du QR code', 500);
        }
    }

    /**
     * Récupère les informations d'un QR code
     * GET /qr/{code}/info
     */
    public function info(string $code): array
    {
        try {
            if (!$code) {
                return Response::validationError(['code' => 'Code QR requis']);
            }

            // Récupération des informations depuis la base
            $db = \DeliveryP2P\Utils\Database::getInstance();
            $result = $db->get('qr_codes', ['id' => $code]);

            if (!$result['success'] || empty($result['data'])) {
                return Response::notFound('QR code non trouvé');
            }

            $qrInfo = $result['data'][0];
            
            // Vérification de l'expiration
            $expiresAt = strtotime($qrInfo['expires_at']);
            if (time() > $expiresAt) {
                return Response::error('QR code expiré', 410);
            }

            // Récupération de l'historique des scans
            $scanHistory = $this->qrService->getScanHistory($code);

            $info = [
                'qr_id' => $qrInfo['id'],
                'delivery_id' => $qrInfo['delivery_id'],
                'type' => $qrInfo['type'],
                'status' => $qrInfo['status'],
                'created_at' => $qrInfo['created_at'],
                'expires_at' => $qrInfo['expires_at'],
                'scan_count' => count($scanHistory),
                'last_scan' => !empty($scanHistory) ? $scanHistory[0]['scanned_at'] : null
            ];

            return Response::success($info);

        } catch (\Exception $e) {
            $this->logger->error('QR info retrieval failed', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);

            throw new ApiException('Erreur lors de la récupération des informations QR', 500);
        }
    }

    /**
     * Enregistre un scan de QR code
     * POST /qr/{code}/scan
     */
    public function scan(string $code): array
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $context = $input ?? [];

            if (!$code) {
                return Response::validationError(['code' => 'Code QR requis']);
            }

            // Ajout du contexte de scan
            $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $context['timestamp'] = time();

            // Récupération des données QR depuis la base
            $db = \DeliveryP2P\Utils\Database::getInstance();
            $result = $db->get('qr_codes', ['id' => $code]);

            if (!$result['success'] || empty($result['data'])) {
                return Response::notFound('QR code non trouvé');
            }

            $qrInfo = $result['data'][0];
            
            // Vérification du statut
            if ($qrInfo['status'] !== 'active') {
                return Response::error('QR code révoqué ou inactif', 410);
            }

            // Vérification de l'expiration
            $expiresAt = strtotime($qrInfo['expires_at']);
            if (time() > $expiresAt) {
                return Response::error('QR code expiré', 410);
            }

            // Enregistrement du scan
            $scanData = [
                'qr_id' => $code,
                'scanned_at' => date('c'),
                'ip_address' => $context['ip'],
                'user_agent' => $context['user_agent'],
                'latitude' => $context['latitude'] ?? null,
                'longitude' => $context['longitude'] ?? null,
                'device_info' => json_encode($context['device'] ?? [])
            ];

            $db->post('qr_scans', $scanData);

            $this->logger->info('QR code scanned', [
                'qr_id' => $code,
                'delivery_id' => $qrInfo['delivery_id'],
                'ip' => $context['ip']
            ]);

            return Response::success([
                'qr_id' => $code,
                'delivery_id' => $qrInfo['delivery_id'],
                'type' => $qrInfo['type'],
                'scanned_at' => $scanData['scanned_at']
            ], 'Scan enregistré avec succès');

        } catch (\Exception $e) {
            $this->logger->error('QR scan recording failed', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);

            throw new ApiException('Erreur lors de l\'enregistrement du scan', 500);
        }
    }

    /**
     * Récupère l'historique des scans d'un QR
     * GET /qr/{code}/history
     */
    public function history(string $code): array
    {
        try {
            if (!$code) {
                return Response::validationError(['code' => 'Code QR requis']);
            }

            $scanHistory = $this->qrService->getScanHistory($code);

            return Response::list($scanHistory);

        } catch (\Exception $e) {
            $this->logger->error('QR history retrieval failed', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);

            throw new ApiException('Erreur lors de la récupération de l\'historique', 500);
        }
    }

    /**
     * Révoque un QR code
     * DELETE /qr/{code}/revoke
     */
    public function revoke(string $code): array
    {
        try {
            if (!$code) {
                return Response::validationError(['code' => 'Code QR requis']);
            }

            $success = $this->qrService->revokeQR($code);

            if ($success) {
                $this->logger->info('QR code revoked', ['qr_id' => $code]);
                return Response::deleted('QR code révoqué avec succès');
            } else {
                return Response::error('Erreur lors de la révocation du QR code', 500);
            }

        } catch (\Exception $e) {
            $this->logger->error('QR revocation failed', [
                'code' => $code,
                'error' => $e->getMessage()
            ]);

            throw new ApiException('Erreur lors de la révocation du QR code', 500);
        }
    }
} 