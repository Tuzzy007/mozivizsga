<?php
require_once 'config.php';
$page_title = "Bejelentkezés";

// Oldal specifikus CSS
$additional_css = '
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 300px);
        padding: 2rem 0;
    }
    
    .auth-box {
        background: white;
        border-radius: 10px;
        padding: 3rem;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .auth-title {
        font-family: "Poppins", sans-serif;
        font-size: 2rem;
        color: #2f3542;
        margin-bottom: 1.5rem;
        text-align: center;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #57606f;
        font-weight: 500;
    }
    
    .form-group input {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    
    .form-group input:focus {
        border-color: #3742fa;
        outline: none;
    }
    
    .error {
        background: #ff6b81;
        color: white;
        padding: 0.8rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    
    .auth-link {
        text-align: center;
        margin-top: 1.5rem;
        color: #57606f;
    }
    
    .auth-link a {
        color: #3742fa;
        text-decoration: none;
        font-weight: 500;
    }
    
    .auth-link a:hover {
        text-decoration: underline;
    }
';

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