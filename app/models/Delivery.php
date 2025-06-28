<?php

namespace App\Models;

use App\Services\SupabaseService;

class Delivery
{
    private $supabase;
    
    public function __construct()
    {
        $this->supabase = new SupabaseService();
    }
    
    /**
     * Créer une nouvelle livraison
     */
    public function create($data)
    {
        return $this->supabase->createDelivery($data);
    }
    
    /**
     * Récupérer une livraison par ID
     */
    public function findById($id)
    {
        return $this->supabase->getDelivery($id);
    }
    
    /**
     * Récupérer les livraisons d'un livreur
     */
    public function getByCourier($courierId, $limit = 50, $offset = 0)
    {
        return $this->supabase->getDeliveriesByCourier($courierId, $limit, $offset);
    }
    
    /**
     * Récupérer les livraisons d'un expéditeur
     */
    public function getByExpeditor($expeditorId, $limit = 50, $offset = 0)
    {
        return $this->supabase->getDeliveriesByExpeditor($expeditorId, $limit, $offset);
    }
    
    /**
     * Mettre à jour le statut d'une livraison
     */
    public function updateStatus($id, $status, $additionalData = [])
    {
        return $this->supabase->updateDeliveryStatus($id, $status, $additionalData);
    }
    
    /**
     * Confirmer une livraison
     */
    public function confirm($id, $qrCode)
    {
        return $this->supabase->confirmDelivery($id, $qrCode);
    }
    
    /**
     * Récupérer toutes les livraisons (admin)
     */
    public function getAll($limit = 50, $offset = 0)
    {
        return $this->supabase->getAllDeliveries($limit, $offset);
    }
    
    /**
     * Récupérer les livraisons par statut
     */
    public function getByStatus($status, $limit = 50, $offset = 0)
    {
        return $this->supabase->getDeliveriesByStatus($status, $limit, $offset);
    }
    
    /**
     * Supprimer une livraison
     */
    public function delete($id)
    {
        return $this->supabase->deleteDelivery($id);
    }
} 