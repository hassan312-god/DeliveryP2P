<?php
namespace App\Controllers;

use App\Services\SupabaseService;
use App\Services\EmailService;
use App\Services\WebPushService;

class DeliveryController {
    /**
     * Mettre à jour le statut d'une livraison (PUT /api/deliveries/{id}/status)
     * - Authentification requise (livreur associé)
     * - Validation des transitions de statut
     * - Notifications temps réel, email, web push
     */
    public function updateStatus($deliveryId, $newStatus, $user) {
        $supabase = new SupabaseService();
        $delivery = $supabase->get('deliveries', ['id' => 'eq.' . $deliveryId]);
        if (!$delivery || !isset($delivery[0])) {
            http_response_code(404);
            return ['error' => 'Livraison introuvable.'];
        }
        $delivery = $delivery[0];
        // Vérifier que l'utilisateur est bien le livreur
        if ($delivery['courier_id'] !== $user['id']) {
            http_response_code(403);
            return ['error' => 'Non autorisé.'];
        }
        // Validation stricte des transitions de statut (exemple simplifié)
        $validTransitions = [
            'assigned' => ['picked_up'],
            'picked_up' => ['in_transit', 'approaching_delivery'],
            'in_transit' => ['approaching_delivery', 'delivered'],
            'approaching_delivery' => ['delivered'],
        ];
        $current = $delivery['delivery_status'];
        if (!isset($validTransitions[$current]) || !in_array($newStatus, $validTransitions[$current])) {
            http_response_code(400);
            return ['error' => 'Transition de statut invalide.'];
        }
        // Mettre à jour le statut
        $updateData = ['delivery_status' => $newStatus, 'updated_at' => date('c')];
        if ($newStatus === 'picked_up') $updateData['pickup_time'] = date('c');
        if ($newStatus === 'delivered') $updateData['delivery_time'] = date('c');
        $supabase->put('deliveries', $updateData, ['id' => 'eq.' . $deliveryId]);
        // Récupérer expéditeur et livreur
        $expeditor = $supabase->get('users', ['id' => 'eq.' . $delivery['expeditor_id']]);
        $courier = $supabase->get('users', ['id' => 'eq.' . $delivery['courier_id']]);
        $expeditor = $expeditor ? $expeditor[0] : null;
        $courier = $courier ? $courier[0] : null;
        // Notifications selon le statut
        switch ($newStatus) {
            case 'picked_up':
                // Notif expéditeur
                $supabase->post('notifications', [
                    'user_id' => $expeditor['id'],
                    'type' => 'parcel_picked_up',
                    'message' => "Votre colis a été ramassé par " . $courier['first_name'],
                    'related_id' => $deliveryId,
                    'channel' => 'realtime',
                    'created_at' => date('c')
                ]);
                EmailService::sendStatusUpdate($expeditor['email'], "Colis ramassé", $deliveryId);
                break;
            case 'approaching_delivery':
                $supabase->post('notifications', [
                    'user_id' => $expeditor['id'],
                    'type' => 'courier_approaching',
                    'message' => "Le livreur approche de la destination.",
                    'related_id' => $deliveryId,
                    'channel' => 'realtime',
                    'created_at' => date('c')
                ]);
                break;
            case 'delivered':
                $supabase->post('notifications', [
                    'user_id' => $expeditor['id'],
                    'type' => 'delivery_confirmed',
                    'message' => "La livraison de votre colis a été confirmée !",
                    'related_id' => $deliveryId,
                    'channel' => 'realtime',
                    'created_at' => date('c')
                ]);
                $supabase->post('notifications', [
                    'user_id' => $courier['id'],
                    'type' => 'delivery_confirmed',
                    'message' => "Livraison confirmée par le destinataire.",
                    'related_id' => $deliveryId,
                    'channel' => 'realtime',
                    'created_at' => date('c')
                ]);
                EmailService::sendStatusUpdate($expeditor['email'], "Colis livré", $deliveryId);
                EmailService::sendStatusUpdate($courier['email'], "Colis livré", $deliveryId);
                break;
        }
        // Web Push (exemple pour expéditeur)
        $subs = $supabase->get('web_push_subscriptions', ['user_id' => 'eq.' . $expeditor['id']]);
        foreach ($subs as $sub) {
            WebPushService::sendPushNotification($sub, "Mise à jour de votre livraison : $newStatus");
        }
        return ['success' => true, 'status' => $newStatus];
    }
} 