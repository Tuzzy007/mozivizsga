<?php
session_start();

// Adatbázis kapcsolat
$host = 'localhost';
$dbname = 'mozivizsga26';
$username = 'mozivizsga26';
$password = 'Mozivizsga2026!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Adatbázis kapcsolódási hiba: " . $e->getMessage());
}

// Alkalmazás konfiguráció
define('APP_NAME', 'SzalkaCinema');
define('BASE_URL', 'https://szalkacinema.hu/');

// Jelenlegi felhasználó lekérdezése
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

$current_user = getCurrentUser();

// Napi random vetítések generálása
function generateDailyScreenings($pdo) {
    try {
        // Aktív filmek lekérése
        $stmt = $pdo->query("SELECT id FROM movies WHERE active = 1");
        $movies = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($movies) < 5) {
            return false; // Nincs elég film
        }
        
        $today = date('Y-m-d');
        $generated = false;
        
        // Ellenőrizzük, hogy vannak-e már vetítések a mai napra
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM screenings WHERE DATE(screening_date) = ?");
        $stmt->execute([$today]);
        $count = $stmt->fetchColumn();
        
        // Ha nincs 5 vetítés a mai napra, generáljunk újat
        if ($count < 5) {
            // Töröljük a mai vetítéseket
            $stmt = $pdo->prepare("DELETE FROM screenings WHERE DATE(screening_date) = ?");
            $stmt->execute([$today]);
            
            // Véletlenszerű filmek kiválasztása
            shuffle($movies);
            $selected_movies = array_slice($movies, 0, 5);
            
            // Időpontok
            $times = ['14:00:00', '16:30:00', '19:00:00', '21:30:00', '23:45:00'];
            
            // Vetítések beszúrása
            $insert_stmt = $pdo->prepare("
                INSERT INTO screenings (movie_id, screening_date, screening_time, hall_number, price, available_seats) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            shuffle($times);
            
            for ($i = 0; $i < 5; $i++) {
                $hall_number = rand(1, 5);
                $price = 2200;
                $available_seats = 120;
                
                $insert_stmt->execute([
                    $selected_movies[$i],
                    $today,
                    $times[$i],
                    $hall_number,
                    $price,
                    $available_seats
                ]);
            }
            
            $generated = true;
        }
        
        return $generated;
    } catch (Exception $e) {
        error_log("Hiba a vetítések generálása során: " . $e->getMessage());
        return false;
    }
}

// Heti műsor generálása (7 napra előre)
function generateWeeklyScreenings($pdo) {
    try {
        $generated = false;
        
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            
            // Ellenőrizzük, hogy vannak-e már vetítések erre a napra
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM screenings WHERE DATE(screening_date) = ?");
            $stmt->execute([$date]);
            $count = $stmt->fetchColumn();
            
            // Ha nincs 5 vetítés, generáljuk
            if ($count < 5) {
                // Aktív filmek lekérése
                $movie_stmt = $pdo->query("SELECT id FROM movies WHERE active = 1");
                $movies = $movie_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (count($movies) < 5) {
                    continue;
                }
                
                // Töröljük a meglévő vetítéseket erre a napra
                $stmt = $pdo->prepare("DELETE FROM screenings WHERE DATE(screening_date) = ?");
                $stmt->execute([$date]);
                
                // Véletlenszerű filmek kiválasztása
                shuffle($movies);
                $selected_movies = array_slice($movies, 0, 5);
                
                // Időpontok
                $times = ['14:00:00', '16:30:00', '19:00:00', '21:30:00', '23:45:00'];
                
                // Vetítések beszúrása
                $insert_stmt = $pdo->prepare("
                    INSERT INTO screenings (movie_id, screening_date, screening_time, hall_number, price, available_seats) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                shuffle($times);
                
                for ($j = 0; $j < 5; $j++) {
                    $hall_number = rand(1, 5);
                    $price = 2200;
                    $available_seats = 120;  
                    
                    $insert_stmt->execute([
                        $selected_movies[$j],
                        $date,
                        $times[$j],
                        $hall_number,
                        $price,
                        $available_seats
                    ]);
                }
                
                $generated = true;
            }
        }
        
        return $generated;
    } catch (Exception $e) {
        error_log("Hiba a heti vetítések generálása során: " . $e->getMessage());
        return false;
    }
}

define('STRIPE_PUBLISHABLE_KEY', 'STRIPE_PUBLISHABLE_KEY'); 
define('STRIPE_SECRET_KEY', 'STRIPE_SECRET_KEY');  
define('STRIPE_WEBHOOK_SECRET', 'whsec_...'); 

// Fizetési státuszok
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_PAID', 'paid');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_REFUNDED', 'refunded');

// Stripe inicializálás
require_once 'vendor/autoload.php';
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Minden oldalbetöltéskor generáljuk a mai vetítéseket, ha kell
generateDailyScreenings($pdo);
?>