#!/bin/bash

echo "🔗 Mise à jour des URLs pour Render..."

# Mettre à jour config.js
echo "📝 Mise à jour de config.js..."
if [ -f "frontend/config.js" ]; then
    # Sauvegarder l'original
    cp frontend/config.js frontend/config.js.backup
    
    # Remplacer les URLs
    sed -i 's|https://deliveryp2p-backend.onrender.com|/backend|g' frontend/config.js
    sed -i 's|http://localhost:8000|/backend|g' frontend/config.js
    
    echo "✅ config.js mis à jour"
else
    echo "❌ config.js non trouvé"
fi

# Mettre à jour js/services/api.js
echo "📝 Mise à jour de js/services/api.js..."
if [ -f "frontend/js/services/api.js" ]; then
    # Sauvegarder l'original
    cp frontend/js/services/api.js frontend/js/services/api.js.backup
    
    # Remplacer les URLs
    sed -i 's|https://deliveryp2p-backend.onrender.com|/backend|g' frontend/js/services/api.js
    sed -i 's|http://localhost:8000|/backend|g' frontend/js/services/api.js
    
    echo "✅ js/services/api.js mis à jour"
else
    echo "❌ js/services/api.js non trouvé"
fi

# Mettre à jour js/app.js
echo "📝 Mise à jour de js/app.js..."
if [ -f "frontend/js/app.js" ]; then
    # Sauvegarder l'original
    cp frontend/js/app.js frontend/js/app.js.backup
    
    # Remplacer les URLs
    sed -i 's|https://deliveryp2p-backend.onrender.com|/backend|g' frontend/js/app.js
    sed -i 's|http://localhost:8000|/backend|g' frontend/js/app.js
    
    echo "✅ js/app.js mis à jour"
else
    echo "❌ js/app.js non trouvé"
fi

# Mettre à jour tous les fichiers HTML qui pourraient contenir des URLs
echo "📝 Mise à jour des fichiers HTML..."
for file in frontend/*.html; do
    if [ -f "$file" ]; then
        # Sauvegarder l'original
        cp "$file" "$file.backup"
        
        # Remplacer les URLs
        sed -i 's|https://deliveryp2p-backend.onrender.com|/backend|g' "$file"
        sed -i 's|http://localhost:8000|/backend|g' "$file"
        
        echo "✅ $(basename "$file") mis à jour"
    fi
done

echo "✅ URLs mises à jour !"
echo "📋 Sauvegardes créées avec l'extension .backup" 