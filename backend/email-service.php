<?php
/**
 * Service d'email pour LivraisonP2P
 * Gère l'envoi d'emails de confirmation, réinitialisation et notifications
 */

require_once 'config.php';

class EmailService {
    private $supabase;
    private $config;
    
    public function __construct() {
        $this->config = new Config();
        $this->supabase = new SupabaseAPI();
    }
    
    /**
     * Envoyer un email de confirmation
     */
    public function sendConfirmationEmail($userId, $email, $token) {
        try {
            // Récupérer les informations de l'utilisateur
            $userData = $this->getUserData($userId);
            if (!$userData) {
                throw new Exception('Utilisateur non trouvé');
            }
            
            $userName = $userData['prenom'] . ' ' . $userData['nom'];
            $confirmationUrl = $this->buildConfirmationUrl($token, $email);
            
            $subject = 'Confirmez votre compte LivraisonP2P';
            $content = $this->getConfirmationEmailTemplate($userName, $confirmationUrl);
            
            // Envoyer l'email
            $result = $this->sendEmail($email, $subject, $content);
            
            if ($result) {
                // Créer une notification
                $this->createNotification($userId, 'email_confirmation_sent', 
                    'Email de confirmation envoyé', 
                    'Un email de confirmation a été envoyé à votre adresse email.',
                    ['email' => $email, 'token' => $token]
                );
                
                return [
                    'success' => true,
                    'message' => 'Email de confirmation envoyé avec succès'
                ];
            } else {
                throw new Exception('Erreur lors de l\'envoi de l\'email');
            }
            
        } catch (Exception $e) {
            error_log('Erreur envoi email confirmation: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Envoyer un email de réinitialisation de mot de passe
     */
    public function sendPasswordResetEmail($email, $token) {
        try {
            // Récupérer les informations de l'utilisateur
            $userData = $this->getUserByEmail($email);
            if (!$userData) {
                throw new Exception('Aucun compte trouvé avec cette adresse email');
            }
            
            $userName = $userData['prenom'] . ' ' . $userData['nom'];
            $resetUrl = $this->buildResetUrl($token, $email);
            
            $subject = 'Réinitialisation de votre mot de passe LivraisonP2P';
            $content = $this->getPasswordResetEmailTemplate($userName, $resetUrl);
            
            // Envoyer l'email
            $result = $this->sendEmail($email, $subject, $content);
            
            if ($result) {
                // Créer une notification
                $this->createNotification($userData['id'], 'password_reset_sent', 
                    'Email de réinitialisation envoyé', 
                    'Un email de réinitialisation de mot de passe a été envoyé.',
                    ['email' => $email, 'token' => $token]
                );
                
                return [
                    'success' => true,
                    'message' => 'Email de réinitialisation envoyé avec succès'
                ];
            } else {
                throw new Exception('Erreur lors de l\'envoi de l\'email');
            }
            
        } catch (Exception $e) {
            error_log('Erreur envoi email réinitialisation: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Envoyer un email de notification de livraison
     */
    public function sendDeliveryNotification($userId, $deliveryData) {
        try {
            $userData = $this->getUserData($userId);
            if (!$userData) {
                throw new Exception('Utilisateur non trouvé');
            }
            
            $userName = $userData['prenom'] . ' ' . $userData['nom'];
            $subject = 'Mise à jour de votre livraison - LivraisonP2P';
            $content = $this->getDeliveryNotificationTemplate($userName, $deliveryData);
            
            $result = $this->sendEmail($userData['email'], $subject, $content);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Notification de livraison envoyée'
                ];
            } else {
                throw new Exception('Erreur lors de l\'envoi de la notification');
            }
            
        } catch (Exception $e) {
            error_log('Erreur notification livraison: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Envoyer un email de confirmation de paiement
     */
    public function sendPaymentConfirmation($userId, $paymentData) {
        try {
            $userData = $this->getUserData($userId);
            if (!$userData) {
                throw new Exception('Utilisateur non trouvé');
            }
            
            $userName = $userData['prenom'] . ' ' . $userData['nom'];
            $subject = 'Confirmation de paiement - LivraisonP2P';
            $content = $this->getPaymentConfirmationTemplate($userName, $paymentData);
            
            $result = $this->sendEmail($userData['email'], $subject, $content);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Confirmation de paiement envoyée'
                ];
            } else {
                throw new Exception('Erreur lors de l\'envoi de la confirmation');
            }
            
        } catch (Exception $e) {
            error_log('Erreur confirmation paiement: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Traiter la file d'attente des emails
     */
    public function processEmailQueue() {
        try {
            // Récupérer les emails en attente
            $pendingEmails = $this->getPendingEmails();
            
            $processed = 0;
            $success = 0;
            $failed = 0;
            
            foreach ($pendingEmails as $email) {
                $processed++;
                
                try {
                    $result = $this->sendEmail(
                        $email['recipient_email'],
                        $email['subject'],
                        $email['content']
                    );
                    
                    if ($result) {
                        // Marquer comme envoyé
                        $this->markEmailAsSent($email['id']);
                        $success++;
                    } else {
                        // Marquer comme échec
                        $this->markEmailAsFailed($email['id'], 'Échec de l\'envoi');
                        $failed++;
                    }
                    
                } catch (Exception $e) {
                    $this->markEmailAsFailed($email['id'], $e->getMessage());
                    $failed++;
                }
                
                // Pause entre les envois pour éviter le spam
                usleep(100000); // 0.1 seconde
            }
            
            return [
                'success' => true,
                'processed' => $processed,
                'success_count' => $success,
                'failed_count' => $failed
            ];
            
        } catch (Exception $e) {
            error_log('Erreur traitement file email: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Nettoyer les anciens emails
     */
    public function cleanupOldEmails() {
        try {
            $deletedCount = $this->deleteOldEmails();
            
            return [
                'success' => true,
                'deleted_count' => $deletedCount
            ];
            
        } catch (Exception $e) {
            error_log('Erreur nettoyage emails: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtenir les statistiques des emails
     */
    public function getEmailStats() {
        try {
            $stats = $this->getEmailStatistics();
            
            return [
                'success' => true,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            error_log('Erreur statistiques emails: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Méthodes privées pour la gestion des données
     */
    
    private function getUserData($userId) {
        $result = $this->supabase->select('profiles', '*', ['id' => $userId]);
        return $result['success'] ? $result['data'][0] : null;
    }
    
    private function getUserByEmail($email) {
        $result = $this->supabase->select('profiles', '*', ['email' => $email]);
        return $result['success'] ? $result['data'][0] : null;
    }
    
    private function buildConfirmationUrl($token, $email) {
        return $this->config->get('site_url') . '/auth/email-confirmation.html?' . 
               http_build_query([
                   'token' => $token,
                   'type' => 'signup',
                   'email' => $email
               ]);
    }
    
    private function buildResetUrl($token, $email) {
        return $this->config->get('site_url') . '/auth/reset-password.html?' . 
               http_build_query([
                   'token' => $token,
                   'type' => 'recovery',
                   'email' => $email
               ]);
    }
    
    private function getPendingEmails() {
        $result = $this->supabase->select(
            'email_queue', 
            '*', 
            ['status' => 'pending'],
            'created_at ASC',
            50
        );
        return $result['success'] ? $result['data'] : [];
    }
    
    private function markEmailAsSent($emailId) {
        return $this->supabase->update('email_queue', 
            ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s')], 
            ['id' => $emailId]
        );
    }
    
    private function markEmailAsFailed($emailId, $errorMessage) {
        return $this->supabase->update('email_queue', 
            [
                'status' => 'failed', 
                'error_message' => $errorMessage,
                'attempts' => 'attempts + 1'
            ], 
            ['id' => $emailId]
        );
    }
    
    private function deleteOldEmails() {
        // Supprimer les emails envoyés de plus de 30 jours
        $result = $this->supabase->delete('email_queue', [
            'status' => 'sent',
            'sent_at' => '< ' . date('Y-m-d H:i:s', strtotime('-30 days'))
        ]);
        
        return $result['success'] ? count($result['data']) : 0;
    }
    
    private function getEmailStatistics() {
        $result = $this->supabase->executeQuery("
            SELECT 
                COUNT(*) as total_emails,
                COUNT(*) FILTER (WHERE status = 'pending') as pending_emails,
                COUNT(*) FILTER (WHERE status = 'sent') as sent_emails,
                COUNT(*) FILTER (WHERE status = 'failed') as failed_emails,
                CASE 
                    WHEN COUNT(*) > 0 THEN 
                        ROUND((COUNT(*) FILTER (WHERE status = 'sent')::NUMERIC / COUNT(*)::NUMERIC) * 100, 2)
                    ELSE 0 
                END as success_rate
            FROM email_queue
            WHERE created_at > NOW() - INTERVAL '30 days'
        ");
        
        return $result['success'] ? $result['data'][0] : [];
    }
    
    private function createNotification($userId, $type, $title, $message, $data = []) {
        return $this->supabase->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data)
        ]);
    }
    
    /**
     * Envoyer un email via le service configuré
     */
    private function sendEmail($to, $subject, $content) {
        $emailService = $this->config->get('email_service', 'smtp');
        
        switch ($emailService) {
            case 'smtp':
                return $this->sendEmailSMTP($to, $subject, $content);
            case 'sendgrid':
                return $this->sendEmailSendGrid($to, $subject, $content);
            case 'mailgun':
                return $this->sendEmailMailgun($to, $subject, $content);
            default:
                return $this->sendEmailSMTP($to, $subject, $content);
        }
    }
    
    /**
     * Envoyer un email via SMTP
     */
    private function sendEmailSMTP($to, $subject, $content) {
        try {
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $this->config->get('email_from_name') . ' <' . $this->config->get('email_from_address') . '>',
                'Reply-To: ' . $this->config->get('email_reply_to'),
                'X-Mailer: LivraisonP2P Email Service'
            ];
            
            $result = mail($to, $subject, $content, implode("\r\n", $headers));
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Erreur SMTP: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envoyer un email via SendGrid
     */
    private function sendEmailSendGrid($to, $subject, $content) {
        try {
            $apiKey = $this->config->get('sendgrid_api_key');
            if (!$apiKey) {
                throw new Exception('Clé API SendGrid non configurée');
            }
            
            $data = [
                'personalizations' => [
                    [
                        'to' => [['email' => $to]]
                    ]
                ],
                'from' => [
                    'email' => $this->config->get('email_from_address'),
                    'name' => $this->config->get('email_from_name')
                ],
                'subject' => $subject,
                'content' => [
                    [
                        'type' => 'text/html',
                        'value' => $content
                    ]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode >= 200 && $httpCode < 300;
            
        } catch (Exception $e) {
            error_log('Erreur SendGrid: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envoyer un email via Mailgun
     */
    private function sendEmailMailgun($to, $subject, $content) {
        try {
            $apiKey = $this->config->get('mailgun_api_key');
            $domain = $this->config->get('mailgun_domain');
            
            if (!$apiKey || !$domain) {
                throw new Exception('Configuration Mailgun incomplète');
            }
            
            $data = [
                'from' => $this->config->get('email_from_name') . ' <' . $this->config->get('email_from_address') . '>',
                'to' => $to,
                'subject' => $subject,
                'html' => $content
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/' . $domain . '/messages');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $apiKey);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode >= 200 && $httpCode < 300;
            
        } catch (Exception $e) {
            error_log('Erreur Mailgun: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Templates d'emails
     */
    
    private function getConfirmationEmailTemplate($userName, $confirmationUrl) {
        return $this->getEmailTemplate('confirmation', [
            'user_name' => $userName,
            'confirmation_url' => $confirmationUrl,
            'site_name' => 'LivraisonP2P',
            'site_url' => $this->config->get('site_url')
        ]);
    }
    
    private function getPasswordResetEmailTemplate($userName, $resetUrl) {
        return $this->getEmailTemplate('password_reset', [
            'user_name' => $userName,
            'reset_url' => $resetUrl,
            'site_name' => 'LivraisonP2P',
            'site_url' => $this->config->get('site_url')
        ]);
    }
    
    private function getDeliveryNotificationTemplate($userName, $deliveryData) {
        return $this->getEmailTemplate('delivery_notification', [
            'user_name' => $userName,
            'delivery' => $deliveryData,
            'site_name' => 'LivraisonP2P',
            'site_url' => $this->config->get('site_url')
        ]);
    }
    
    private function getPaymentConfirmationTemplate($userName, $paymentData) {
        return $this->getEmailTemplate('payment_confirmation', [
            'user_name' => $userName,
            'payment' => $paymentData,
            'site_name' => 'LivraisonP2P',
            'site_url' => $this->config->get('site_url')
        ]);
    }
    
    private function getEmailTemplate($template, $data) {
        $templateFile = __DIR__ . '/templates/' . $template . '.html';
        
        if (!file_exists($templateFile)) {
            // Template par défaut
            return $this->getDefaultEmailTemplate($template, $data);
        }
        
        $content = file_get_contents($templateFile);
        
        // Remplacer les variables
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }
    
    private function getDefaultEmailTemplate($template, $data) {
        $siteName = $data['site_name'] ?? 'LivraisonP2P';
        $siteUrl = $data['site_url'] ?? 'https://livraisonp2p.com';
        
        $baseTemplate = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>{{subject}}</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="color: #3B82F6;">{{site_name}}</h1>
                </div>
                <div style="background: #f8f9fa; padding: 30px; border-radius: 10px;">
                    {{content}}
                </div>
                <div style="text-align: center; margin-top: 30px; color: #666; font-size: 14px;">
                    <p>© 2024 {{site_name}}. Tous droits réservés.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $content = '';
        
        switch ($template) {
            case 'confirmation':
                $content = '
                <h2 style="color: #2d3748; margin-bottom: 20px;">Bonjour {{user_name}} !</h2>
                <p>Merci de vous être inscrit sur {{site_name}}. Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{confirmation_url}}" style="background: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Confirmer mon compte</a>
                </div>
                <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
                <p style="word-break: break-all; color: #3B82F6;">{{confirmation_url}}</p>
                <p>Ce lien expirera dans 24 heures.</p>';
                break;
                
            case 'password_reset':
                $content = '
                <h2 style="color: #2d3748; margin-bottom: 20px;">Bonjour {{user_name}} !</h2>
                <p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe :</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{reset_url}}" style="background: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Réinitialiser mon mot de passe</a>
                </div>
                <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
                <p style="word-break: break-all; color: #3B82F6;">{{reset_url}}</p>
                <p>Ce lien expirera dans 1 heure.</p>';
                break;
                
            default:
                $content = '<p>Email de {{site_name}}</p>';
        }
        
        $baseTemplate = str_replace('{{content}}', $content, $baseTemplate);
        
        foreach ($data as $key => $value) {
            $baseTemplate = str_replace('{{' . $key . '}}', $value, $baseTemplate);
        }
        
        return $baseTemplate;
    }
}

// API endpoints pour les emails
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailService = new EmailService();
    $action = $_POST['action'] ?? '';
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'send_confirmation':
            $userId = $_POST['user_id'] ?? '';
            $email = $_POST['email'] ?? '';
            $token = $_POST['token'] ?? '';
            
            if (!$userId || !$email || !$token) {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
                exit;
            }
            
            $result = $emailService->sendConfirmationEmail($userId, $email, $token);
            echo json_encode($result);
            break;
            
        case 'send_password_reset':
            $email = $_POST['email'] ?? '';
            $token = $_POST['token'] ?? '';
            
            if (!$email || !$token) {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
                exit;
            }
            
            $result = $emailService->sendPasswordResetEmail($email, $token);
            echo json_encode($result);
            break;
            
        case 'process_queue':
            $result = $emailService->processEmailQueue();
            echo json_encode($result);
            break;
            
        case 'cleanup':
            $result = $emailService->cleanupOldEmails();
            echo json_encode($result);
            break;
            
        case 'stats':
            $result = $emailService->getEmailStats();
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
    }
}
?> 