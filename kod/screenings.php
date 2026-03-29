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
        ?>   
    </style>
    <link rel="stylesheet" href="css/screenings.css">
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