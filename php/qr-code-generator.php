<?php
/**
 * Générateur de QR codes en PHP
 * API pour générer et gérer les QR codes
 */

require_once 'config.php';
require_once 'supabase-api.php';

// Vérifier la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$api = new SupabaseAPI();

// Traitement des requêtes
switch ($method) {
    case 'GET':
        handleGetRequest();
        break;
    case 'POST':
        handlePostRequest();
        break;
    default:
        sendErrorResponse('Méthode non autorisée', 405);
        break;
}

/**
 * Gérer les requêtes GET
 */
function handleGetRequest() {
    global $api;
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            $userId = $_GET['user_id'] ?? '';
            $type = $_GET['type'] ?? '';
            $filters = [];
            
            if ($userId) {
                $filters['user_id'] = $userId;
            }
            if ($type && $type !== 'all') {
                $filters['type'] = $type;
            }
            
            $result = $api->getQRCodes($filters);
            sendJsonResponse($result);
            break;
            
        case 'stats':
            $userId = $_GET['user_id'] ?? '';
            if (!$userId) {
                sendErrorResponse('ID utilisateur requis');
                return;
            }
            
            $result = $api->getQRCodeStats($userId);
            sendJsonResponse($result);
            break;
            
        case 'search':
            $userId = $_GET['user_id'] ?? '';
            $searchTerm = $_GET['q'] ?? '';
            $type = $_GET['type'] ?? null;
            
            if (!$userId || !$searchTerm) {
                sendErrorResponse('ID utilisateur et terme de recherche requis');
                return;
            }
            
            $result = $api->searchQRCodes($userId, $searchTerm, $type);
            sendJsonResponse($result);
            break;
            
        case 'export':
            $userId = $_GET['user_id'] ?? '';
            if (!$userId) {
                sendErrorResponse('ID utilisateur requis');
                return;
            }
            
            $result = $api->exportUserQRCodes($userId);
            if ($result['success']) {
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="qr_codes_export_' . date('Y-m-d') . '.json"');
                echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                sendErrorResponse($result['error']);
            }
            break;
            
        default:
            sendErrorResponse('Action non reconnue');
            break;
    }
}

/**
 * Gérer les requêtes POST
 */
function handlePostRequest() {
    global $api;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'generate':
            generateQRCode($input);
            break;
            
        case 'create':
            createQRCode($input);
            break;
            
        case 'update':
            updateQRCode($input);
            break;
            
        case 'delete':
            deleteQRCode($input);
            break;
            
        case 'toggle_favorite':
            toggleFavorite($input);
            break;
            
        case 'increment_scan':
            incrementScanCount($input);
            break;
            
        default:
            sendErrorResponse('Action non reconnue');
            break;
    }
}

/**
 * Générer un QR code
 */
function generateQRCode($data) {
    $content = $data['content'] ?? '';
    $type = $data['type'] ?? 'custom';
    $title = $data['title'] ?? 'QR Code';
    $description = $data['description'] ?? '';
    $userId = $data['user_id'] ?? '';
    
    if (!$content || !$userId) {
        sendErrorResponse('Contenu et ID utilisateur requis');
        return;
    }
    
    // Valider le type
    global $QR_CODE_TYPES;
    if (!array_key_exists($type, $QR_CODE_TYPES)) {
        sendErrorResponse('Type de QR code invalide');
        return;
    }
    
    // Générer le QR code avec une bibliothèque PHP
    $qrCodeDataURL = generateQRCodeImage($content);
    
    if (!$qrCodeDataURL) {
        sendErrorResponse('Erreur lors de la génération du QR code');
        return;
    }
    
    // Préparer les données pour la base
    $qrCodeData = [
        'user_id' => $userId,
        'content' => $content,
        'qr_code_data' => $qrCodeDataURL,
        'type' => $type,
        'title' => $title,
        'description' => $description,
        'metadata' => json_encode($data['metadata'] ?? [])
    ];
    
    global $api;
    $result = $api->createQRCode($qrCodeData);
    
    if ($result['success']) {
        logMessage('INFO', 'QR code généré', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title
        ]);
        
        sendSuccessResponse($result['data'], 'QR code généré avec succès');
    } else {
        sendErrorResponse($result['error']);
    }
}

/**
 * Créer un QR code (sans génération d'image)
 */
function createQRCode($data) {
    $qrCodeData = [
        'user_id' => $data['user_id'] ?? '',
        'content' => $data['content'] ?? '',
        'qr_code_data' => $data['qr_code_data'] ?? '',
        'type' => $data['type'] ?? 'custom',
        'title' => $data['title'] ?? 'QR Code',
        'description' => $data['description'] ?? '',
        'metadata' => json_encode($data['metadata'] ?? [])
    ];
    
    if (!$qrCodeData['user_id'] || !$qrCodeData['content']) {
        sendErrorResponse('ID utilisateur et contenu requis');
        return;
    }
    
    global $api;
    $result = $api->createQRCode($qrCodeData);
    
    if ($result['success']) {
        sendSuccessResponse($result['data'], 'QR code créé avec succès');
    } else {
        sendErrorResponse($result['error']);
    }
}

/**
 * Mettre à jour un QR code
 */
function updateQRCode($data) {
    $qrCodeId = $data['id'] ?? '';
    $updates = $data['updates'] ?? [];
    
    if (!$qrCodeId) {
        sendErrorResponse('ID du QR code requis');
        return;
    }
    
    global $api;
    $result = $api->update('qr_codes', $updates, ['id' => $qrCodeId]);
    
    if ($result['success']) {
        sendSuccessResponse($result['data'], 'QR code mis à jour avec succès');
    } else {
        sendErrorResponse($result['error']);
    }
}

/**
 * Supprimer un QR code
 */
function deleteQRCode($data) {
    $qrCodeId = $data['id'] ?? '';
    
    if (!$qrCodeId) {
        sendErrorResponse('ID du QR code requis');
        return;
    }
    
    global $api;
    $result = $api->delete('qr_codes', ['id' => $qrCodeId]);
    
    if ($result['success']) {
        sendSuccessResponse(null, 'QR code supprimé avec succès');
    } else {
        sendErrorResponse($result['error']);
    }
}

/**
 * Basculer le statut favori
 */
function toggleFavorite($data) {
    $qrCodeId = $data['id'] ?? '';
    $userId = $data['user_id'] ?? '';
    
    if (!$qrCodeId || !$userId) {
        sendErrorResponse('ID du QR code et ID utilisateur requis');
        return;
    }
    
    global $api;
    $result = $api->rpc('toggle_qr_favorite', [
        'p_qr_id' => $qrCodeId,
        'p_user_id' => $userId
    ]);
    
    if ($result['success']) {
        sendSuccessResponse(['is_favorite' => $result['data']], 'Statut favori mis à jour');
    } else {
        sendErrorResponse($result['error']);
    }
}

/**
 * Incrémenter le compteur de scans
 */
function incrementScanCount($data) {
    $qrCodeId = $data['id'] ?? '';
    
    if (!$qrCodeId) {
        sendErrorResponse('ID du QR code requis');
        return;
    }
    
    global $api;
    $result = $api->rpc('increment_qr_scan_count', ['p_qr_id' => $qrCodeId]);
    
    if ($result['success']) {
        sendSuccessResponse(null, 'Compteur de scans incrémenté');
    } else {
        sendErrorResponse($result['error']);
    }
}

/**
 * Générer une image QR code (simulation)
 * En production, utilisez une bibliothèque comme endroid/qr-code
 */
function generateQRCodeImage($content) {
    // Simulation de génération de QR code
    // En production, utilisez une vraie bibliothèque PHP
    $qrCodeData = [
        'content' => $content,
        'timestamp' => time(),
        'generated_by' => 'php-api'
    ];
    
    // Encoder en base64 pour simuler une image
    $jsonData = json_encode($qrCodeData);
    $base64Data = base64_encode($jsonData);
    
    // Retourner un data URL simulé
    return 'data:application/json;base64,' . $base64Data;
}

/**
 * Valider les données d'entrée
 */
function validateQRCodeData($data) {
    $errors = [];
    
    if (empty($data['content'])) {
        $errors[] = 'Le contenu est requis';
    }
    
    if (strlen($data['content']) > 2000) {
        $errors[] = 'Le contenu ne peut pas dépasser 2000 caractères';
    }
    
    if (empty($data['title'])) {
        $errors[] = 'Le titre est requis';
    }
    
    if (strlen($data['title']) > 255) {
        $errors[] = 'Le titre ne peut pas dépasser 255 caractères';
    }
    
    global $QR_CODE_TYPES;
    if (!empty($data['type']) && !array_key_exists($data['type'], $QR_CODE_TYPES)) {
        $errors[] = 'Type de QR code invalide';
    }
    
    return $errors;
}

/**
 * Nettoyer les données d'entrée
 */
function sanitizeQRCodeData($data) {
    return [
        'content' => sanitizeInput($data['content'] ?? ''),
        'title' => sanitizeInput($data['title'] ?? ''),
        'description' => sanitizeInput($data['description'] ?? ''),
        'type' => sanitizeInput($data['type'] ?? 'custom'),
        'user_id' => sanitizeInput($data['user_id'] ?? ''),
        'metadata' => $data['metadata'] ?? []
    ];
}

// Log de fin
logMessage('INFO', 'API QR Code terminée', [
    'method' => $method,
    'action' => $_GET['action'] ?? $_POST['action'] ?? 'unknown'
]);
?> 