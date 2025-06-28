<?php
namespace App\Services;

class WebPushService {
    /**
     * Envoie une notification push navigateur
     * @param array $subscription Détails de l'abonnement (endpoint, clés)
     * @param string $message Message à envoyer
     * @return bool
     */
    public static function sendPushNotification($subscription, $message) {
        // TODO: Intégrer web-push-php ou équivalent
        // $webPush->sendNotification($subscription['endpoint'], $message, ...);
        return true;
    }
} 