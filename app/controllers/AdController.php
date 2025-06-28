<?php
namespace App\Controllers;

use App\Services\SupabaseService;
use App\Services\EmailService;
use App\Services\WebPushService;

class AdController {
    /**
     * Créer une annonce (POST /api/ads)
     * - Authentification requise (expéditeur)
     * - Validation des données
     * - Géocodage des adresses (optionnel)
     * - Insertion dans Supabase
     * - Notifications multi-canaux aux livreurs disponibles
     */
    public function create($request, $user) {
        // 1. Vérifier le rôle
        if ($user['role'] !== 'expeditor') {
            http_response_code(403);
            return ['error' => 'Seuls les expéditeurs peuvent créer une annonce.'];
        }
        // 2. Validation basique
        $required = ['title','description','pickup_address','delivery_address','amount'];
        foreach ($required as $field) {
            if (empty($request[$field])) {
                http_response_code(400);
                return ['error' => "Champ requis manquant: $field"]; 
            }
        }
        // 3. Géocodage (optionnel)
        // $coords = MapService::geocode($request['pickup_address']);
        // $request['pickup_lat'] = $coords['lat']; $request['pickup_lng'] = $coords['lng'];
        // 4. Insertion dans Supabase
        $supabase = new SupabaseService();
        $ad = $supabase->post('ads', array_merge($request, [
            'expeditor_id' => $user['id'],
            'status' => 'pending',
            'created_at' => date('c')
        ]));
        if (!$ad || isset($ad['error'])) {
            http_response_code(500);
            return ['error' => 'Erreur lors de la création de l\'annonce.'];
        }
        // 5. Trouver les livreurs disponibles (exemple: tous les livreurs actifs)
        $couriers = $supabase->get('users', ['role' => 'eq.courier', 'is_available' => 'eq.true']);
        foreach ($couriers as $courier) {
            // Notification Realtime
            $supabase->post('notifications', [
                'user_id' => $courier['id'],
                'type' => 'new_ad',
                'message' => "Nouvelle annonce à proximité : " . $ad['title'],
                'related_id' => $ad['id'],
                'channel' => 'realtime',
                'created_at' => date('c')
            ]);
            // Email
            EmailService::sendNewAdNotification($courier['email'], $ad);
            // Web Push
            $subs = $supabase->get('web_push_subscriptions', ['user_id' => 'eq.' . $courier['id']]);
            foreach ($subs as $sub) {
                WebPushService::sendPushNotification($sub, "Nouvelle annonce à proximité !");
            }
        }
        return ['success' => true, 'ad' => $ad];
    }
} 