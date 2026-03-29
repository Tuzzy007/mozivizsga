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