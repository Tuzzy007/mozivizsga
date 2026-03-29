<?php
require_once 'config.php';
$page_title = "Főoldal - Heti műsor";

// Heti műsor generálása (7 napra előre)
generateWeeklyScreenings($pdo);

// Heti műsor lekérése
$weekly_schedule_stmt = $pdo->prepare("
    SELECT s.*, m.title as movie_title, m.poster_url, m.duration, m.genre, m.rating
    FROM screenings s 
    JOIN movies m ON s.movie_id = m.id 
    WHERE s.screening_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
    ORDER BY s.screening_date, s.screening_time
");
$weekly_schedule_stmt->execute();
$weekly_schedule = $weekly_schedule_stmt->fetchAll(PDO::FETCH_ASSOC);

// Dátumok szerint csoportosítás
$schedule_by_date = [];
foreach ($weekly_schedule as $screening) {
    $date = $screening['screening_date'];
    if (!isset($schedule_by_date[$date])) {
        $schedule_by_date[$date] = [];
    }
    $schedule_by_date[$date][] = $screening;
}

// Magyar napnevek
$hungarian_days = [
    'Monday' => 'Hétfő',
    'Tuesday' => 'Kedd',
    'Wednesday' => 'Szerda',
    'Thursday' => 'Csütörtök',
    'Friday' => 'Péntek',
    'Saturday' => 'Szombat',
    'Sunday' => 'Vasárnap'
];

// Magyar hónapnevek
$hungarian_months = [
    'January' => 'Január',
    'February' => 'Február',
    'March' => 'Március',
    'April' => 'Április',
    'May' => 'Május',
    'June' => 'Június',
    'July' => 'Július',
    'August' => 'Augusztus',
    'September' => 'Szeptember',
    'October' => 'Október',
    'November' => 'November',
    'December' => 'December'
];

// Következő 7 nap dátumai magyar nevekkel
$next_7_days = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $english_day = date('l', strtotime($date));
    $english_month = date('F', strtotime($date));
    $day_number = date('d', strtotime($date));
    
    $next_7_days[] = [
        'date' => $date,
        'day_name' => $hungarian_days[$english_day],
        'day_number' => $day_number,
        'month_name' => $hungarian_months[$english_month],
        'is_today' => ($i == 0),
        'is_tomorrow' => ($i == 1),
        'full_date' => $day_number . '. ' . $hungarian_months[$english_month]
    ];
}
// Oldal tartalma
ob_start();
?>
<head>
<style>
    <?php include 'style.css'; ?>
    </style>
    <link rel="stylesheet" href="css/index.css">
</head>
<div class="container">
    <main class="main-content">
        
        <!-- HERO - EGYSZERŰ, TISZTA, PIROS -->
        <section class="hero">
            <h1>SZALKA<span>CINEMA</span></h1>
            <div>
                <a href="screenings.php" class="btn btn-primary">
                    <i class="fas fa-ticket-alt"></i> Jegyfoglalás most
                </a>
            </div>
        </section>
        
        <!-- HETI MŰSOR - NAPOKRA BONTVA -->
        <section>
            <h2 class="section-title">
                <i class="fas fa-calendar-week"></i> 
                Válassz napot
            </h2>
            
            <!-- DÁTUM FÜLEK -->
            <div class="date-tabs" id="dateTabs">
                <?php foreach($next_7_days as $day): ?>
                    <div class="date-tab <?php echo $day['is_today'] ? 'active' : ''; ?>" 
                         onclick="showDay('day-<?php echo $day['date']; ?>', this)">
                        <span class="day-name"><?php echo $day['day_name']; ?></span>
                        <span class="day-number"><?php echo $day['day_number']; ?></span>
                        <span class="day-month"><?php echo $day['month_name']; ?></span>
                        <?php if($day['is_today']): ?>
                            <span class="today-badge">MA</span>
                        <?php elseif($day['is_tomorrow']): ?>
                            <span class="tomorrow-badge">HOLNAP</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- NAPI MŰSOR TARTALOM -->
            <div class="daily-schedule-container">
                <?php foreach($next_7_days as $day): 
                    $date = $day['date'];
                    $day_screenings = $schedule_by_date[$date] ?? [];
                ?>
                    <div id="day-<?php echo $date; ?>" class="day-screenings <?php echo $day['is_today'] ? 'active' : ''; ?>">
                        
                        <!-- DÁTUM FEJLÉC -->
                        <div class="selected-date-header">
                            <h3>
                                <i class="fas fa-calendar-day"></i>
                                <?php echo $day['day_name'] . ', ' . $day['full_date']; ?>
                            </h3>
                            <?php if($day['is_today']): ?>
                                <span class="today-badge-large">MAI VETÍTÉSEK</span>
                            <?php elseif($day['is_tomorrow']): ?>
                                <span class="today-badge-large">HOLNAPI VETÍTÉSEK</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- VETÍTÉSEK LISTA -->
                        <?php if(count($day_screenings) > 0): ?>
                            <div class="screenings-list">
                                <?php foreach($day_screenings as $screening): ?>
                                    <div class="screening-item">
                                        <div class="screening-time">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo date('H:i', strtotime($screening['screening_time'])); ?>
                                        </div>
                                        
                                        <div class="screening-movie-info">
                                            <h4><?php echo htmlspecialchars($screening['movie_title']); ?></h4>
                                            <div class="screening-meta">
                                                <span><i class="fas fa-door-closed"></i> <?php echo $screening['hall_number']; ?>. terem</span>
                                                <span><i class="fas fa-chair"></i> <?php echo $screening['available_seats']; ?> hely</span>
                                                <span><i class="fas fa-star"></i> <?php echo $screening['rating']; ?>/10</span>
                                                <span><i class="fas fa-film"></i> <?php echo htmlspecialchars($screening['genre']); ?></span>
                                                <span><i class="fas fa-hourglass-half"></i> <?php echo $screening['duration']; ?>'</span>
                                            </div>
                                        </div>
                                        
                                        <div style="text-align: right;">
                                            <div class="screening-price">
                                                <?php echo number_format($screening['price'], 0, ',', ' '); ?> Ft
                                                <small>/fő</small>
                                            </div>
                                            <a href="booking.php?screening=<?php echo $screening['id']; ?>" class="btn-booking">
                                                <i class="fas fa-ticket-alt"></i> Foglalás
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- NINCS VETÍTÉS -->
                            <div class="no-screenings">
                                <i class="fas fa-video-slash"></i>
                                <h4>Ezen a napon még nincs vetítés</h4>
                                <p style="font-size: 1rem; color: #6C0808; margin-top: 0.5rem;">
                                    Nézz vissza később, folyamatosan töltjük fel a műsort!
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        
    </main>
</div>

<script src="js/index.js"></script>
<?php
$page_content = ob_get_clean();

include 'header.php';
echo $page_content;
include 'footer.php';
?>