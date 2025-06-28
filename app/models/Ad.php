<?php

namespace App\Models;

use App\Services\SupabaseService;

class Ad
{
    private $supabase;
    
    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }
    
    /**
     * Créer une nouvelle annonce
     */
    public function create($data)
    {
        return $this->supabase->createAd($data);
    }
    
    /**
     * Récupérer une annonce par ID
     */
    public function findById($id)
    {
        return $this->supabase->getAd($id);
    }
    
    /**
     * Récupérer toutes les annonces disponibles
     */
    public function getAvailable($limit = 50, $offset = 0)
    {
        return $this->supabase->getAvailableAds($limit, $offset);
    }
    
    /**
     * Récupérer les annonces d'un expéditeur
     */
    public function getByExpeditor($expeditorId, $limit = 50, $offset = 0)
    {
        return $this->supabase->getAdsByExpeditor($expeditorId, $limit, $offset);
    }
    
    /**
     * Mettre à jour une annonce
     */
    public function update($id, $data)
    {
        return $this->supabase->updateAd($id, $data);
    }
    
    /**
     * Supprimer une annonce
     */
    public function delete($id)
    {
        return $this->supabase->deleteAd($id);
    }
    
    /**
     * Rechercher des annonces par critères
     */
    public function search($criteria)
    {
        return $this->supabase->searchAds($criteria);
    }
    
    /**
     * Marquer une annonce comme acceptée
     */
    public function markAsAccepted($id, $courierId)
    {
        return $this->supabase->markAdAsAccepted($id, $courierId);
    }
} 