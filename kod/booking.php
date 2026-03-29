<?php
require_once 'config.php';
$page_title = "Jegyfoglalás";

// Csak bejelentkezett felhasználó foglalhat
if(!$current_user) {
    header("Location: login.php");
    exit();
}

// Vetítés ellenőrzése
if(!isset($_GET['screening'])) {
    header("Location: screenings.php");
    exit();
}

$screening_id = intval($_GET['screening']);
$stmt = $pdo->prepare("
    SELECT s.*, m.title as movie_title, m.duration, m.poster_url, m.genre
    FROM screenings s 
    JOIN movies m ON s.movie_id = m.id 
    WHERE s.id = ?
");
$stmt->execute([$screening_id]);
$screening = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$screening) {
    header("Location: screenings.php");
    exit();
}

// Foglalás időkorlát ellenőrzése - maximum 15 perccel a vetítés előtt
$screening_datetime = $screening['screening_date'] . ' ' . $screening['screening_time'];
$screening_timestamp = strtotime($screening_datetime);
$current_timestamp = time();
$minutes_until_screening = round(($screening_timestamp - $current_timestamp) / 60);

if($minutes_until_screening < 15) {
    if($minutes_until_screening < 0) {
        $error = "Ez a vetítés már elkezdődött, jegy már nem vásárolható!";
    } else {
        $error = "Jegy már csak a vetítés kezdete előtt 15 perccel vásárolható! (Még " . $minutes_until_screening . " perc van hátra)";
    }
    $booking_blocked = true;
} else {
    $booking_blocked = false;
}

// Foglalás feldolgozása
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_tickets']) && !$booking_blocked) {
    $selected_seats = isset($_POST['selected_seats']) ? json_decode($_POST['selected_seats'], true) : [];
    
    if(empty($selected_seats)) {
        $error = "Kérem válasszon ki legalább egy helyet!";
    } else {
        // Ellenőrzés, hogy szabadok-e a kiválasztott helyek
        $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
        $stmt = $pdo->prepare("
            SELECT seat_number FROM tickets 
            WHERE screening_id = ? AND seat_number IN ($placeholders) AND status = 'active'
        ");
        
        $params = array_merge([$screening_id], $selected_seats);
        $stmt->execute($params);
        $occupied_seats_check = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if(!empty($occupied_seats_check)) {
            $error = "A következő hely(ek) már foglaltak: " . implode(', ', $occupied_seats_check);
        } else {
            try {
                $pdo->beginTransaction();
                
                // Jegyek létrehozása
                $stmt = $pdo->prepare("
                    INSERT INTO tickets (user_id, screening_id, seat_number, price_paid) 
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach($selected_seats as $seat_number) {
                    $stmt->execute([$current_user['id'], $screening_id, $seat_number, $screening['price']]);
                }
                
                // Szabad helyek számának frissítése
                $stmt = $pdo->prepare("UPDATE screenings SET available_seats = available_seats - ? WHERE id = ?");
                $stmt->execute([count($selected_seats), $screening_id]);
                
                $pdo->commit();
                
                $success = "Sikeres jegyfoglalás! " . count($selected_seats) . " jegyet foglalt le.";
                $ticket_count = count($selected_seats);
                header("refresh:3;url=tickets.php");
            } catch(Exception $e) {
                $pdo->rollBack();
                $error = "Hiba történt a foglalás során!";
            }
        }
    }
}

// Szabad helyek generálása
$available_seats = $screening['available_seats'];
$seat_rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
$seat_numbers_per_row = 15;

// Foglalt helyek lekérdezése
$stmt = $pdo->prepare("SELECT seat_number FROM tickets WHERE screening_id = ? AND status = 'active'");
$stmt->execute([$screening_id]);
$occupied_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/booking.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1 class="section-title">Jegyfoglalás</h1>
        </div>
        
        <main class="main-content">
            <?php if(isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
                <div class="success">
                    <?php echo $success; ?><br>
                    <small>Átirányítás a jegyeimhez 3 másodperc múlva...</small>
                </div>
            <?php endif; ?>
            
            <div class="booking-container">
                <div class="movie-info-card">
                    <img src="<?php echo htmlspecialchars($screening['poster_url']); ?>" 
                         alt="<?php echo htmlspecialchars($screening['movie_title']); ?>" 
                         class="movie-poster"
                         onerror="this.src='https://via.placeholder.com/400x300/F5F5F5/380A0A?text=<?php echo urlencode($screening['movie_title']); ?>'">
                    
                    <h2 class="movie-title"><?php echo htmlspecialchars($screening['movie_title']); ?></h2>
                    
                    <div class="detail-item">
                        <span class="detail-label">Vetítés dátuma:</span>
                        <span class="detail-value">
                            <i class="fas fa-calendar-alt" style="color: #FF6B6B;"></i> 
                            <?php echo date('Y.m.d.', strtotime($screening['screening_date'])); ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Vetítés ideje:</span>
                        <span class="detail-value">
                            <i class="fas fa-clock" style="color: #FF6B6B;"></i> 
                            <?php echo date('H:i', strtotime($screening['screening_time'])); ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Terem száma:</span>
                        <span class="detail-value">
                            <i class="fas fa-door-closed" style="color: #FF6B6B;"></i> 
                            <?php echo $screening['hall_number']; ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Film hossza:</span>
                        <span class="detail-value">
                            <i class="fas fa-film" style="color: #FF6B6B;"></i> 
                            <?php echo $screening['duration']; ?> perc
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Műfaj:</span>
                        <span class="detail-value">
                            <i class="fas fa-tags" style="color: #FF6B6B;"></i> 
                            <?php echo htmlspecialchars($screening['genre']); ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Szabad helyek:</span>
                        <span class="detail-value">
                            <i class="fas fa-chair" style="color: #FF6B6B;"></i> 
                            <?php echo $available_seats; ?>
                        </span>
                    </div>
                </div>
                
                <div class="booking-form-card">
                    <?php if(!isset($booking_blocked) || !$booking_blocked): ?>
                        <h2 style="font-family: 'Poppins', sans-serif; color: #380A0A; margin-bottom: 1.5rem; font-size: 1.4rem;">
                            Válassza ki a hely(ek)et
                        </h2>
                        
                        <div class="seat-limit-warning" id="seatLimitWarning" style="display: none; background: #EA3232; color: white; padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; border: 1px solid #FF6B6B; text-align: center; font-weight: 600;">
                            <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                            Maximum 6 helyet választhat ki egyszerre!
                        </div>
                        
                        <div class="seating-plan">
                            <div class="screen">VÁSZON</div>
                            
                            <div class="seats-grid" id="seats-container">
                                <?php for($i = 0; $i < count($seat_rows); $i++):
                                    $row = $seat_rows[$i];
                                ?>
                                <div class="seat-row">
                                    <div class="row-label"><?php echo $row; ?></div>
                                    <?php for($j = 1; $j <= $seat_numbers_per_row; $j++): 
                                        $seat = $row . str_pad($j, 2, '0', STR_PAD_LEFT);
                                        $is_occupied = in_array($seat, $occupied_seats);
                                    ?>
                                    <div class="seat <?php echo $is_occupied ? 'occupied' : 'available'; ?>" 
                                         data-seat="<?php echo $seat; ?>"
                                         onclick="<?php echo !$is_occupied ? "toggleSeat('$seat', this)" : ""; ?>">
                                        <?php echo $j; ?>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="seat-legend">
                                <div class="legend-item">
                                    <div class="legend-color available"></div>
                                    <span class="legend-label">Szabad</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color selected"></div>
                                    <span class="legend-label">Kiválasztott</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color occupied"></div>
                                    <span class="legend-label">Foglalt</span>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="" id="bookingForm">
                            <input type="hidden" name="selected_seats" id="selectedSeatsInput" value="[]">
                            
                            <div class="selected-seats-info">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="color: #380A0A; font-weight: 600;">
                                        <i class="fas fa-check-circle" style="color: #FF6B6B;"></i> 
                                        Kiválasztott helyek:
                                    </span>
                                    <span id="selectedCount" style="color: #6C0808; font-weight: 600;">0 db</span>
                                </div>
                                <div class="selected-seats-list" id="selectedSeatsList">
                                    <!-- Ide kerülnek a kiválasztott helyek badge-ek -->
                                </div>
                                
                                <div class="booking-summary">
                                    <span class="summary-label">Végösszeg:</span>
                                    <span class="summary-value" id="totalPrice">0 Ft</span>
                                </div>
                            </div>
                            
                            <div class="user-info-display">
                                <h3 style="color: #380A0A; margin-bottom: 1rem; font-size: 1.1rem;">Személyes adatok</h3>
                                <div class="info-row">
                                    <span style="color: #6C0808;">Név:</span>
                                    <span style="color: #380A0A; font-weight: 600;"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span style="color: #6C0808;">Email:</span>
                                    <span style="color: #380A0A; font-weight: 600;"><?php echo htmlspecialchars($current_user['email']); ?></span>
                                </div>
                            </div>
                            
                            <!-- FIZETÉSI MÓD SZEKCIÓ -->
                            <div class="payment-method-section">
                                <h3 style="color: #380A0A; margin-bottom: 1rem; font-size: 1.1rem;">
                                    <i class="fas fa-credit-card" style="color: #FF6B6B;"></i> 
                                    Fizetési mód
                                </h3>

                                <div class="payment-options">
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="payment_card" value="card" checked>
                                        <label for="payment_card">
                                            <i class="fas fa-credit-card"></i>
                                            Bankkártya (online)
                                        </label>
                                    </div>
                                                                    
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="payment_cash" value="cash">
                                        <label for="payment_cash">
                                            <i class="fas fa-money-bill-wave"></i>
                                            Készpénz (helyszínen)
                                        </label>
                                    </div>
                                </div>

                                <!-- Bankkártyás fizetés info -->
                                <div class="card-details" id="cardDetails" style="display: block;">
                                    <div style="background: rgba(255, 107, 107, 0.1); padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                                        <p style="color: #380A0A; margin-bottom: 0;">
                                            <i class="fas fa-lock" style="color: #FF6B6B;"></i> 
                                            A bankkártyás fizetés biztonságos stripe rendszeren keresztül történik.
                                        </p>
                                    </div>
                                </div>

                                <!-- SZÉP kártya részletek (alapból rejtve) -->
                                <div class="szep-details" id="szepDetails" style="display: none;">
                                    <div class="form-group">
                                        <label for="szep_subaccount">Alszámla <span style="color: #FF6B6B;">*</span></label>
                                        <select id="szep_subaccount" name="szep_subaccount">
                                            <option value="">Válasszon alszámlát</option>
                                            <option value="szallas">Szálláshely</option>
                                            <option value="vendeglatas">Vendéglátás</option>
                                            <option value="szabadido">Szabadidő</option>
                                        </select>
                                        <small style="color: #6C0808; display: block; margin-top: 0.3rem;">Kötelező mező</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="szep_number">SZÉP kártya szám <span style="color: #FF6B6B;">*</span></label>
                                        <input type="text" id="szep_number" name="szep_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                        <small style="color: #6C0808; display: block; margin-top: 0.3rem;">Kötelező mező</small>
                                    </div>
                                </div>

                                <!-- Átutalás info (alapból rejtve) -->
                                <div class="transfer-info" id="transferInfo" style="display: none;">
                                    <div style="background: rgba(255, 107, 107, 0.1); padding: 1rem; border-radius: 6px; margin-top: 0.5rem;">
                                        <p style="color: #380A0A; margin-bottom: 0.5rem;">
                                            <i class="fas fa-info-circle" style="color: #FF6B6B;"></i> 
                                            Átutalásos fizetés esetén a foglalás 24 órán belül automatikusan törlődik, ha nem érkezik meg az összeg.
                                        </p>
                                        <p style="color: #6C0808; font-weight: 600; margin-bottom: 0.3rem;">Számlaszám:</p>
                                        <p style="color: #380A0A; font-family: monospace; font-size: 1.1rem;">12345678-12345678-12345678</p>
                                        <p style="color: #6C0808; font-weight: 600; margin-bottom: 0.3rem;">Közlemény:</p>
                                        <p style="color: #380A0A; font-family: monospace;">FOGLALÁS-<?php echo $screening_id; ?>-<?php echo $current_user['id']; ?></p>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="selected_payment_method" id="selectedPaymentMethod" value="card">
                            <input type="hidden" name="book_tickets" value="1">

                            <div class="booking-actions">
                                <button type="button" onclick="startPayment()" class="btn btn-primary" id="paymentButton" disabled>
                                    <i class="fas fa-credit-card"></i> Fizetés bankkártyával
                                </button>
                                
                                <a href="screenings.php?movie=<?php echo $screening['movie_id']; ?>" class="btn btn-secondary" style="text-align: center; color: #380A0A;">
                                    <i class="fas fa-arrow-left"></i> Mégse
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="booking-blocked">
                            <i class="fas fa-clock"></i>
                            <h3>Jegyvásárlás nem lehetséges</h3>
                            <p style="color: #6C0808; margin-bottom: 2rem; font-size: 1.1rem;"><?php echo $error; ?></p>
                            <a href="screenings.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Vissza a vetítésekhez
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <!-- PHP változók átadása JavaScriptnek -->
    <script>
        // Globális JavaScript változók a PHP-ből
        const TICKET_PRICE = <?php echo $screening['price']; ?>;
        const SCREENING_ID = <?php echo $screening_id; ?>;
        const MOVIE_TITLE = '<?php echo addslashes($screening['movie_title']); ?>';
        const SCREENING_DATE = '<?php echo $screening['screening_date']; ?>';
        const SCREENING_TIME = '<?php echo $screening['screening_time']; ?>';
        const POSTER_URL = '<?php echo addslashes($screening['poster_url']); ?>';
        const MAX_SEATS = 6;
        const USER_NAME = '<?php echo addslashes($current_user['full_name']); ?>';
        const USER_EMAIL = '<?php echo addslashes($current_user['email']); ?>';
    </script>
    
    <!-- Külső JavaScript fájl -->
    <script src="js/booking.js"></script>
</body>
</html>