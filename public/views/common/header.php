<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'LivraisonP2P - Livraison entre particuliers'; ?></title>
    
    <!-- Meta tags SEO -->
    <meta name="description" content="Plateforme de livraison entre particuliers. Connectez expéditeurs et livreurs pour des livraisons sécurisées et rapides.">
    <meta name="keywords" content="livraison, particuliers, transport, colis, expédition">
    <meta name="author" content="LivraisonP2P">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Supabase JS -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    
    <!-- Configuration Supabase pour JavaScript -->
    <script>
        // Configuration Supabase (à remplacer par les vraies clés)
        const SUPABASE_URL = '<?php echo $_ENV['SUPABASE_URL'] ?? ''; ?>';
        const SUPABASE_ANON_KEY = '<?php echo $_ENV['SUPABASE_ANON_KEY'] ?? ''; ?>';
        
        // Initialiser le client Supabase
        const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
        
        // Variables globales pour l'utilisateur connecté
        const currentUser = {
            id: '<?php echo $_SESSION['user_id'] ?? ''; ?>',
            role: '<?php echo $_SESSION['user_role'] ?? ''; ?>',
            name: '<?php echo $_SESSION['user_name'] ?? ''; ?>'
        };
    </script>
</head>
<body>
    <!-- Navigation principale -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <!-- Logo/Brand -->
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-truck me-2"></i>
                LivraisonP2P
            </a>
            
            <!-- Bouton toggle pour mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Menu de navigation -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home me-1"></i>
                            Accueil
                        </a>
                    </li>
                    
                    <?php if ($is_logged_in): ?>
                        <!-- Menu pour utilisateurs connectés -->
                        <?php if ($user_role === 'expeditor'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/expeditor/dashboard">
                                    <i class="fas fa-tachometer-alt me-1"></i>
                                    Tableau de bord
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/expeditor/create-ad">
                                    <i class="fas fa-plus me-1"></i>
                                    Nouvelle annonce
                                </a>
                            </li>
                        <?php elseif ($user_role === 'courier'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/courier/dashboard">
                                    <i class="fas fa-tachometer-alt me-1"></i>
                                    Tableau de bord
                                </a>
                            </li>
                        <?php elseif ($user_role === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/dashboard">
                                    <i class="fas fa-cogs me-1"></i>
                                    Administration
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <!-- Menu utilisateur -->
                <ul class="navbar-nav">
                    <?php if ($is_logged_in): ?>
                        <!-- Notifications -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="badge bg-danger notification-badge" id="notificationCount" style="display: none;">0</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" id="notificationsList">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="#" id="noNotifications">Aucune notification</a></li>
                            </ul>
                        </li>
                        
                        <!-- Profil utilisateur -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><h6 class="dropdown-header">Mon compte</h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/profile">
                                    <i class="fas fa-user me-2"></i>Profil
                                </a></li>
                                <li><a class="dropdown-item" href="/settings">
                                    <i class="fas fa-cog me-2"></i>Paramètres
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="logout()">
                                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Menu pour visiteurs -->
                        <li class="nav-item">
                            <a class="nav-link" href="/login">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                Connexion
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light btn-sm ms-2" href="/register">
                                <i class="fas fa-user-plus me-1"></i>
                                Inscription
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Espace pour la navbar fixed -->
    <div style="height: 76px;"></div>
    
    <!-- Container principal -->
    <main class="container-fluid py-4"> 