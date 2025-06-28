<?php

namespace App\Models;

use App\Services\SupabaseService;

class User
{
    private $supabase;
    
    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }
    
    /**
     * Créer un nouvel utilisateur
     */
    public function create($data)
    {
        return $this->supabase->createUser($data);
    }
    
    /**
     * Récupérer un utilisateur par ID
     */
    public function findById($id)
    {
        return $this->supabase->getUser($id);
    }
    
    /**
     * Récupérer un utilisateur par email
     */
    public function findByEmail($email)
    {
        return $this->supabase->getUserByEmail($email);
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function update($id, $data)
    {
        return $this->supabase->updateUser($id, $data);
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function delete($id)
    {
        return $this->supabase->deleteUser($id);
    }
    
    /**
     * Récupérer tous les utilisateurs (admin)
     */
    public function getAll($limit = 50, $offset = 0)
    {
        return $this->supabase->getAllUsers($limit, $offset);
    }
    
    /**
     * Vérifier si l'utilisateur existe
     */
    public function exists($email)
    {
        $user = $this->findByEmail($email);
        return $user !== null;
    }
} 