<?php if(!isset($no_footer)): ?>
    </div> <!-- container zárása, ha szükséges -->
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Rólunk</h3>
                    <p>A <?php echo APP_NAME; ?> a legjobb filmélményt kínálja kiváló vetítésekkel és kényelmes foglalási rendszerrel.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Gyors linkek</h3>
                    <ul>
                        <li><a href="index.php">Főoldal</a></li>
                        <li><a href="movies.php">Filmek</a></li>
                        <li><a href="screenings.php">Vetítések</a></li>
                        <li><a href="tickets.php">Jegyek</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Kapcsolat</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> 1234 Mátészalka, Mozi utca 1.</li>
                        <li><i class="fas fa-phone"></i> +36 1 234 5678</li>
                        <li><i class="fas fa-envelope"></i> info@szalkacinema.hu</li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Minden jog fenntartva</p>
            </div>
        </div>
    </footer>
    
    <?php if(isset($additional_js)): ?>
        <script><?php echo $additional_js; ?></script>
    <?php endif; ?>
    
    </body>
    </html>
<?php endif; ?>