<?php
require_once 'config.php';

// Hibakeresés kikapcsolva élesben - de ha kell, bekapcsolhatod
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if(!$current_user) {
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: screenings.php");
    exit();
}

// Adatok beolvasása
$screening_id = intval($_POST['screening_id']);
$selected_seats = json_decode($_POST['selected_seats'], true);

// Ellenőrzés
if(empty($selected_seats)) {
    $_SESSION['error'] = "Nincsenek kiválasztott helyek!";
    header("Location: booking.php?screening=$screening_id");
    exit();
}

// Jegyár lekérése az adatbázisból
$stmt = $pdo->prepare("SELECT price FROM screenings WHERE id = ?");
$stmt->execute([$screening_id]);
$ticket_price = intval($stmt->fetchColumn());

// Összeg számítása - FILLÉRBEN! (1 Ft = 100 fillér a Stripe-nak)
$total_amount_filler = count($selected_seats) * $ticket_price * 100;

// Minimum összeg ellenőrzés
if ($total_amount_filler < 17500) { // 175 Ft minimum
    $_SESSION['error'] = "A fizetendő összeg minimum 175 Ft kell legyen!";
    header("Location: booking.php?screening=$screening_id");
    exit();
}

// Ellenőrzés, hogy szabadok-e még a helyek
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
    // Egyedi azonosító
    $order_id = 'ORDER-' . $screening_id . '-' . $current_user['id'] . '-' . time();
    
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'huf',
                'product_data' => [
                    'name' => 'Mozi jegy(ek) - ' . $_POST['movie_title'],
                    'description' => 'Helyek: ' . implode(', ', $selected_seats) . ' - ' . count($selected_seats) . ' db' . 
                                     ' Kártya szám: 4242 4242 4242 4242' . 
                                     ' Elutasított: 4000 0000 0000 9995',
                    'images' => isset($_POST['poster_url']) ? [$_POST['poster_url']] : [],
                ],
                'unit_amount' => $total_amount_filler, // FILLÉRBEN!
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/stripe_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/booking.php?screening=' . $screening_id,
        'client_reference_id' => (string)$current_user['id'],
        'metadata' => [
            'screening_id' => (string)$screening_id,
            'seats' => implode(',', $selected_seats),
            'user_id' => (string)$current_user['id'],
            'order_id' => $order_id
        ],
        'locale' => 'hu',
    ]);
    
    // Fizetés mentése adatbázisba
    $stmt = $pdo->prepare("
        INSERT INTO payments (user_id, screening_id, stripe_session_id, amount, seats, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $current_user['id'],
        $screening_id,
        $session->id,
        $total_amount_filler / 100, // Visszaváltás Ft-ra a tároláshoz
        json_encode($selected_seats),
        PAYMENT_STATUS_PENDING
    ]);
    
    // Session mentése
    $_SESSION['pending_payment_id'] = $pdo->lastInsertId();
    $_SESSION['pending_seats'] = $selected_seats;
    $_SESSION['pending_screening'] = $screening_id;
    
    // Átirányítás Stripe-ra
    header("Location: " . $session->url);
    exit();
    
} catch (\Stripe\Exception\InvalidRequestException $e) {
    // Részletes hibanaplózás
    error_log("=== STRIPE INVALID REQUEST ===");
    error_log("Message: " . $e->getMessage());
    error_log("Code: " . $e->getError()->code);
    error_log("Param: " . $e->getError()->param);
    error_log("Amount filler: " . $total_amount_filler);
    error_log("Seats: " . implode(',', $selected_seats));
    
    $_SESSION['error'] = "Fizetési hiba: " . $e->getError()->code . " - " . $e->getMessage();
    header("Location: booking.php?screening=$screening_id");
    
} catch (\Stripe\Exception\CardException $e) {
    $_SESSION['error'] = "Kártya hiba: " . $e->getError()->message;
    header("Location: booking.php?screening=$screening_id");
} catch (\Stripe\Exception\RateLimitException $e) {
    $_SESSION['error'] = "Túl sok próbálkozás, kérjük várjon!";
    header("Location: booking.php?screening=$screening_id");
} catch (\Stripe\Exception\AuthenticationException $e) {
    $_SESSION['error'] = "Fizetési rendszer hiba (API kulcs)!";
    header("Location: booking.php?screening=$screening_id");
} catch (\Stripe\Exception\ApiConnectionException $e) {
    $_SESSION['error'] = "Hálózati hiba, kérjük próbálja újra!";
    header("Location: booking.php?screening=$screening_id");
} catch (Exception $e) {
    error_log("Stripe general error: " . $e->getMessage());
    $_SESSION['error'] = "Ismeretlen hiba: " . $e->getMessage();
    header("Location: booking.php?screening=$screening_id");
}
?>