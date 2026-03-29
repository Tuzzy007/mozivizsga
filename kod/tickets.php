<?php
require_once 'config.php';
$page_title = "Jegyek";

// Csak bejelentkezett felhasználó láthatja a jegyeket
if(!$current_user) {
    header("Location: login.php");
    exit();
}

// Felhasználó jegyének lekérdezése
$stmt = $pdo->prepare("
    SELECT t.*, m.title as movie_title, m.poster_url,
           s.screening_date, s.screening_time, s.hall_number,
           CONCAT(s.screening_date, ' ', s.screening_time) as screening_datetime
    FROM tickets t
    JOIN screenings s ON t.screening_id = s.id
    JOIN movies m ON s.movie_id = m.id
    WHERE t.user_id = ?
    ORDER BY t.purchase_date DESC
");
$stmt->execute([$current_user['id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php
        if (file_exists('style.css')) {
            include 'style.css';
        }
        ?>
    </style>
    <link rel="stylesheet" href="css/tickets.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1 class="section-title">Jegyek</h1>
        </div>
        
        <main class="main-content">
            <?php if(count($tickets) > 0): ?>
                <div class="tickets-grid">
                    <?php foreach($tickets as $ticket):
                        // Ellenőrizzük, hogy lejárt-e a vetítés
                        $current_timestamp = time();
                        $screening_timestamp = strtotime($ticket['screening_datetime']);
                        
                        // Ha aktív a jegy, de a vetítés már elkezdődött, frissítsük a státuszt
                        if($ticket['status'] == 'active' && $screening_timestamp < $current_timestamp) {
                            $ticket['status'] = 'expired';
                        }
                        
                        // Egyedi jegy azonosító a nyomtatáshoz
                        $ticket_qr_code = 'MOZI' . str_pad($ticket['id'], 8, '0', STR_PAD_LEFT);
                    ?>
                    <div class="ticket-card" id="ticket-<?php echo $ticket['id']; ?>">
                        <div class="ticket-header">
                            <div class="ticket-poster-container">
                                <span class="ticket-id-badge">
                                    <i class="fas fa-ticket-alt"></i> #<?php echo $ticket_qr_code; ?>
                                </span>
                                <img src="<?php echo htmlspecialchars($ticket['poster_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($ticket['movie_title']); ?>" 
                                     class="ticket-poster"
                                     onerror="this.src='https://via.placeholder.com/100x150/ecf0f1/2c3e50?text=<?php echo urlencode($ticket['movie_title']); ?>'">
                            </div>
                            <div class="ticket-info">
                                <h3 class="ticket-title"><?php echo htmlspecialchars($ticket['movie_title']); ?></h3>
                            </div>
                        </div>
                        
                        <div class="ticket-details">
                            <div class="detail-row">
                                <span class="detail-label">Vetítés dátuma:</span>
                                <span class="detail-value">
                                    <i class="fas fa-calendar-alt"></i> <?php echo date('Y.m.d.', strtotime($ticket['screening_date'])); ?>
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Vetítés ideje:</span>
                                <span class="detail-value">
                                    <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($ticket['screening_time'])); ?>
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Terem:</span>
                                <span class="detail-value">
                                    <i class="fas fa-door-closed"></i> <?php echo $ticket['hall_number']; ?>
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Helyszám:</span>
                                <span class="detail-value">
                                    <i class="fas fa-chair"></i> <?php echo $ticket['seat_number']; ?>
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Fizetett összeg:</span>
                                <span class="detail-value price-value">
                                    <i class="fas fa-ticket-alt"></i> <?php echo number_format($ticket['price_paid'], 0, ',', ' '); ?> Ft
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Vásárlás dátuma:</span>
                                <span class="detail-value">
                                    <i class="fas fa-shopping-cart"></i> <?php echo date('Y.m.d. H:i', strtotime($ticket['purchase_date'])); ?>
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Státusz:</span>
                                <span class="detail-value status-<?php echo $ticket['status']; ?>">
                                    <?php 
                                    $status_text = [
                                        'active' => 'Aktív',
                                        'used' => 'Felhasznált',
                                        'cancelled' => 'Törölt',
                                        'expired' => 'Lejárt',
                                        'cash_pending' => 'Fizetésre vár (helyszínen)'
                                    ];
                                    echo $status_text[$ticket['status']] ?? $ticket['status'];
                                    ?>
                                </span>
                            </div>
                            
                            <!-- QR kód / Vonalkód szerű azonosító -->
                            <div class="ticket-qr no-print">
                                <i class="fas fa-qrcode"></i> 
                                Jegyazonosító: <strong><?php echo $ticket_qr_code; ?></strong>
                            </div>
                            
                            <!-- Nyomtatás gomb -->
                            <div class="ticket-actions no-print">
                                <button onclick="printTicket(<?php echo $ticket['id']; ?>)" class="btn-print-ticket">
                                    <i class="fas fa-print"></i> Jegy nyomtatása
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-tickets">
                    <i class="fas fa-ticket-alt"></i>
                    <h3>Nincs jegye</h3>
                    <p>Még nem vásárolt jegyet. Vásároljon jegyet egy vetítéshez!</p>
                    <a href="screenings.php" class="btn btn-primary" style="margin-top: 1rem;">Vetítések böngészése</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script src="js/tickets.js"></script>
</body>
</html>