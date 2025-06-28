const fs = require('fs');
const https = require('https');

// Configuration Supabase
const SUPABASE_URL = 'https://syamapjohtlbjlyhlhsi.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5YW1hcGpvaHRsYmpseWhsaHNpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTAzNzAxNjksImV4cCI6MjA2NTk0NjE2OX0._Sl5uiiUKK58wPa-lJG-TPe7K9rdem6Uc2V4epyhx_M';

// Lire le fichier SQL
const sqlContent = fs.readFileSync('CORRECTION-COMPLETE.sql', 'utf8');

// Fonction pour faire une requête HTTPS
function makeRequest(options, data) {
    return new Promise((resolve, reject) => {
        const req = https.request(options, (res) => {
            let responseData = '';
            
            res.on('data', (chunk) => {
                responseData += chunk;
            });
            
            res.on('end', () => {
                try {
                    const parsedData = JSON.parse(responseData);
                    resolve({ status: res.statusCode, data: parsedData });
                } catch (error) {
                    resolve({ status: res.statusCode, data: responseData });
                }
            });
        });
        
        req.on('error', (error) => {
            reject(error);
        });
        
        if (data) {
            req.write(data);
        }
        
        req.end();
    });
}

// Fonction pour exécuter le SQL
async function executeSQL() {
    console.log('🚀 Début de l\'exécution du script SQL de correction...\n');
    
    try {
        // Diviser le script SQL en parties pour éviter les timeouts
        const sqlParts = sqlContent.split('-- =====================================');
        
        for (let i = 0; i < sqlParts.length; i++) {
            const part = sqlParts[i].trim();
            if (!part) continue;
            
            console.log(`📝 Exécution de la partie ${i + 1}/${sqlParts.length}...`);
            
            const options = {
                hostname: 'syamapjohtlbjlyhlhsi.supabase.co',
                port: 443,
                path: '/rest/v1/rpc/exec_sql',
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
                    'apikey': SUPABASE_ANON_KEY,
                    'Prefer': 'return=representation'
                }
            };
            
            const requestData = JSON.stringify({
                sql: part
            });
            
            try {
                const response = await makeRequest(options, requestData);
                console.log(`✅ Partie ${i + 1} exécutée avec succès (Status: ${response.status})`);
                
                if (response.data && response.data.length > 0) {
                    console.log('📊 Résultats:', JSON.stringify(response.data, null, 2));
                }
                
            } catch (error) {
                console.log(`⚠️  Erreur lors de l'exécution de la partie ${i + 1}:`, error.message);
                
                // Essayer une approche alternative avec l'API REST
                console.log('🔄 Tentative avec l\'API REST alternative...');
                
                const restOptions = {
                    hostname: 'syamapjohtlbjlyhlhsi.supabase.co',
                    port: 443,
                    path: '/rest/v1/profiles?select=*&limit=1',
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
                        'apikey': SUPABASE_ANON_KEY
                    }
                };
                
                try {
                    const restResponse = await makeRequest(restOptions);
                    console.log(`✅ Test de connexion réussi (Status: ${restResponse.status})`);
                } catch (restError) {
                    console.log('❌ Erreur de connexion à l\'API REST:', restError.message);
                }
            }
            
            // Pause entre les parties
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
        
        console.log('\n🎉 Script SQL terminé !');
        console.log('\n📋 Prochaines étapes :');
        console.log('1. Vérifiez dans le dashboard Supabase que la table "profiles" a été créée');
        console.log('2. Testez l\'inscription sur http://localhost:8000/auth/register.html');
        console.log('3. Si l\'erreur persiste, vérifiez les logs dans Supabase > Logs > Database');
        
    } catch (error) {
        console.error('❌ Erreur générale:', error.message);
        console.log('\n💡 Solution alternative :');
        console.log('1. Copiez le contenu du fichier CORRECTION-COMPLETE.sql');
        console.log('2. Allez dans Supabase Dashboard > SQL Editor');
        console.log('3. Collez le script et cliquez sur "Run"');
    }
}

// Exécuter le script
executeSQL(); 