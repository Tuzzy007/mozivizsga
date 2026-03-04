<?php
require_once 'config.php';
$page_title = "Vetítések";

// Film alapú szűrés
$movie_id = isset($_GET['movie']) ? intval($_GET['movie']) : 0;

// Keresés paraméterek - ALAPÉRTELMEZETTEN ÜRES DÁTUM
$search_date = isset($_GET['date']) ? $_GET['date'] : '';
$search_movie = isset($_GET['search_movie']) ? trim($_GET['search_movie']) : '';

// Alap SQL lekérdezés
$sql = "SELECT s.*, m.title as movie_title, m.duration, m.poster_url, m.genre 
        FROM screenings s 
        JOIN movies m ON s.movie_id = m.id 
        WHERE s.screening_date >= CURDATE()";

$params = [];

// Film alapú szűrés
if($movie_id > 0) {
    $sql .= " AND s.movie_id = ?";
    $params[] = $movie_id;
}

// Dátum szerinti szűrés - CSAK HA NEM ÜRES
if(!empty($search_date)) {
    $sql .= " AND s.screening_date = ?";
    $params[] = $search_date;
}

// Film cím szerinti keresés
if(!empty($search_movie)) {
    $sql .= " AND m.title LIKE ?";
    $params[] = "%$search_movie%";
}

$sql .= " ORDER BY s.screening_date, s.screening_time";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$screenings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Film információk ha film alapú szűrés van
$movie_info = null;
if($movie_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$movie_id]);
    $movie_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Oldal specifikus CSS - a színpalettából
$additional_css = '
    .hero {
        background: linear-gradient(135deg, #D23A3A, #EA3232, #FF6B6B);
        background-size: cover;
        background-position: center;
        color: white;
        padding: 4rem 0;
        text-align: center;
        border-radius: 10px;
        margin-bottom: 3rem;
        box-shadow: 0 8px 25px rgba(210, 58, 58, 0.3);
    }
    
    .hero h1 {
        font-family: "Poppins", sans-serif;
        font-size: 3rem;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 8px rgba(56, 10, 10, 0.5);
    }
    
    .hero p {
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto 2rem;
        color: #F9F9F9;
    }
    
    .filters {
        background: linear-gradient(135deg, #F9F9F9, #F5F5F5);
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 3rem;
        border: 1px solid #F57272;
        box-shadow: 0 8px 20px rgba(135, 47, 47, 0.15);
    }
    
    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        align-items: end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-group label {
        margin-bottom: 0.5rem;
        color: #380A0A;
        font-weight: 600;
        font-family: "Poppins", sans-serif;
    }
    
    .filter-group select,
    .filter-group input {
        padding: 0.8rem;
        border: 1px solid #D49E9E;
        border-radius: 6px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: white;
    }
    
    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: #FF6B6B;
        box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
    }
    
    .screenings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .screening-card {
        background: linear-gradient(135deg, #F9F9F9, #F5F5F5);
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #F57272;
        box-shadow: 0 8px 20px rgba(135, 47, 47, 0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }
    
    .screening-card:hover {
        transform: translateY(-8px);
        border-color: #EA3232;
        box-shadow: 0 15px 30px rgba(210, 58, 58, 0.25);
    }
    
    .screening-header {
        display: flex;
        gap: 1.5rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #380A0A, #6C0808);
        color: white;
        border-bottom: 3px solid #FF6B6B;
    }
    
    .screening-poster-container {
        position: relative;
        min-width: 100px;
    }
    
    .screening-poster {
        width: 100px;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #FF6B6B;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    .screening-date-badge {
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
    
    .screening-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .screening-title {
        font-family: "Poppins", sans-serif;
        font-size: 1.4rem;
        color: white;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .screening-meta {
        color: #F57272;
        font-size: 0.9rem;
        margin-bottom: 0.8rem;
    }
    
    .screening-time {
        font-size: 1.2rem;
        color: white;
        font-weight: bold;
        background: rgba(234, 50, 50, 0.2);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        display: inline-block;
        margin-top: 0.5rem;
    }
    
    .screening-details {
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
        color: #000000 !important;
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
        background: rgba(46, 204, 113, 0.1);
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
    }
    
    .status-warning {
        color: #e67e22 !important;
        background: rgba(230, 126, 34, 0.1);
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
    }
    
    .status-expired {
        color: #95a5a6 !important;
        background: rgba(149, 165, 166, 0.1);
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
    }
    
    .no-screenings {
        background: linear-gradient(135deg, #F9F9F9, #F5F5F5);
        border-radius: 10px;
        padding: 4rem 2rem;
        text-align: center;
        color: #6C0808;
        border: 1px solid #F57272;
        box-shadow: 0 8px 20px rgba(135, 47, 47, 0.15);
    }
    
    .no-screenings i {
        font-size: 4rem;
        color: #FF6B6B;
        margin-bottom: 1.5rem;
    }
    
    .no-screenings h3 {
        color: #380A0A;
        margin-bottom: 0.5rem;
        font-family: "Poppins", sans-serif;
    }
    
    .movie-highlight {
        background: linear-gradient(135deg, #6C0808, #380A0A);
        color: white;
        border-radius: 10px;
        padding: 2rem;
        margin-bottom: 3rem;
        display: flex;
        gap: 2rem;
        align-items: center;
        box-shadow: 0 8px 25px rgba(108, 8, 8, 0.3);
        border: 2px solid #EA3232;
    }
    
    .movie-highlight-poster {
        width: 120px;
        height: 180px;
        object-fit: cover;
        border-radius: 8px;
        border: 3px solid #FF6B6B;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .movie-highlight h2 {
        font-family: "Poppins", sans-serif;
        color: white;
        margin-bottom: 0.5rem;
        font-size: 1.8rem;
    }
    
    .movie-highlight p {
        color: #F57272;
        margin-bottom: 1.5rem;
    }
    
    .screening-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }
    
    .screening-actions .btn {
        flex: 1;
        text-align: center;
        padding: 0.7rem 0.5rem;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 100%;
    }
    
    .screening-actions .btn-primary {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 100%;
    }
    
    .screening-actions .btn-secondary {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 100%;
    }
    
    .results-count {
        color: #872F2F;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding: 0.8rem 1.5rem;
        background: rgba(255, 107, 107, 0.1);
        border-radius: 6px;
        display: inline-block;
        font-family: "Poppins", sans-serif;
        border-left: 4px solid #D23A3A;
    }
    
    /* Reszponzív design */
    @media (max-width: 768px) {
        .hero h1 {
            font-size: 2.2rem;
        }
        
        .hero {
            padding: 3rem 0;
        }
        
        .screenings-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .screening-header {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }
        
        .screening-poster-container {
            align-self: center;
        }
        
        .movie-highlight {
            flex-direction: column;
            text-align: center;
            padding: 1.5rem;
        }
        
        .filter-form {
            grid-template-columns: 1fr;
        }
        
        .screening-actions {
            flex-direction: column;
        }
    }
    
    @media (max-width: 480px) {
        .hero h1 {
            font-size: 1.8rem;
        }
        
        .screenings-grid {
            grid-template-columns: 1fr;
        }
        
        .screening-title {
            font-size: 1.2rem;
        }
        
        .screening-header {
            padding: 1rem;
        }
        
        .screening-poster {
            width: 90px;
            height: 135px;
        }
    }
    
    /* Stílus az üres dátum inputhoz */
    .filter-group input[type="date"]:invalid {
        color: #6c757d;
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
        
        /* Extra stílusok */
        .main-content {
            padding: 2rem 0;
        }
        
        .page-header {
            background: linear-gradient(135deg, #380A0A, #6C0808);
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1 class="section-title">
                <?php echo $movie_info ? htmlspecialchars($movie_info['title']) . ' vetítései' : 'Vetítések'; ?>
            </h1>
        </div>
        
        <main class="main-content">
            <?php if($movie_info): ?>
                <div class="movie-highlight">
                    <img src="<?php echo htmlspecialchars($movie_info['poster_url']); ?>" 
                         alt="<?php echo htmlspecialchars($movie_info['title']); ?>" 
                         class="movie-highlight-poster"
                         onerror="this.src='https://via.placeholder.com/120x180/ecf0f1/2c3e50?text=<?php echo urlencode($movie_info['title']); ?>'">
                    <div>
                        <h2><?php echo htmlspecialchars($movie_info['title']); ?></h2>
                        <p>Vetítések a filmhez</p>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="movies.php" class="btn btn-secondary">Összes film</a>
                            <a href="movie.php?id=<?php echo $movie_info['id']; ?>" class="btn btn-primary">Film részletei</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="filters">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-group">
                        <label for="search_movie">Film keresése</label>
                        <input type="text" id="search_movie" name="search_movie" placeholder="Film címe..." value="<?php echo htmlspecialchars($search_movie); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date">Dátum szűrés</label>
                        <input type="date" id="date" name="date" 
                               value="<?php echo htmlspecialchars($search_date); ?>" 
                               min="<?php echo date('Y-m-d'); ?>"
                               placeholder="Válassz dátumot">
                    </div>
                    
                    <?php if($movie_id): ?>
                        <input type="hidden" name="movie" value="<?php echo $movie_id; ?>">
                    <?php endif; ?>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary" style="display: flex; text-align: center;">Szűrés</button>
                        <a href="screenings.php<?php echo $movie_id ? '?movie=' . $movie_id : ''; ?>" class="btn btn-secondary" style="margin-top: 0.5rem; display: block; text-align: center; color:black;">Szűrők törlése</a>
                    </div>
                </form>
            </div>
            
            <?php if(count($screenings) > 0): ?>
                <div class="results-count">
                    <?php echo count($screenings); ?> vetítés található
                    <?php if(!empty($search_movie)): ?>
                        a "<?php echo htmlspecialchars($search_movie); ?>" filmhez
                    <?php endif; ?>
                    <?php if(!empty($search_date)): ?>
                        <span style="background: rgba(210, 58, 58, 0.2); padding: 0.2rem 0.5rem; border-radius: 4px; margin-left: 0.3rem;">
                            <i class="fas fa-calendar-alt" style="color: #D23A3A;"></i> <?php echo htmlspecialchars($search_date); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="screenings-grid">
                    <?php foreach($screenings as $screening): 
                        // Vetítés állapotának meghatározása
                        $screening_datetime = $screening['screening_date'] . ' ' . $screening['screening_time'];
                        $screening_timestamp = strtotime($screening_datetime);
                        $current_timestamp = time();
                        $minutes_until = round(($screening_timestamp - $current_timestamp) / 60);

                        if($minutes_until < 0) {
                            $screening_status = 'expired';
                            $status_text = 'Lejárt';
                            $status_class = 'status-expired';
                            $booking_disabled = true;
                        } elseif($minutes_until < 15) {
                            $screening_status = 'closing';
                            $status_text = 'Utolsó 15 perc';
                            $status_class = 'status-warning';
                            $booking_disabled = false;
                        } else {
                            $screening_status = 'active';
                            $status_text = 'Foglalható';
                            $status_class = 'status-active';
                            $booking_disabled = false;
                        }
                    ?>
                    <div class="screening-card">
                        <div class="screening-header">
                            <div class="screening-poster-container">
                                <span class="screening-date-badge">
                                    <i class="fas fa-calendar-day"></i> <?php echo date('m.d', strtotime($screening['screening_date'])); ?>
                                </span>
                                <img src="<?php echo htmlspecialchars($screening['poster_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($screening['movie_title']); ?>" 
                                     class="screening-poster"
                                     onerror="this.src='https://via.placeholder.com/100x150/ecf0f1/2c3e50?text=<?php echo urlencode($screening['movie_title']); ?>'">
                            </div>
                            <div class="screening-info">
                                <h3 class="screening-title"><?php echo htmlspecialchars($screening['movie_title']); ?></h3>
                                <div class="screening-meta">
                                    <span><?php echo htmlspecialchars($screening['genre']); ?></span> • 
                                    <span><?php echo $screening['duration']; ?> perc</span>
                                </div>
                                <div class="screening-time">
                                    <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($screening['screening_time'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="screening-details">
                            <div class="detail-row">
                                <span class="detail-label">Teljes dátum:</span>
                                <span class="detail-value">
                                    <i class="fas fa-calendar-alt"></i> <?php echo date('Y.m.d.', strtotime($screening['screening_date'])); ?>
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Terem száma:</span>
                                <span class="detail-value">
                                    <i class="fas fa-door-closed"></i> <?php echo $screening['hall_number']; ?>
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Jegyár:</span>
                                <span class="detail-value price-value">
                                    <i class="fas fa-ticket-alt"></i> <?php echo number_format($screening['price'], 0, ',', ' '); ?> Ft
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Szabad helyek:</span>
                                <span class="detail-value">
                                    <i class="fas fa-chair"></i> <?php echo $screening['available_seats']; ?> db
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Állapot:</span>
                                <span class="detail-value <?php echo $status_class; ?>">
                                    <i class="fas fa-clock"></i> <?php echo $status_text; ?>
                                    <?php if($screening_status == 'closing'): ?>
                                        (Még <?php echo $minutes_until; ?> perc)
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="screening-actions">
                                <a href="movie.php?id=<?php echo $screening['movie_id']; ?>" class="btn btn-secondary" style="color: black;">Film részletei</a>
                                <?php if($screening_status != 'expired'): ?>
                                    <a href="booking.php?screening=<?php echo $screening['id']; ?>" class="btn btn-primary">Jegyfoglalás</a>
                                <?php else: ?>
                                    <button class="btn btn-primary" style="opacity: 0.5; cursor: not-allowed;" disabled>Lejárt</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-screenings">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Nincs találat</h3>
                    <p>A keresési feltételeknek megfelelő vetítés nem található.</p>
                    <a href="screenings.php<?php echo $movie_id ? '?movie=' . $movie_id : ''; ?>" class="btn btn-primary" style="margin-top: 1.5rem;">Összes vetítés</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>