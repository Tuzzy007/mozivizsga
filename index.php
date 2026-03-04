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

// Oldal specifikus CSS - TISZTA PIROS, MODERN, RÖVID
$additional_css = '
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");
    
    body {
        background: linear-gradient(135deg, #1a0a0a 0%, #2d1a1a 100%);
    }
    
    /* HERO - MINIMAL PIROS */
    .hero {
        background: linear-gradient(0deg, #8b000000, #630000);
        background: url("https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80") no-repeat center center;
        color: white;
        padding: 4rem 0;
        text-align: center;
        border-radius: 24px;
        margin-bottom: 3rem;
        box-shadow: 0 15px 30px rgba(80, 0, 0, 0.4);
        position: relative;
        overflow: hidden;
    }
    
    .hero::before {
        position: absolute;
        right: 20px;
        bottom: 20px;
        font-size: 80px;
        opacity: 0.1;
    }
    
    .hero h1 {
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 0.8rem;
        letter-spacing: -0.5px;
        text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
    }
    
    .hero p {
        font-size: 1.2rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }
    
    /* SZEKCIÓ CÍM */
    .section-title {
        font-size: 2rem;
        font-weight: 700;
        color: white;
        margin-bottom: 1.8rem;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 3px solid #D23A3A;
        padding-bottom: 0.8rem;
    }
    
    .section-title i {
        color: #FF6B6B;
    }
    
    /* DÁTUM FÜLEK - ELEGÁNS PIROS */
    .date-tabs {
        display: flex;
        gap: 0.8rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
        justify-content: center;
    }
    
    .date-tab {
        padding: 0.9rem 1.2rem;
        background: white;
        border: none;
        border-radius: 16px;
        color: #380A0A;
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: 600;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 100px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border: 1px solid rgba(210, 58, 58, 0.2);
    }
    
    .date-tab .day-name {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6C0808;
    }
    
    .date-tab .day-number {
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1.2;
        color: #8B0000;
    }
    
    .date-tab .day-month {
        font-size: 0.8rem;
        color: #872F2F;
    }
    
    .date-tab.active {
        background: linear-gradient(145deg, #8B0000, #D23A3A);
        border-color: #FF6B6B;
        box-shadow: 0 8px 15px rgba(210, 58, 58, 0.4);
    }
    
    .date-tab.active .day-name,
    .date-tab.active .day-number,
    .date-tab.active .day-month {
        color: white;
    }
    
    .date-tab .today-badge,
    .date-tab .tomorrow-badge {
        background: #FF6B6B;
        color: white;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        margin-top: 4px;
        letter-spacing: 0.5px;
    }
    
    /* NAPI MŰSOR KONTÉNER */
    .daily-schedule-container {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 20px 35px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 107, 107, 0.3);
        min-height: 500px;
    }
    
    .day-screenings {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .day-screenings.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* KIVÁLASZTOTT DÁTUM CÍM */
    .selected-date-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #FF6B6B;
    }
    
    .selected-date-header h3 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #380A0A;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .selected-date-header h3 i {
        color: #D23A3A;
    }
    
    .today-badge-large {
        background: linear-gradient(145deg, #8B0000, #D23A3A);
        color: white;
        padding: 0.4rem 1.2rem;
        border-radius: 30px;
        font-size: 0.9rem;
        font-weight: 600;
        letter-spacing: 1px;
    }
    
    /* VETÍTÉSEK LISTA - TISZTA, ÁTTEKINTHETŐ */
    .screenings-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .screening-item {
        display: grid;
        grid-template-columns: 130px 1fr auto;
        gap: 1.5rem;
        align-items: center;
        padding: 1.2rem;
        background: #FEF9F9;
        border-radius: 16px;
        border-left: 6px solid #D23A3A;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(139, 0, 0, 0.08);
    }
    
    .screening-item:hover {
        background: white;
        border-left-color: #8B0000;
        box-shadow: 0 8px 20px rgba(210, 58, 58, 0.15);
        transform: translateX(5px);
    }
    
    .screening-time {
        background: linear-gradient(145deg, #630000, #8B0000);
        color: white;
        padding: 0.8rem;
        border-radius: 12px;
        text-align: center;
        font-weight: 700;
        font-size: 1.2rem;
        box-shadow: 0 4px 0 #380A0A;
    }
    
    .screening-time i {
        color: #FFB6B6;
        margin-right: 6px;
    }
    
    .screening-movie-info h4 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #380A0A;
        margin-bottom: 0.5rem;
    }
    
    .screening-meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        color: #6C0808;
        font-size: 0.9rem;
    }
    
    .screening-meta i {
        color: #D23A3A;
        margin-right: 5px;
        width: 16px;
    }
    
    .screening-price {
        background: #8B0000;
        color: white;
        padding: 0.5rem 1.2rem;
        border-radius: 30px;
        font-weight: 700;
        font-size: 1.2rem;
        display: inline-block;
        box-shadow: 0 4px 0 #4A0000;
    }
    
    .screening-price small {
        font-size: 0.8rem;
        font-weight: 400;
        opacity: 0.9;
    }
    
    .btn-booking {
        background: linear-gradient(145deg, #D23A3A, #8B0000);
        color: white;
        border: none;
        padding: 0.7rem 1.5rem;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 0.5rem;
        text-decoration: none;
    }
    
    .btn-booking:hover {
        background: linear-gradient(145deg, #8B0000, #630000);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(139, 0, 0, 0.3);
    }
    
    /* NINCS VETÍTÉS */
    .no-screenings {
        text-align: center;
        padding: 4rem 2rem;
        color: #6C0808;
        background: #FFF5F5;
        border-radius: 16px;
    }
    
    .no-screenings i {
        font-size: 4rem;
        color: #D23A3A;
        opacity: 0.5;
        margin-bottom: 1rem;
    }
    
    .no-screenings h4 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #380A0A;
        margin-bottom: 0.5rem;
    }
    
    /* RESZPONZÍV */
    @media (max-width: 768px) {
        .hero h1 { font-size: 2rem; }
        .hero p { font-size: 1rem; }
        .section-title { font-size: 1.6rem; }
        
        .screening-item {
            grid-template-columns: 1fr;
            gap: 1rem;
            text-align: center;
        }
        
        .screening-time {
            width: 120px;
            margin: 0 auto;
        }
        
        .screening-meta {
            justify-content: center;
        }
        
        .date-tab {
            min-width: 85px;
            padding: 0.7rem 0.8rem;
        }
        
        .date-tab .day-number {
            font-size: 1.5rem;
        }
        
        .selected-date-header h3 {
            font-size: 1.4rem;
        }
    }
    
    @media (max-width: 480px) {
        .daily-schedule-container {
            padding: 1.2rem;
        }
        
        .date-tab {
            min-width: 75px;
            padding: 0.6rem 0.5rem;
        }
        
        .date-tab .day-number {
            font-size: 1.3rem;
        }
        
        .selected-date-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }

    .hero h1 span {
        display: inline-block;
        background: linear-gradient(45deg, #9e7070, #6C0808);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .hero h1 {
        display: inline-block;
        background: linear-gradient(45deg, #ffffff, #915555);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
';

// Oldal tartalma
ob_start();
?>
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

<script>
    function showDay(dayId, element) {
        // Minden day-screenings elrejtése
        document.querySelectorAll('.day-screenings').forEach(el => {
            el.classList.remove('active');
        });
        
        // Kiválasztott megjelenítése
        document.getElementById(dayId).classList.add('active');
        
        // Minden date-tab active eltávolítása
        document.querySelectorAll('.date-tab').forEach(el => {
            el.classList.remove('active');
        });
        
        // Kiválasztott active hozzáadása
        element.classList.add('active');
        
        // SIMÁN GÖRDÜL
        document.getElementById(dayId).scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }
    
    // URL hash kezelés (ha közvetlen napra akarunk ugrani)
    window.addEventListener('load', function() {
        const hash = window.location.hash;
        if(hash && hash.includes('day-')) {
            const dayId = hash.substring(1);
            const tabElement = document.querySelector(`[onclick*="${dayId}"]`);
            if(tabElement) {
                showDay(dayId, tabElement);
            }
        }
    });
</script>
<?php
$page_content = ob_get_clean();

include 'header.php';
echo $page_content;
include 'footer.php';
?>