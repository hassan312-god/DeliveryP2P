<?php

declare(strict_types=1);

namespace DeliveryP2P\Utils;

/**
 * Système de cache simple
 * Optimisation des performances pour Render
 */
class Cache
{
    private string $cachePath;
    private int $defaultTtl;

    public function __construct()
    {
        $this->cachePath = $_ENV['CACHE_PATH'] ?? __DIR__ . '/../../storage/cache';
        $this->defaultTtl = (int) ($_ENV['CACHE_TTL'] ?? 3600);
        
        // Création du répertoire de cache si nécessaire
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Stocke une valeur en cache
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? $this->defaultTtl;
            $expiry = time() + $ttl;
            
            $data = [
                'value' => $value,
                'expiry' => $expiry,
                'created_at' => time()
            ];
            
            $filename = $this->getCacheFilename($key);
            $content = serialize($data);
            
            return file_put_contents($filename, $content) !== false;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Récupère une valeur du cache
     */
    public function get(string $key)
    {
        try {
            $filename = $this->getCacheFilename($key);
            
            if (!file_exists($filename)) {
                return null;
            }
            
            $content = file_get_contents($filename);
            if ($content === false) {
                return null;
            }
            
            $data = unserialize($content);
            
            if (!$data || !isset($data['expiry']) || !isset($data['value'])) {
                return null;
            }
            
            // Vérification de l'expiration
            if (time() > $data['expiry']) {
                $this->delete($key);
                return null;
            }
            
            return $data['value'];
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Supprime une valeur du cache
     */
    public function delete(string $key): bool
    {
        try {
            $filename = $this->getCacheFilename($key);
            
            if (file_exists($filename)) {
                return unlink($filename);
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vérifie si une clé existe en cache
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Incrémente une valeur numérique en cache
     */
    public function increment(string $key, int $value = 1): int
    {
        $current = $this->get($key);
        
        if ($current === null) {
            $current = 0;
        }
        
        if (!is_numeric($current)) {
            $current = 0;
        }
        
        $newValue = (int) $current + $value;
        $this->set($key, $newValue);
        
        return $newValue;
    }

    /**
     * Décrémente une valeur numérique en cache
     */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }

    /**
     * Récupère plusieurs valeurs en une fois
     */
    public function getMultiple(array $keys): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        
        return $result;
    }

    /**
     * Stocke plusieurs valeurs en une fois
     */
    public function setMultiple(array $values, int $ttl = null): bool
    {
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Supprime plusieurs clés en une fois
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Vide tout le cache
     */
    public function clear(): bool
    {
        try {
            $files = glob($this->cachePath . '/*.cache');
            
            foreach ($files as $file) {
                unlink($file);
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Récupère les statistiques du cache
     */
    public function getStats(): array
    {
        $files = glob($this->cachePath . '/*.cache');
        $totalSize = 0;
        $expiredCount = 0;
        $validCount = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $content = file_get_contents($file);
            if ($content !== false) {
                $data = unserialize($content);
                
                if ($data && isset($data['expiry'])) {
                    if (time() > $data['expiry']) {
                        $expiredCount++;
                    } else {
                        $validCount++;
                    }
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_entries' => $validCount,
            'expired_entries' => $expiredCount,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        ];
    }

    /**
     * Nettoie les entrées expirées
     */
    public function cleanExpired(): int
    {
        $files = glob($this->cachePath . '/*.cache');
        $cleanedCount = 0;
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $data = unserialize($content);
                
                if ($data && isset($data['expiry']) && time() > $data['expiry']) {
                    if (unlink($file)) {
                        $cleanedCount++;
                    }
                }
            }
        }
        
        return $cleanedCount;
    }

    /**
     * Génère le nom de fichier pour une clé de cache
     */
    private function getCacheFilename(string $key): string
    {
        $hash = hash('sha256', $key);
        return $this->cachePath . '/' . $hash . '.cache';
    }

    /**
     * Cache avec TTL automatique basé sur la clé
     */
    public function remember(string $key, callable $callback, int $ttl = null)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Cache pour les tags (simulation)
     */
    public function tags(array $tags): self
    {
        // Implémentation simple des tags
        $this->currentTags = $tags;
        return $this;
    }

    /**
     * Flush les caches par tags
     */
    public function flush(): bool
    {
        if (isset($this->currentTags)) {
            // Logique pour supprimer les caches par tags
            return true;
        }
        
        return $this->clear();
    }
} 