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
        $occupied_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if(!empty($occupied_seats)) {
            $error = "A következő hely(ek) már foglaltak: " . implode(', ', $occupied_seats);
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

// Oldal specifikus CSS - a színpalettából
$additional_css = '
    /* ===== ALAPÉRTELMEZETT BEÁLLÍTÁSOK ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    /* ===== GLOBÁLIS STÍLUSOK ===== */
    body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f5f5;
        color: #380A0A;
        line-height: 1.6;
    }
    
    .container {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* ===== HEADER ===== */
    .page-header {
        background: linear-gradient(135deg, #380A0A, #6C0808);
        padding: 2rem 0;
        margin: 2rem 0;
        border-radius: 10px;
        text-align: center;
        width: 100%;
    }
    
    .section-title {
        color: white;
        font-size: clamp(1.5rem, 5vw, 2.5rem);
        font-family: "Poppins", sans-serif;
        margin: 0;
        padding: 0 20px;
    }
    
    /* ===== MAIN CONTENT ===== */
    .main-content {
        padding: 2rem 0;
        width: 100%;
    }
    
    /* ===== BOOKING CONTAINER (GRID) ===== */
    .booking-container {
        display: grid;
        grid-template-columns: minmax(300px, 1fr) minmax(500px, 2fr);
        gap: 2rem;
        width: 100%;
    }
    
    /* ===== MOVIE INFO CARD ===== */
    .movie-info-card {
        background: linear-gradient(135deg, #F9F9F9, #F5F5F5);
        border-radius: 10px;
        padding: clamp(1rem, 3vw, 2rem);
        border: 1px solid #F57272;
        box-shadow: 0 8px 20px rgba(135, 47, 47, 0.15);
        height: fit-content;
        width: 100%;
    }
    
    .movie-poster {
        width: 100%;
        height: auto;
        max-height: 300px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #FF6B6B;
        margin-bottom: 1.5rem;
    }
    
    .movie-title {
        font-family: "Poppins", sans-serif;
        font-size: clamp(1.2rem, 4vw, 1.5rem);
        color: #380A0A;
        margin-bottom: 1rem;
        font-weight: 600;
        word-wrap: break-word;
    }
    
    .detail-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.8rem;
        padding-bottom: 0.8rem;
        border-bottom: 1px solid #D49E9E;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .detail-label {
        color: #6C0808;
        font-weight: 500;
        font-size: clamp(0.85rem, 2vw, 0.95rem);
    }
    
    .detail-value {
        color: #380A0A;
        font-weight: 600;
        text-align: right;
        word-break: break-word;
        font-size: clamp(0.85rem, 2vw, 0.95rem);
    }
    
    .detail-value i {
        color: #FF6B6B;
        margin-right: 5px;
    }
    
    .price-total {
        background: rgba(255, 107, 107, 0.1);
        padding: clamp(1rem, 3vw, 1.5rem);
        border-radius: 8px;
        text-align: center;
        margin-top: 1.5rem;
        border: 1px solid #FF6B6B;
    }
    
    .total-price {
        font-size: clamp(1.5rem, 5vw, 1.8rem);
        color: #872F2F;
        font-weight: bold;
        font-family: "Poppins", sans-serif;
    }
    
    /* ===== BOOKING FORM CARD ===== */
    .booking-form-card {
        background: linear-gradient(135deg, #F9F9F9, #F5F5F5);
        border-radius: 10px;
        padding: clamp(1rem, 3vw, 2rem);
        border: 1px solid #F57272;
        box-shadow: 0 8px 20px rgba(135, 47, 47, 0.15);
        width: 100%;
    }
    
    .booking-form-card h2 {
        font-family: "Poppins", sans-serif;
        color: #380A0A;
        margin-bottom: 1.5rem;
        font-size: clamp(1.2rem, 4vw, 1.4rem);
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        flex-shrink: 0;
    }
    
    .legend-color.available {
        background: #D49E9E;
    }
    
    .legend-color.selected {
        background: linear-gradient(135deg, #FF6B6B, #D23A3A);
    }
    
    .legend-color.occupied {
        background: #872F2F;
    }
    
    .legend-label {
        color: #380A0A;
        font-size: clamp(0.8rem, 2vw, 0.9rem);
    }
    
    /* ===== SELECTED SEATS INFO ===== */
    .selected-seats-info {
        background: rgba(255, 107, 107, 0.1);
        padding: clamp(1rem, 3vw, 1.5rem);
        border-radius: 8px;
        margin: 1.5rem 0;
        border: 1px solid #F57272;
    }
    
    .selected-seats-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
        max-width: 100%;
    }
    
    .selected-seat-badge {
        background: linear-gradient(135deg, #FF6B6B, #D23A3A);
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: clamp(0.8rem, 2vw, 0.9rem);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .selected-seat-badge i {
        cursor: pointer;
        padding: 0.2rem;
    }
    
    .booking-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 2px solid #FF6B6B;
    }
    
    .summary-label {
        color: #6C0808;
        font-size: clamp(1rem, 3vw, 1.1rem);
        font-weight: 600;
    }
    
    .summary-value {
        color: #380A0A;
        font-size: clamp(1.1rem, 4vw, 1.3rem);
        font-weight: bold;
    }
    
    /* ===== USER INFO DISPLAY ===== */
    .user-info-display {
        background: rgba(255, 107, 107, 0.1);
        padding: clamp(1rem, 3vw, 1.5rem);
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px solid #F57272;
    }
    
    .user-info-display h3 {
        color: #380A0A;
        margin-bottom: 1rem;
        font-size: clamp(1rem, 3vw, 1.1rem);
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.8rem;
        padding-bottom: 0.8rem;
        border-bottom: 1px solid rgba(212, 158, 158, 0.3);
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    /* ===== PAYMENT SECTION ===== */
    .payment-method-section {
        background: rgba(255, 107, 107, 0.05);
        padding: clamp(1rem, 3vw, 1.5rem);
        border-radius: 8px;
        margin: 1.5rem 0;
        border: 1px solid #F57272;
    }
    
    .payment-method-section h3 {
        color: #380A0A;
        margin-bottom: 1rem;
        font-size: clamp(1rem, 3vw, 1.1rem);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .payment-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin: 1rem 0;
    }
    
    .payment-option {
        background: white;
        border: 1px solid #D49E9E;
        border-radius: 6px;
        padding: 1rem;
        transition: all 0.3s ease;
    }
    
    .payment-option:hover {
        border-color: #FF6B6B;
        box-shadow: 0 2px 8px rgba(255, 107, 107, 0.2);
    }
    
    .payment-option input[type="radio"] {
        display: none;
    }
    
    .payment-option label {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        cursor: pointer;
        color: #380A0A;
        font-size: clamp(0.85rem, 2vw, 0.95rem);
        width: 100%;
    }
    
    .payment-option label i {
        color: #872F2F;
        font-size: 1.2rem;
    }
    
    .payment-option:has(input[type="radio"]:checked) {
        border: 2px solid #FF6B6B;
        background: rgba(255, 107, 107, 0.05);
    }
    
    /* ===== PAYMENT DETAILS SECTIONS ===== */
    .card-details,
    .szep-details,
    .transfer-info {
        margin-top: 1.5rem;
        padding: clamp(1rem, 3vw, 1.5rem);
        background: white;
        border-radius: 6px;
        border: 1px solid #F57272;
    }
    
    .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .form-group {
        flex: 1 1 200px;
        min-width: 200px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #6C0808;
        font-weight: 500;
        font-size: clamp(0.85rem, 2vw, 0.9rem);
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #D49E9E;
        border-radius: 4px;
        font-size: clamp(0.9rem, 2vw, 0.95rem);
        transition: all 0.3s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #FF6B6B;
        box-shadow: 0 0 0 2px rgba(255, 107, 107, 0.2);
    }
    
    .form-group small {
        color: #6C0808;
        font-size: 0.8rem;
        margin-top: 0.3rem;
        display: block;
    }
    
    /* ===== MESSAGES ===== */
    .error {
        background: linear-gradient(135deg, #EA3232, #6C0808);
        color: white;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
        border: 1px solid #FF6B6B;
        font-size: clamp(0.9rem, 2vw, 1rem);
    }
    
    .success {
        background: linear-gradient(135deg, #872F2F, #380A0A);
        color: white;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
        border: 1px solid #D49E9E;
        font-size: clamp(0.9rem, 2vw, 1rem);
    }
    
    .seat-limit-warning {
        display: none;
        background: #EA3232;
        color: white;
        padding: 0.8rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        border: 1px solid #FF6B6B;
        text-align: center;
        font-weight: 600;
        font-size: clamp(0.9rem, 2vw, 1rem);
        animation: fadeInOut 0.3s ease;
    }
    
    .max-seats-warning {
        background: rgba(255, 107, 107, 0.2);
        color: #6C0808;
        padding: 0.5rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        font-size: clamp(0.85rem, 2vw, 0.9rem);
        text-align: center;
        border: 1px solid #FF6B6B;
    }
    
    /* ===== BOOKING BLOCKED ===== */
    .booking-blocked {
        text-align: center;
        padding: clamp(2rem, 5vw, 3rem);
        background: rgba(255, 107, 107, 0.1);
        border-radius: 8px;
        border: 2px solid #EA3232;
    }
    
    .booking-blocked i {
        font-size: clamp(3rem, 10vw, 4rem);
        color: #D23A3A;
        margin-bottom: 1.5rem;
    }
    
    .booking-blocked h3 {
        color: #380A0A;
        margin-bottom: 1rem;
        font-family: "Poppins", sans-serif;
        font-size: clamp(1.2rem, 4vw, 1.5rem);
    }
    
    .booking-blocked p {
        color: #6C0808;
        margin-bottom: 2rem;
        font-size: clamp(1rem, 3vw, 1.1rem);
    }
    
    /* ===== BUTTONS ===== */
    .booking-actions {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 2rem;
    }
    
    .btn {
        display: inline-block;
        padding: 0.8rem 1.5rem;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: clamp(0.9rem, 2vw, 1rem);
        text-align: center;
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #FF6B6B, #D23A3A);
        color: white;
        border: 1px solid #872F2F;
    }
    
    .btn-primary:hover:not(:disabled) {
        background: linear-gradient(135deg, #D23A3A, #872F2F);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(210, 58, 58, 0.3);
    }
    
    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .btn-secondary {
        background: transparent;
        color: #6C0808;
        border: 2px solid #F57272;
    }
    
    .btn-secondary:hover {
        background: #F57272;
        color: white;
    }
    
    /* ===== ANIMATIONS ===== */
    @keyframes fadeInOut {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* ===== TOUCH DEVICE OPTIMIZATIONS ===== */
    @media (hover: none) and (pointer: coarse) {
        .seat {
            min-width: 44px;
            min-height: 44px;
        }
        
        .btn,
        .payment-option label,
        .selected-seat-badge i {
            min-height: 44px;
        }
        
        .seat:hover:not(.occupied) {
            transform: none;
        }
    }
    
    /* ===== RESPONSIVE BREAKPOINTS ===== */
    
    /* Extra small devices (phones, 600px and down) */
    @media only screen and (max-width: 600px) {
        .container {
            padding: 0 10px;
        }
        
        .booking-container {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .page-header {
            padding: 1.5rem 0;
            margin: 1rem 0;
        }
        
        .movie-info-card,
        .booking-form-card {
            padding: 1rem;
        }
        
        .movie-poster {
            max-height: 200px;
        }
        
        .seat-row {
            gap: 0.2rem;
        }
        
        .seat {
            width: 28px;
            height: 28px;
            font-size: 0.7rem;
        }
        
        
        .seat-legend {
            gap: 1rem;
        }
        
        .payment-options {
            grid-template-columns: 1fr;
        }
        
        .detail-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .detail-value {
            text-align: left;
        }
        
        .form-row {
            flex-direction: column;
        }
        
        .form-group {
            min-width: 100%;
        }
    }
    
    /* Small devices (portrait tablets and large phones, 600px to 768px) */
    @media only screen and (min-width: 601px) and (max-width: 768px) {
        .booking-container {
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        
        .seat {
            width: 30px;
            height: 30px;
            font-size: 0.75rem;
        }
        
        .movie-poster {
            max-height: 250px;
        }
        
        .payment-options {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    /* Medium devices (landscape tablets, 769px to 992px) */
    @media only screen and (min-width: 769px) and (max-width: 992px) {
        .booking-container {
            grid-template-columns: 1fr 1.5fr;
            gap: 1.5rem;
        }
        
        .seat {
            width: 32px;
            height: 32px;
            font-size: 0.8rem;
        }
        
        .seat-row {
            gap: 0.3rem;
        }
    }
    
    /* Large devices (desktops, 993px to 1200px) */
    @media only screen and (min-width: 993px) and (max-width: 1200px) {
        .booking-container {
            gap: 2rem;
        }
        
        .seat {
            width: 35px;
            height: 35px;
        }
    }
    
    /* Extra large devices (large desktops, 1201px and up) */
    @media only screen and (min-width: 1201px) {
        .container {
            padding: 0 30px;
        }
        
        .seat:hover:not(.occupied) {
            transform: scale(1.1);
        }
    }
    
    /* ===== PRINT STYLES ===== */
    @media print {
        .booking-actions,
        .payment-method-section,
        .seat-legend,
        .seat {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
    }

    /* ===== SEATING PLAN ===== */
    .seating-plan {
        background: white;
        border-radius: 8px;
        padding: clamp(1rem, 3vw, 2rem);
        margin-bottom: 2rem;
        border: 1px solid #F57272;
        box-shadow: 0 5px 15px rgba(135, 47, 47, 0.1);
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .screen {
        background: linear-gradient(135deg, #380A0A, #6C0808);
        color: white;
        text-align: center;
        padding: 1rem;
        margin-bottom: 2rem;
        border-radius: 4px;
        font-weight: bold;
        font-family: "Poppins", sans-serif;
        border: 2px solid #FF6B6B;
        width: 100%;
    }
    
    .seats-grid {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        width: 100%;
        min-width: min-content;
    }
    
    .seat-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
        align-items: center;
        justify-content: flex-start;
        width: 100%;
    }
    
    /* Mobil nézetben a sorok több sorba törnek */
    @media (max-width: 768px) {
        .seat-row {
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.4rem;
            margin-bottom: 0.8rem;
            border-bottom: 1px solid #D49E9E;
            padding-bottom: 0.8rem;
        }
        
        /* Az utolsó sornál ne legyen alsó border */
        .seat-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        
        /* Sorok közötti elválasztás */
        .seats-grid {
            gap: 0;
        }
    }
    
    .row-label {
        width: 30px;
        text-align: center;
        font-weight: bold;
        color: #380A0A;
        font-family: "Poppins", sans-serif;
        font-size: clamp(0.8rem, 2vw, 1rem);
        flex-shrink: 0;
    }
    
    /* Mobil nézetben a sorjelző is középen legyen */
    @media (max-width: 768px) {
        .row-label {
            width: 100%;
            text-align: center;
            margin-bottom: 0.3rem;
            font-size: 1rem;
            background: rgba(210, 58, 58, 0.1);
            padding: 0.2rem;
            border-radius: 4px;
        }
    }
    
    .seat {
        width: clamp(25px, 4vw, 35px);
        height: clamp(25px, 4vw, 35px);
        display: flex;
        align-items: center;
        justify-content: center;
        background: #D49E9E;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: bold;
        font-size: clamp(0.65rem, 2vw, 0.9rem);
        color: #380A0A;
        position: relative;
        flex-shrink: 0;
    }
    
    /* Mobil nézetben a helyek nagyobbak és jobban megnyomhatók */
    @media (max-width: 768px) {
        .seat {
            width: 40px;
            height: 40px;
            font-size: 0.85rem;
        }
    }
    
    /* Nagyon kis képernyőn még nagyobb helyek */
    @media (max-width: 480px) {
        .seat {
            width: 45px;
            height: 45px;
            font-size: 0.9rem;
        }
        
        .seat-row {
            gap: 0.5rem;
        }
    }
    
    .seat:hover:not(.occupied) {
        background: #F57272;
        transform: scale(1.1);
        z-index: 1;
    }
    
    .seat.selected {
        background: linear-gradient(135deg, #FF6B6B, #D23A3A);
        color: white;
        box-shadow: 0 3px 8px rgba(210, 58, 58, 0.3);
    }
    
    .seat.occupied {
        background: #872F2F;
        color: white;
        cursor: not-allowed;
        opacity: 0.7;
    }
    
    /* ===== SEAT LEGEND ===== */
    .seat-legend {
        display: flex;
        justify-content: center;
        gap: clamp(1rem, 3vw, 2rem);
        margin-top: 2rem;
        flex-wrap: wrap;
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

        echo $additional_css; 
        ?>
 
    </style>
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
                                
                                <a href="screenings.php?movie=<?php echo $screening['movie_id']; ?>" class="btn btn-secondary" style="text-align: center;">
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
    
<script>
        // Kiválasztott helyek tömbje
        let selectedSeats = [];
        const MAX_SEATS = 6;
        const TICKET_PRICE = <?php echo $screening['price']; ?>;
                                    
        // Hely kiválasztása/eltávolítása
        function toggleSeat(seatNumber, element) {
            const index = selectedSeats.indexOf(seatNumber);
            const warningElement = document.getElementById('seatLimitWarning');

            if(index === -1) {
                // Hely hozzáadása
                if(selectedSeats.length >= MAX_SEATS) {
                    // Figyelmeztetés megjelenítése
                    if (warningElement) {
                        warningElement.style.display = 'block';
                        // Automatikusan eltüntetjük 3 másodperc után
                        setTimeout(() => {
                            warningElement.style.display = 'none';
                        }, 10000);
                    }
                    return;
                }

                // Ha nincs elérve a limit, elrejtjük a figyelmeztetést (ha látszana)
                if (warningElement) {
                    warningElement.style.display = 'none';
                }

                selectedSeats.push(seatNumber);
                element.classList.remove('available');
                element.classList.add('selected');
            } else {
                // Hely eltávolítása - ilyenkor is elrejtjük a figyelmeztetést
                if (warningElement) {
                    warningElement.style.display = 'none';
                }
                selectedSeats.splice(index, 1);
                element.classList.remove('selected');
                element.classList.add('available');
            }

            // Felület frissítése
            updateSelectedSeatsDisplay();
        }
                                    
        // Kiválasztott helyek megjelenítésének frissítése
        function updateSelectedSeatsDisplay() {
            // Lista frissítése
            const selectedList = document.getElementById('selectedSeatsList');
            const countElement = document.getElementById('selectedCount');
            const totalPriceElement = document.getElementById('totalPrice');
            const paymentButton = document.getElementById('paymentButton');
            const selectedSeatsInput = document.getElementById('selectedSeatsInput');
                                    
            // Badge-ek generálása - csak ha létezik a selectedList
            if (selectedList) {
                selectedList.innerHTML = '';
                selectedSeats.sort().forEach(seat => {
                    const badge = document.createElement('span');
                    badge.className = 'selected-seat-badge';
                    badge.innerHTML = `${seat} <i class="fas fa-times" onclick="removeSeat('${seat}')"></i>`;
                    selectedList.appendChild(badge);
                });
            }
                                    
            // Darabszám és végösszeg frissítése
            if (countElement) countElement.textContent = selectedSeats.length + ' db';
            const totalPrice = selectedSeats.length * TICKET_PRICE;
            if (totalPriceElement) totalPriceElement.textContent = totalPrice.toLocaleString('hu-HU') + ' Ft';
                                    
            // Payment gomb frissítése
            if (paymentButton) {
                const selectedPayment = document.querySelector('input[name="payment_method"]:checked')?.value;
                if(selectedSeats.length === 0) {
                    paymentButton.disabled = true;
                    paymentButton.style.opacity = '0.5';
                    paymentButton.style.cursor = 'not-allowed';
                    paymentButton.innerHTML = '<i class="fas fa-credit-card"></i> Fizetés bankkártyával';
                } else {
                    paymentButton.disabled = false;
                    paymentButton.style.opacity = '1';
                    paymentButton.style.cursor = 'pointer';
                    
                    // Fizetési mód alapján gomb szövegének módosítása
                    if (selectedPayment === 'cash') {
                        paymentButton.innerHTML = '<i class="fas fa-money-bill-wave"></i> Foglalás készpénzzel: ' + 
                            totalPrice.toLocaleString('hu-HU') + ' Ft';
                    } else {
                        paymentButton.innerHTML = '<i class="fas fa-credit-card"></i> Fizetés bankkártyával: ' + 
                            totalPrice.toLocaleString('hu-HU') + ' Ft';
                    }
                }
            }
                                    
            // Hidden input frissítése
            if (selectedSeatsInput) {
                selectedSeatsInput.value = JSON.stringify(selectedSeats);
            }
        }
                                    
        // Hely eltávolítása a badge-ről
        function removeSeat(seatNumber) {
            const seatElement = document.querySelector(`.seat[data-seat="${seatNumber}"]`);
            if(seatElement) {
                const index = selectedSeats.indexOf(seatNumber);
                if(index !== -1) {
                    selectedSeats.splice(index, 1);
                    seatElement.classList.remove('selected');
                    seatElement.classList.add('available');

                    // Figyelmeztetés elrejtése
                    const warningElement = document.getElementById('seatLimitWarning');
                    if (warningElement) {
                        warningElement.style.display = 'none';
                    }

                    updateSelectedSeatsDisplay();
                }
            }
        }
                                    
        // Lap betöltésekor ellenőrizzük, hogy van-e szabad hely
        document.addEventListener('DOMContentLoaded', function() {
            const availableSeats = document.querySelectorAll('.seat.available');
            const paymentButton = document.getElementById('paymentButton');
                                    
            if(availableSeats.length === 0) {
                if(paymentButton) {
                    paymentButton.disabled = true;
                    paymentButton.innerHTML = '<i class="fas fa-times-circle"></i> Nincs szabad hely';
                    paymentButton.style.backgroundColor = "#6C0808";
                }
                                    
                const maxSeatsWarning = document.querySelector('.max-seats-warning');
                if(maxSeatsWarning) {
                    maxSeatsWarning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Nincsenek szabad helyek erre a vetítésre!';
                    maxSeatsWarning.style.backgroundColor = 'rgba(210, 58, 58, 0.2)';
                }
            }
                                    
            // Fizetési módok kezelése inicializálása
            initPaymentMethods();
            updateSelectedSeatsDisplay();
        });

        // Fizetési módok kezelése
        function initPaymentMethods() {
            const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
            const cardDetails = document.getElementById('cardDetails');
            const szepDetails = document.getElementById('szepDetails');
            const transferInfo = document.getElementById('transferInfo');
            const selectedPaymentInput = document.getElementById('selectedPaymentMethod');
            const paymentButton = document.getElementById('paymentButton');

            // SZÉP kártya mezők referenciái
            const szepSubaccount = document.getElementById('szep_subaccount');
            const szepNumber = document.getElementById('szep_number');

            if (paymentRadios.length > 0) {
                paymentRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        // Minden plusz mező elrejtése
                        if (cardDetails) cardDetails.style.display = 'none';
                        if (szepDetails) szepDetails.style.display = 'none';
                        if (transferInfo) transferInfo.style.display = 'none';

                        // SZÉP kártya mezők kötelező tulajdonságának eltávolítása
                        if(szepSubaccount) szepSubaccount.required = false;
                        if(szepNumber) szepNumber.required = false;

                        // Kiválasztott érték mentése
                        if (selectedPaymentInput) selectedPaymentInput.value = this.value;

                        // Aktuális opcióhoz tartozó mezők megjelenítése
                        switch(this.value) {
                            case 'card':
                                if (cardDetails) cardDetails.style.display = 'block';
                                break;
                            case 'szep':
                                if (szepDetails) szepDetails.style.display = 'block';
                                if(szepSubaccount) szepSubaccount.required = true;
                                if(szepNumber) szepNumber.required = true;
                                break;
                            case 'transfer':
                                if (transferInfo) transferInfo.style.display = 'block';
                                break;
                            case 'cash':
                                // Készpénz esetén nem kell semmit megjeleníteni
                                break;
                        }
                        
                        // Gomb szövegének frissítése
                        updateSelectedSeatsDisplay();
                    });
                });
            }
        }

        // SZÉP kártya szám formázás
        document.addEventListener('input', function(e) {
            if(e.target.id === 'szep_number') {
                e.target.value = e.target.value
                    .replace(/\s/g, '')
                    .replace(/\D/g, '')
                    .replace(/(\d{4})/g, '$1 ')
                    .trim()
                    .substring(0, 19);
            }
        });

        function startPayment() {
            if(selectedSeats.length === 0) {
                alert('Kérem válasszon ki legalább egy helyet!');
                return;
            }

            const selectedPayment = document.querySelector('input[name="payment_method"]:checked')?.value;
            
            if (selectedPayment === 'card') {
                startStripePayment();
            } else if (selectedPayment === 'cash') {
                submitCashBooking();
            } else {
                alert('A választott fizetési mód jelenleg nem elérhető. Kérjük válasszon bankkártyát vagy készpénzt!');
            }
        }
        
        function submitCashBooking() {
            // Űrlap adatok összegyűjtése
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'cash_booking.php';
            
            const fields = {
                'screening_id': '<?php echo $screening_id; ?>',
                'selected_seats': JSON.stringify(selectedSeats),
                'book_tickets': '1'
            };
            
            for(const [name, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function startStripePayment() {
            const totalPriceFt = selectedSeats.length * TICKET_PRICE;
            if (totalPriceFt < 175) {
                alert('A fizetendő összeg minimum 175 Ft kell legyen! Válasszon több jegyet!');
                return;
            }
        
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'stripe_payment.php';
        
            const fields = {
                'screening_id': '<?php echo $screening_id; ?>',
                'selected_seats': JSON.stringify(selectedSeats),
                'movie_title': '<?php echo addslashes($screening['movie_title']); ?>',
                'screening_date': '<?php echo $screening['screening_date']; ?>',
                'screening_time': '<?php echo $screening['screening_time']; ?>',
                'poster_url': '<?php echo addslashes($screening['poster_url']); ?>'
            };
        
            for(const [name, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }
        
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>