<?php
namespace App\Controllers;

use App\Services\SupabaseService;

class ChatController {
    /**
     * Envoyer un message de chat (POST /api/chat/send)
     * - Authentification requise
     * - Validation des données
     * - Insertion dans Supabase (table chat_messages)
     */
    public function send($request, $user) {
        $required = ['delivery_id', 'content'];
        foreach ($required as $field) {
            if (empty($request[$field])) {
                http_response_code(400);
                return ['error' => "Champ requis manquant: $field"];
            }
        }
        $supabase = new SupabaseService();
        $data = [
            'delivery_id' => $request['delivery_id'],
            'sender_id' => $user['id'],
            'content' => $request['content'],
            'created_at' => date('c')
        ];
        $msg = $supabase->post('chat_messages', $data);
        return ['success' => true, 'message' => $msg];
    }

    /**
     * Récupérer l'historique des messages (GET /api/chat/messages/{delivery_id})
     * - Authentification requise
     * - Récupère tous les messages pour une livraison donnée
     */
    public function messages($delivery_id, $user) {
        $supabase = new SupabaseService();
        $msgs = $supabase->get('chat_messages', ['delivery_id' => 'eq.' . $delivery_id, 'order' => 'created_at.asc']);
        return ['success' => true, 'messages' => $msgs];
    }
} 