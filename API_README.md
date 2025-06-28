# 🚀 API DeliveryP2P - Documentation

## 📍 **URL de base**
```
https://deliveryp2p-api.onrender.com
```

## 🔧 **Endpoints disponibles**

### 1. **Health Check**
```http
GET /health
```

**Réponse :**
```json
{
    "status": "healthy",
    "timestamp": "2025-06-28T12:00:00+02:00",
    "version": "2.0.0",
    "environment": "production",
    "php_version": "8.1.0",
    "database": {
        "status": "configured",
        "message": "Variables Supabase configurées"
    },
    "services": {
        "qr_code": "available",
        "authentication": "available",
        "tracking": "available",
        "notifications": "available"
    }
}
```

### 2. **Test QR Endpoint**
```http
GET /qr
```

**Réponse :**
```json
{
    "success": true,
    "message": "QR API endpoint accessible",
    "endpoints": {
        "GET /qr": "Test endpoint",
        "POST /qr/generate": "Generate QR code"
    },
    "timestamp": "2025-06-28T12:00:00+02:00"
}
```

### 3. **Génération QR Code**
```http
POST /qr/generate
Content-Type: application/json

{
    "data": "https://deliveryp2p.onrender.com",
    "size": 300
}
```

**Réponse :**
```json
{
    "success": true,
    "data": {
        "qr_code": "base64_encoded_data",
        "qr_code_url": "data:image/png;base64,base64_encoded_data",
        "data": "https://deliveryp2p.onrender.com",
        "size": 300,
        "timestamp": "2025-06-28T12:00:00+02:00",
        "message": "QR code généré avec succès"
    }
}
```

## 🧪 **Tests en local**

### Démarrer le serveur local :
```bash
cd api
php -S localhost:8000 index-simple.php
```

### Tester les endpoints :
```bash
# Health check
curl -X GET "http://localhost:8000/health"

# Test QR
curl -X GET "http://localhost:8000/qr"

# Générer QR
curl -X POST "http://localhost:8000/qr/generate" \
  -H "Content-Type: application/json" \
  -d '{"data": "test123", "size": 200}'
```

## 🔄 **Déploiement**

### Déployer automatiquement :
```bash
./deploy-api.sh
```

### Déploiement manuel :
```bash
git add .
git commit -m "Update API"
git push origin main
```

## 📱 **Intégration Frontend**

### Exemple d'utilisation dans le frontend :
```javascript
// Générer un QR code
const generateQR = async (data, size = 200) => {
    try {
        const response = await fetch('https://deliveryp2p-api.onrender.com/qr/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ data, size })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Afficher le QR code
            const qrImage = document.createElement('img');
            qrImage.src = result.data.qr_code_url;
            document.body.appendChild(qrImage);
        }
    } catch (error) {
        console.error('Erreur génération QR:', error);
    }
};
```

## 🛠 **Configuration**

### Variables d'environnement requises :
- `SUPABASE_URL` : URL de votre base de données Supabase
- `SUPABASE_ANON_KEY` : Clé anonyme Supabase
- `SUPABASE_SERVICE_ROLE_KEY` : Clé de service Supabase

### Configuration Render :
- **Runtime** : PHP
- **Build Command** : `echo "No build required for PHP"`
- **Start Command** : `cd api && php -S 0.0.0.0:$PORT index-simple.php`

## 📊 **Statut du service**

- ✅ **API Health** : Fonctionnel
- ✅ **QR Generation** : Fonctionnel
- ✅ **Database Connection** : Configuré
- ✅ **Deployment** : Actif sur Render

## 🔗 **Liens utiles**

- **Frontend** : https://deliveryp2p.onrender.com
- **API** : https://deliveryp2p-api.onrender.com
- **Documentation** : Ce fichier 