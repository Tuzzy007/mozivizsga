<?php
require_once 'config.php';

if(!$current_user) {
    header("Location: login.php");
    exit();
}

if(!isset($_GET['session_id'])) {
    header("Location: screenings.php");
    exit();
}

$session_id = $_GET['session_id'];

try {
    // Stripe session lekérése
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    
    // Fizetés megkeresése adatbázisban
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE stripe_session_id = ?");
    $stmt->execute([$session_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$payment) {
        die("Fizetés nem található!");
    }
    
    // Csak a fizető felhasználó férhet hozzá
    if($payment['user_id'] != $current_user['id']) {
        die("Hozzáférés megtagadva!");
    }
    
    // Ha már sikeresen feldolgoztuk
    if($payment['status'] == PAYMENT_STATUS_PAID) {
        header("Location: tickets.php?success=already_paid");
        exit();
    }
    
    // Fizetés státuszának frissítése
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = ?, paid_at = NOW(), stripe_payment_intent = ?
        WHERE id = ?
    ");
    $stmt->execute([
        PAYMENT_STATUS_PAID,
        $session->payment_intent,
        $payment['id']
    ]);
    
    // Jegyek létrehozása
    $selected_seats = json_decode($payment['seats'], true);
    $screening_id = $payment['screening_id'];
    
    // Ellenőrizzük újra, hogy szabadok-e
    $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
    $stmt = $pdo->prepare("
        SELECT seat_number FROM tickets 
        WHERE screening_id = ? AND seat_number IN ($placeholders) AND status = 'active'
    ");
    $params = array_merge([$screening_id], $selected_seats);
    $stmt->execute($params);
    $occupied_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if(!empty($occupied_seats)) {
        // Ha foglalt, refund
        \Stripe\Refund::create([
            'payment_intent' => $session->payment_intent,
        ]);
        
        $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE id = ?");
        $stmt->execute([PAYMENT_STATUS_FAILED, $payment['id']]);
        
        die("A kiválasztott helyek már foglaltak! A fizetett összeget visszautaltuk.");
    }
    
    // Szék árának lekérése
    $stmt = $pdo->prepare("SELECT price FROM screenings WHERE id = ?");
    $stmt->execute([$screening_id]);
    $screening = $stmt->fetch(PDO::FETCH_ASSOC);
    $ticket_price = $screening['price'];
    
    $pdo->beginTransaction();
    
    try {
        // Jegyek beszúrása
        $stmt = $pdo->prepare("
            INSERT INTO tickets (user_id, screening_id, seat_number, price_paid, payment_id, payment_status, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        
        foreach($selected_seats as $seat_number) {
            $stmt->execute([
                $current_user['id'],
                $screening_id,
                $seat_number,
                $ticket_price,
                $payment['id'],
                PAYMENT_STATUS_PAID
            ]);
        }
        
        // Szabad helyek számának frissítése
        $stmt = $pdo->prepare("UPDATE screenings SET available_seats = available_seats - ? WHERE id = ?");
        $stmt->execute([count($selected_seats), $screening_id]);
        
        $pdo->commit();
        
        // Session törlése
        unset($_SESSION['pending_payment_id']);
        unset($_SESSION['pending_seats']);
        unset($_SESSION['pending_screening']);
        
        // Sikeres oldal megjelenítése
        $success_message = "Sikeres fizetés és foglalás!";
        $ticket_count = count($selected_seats);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Refund, ha hiba történt
        \Stripe\Refund::create([
            'payment_intent' => $session->payment_intent,
        ]);
        
        $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE id = ?");
        $stmt->execute([PAYMENT_STATUS_FAILED, $payment['id']]);
        
        die("Hiba történt a jegyek rögzítésekor! A fizetett összeget visszautaltuk.");
    }
    
} catch (Exception $e) {
    die("Hiba történt: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sikeres foglalás - <?php echo APP_NAME; ?></title>
    <style>
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
            padding: 40px;
            background: linear-gradient(135deg, #F9F9F9, #F5F5F5);
            border-radius: 10px;
            border: 1px solid #F57272;
            box-shadow: 0 8px 20px rgba(135, 47, 47, 0.15);
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .success-title {
            color: #380A0A;
            font-size: 28px;
            margin-bottom: 20px;
            font-family: "Poppins", sans-serif;
        }
        
        .success-message {
            color: #6C0808;
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .ticket-summary {
            background: rgba(255, 107, 107, 0.1);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #FF6B6B;
            text-align: left;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #FF6B6B, #D23A3A);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin: 5px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(210, 58, 58, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #545b62);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1 class="success-title">Sikeres foglalás!</h1>
        
        <div class="success-message">
            <?php echo $success_message; ?>
        </div>
        
        <div class="ticket-summary">
            <h3 style="color: #380A0A; margin-bottom: 15px;">Foglalás részletei:</h3>
            <p style="color: #6C0808; margin-bottom: 10px;">
                <i class="fas fa-ticket-alt"></i> 
                <strong><?php echo $ticket_count; ?> db jegy</strong>
            </p>
            <p style="color: #6C0808; margin-bottom: 10px;">
                <i class="fas fa-chair"></i> 
                Helyek: <?php echo implode(', ', $selected_seats); ?>
            </p>
            <p style="color: #6C0808; margin-bottom: 10px;">
                <i class="fas fa-credit-card"></i> 
                Fizetett összeg: <strong><?php echo number_format($payment['amount'], 0, ',', ' '); ?> Ft</strong>
            </p>
            <p style="color: #6C0808; margin-bottom: 0;">
                <i class="fas fa-clock"></i> 
                Foglalás időpontja: <?php echo date('Y.m.d. H:i'); ?>
            </p>
        </div>
        
        <div>
            <a href="tickets.php" class="btn">
                <i class="fas fa-ticket-alt"></i> Jegyeim megtekintése
            </a>
            <a href="screenings.php" class="btn btn-secondary">
                <i class="fas fa-film"></i> Tovább a műsorra
            </a>
        </div>
        
        <p style="color: #872F2F; margin-top: 30px; font-size: 14px;">
            <i class="fas fa-envelope"></i> 
            A jegyeket emailben is elküldtük a(z) <?php echo htmlspecialchars($current_user['email']); ?> címre.
        </p>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>