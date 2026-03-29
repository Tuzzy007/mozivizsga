<?php
require_once 'config.php';
$page_title = "Bejelentkezés";

// Bejelentkezési logika
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if(empty($username) || empty($password)) {
        $errors[] = "Minden mező kitöltése kötelező!";
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Hibás felhasználónév vagy jelszó!";
        }
    }
}

// Oldal tartalma
ob_start();
?>
    <head>
        <link rel="stylesheet" href="css/login.css">
    </head>
    <div class="container">
        <div class="auth-container">
            <div class="auth-box">
                <h2 class="auth-title">Bejelentkezés</h2>
                
                <?php if(isset($errors) && !empty($errors)): ?>
                    <div class="error">
                        <?php foreach($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Felhasználónév vagy Email</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Jelszó</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width:100%;">Bejelentkezés</button>
                </form>
                
                <div class="auth-link">
                    <p>Nincs még fiókja? <a href="register.php">Regisztráljon most!</a></p>
                </div>
            </div>
        </div>
    </div>
<?php
$page_content = ob_get_clean();

// Header betöltése
include 'header.php';

// Oldal tartalmának kiírása
echo $page_content;

// Footer betöltése
include 'footer.php';
?>