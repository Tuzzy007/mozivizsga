<?php
require_once 'config.php';
$page_title = "Regisztráció";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $full_name = trim($_POST['full_name']);
    
    $errors = [];
    
    if(empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $errors[] = "Minden mező kitöltése kötelező!";
    }
    
    if($password !== $password_confirm) {
        $errors[] = "A jelszavak nem egyeznek!";
    }
    
    if(strlen($password) < 6) {
        $errors[] = "A jelszónak legalább 6 karakter hosszúnak kell lennie!";
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if($stmt->rowCount() > 0) {
            $errors[] = "A felhasználónév vagy email már foglalt!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
            if($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                $success = "Sikeres regisztráció! Most már bejelentkezhet.";
                header("refresh:2;url=login.php");
            } else {
                $errors[] = "Hiba történt a regisztráció során!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php include 'style.css'; ?>
        
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
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .auth-title {
            font-family: 'Poppins', sans-serif;
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
        
        .success {
            background: #2ed573;
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
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="auth-container">
            <div class="auth-box">
                <h2 class="auth-title">Regisztráció</h2>
                
                <?php if(isset($errors) && !empty($errors)): ?>
                    <div class="error">
                        <?php foreach($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($success)): ?>
                    <div class="success">
                        <p><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">Teljes név</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Felhasználónév</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email cím</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Jelszó</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Jelszó megerősítése</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width:100%;">Regisztráció</button>
                </form>
                
                <div class="auth-link">
                    <p>Már van fiókja? <a href="login.php">Jelentkezzen be!</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>