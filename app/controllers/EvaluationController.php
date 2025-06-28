<?php
namespace App\Controllers;

use App\Services\SupabaseService;

class EvaluationController {
    /**
     * Créer une évaluation (POST /api/evaluations)
     * - Authentification requise
     * - Validation des données
     * - Insertion dans Supabase (table evaluations)
     */
    public function create($request, $user) {
        $required = ['delivery_id', 'evaluated_user_id', 'rating', 'comment'];
        foreach ($required as $field) {
            if (empty($request[$field])) {
                http_response_code(400);
                return ['error' => "Champ requis manquant: $field"];
            }
        }
        $supabase = new SupabaseService();
        $data = [
            'delivery_id' => $request['delivery_id'],
            'evaluator_id' => $user['id'],
            'evaluated_user_id' => $request['evaluated_user_id'],
            'rating' => $request['rating'],
            'comment' => $request['comment'],
            'created_at' => date('c')
        ];
        $eval = $supabase->post('evaluations', $data);
        return ['success' => true, 'evaluation' => $eval];
    }

    /**
     * Récupérer les évaluations d'un utilisateur (GET /api/evaluations/{user_id})
     * - Authentification requise
     * - Récupère toutes les évaluations reçues par un utilisateur
     */
    public function userEvaluations($user_id, $user) {
        $supabase = new SupabaseService();
        $evals = $supabase->get('evaluations', ['evaluated_user_id' => 'eq.' . $user_id, 'order' => 'created_at.desc']);
        return ['success' => true, 'evaluations' => $evals];
    }
} 