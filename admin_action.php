<?php
require_once 'config.php';

// Csak adminnak engedélyezett
if(!$current_user || $current_user['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

if(isset($_GET['action'])) {
    if($_GET['action'] == 'delete_movie' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM screenings WHERE movie_id = ?");
        $stmt->execute([$id]);
        $has_screenings = $stmt->fetchColumn();
        
        if($has_screenings > 0) {
            $_SESSION['error'] = "Nem lehet törölni a filmet, mert vannak hozzá kapcsolódó vetítések!";
            header("Location: admin.php?tab=movies");
            exit();
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE movie_id = ?");
        $stmt->execute([$id]);
        $has_comments = $stmt->fetchColumn();
        
        if($has_comments > 0) {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE movie_id = ?");
            $stmt->execute([$id]);
        }
        
        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
        if($stmt->execute([$id])) {
            $_SESSION['message'] = "Film sikeresen törölve!";
        } else {
            $_SESSION['error'] = "Hiba történt a film törlése során!";
        }
        header("Location: admin.php?tab=movies");
        exit();
        
    } elseif($_GET['action'] == 'delete_screening' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE screening_id = ?");
        $stmt->execute([$id]);
        $has_tickets = $stmt->fetchColumn();
        
        if($has_tickets > 0) {
            $_SESSION['error'] = "Nem lehet törölni a vetítést, mert vannak hozzá kapcsolódó jegyek!";
            header("Location: admin.php?tab=screenings");
            exit();
        }
        
        $stmt = $pdo->prepare("DELETE FROM screenings WHERE id = ?");
        if($stmt->execute([$id])) {
            $_SESSION['message'] = "Vetítés sikeresen törölve!";
        } else {
            $_SESSION['error'] = "Hiba történt a vetítés törlése során!";
        }

        header("Location: admin.php?tab=screenings");

        exit();

    } elseif($_GET['action'] == 'delete_comment' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        if($stmt->execute([$id])) {
            $_SESSION['message'] = "Komment sikeresen törölve!";
        } else {
            $_SESSION['error'] = "Hiba történt a komment törlése során!";
        }
        header("Location: admin.php?tab=comments");
        exit();
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if($action == 'add_movie') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $director = trim($_POST['director']);
        $duration = intval($_POST['duration']);
        $release_year = !empty($_POST['release_year']) ? intval($_POST['release_year']) : NULL;
        $genre = trim($_POST['genre']);
        $rating = !empty($_POST['rating']) ? floatval($_POST['rating']) : NULL;
        $poster_url = trim($_POST['poster_url']);
        $trailer_url = trim($_POST['trailer_url']);
        
        $stmt = $pdo->prepare("
            INSERT INTO movies (title, description, director, duration, release_year, genre, rating, poster_url, trailer_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if($stmt->execute([$title, $description, $director, $duration, $release_year, $genre, $rating, $poster_url, $trailer_url])) {
            $_SESSION['message'] = "Film sikeresen hozzáadva!";
            header("Location: admin.php?tab=movies");
        } else {
            $_SESSION['error'] = "Hiba történt a film hozzáadása során!";
            header("Location: admin.php?tab=new_movie");
        }
        exit();
        
    } elseif($action == 'update_movie') {
        $id = intval($_POST['id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $director = trim($_POST['director']);
        $duration = intval($_POST['duration']);
        $release_year = !empty($_POST['release_year']) ? intval($_POST['release_year']) : NULL;
        $genre = trim($_POST['genre']);
        $rating = !empty($_POST['rating']) ? floatval($_POST['rating']) : NULL;
        $poster_url = trim($_POST['poster_url']);
        $trailer_url = trim($_POST['trailer_url']);
        $active = isset($_POST['active']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE movies SET 
                title = ?, 
                description = ?, 
                director = ?, 
                duration = ?, 
                release_year = ?, 
                genre = ?, 
                rating = ?, 
                poster_url = ?, 
                trailer_url = ?,
                active = ?
            WHERE id = ?
        ");
        
        if($stmt->execute([$title, $description, $director, $duration, $release_year, $genre, $rating, $poster_url, $trailer_url, $active, $id])) {
            $_SESSION['message'] = "Film sikeresen frissítve!";
            header("Location: admin.php?tab=movies");
        } else {
            $_SESSION['error'] = "Hiba történt a film frissítése során!";
            header("Location: admin.php?tab=edit_movie&id=" . $id);
        }
        exit();
        
    } elseif($action == 'update_screening') {
        $id = intval($_POST['id']);
        $movie_id = intval($_POST['movie_id']);
        $screening_date = $_POST['screening_date'];
        $screening_time = $_POST['screening_time'];
        $hall_number = intval($_POST['hall_number']);
        $price = floatval($_POST['price']);
        $available_seats = intval($_POST['available_seats']);
        
        $stmt = $pdo->prepare("
            UPDATE screenings SET 
                movie_id = ?, 
                screening_date = ?, 
                screening_time = ?, 
                hall_number = ?, 
                price = ?, 
                available_seats = ?
            WHERE id = ?
        ");
        
        if($stmt->execute([$movie_id, $screening_date, $screening_time, $hall_number, $price, $available_seats, $id])) {
            $_SESSION['message'] = "Vetítés sikeresen frissítve!";
            header("Location: admin.php?tab=screenings");
        } else {
            $_SESSION['error'] = "Hiba történt a vetítés frissítése során!";
            header("Location: admin.php?tab=edit_screening&id=" . $id);
        }
        exit();
        
    } elseif($action == 'update_user') {
        $id = intval($_POST['id']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $role = trim($_POST['role']);
        $new_password = trim($_POST['new_password']);
        
        // Ellenőrizd, hogy a felhasználónév vagy email már létezik-e másik felhasználónál
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $id]);
        
        if($stmt->rowCount() > 0) {
            $_SESSION['error'] = "A felhasználónév vagy email már foglalt!";
            header("Location: admin.php?tab=edit_user&id=" . $id);
            exit();
        }
        
        if(!empty($new_password)) {
            // Új jelszó beállítása
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    full_name = ?, 
                    role = ?,
                    password = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $full_name, $role, $hashed_password, $id]);
        } else {
            // Jelszó változatlanul marad
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    full_name = ?, 
                    role = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $full_name, $role, $id]);
        }
        
        $_SESSION['message'] = "Felhasználó sikeresen frissítve!";
        header("Location: admin.php?tab=users");
        exit();
    } elseif($action == 'add_screening') {
        $movie_id = intval($_POST['movie_id']);
        $screening_date = $_POST['screening_date'];
        $screening_time = $_POST['screening_time'];
        $hall_number = intval($_POST['hall_number']);
        $price = floatval($_POST['price']);
        $available_seats = intval($_POST['available_seats']);
        
        $stmt = $pdo->prepare("
            INSERT INTO screenings (movie_id, screening_date, screening_time, hall_number, price, available_seats) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if($stmt->execute([$movie_id, $screening_date, $screening_time, $hall_number, $price, $available_seats])) {
            $_SESSION['message'] = "Vetítés sikeresen hozzáadva!";
            header("Location: admin.php?tab=screenings");
        } else {
            $_SESSION['error'] = "Hiba történt a vetítés hozzáadása során!";
            header("Location: admin.php?tab=new_screening");
        }
        exit();
    }
}

header("Location: admin.php");
?>