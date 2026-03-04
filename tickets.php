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

// Oldal specifikus CSS - a színpalettából
$additional_css = '
    .page-header {
        background: linear-gradient(135deg, #380A0A, #6C0808);
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 10px;
        text-align: center;
    }
    
    .tickets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .ticket-card {
        background: linear-gradient(135deg, #F9F9F9, #F5F5F5);
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #F57272;
        box-shadow: 0 8px 20px rgba(135, 47, 47, 0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }
    
    .ticket-card:hover {
        transform: translateY(-8px);
        border-color: #EA3232;
        box-shadow: 0 15px 30px rgba(210, 58, 58, 0.25);
    }
    
    .ticket-header {
        display: flex;
        gap: 1.5rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #380A0A, #6C0808);
        color: white;
        border-bottom: 3px solid #FF6B6B;
    }
    
    .ticket-poster-container {
        position: relative;
        min-width: 100px;
    }
    
    .ticket-poster {
        width: 100px;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #FF6B6B;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    .ticket-id-badge {
        position: absolute;
        top: -10px;
        left: -10px;
        background: linear-gradient(135deg, #FF6B6B, #D23A3A);
        color: white;
        padding: 0.5rem 0.8rem;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.9rem;
        box-shadow: 0 4px 8px rgba(210, 58, 58, 0.3);
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .ticket-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .ticket-title {
        font-family: "Poppins", sans-serif;
        font-size: 1.4rem;
        color: white;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .ticket-details {
        padding: 1.5rem;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.8rem;
        padding-bottom: 0.8rem;
        border-bottom: 1px solid #D49E9E;
    }
    
    .detail-label {
        color: #380A0A;
        font-weight: 600;
    }
    
    .detail-value {
        color: #000000;
        font-weight: 600;
    }
    
    .detail-value i {
        color: #FF6B6B;
        margin-right: 5px;
    }
    
    .price-value {
        color: #872F2F !important;
        font-size: 1.1rem;
        font-weight: 700;
    }
    
    .status-active {
        color: #2ecc71 !important;
        font-weight: 600;
        background: rgba(46, 204, 113, 0.1);
        padding: 0.3rem 0.8rem;
        border-radius: 4px;
        display: inline-block;
    }
    
    .status-used {
        color: #3498db !important;
        font-weight: 600;
        background: rgba(52, 152, 219, 0.1);
        padding: 0.3rem 0.8rem;
        border-radius: 4px;
        display: inline-block;
    }
    
    .status-cancelled {
        color: #e74c3c !important;
        font-weight: 600;
        background: rgba(231, 76, 60, 0.1);
        padding: 0.3rem 0.8rem;
        border-radius: 4px;
        display: inline-block;
    }
    
    .status-expired {
        color: #95a5a6 !important;
        font-weight: 600;
        background: rgba(149, 165, 166, 0.1);
        padding: 0.3rem 0.8rem;
        border-radius: 4px;
        display: inline-block;
    }
    
    .no-tickets {
        background: linear-gradient(135deg, #F9F9F9, #F5F5F5);
        border-radius: 10px;
        padding: 4rem 2rem;
        text-align: center;
        color: #6C0808;
        border: 1px solid #F57272;
        box-shadow: 0 8px 20px rgba(135, 47, 47, 0.15);
    }
    
    .no-tickets i {
        font-size: 4rem;
        color: #FF6B6B;
        margin-bottom: 1.5rem;
    }
    
    .no-tickets h3 {
        color: #380A0A;
        margin-bottom: 0.5rem;
        font-family: "Poppins", sans-serif;
    }
    
    /* Reszponzív design */
    @media (max-width: 768px) {
        .tickets-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .ticket-header {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }
        
        .ticket-poster-container {
            align-self: center;
        }
    }
    
    @media (max-width: 480px) {
        .tickets-grid {
            grid-template-columns: 1fr;
        }
        
        .ticket-title {
            font-size: 1.2rem;
        }
        
        .ticket-header {
            padding: 1rem;
        }
        
        .ticket-poster {
            width: 90px;
            height: 135px;
        }
    }
';
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
        // Globális CSS betöltése
        if (file_exists('style.css')) {
            include 'style.css';
        }
        
        // Oldal specifikus CSS hozzáadása
        echo $additional_css; 
        ?>
        
        .main-content {
            padding: 2rem 0;
        }
    </style>
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
                            // Itt frissíthetnénk az adatbázisban is, de most csak a megjelenítéshez állítjuk
                            $ticket['status'] = 'expired';
                        }
                    ?>
                    <div class="ticket-card">
                        <div class="ticket-header">
                            <div class="ticket-poster-container">
                                <span class="ticket-id-badge">
                                    <i class="fas fa-ticket-alt"></i> #MOZI<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?>
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
                                        'expired' => 'Lejárt'
                                    ];
                                    echo $status_text[$ticket['status']] ?? $ticket['status'];
                                    ?>
                                </span>
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
</body>
</html>