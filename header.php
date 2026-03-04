<?php
// A header fájl csak akkor jelenjen meg, ha nincs tiltva
if(!isset($no_header)):
// Jelenlegi oldal meghatározása
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo isset($page_title) ? $page_title : 'Főoldal'; ?></title>
    
    <!-- Favicon - Alap -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Favicon - Modern böngészők -->
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    
    <!-- Apple Touch Icon (iOS) -->
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    
    <!-- Android Chrome -->
    <link rel="manifest" href="site.webmanifest">
    
    <!-- Szín a böngésző címsorához (Android) -->
    <meta name="theme-color" content="#380A0A">
    <meta name="msapplication-TileColor" content="#380A0A">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <?php if(isset($additional_css)): ?>
        <style><?php echo $additional_css; ?></style>
    <?php endif; ?>
</head>
<body>

    <header>
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <a href="index.php" class="logo">
                        <i class="fas fa-film"></i> 
                        <span><?php echo APP_NAME; ?></span>
                    </a>
                    
                    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Menü">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <nav id="mainNav">
                    <ul>
                        <li><a href="index.php" <?php echo $current_page == 'index.php' ? 'class="active"' : ''; ?>>Főoldal</a></li>
                        <li><a href="movies.php" <?php echo $current_page == 'movies.php' ? 'class="active"' : ''; ?>>Filmek</a></li>
                        <li><a href="screenings.php" <?php echo $current_page == 'screenings.php' ? 'class="active"' : ''; ?>>Vetítések</a></li>
                        <li><a href="tickets.php" <?php echo $current_page == 'tickets.php' ? 'class="active"' : ''; ?>>Jegyek</a></li>
                    </ul>
                </nav>
                
                <div class="header-right">
                    <a href="https://drive.google.com/drive/folders/1OqzHKzcg6bzjmK5NPPwGTGHwkmdmvD3C?usp=sharing" target="_blank" class="doc-btn" aria-label="Dokumentációk">
                        <i class="fas fa-file-alt"></i>
                        <span>Dokumentációk</span>
                    </a>
                    
                    <div class="user-menu">
                        <?php if($current_user): ?>
                            <div class="user-dropdown">
                                <button class="user-btn" id="userDropdownBtn">
                                    <i class="fas fa-user-circle"></i>
                                    <span class="user-name"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="dropdown-menu" id="userDropdownMenu">
                                    <?php if($current_user['role'] == 'admin'): ?>
                                    <a href="admin.php"><i class="fas fa-cog"></i> Admin</a>
                                    <?php endif; ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Kijelentkezés</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline">Bejelentkezés</a>
                            <a href="register.php" class="btn btn-primary">Regisztráció</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- JavaScript a reszponzív menühöz és dropdown-hoz -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobil menü toggle
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const mainNav = document.getElementById('mainNav');
        
        if (mobileToggle && mainNav) {
            mobileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                mainNav.classList.toggle('active');
                // Ikon váltás
                const icon = this.querySelector('i');
                if (icon.classList.contains('fa-bars')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        }
        
        // User dropdown
        const userBtn = document.getElementById('userDropdownBtn');
        const userMenu = document.getElementById('userDropdownMenu');
        
        if (userBtn && userMenu) {
            userBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.classList.toggle('show');
            });
            
            // Kattintás a dokumentumra bezárja a dropdownot
            document.addEventListener('click', function() {
                if (userMenu.classList.contains('show')) {
                    userMenu.classList.remove('show');
                }
            });
            
            // Ne zárja be, ha a dropdown-ra kattintunk
            userMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Kattintás a dokumentumra - ha a menü nyitva van és kívül kattintunk, zárjuk be
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('mainNav');
            const toggle = document.getElementById('mobileMenuToggle');
            
            if (nav && toggle && nav.classList.contains('active')) {
                // Ha nem a menüre és nem a toggle gombra kattintottunk
                if (!nav.contains(event.target) && !toggle.contains(event.target)) {
                    nav.classList.remove('active');
                    // Visszaállítjuk a hamburger ikont
                    const icon = toggle.querySelector('i');
                    if (icon && icon.classList.contains('fa-times')) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            }
        });

        // Menü bezárása linkre kattintáskor (mobil nézetben)
        const navLinks = document.querySelectorAll('nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    const nav = document.getElementById('mainNav');
                    const toggle = document.getElementById('mobileMenuToggle');
                    if (nav && nav.classList.contains('active')) {
                        nav.classList.remove('active');
                        const icon = toggle.querySelector('i');
                        if (icon && icon.classList.contains('fa-times')) {
                            icon.classList.remove('fa-times');
                            icon.classList.add('fa-bars');
                        }
                    }
                }
            });
        });
        
        // Reszponzív viselkedés - ablak átméretezéskor
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && mainNav && mainNav.classList.contains('active')) {
                mainNav.classList.remove('active');
                // Visszaállítjuk a hamburger ikont
                const icon = mobileToggle?.querySelector('i');
                if (icon && icon.classList.contains('fa-times')) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    });
    </script>

<?php endif; ?>