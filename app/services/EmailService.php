<?php
namespace App\Services;

class EmailService {
    /**
     * Envoie un email transactionnel
     * @param string $to Email du destinataire
     * @param string $subject Sujet
     * @param string $body Corps du message
     * @return bool
     */
    public static function send($to, $subject, $body) {
        // TODO: Intégrer SendGrid/Mailgun ou utiliser mail()
        // mail($to, $subject, $body);
        return true;
    }

    /**
     * Notification email pour nouvelle annonce
     */
    public static function sendNewAdNotification($courierEmail, $adDetails) {
        $subject = "Nouvelle demande de livraison disponible !";
        $body = "Une nouvelle annonce est disponible : " . $adDetails['title'];
        return self::send($courierEmail, $subject, $body);
    }

    /**
     * Notification email pour changement de statut de livraison
     */
    public static function sendStatusUpdate($userEmail, $status, $deliveryId) {
        $subject = "Mise à jour de votre livraison";
        $body = "Statut de la livraison #$deliveryId : $status";
        return self::send($userEmail, $subject, $body);
    }
} 