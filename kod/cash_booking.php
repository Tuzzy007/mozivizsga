<?php
require_once 'config.php';

// Csak bejelentkezett felhasználó foglalhat
if(!$current_user) {
    header("Location: login.php");
    exit();
}

// Ellenőrizzük, hogy POST kérés-e
if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: screenings.php");
    exit();
}

$screening_id = intval($_POST['screening_id']);
$selected_seats = json_decode($_POST['selected_seats'], true);

// Ellenőrzések
if(empty($selected_seats)) {
    $_SESSION['error'] = "Nincsenek kiválasztott helyek!";
    header("Location: booking.php?screening=$screening_id");
    exit();
}

// Vetítés adatainak lekérése
$stmt = $pdo->prepare("
    SELECT s.*, m.title as movie_title, m.poster_url
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

// Ellenőrizzük, hogy szabadok-e a kiválasztott helyek
$placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
$stmt = $pdo->prepare("
    SELECT seat_number FROM tickets 
    WHERE screening_id = ? AND seat_number IN ($placeholders) AND status = 'active'
");
$params = array_merge([$screening_id], $selected_seats);
$stmt->execute($params);
$occupied_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);

if(!empty($occupied_seats)) {
    $_SESSION['error'] = "A következő hely(ek) már foglaltak: " . implode(', ', $occupied_seats);
    header("Location: booking.php?screening=$screening_id");
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Összeg kiszámítása
    $total_amount = count($selected_seats) * $screening['price'];
    
    // Létrehozunk egy dummy stripe_session_id-t a készpénzes fizetéshez
    $cash_session_id = 'CASH-' . $screening_id . '-' . $current_user['id'] . '-' . time() . '-' . uniqid();
    
    // Fizetés mentése adatbázisba (készpénz)
    $stmt = $pdo->prepare("
        INSERT INTO payments (user_id, screening_id, stripe_session_id, amount, seats, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $current_user['id'],
        $screening_id,
        $cash_session_id,
        $total_amount,
        json_encode($selected_seats),
        'paid'  // Készpénz esetén azonnal fizetettnek jelöljük
    ]);
    
    $payment_id = $pdo->lastInsertId();
    
    // Jegyek létrehozása
    $stmt = $pdo->prepare("
        INSERT INTO tickets (user_id, screening_id, seat_number, price_paid, payment_id, payment_status, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ");
    
    foreach($selected_seats as $seat_number) {
        $stmt->execute([
            $current_user['id'],
            $screening_id,
            $seat_number,
            $screening['price'],
            $payment_id,
            'paid'
        ]);
    }
    
    // Szabad helyek számának frissítése
    $stmt = $pdo->prepare("UPDATE screenings SET available_seats = available_seats - ? WHERE id = ?");
    $stmt->execute([count($selected_seats), $screening_id]);
    
    $pdo->commit();
    
    // Átirányítás a stripe_success.php-ra, mintha Stripe fizetés történt volna
    // A stripe_success.php fogja kezelni a sikeres oldal megjelenítését
    header("Location: stripe_success.php?session_id=" . urlencode($cash_session_id));
    exit();
    
} catch(Exception $e) {
    $pdo->rollBack();
    error_log("Készpénzes foglalás hiba: " . $e->getMessage());
    $_SESSION['error'] = "Hiba történt a foglalás során! Kérjük, próbálja újra.";
    header("Location: booking.php?screening=$screening_id");
    exit();
}
?>