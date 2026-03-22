<?php
require_once 'config.php';

// Csak adminnak engedélyezett
if(!$current_user || $current_user['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$page_title = "Admin felület";
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Statisztikák
$stats = [
    'total_movies' => $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_screenings' => $pdo->query("SELECT COUNT(*) FROM screenings WHERE screening_date >= CURDATE()")->fetchColumn(),
    'total_tickets' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'active'")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(price_paid) FROM tickets WHERE status = 'active'")->fetchColumn()
];

// Szerkesztéshez film adatok betöltése
$edit_movie = null;
if($active_tab == 'edit_movie' && isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$movie_id]);
    $edit_movie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$edit_movie) {
        header("Location: admin.php?tab=movies");
        exit();
    }
}

if(isset($_SESSION['message'])) {
    $success_message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if(isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
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
        .admin-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            min-height: calc(100vh - 300px);
        }

        /* Admin Sidebar */
        .admin-sidebar {
            background: linear-gradient(135deg, #F9F9F9, #F5F5F5);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 8px 20px rgba(135, 47, 47, 0.15);
            height: fit-content;
            border: 1px solid #F57272;
            position: sticky;
            top: 20px;
        }

        .admin-sidebar h3 {
            font-family: 'Poppins', sans-serif;
            color: #380A0A;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #D49E9E;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-sidebar h3 i {
            color: #D23A3A;
        }

        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .admin-nav-item {
            padding: 0.8rem 1rem;
            color: #6C0808;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid transparent;
            cursor: pointer;
            background: transparent;
            width: 100%;
            text-align: left;
            font-size: 1rem;
            font-weight: 500;
        }

        .admin-nav-item i {
            width: 24px;
            font-size: 1.1rem;
            color: #D23A3A;
            transition: all 0.3s ease;
        }

        .admin-nav-item:hover {
            background: linear-gradient(135deg, #F57272, #D49E9E);
            color: #380A0A;
            border-color: #F57272;
            transform: translateX(5px);
        }

        .admin-nav-item:hover i {
            color: #380A0A;
        }

        .admin-nav-item.active {
            background: linear-gradient(135deg, #FF6B6B, #D23A3A);
            color: white;
            border: 1px solid #EA3232;
            box-shadow: 0 4px 8px rgba(210, 58, 58, 0.3);
        }

        .admin-nav-item.active i {
            color: white;
        }

        /* Admin Content */
        .admin-content {
            background: linear-gradient(135deg, #F9F9F9, #F5F5F5);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 8px 20px rgba(135, 47, 47, 0.15);
            border: 1px solid #F57272;
            overflow-x: auto;
        }

        .admin-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            color: #380A0A;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #D23A3A;
            display: inline-block;
        }

        /* Admin Header */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* Statisztika kártyák */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            border-top: 4px solid #FF6B6B;
            box-shadow: 0 5px 15px rgba(210, 58, 58, 0.1);
            transition: transform 0.3s ease;
            border: 1px solid #F57272;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(210, 58, 58, 0.2);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #FF6B6B, #F57272);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #380A0A;
            margin: 0.5rem 0;
            font-family: 'Poppins', sans-serif;
        }

        .stat-label {
            color: #872F2F;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Admin táblázatok */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(135, 47, 47, 0.1);
            border: 1px solid #F57272;
        }

        .admin-table th,
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #D49E9E;
        }

        .admin-table th {
            background: linear-gradient(135deg, #380A0A, #6C0808);
            color: white;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }

        .admin-table tr:hover {
            background: rgba(255, 107, 107, 0.05);
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        /* Akció gombok */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.5rem 0.8rem;
            font-size: 0.9rem;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-edit {
            background: linear-gradient(135deg, #F57272, #D49E9E);
            color: #380A0A;
            border: 1px solid #F57272;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #D49E9E, #F57272);
            transform: translateY(-2px);
        }

        .btn-delete {
            background: linear-gradient(135deg, #EA3232, #6C0808);
            color: white;
            border: 1px solid #EA3232;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #872F2F, #6C0808);
            transform: translateY(-2px);
        }

        /* Űrlap elemek */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #380A0A;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #D49E9E;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: #000000;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FF6B6B;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Státusz címkék */
        .status-active {
            color: #872F2F;
            font-weight: 600;
            background: rgba(135, 47, 47, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            display: inline-block;
        }

        .status-inactive {
            color: #6C0808;
            font-weight: 600;
            background: rgba(108, 8, 8, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            display: inline-block;
        }

        /* Értékelés csillagok */
        .rating-stars {
            color: #FF6B6B;
        }

        /* Gombok */
        .admin-content .btn-primary {
            background: linear-gradient(135deg, #FF6B6B, #D23A3A);
            border: none;
            padding: 0.8rem 1.5rem;
            transition: all 0.3s ease;
            color: white;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-content .btn-primary:hover {
            background: linear-gradient(135deg, #EA3232, #872F2F);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 8, 8, 0.4);
        }

        .admin-content .btn-secondary {
            background: linear-gradient(135deg, #F57272, #D49E9E);
            color: #380A0A;
            border: 1px solid #F57272;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 6px;
            font-weight: 600;
        }

        .admin-content .btn-secondary:hover {
            background: linear-gradient(135deg, #D49E9E, #F57272);
        }

        /* Üres állapot */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6C0808;
        }

        .empty-state i {
            font-size: 3rem;
            color: #F57272;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: #380A0A;
        }

        /* Kapcsoló gomb */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #2ed573;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        /* Üzenetek */
        .success-message {
            background: #2ed573;
            color: white;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .error-message {
            background: #ff4757;
            color: white;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        /* ==================== RESZPONZÍV DESIGN ==================== */

        /* Tablet (768px - 1024px) */
        @media (max-width: 1024px) {
            .admin-container {
                grid-template-columns: 260px 1fr;
                gap: 1.5rem;
            }

            .admin-sidebar h3 {
                font-size: 1.2rem;
            }

            .admin-nav-item {
                padding: 0.7rem 0.9rem;
                font-size: 0.95rem;
            }
        }

        /* Mobil (768px alatt) - itt történik a reszponzív átalakítás */
        @media (max-width: 768px) {
            .admin-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            /* Sidebar - elveszti a sticky tulajdonságot, normál flow */
            .admin-sidebar {
                position: static;
                padding: 1.2rem;
            }

            .admin-sidebar h3 {
                font-size: 1.2rem;
                margin-bottom: 1rem;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            /* Admin navigáció - Mobilban görgethető, de megmarad függőleges */
            .admin-nav {
                display: flex;
                flex-direction: column;
                gap: 0.6rem;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease-out;
                opacity: 0;
            }

            /* Aktív állapotban látszik a menü */
            .admin-sidebar.active .admin-nav {
                max-height: 500px;
                opacity: 1;
            }

            /* Alapértelmezetten nyitva a menü (ha nincs JS, akkor is látszik) */
            .admin-nav {
                max-height: 500px;
                opacity: 1;
            }

            .admin-nav-item {
                padding: 0.7rem 1rem;
                font-size: 0.95rem;
            }

            .admin-nav-item:hover {
                transform: translateX(0);
                transform: translateY(-2px);
            }

            /* Admin content */
            .admin-content {
                padding: 1.2rem;
            }

            /* Táblázatok mobilban görgethetők */
            .admin-table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                white-space: nowrap;
            }

            .admin-table th,
            .admin-table td {
                padding: 0.8rem;
                font-size: 0.85rem;
            }

            /* Űrlap mobilban */
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            /* Statisztika kártyák */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .admin-header .btn {
                width: 100%;
                justify-content: center;
            }

            .action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }

        /* Kis mobil (480px alatt) */
        @media (max-width: 480px) {
            .admin-sidebar {
                padding: 1rem;
            }

            .admin-sidebar h3 {
                font-size: 1.1rem;
            }

            .admin-nav-item {
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
            }

            .admin-nav-item i {
                width: 20px;
                font-size: 1rem;
            }

            .admin-content {
                padding: 1rem;
            }

            .admin-title {
                font-size: 1.3rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }

            .stat-card {
                display: flex;
                align-items: center;
                justify-content: space-between;
                text-align: left;
                padding: 0.8rem 1rem;
            }

            .stat-card i {
                margin-bottom: 0;
                font-size: 1.5rem;
            }

            .stat-card > div {
                text-align: right;
            }

            .stat-value {
                font-size: 1.3rem;
                margin: 0;
            }

            .admin-table th,
            .admin-table td {
                padding: 0.6rem;
                font-size: 0.75rem;
            }

            .btn-sm {
                padding: 0.35rem 0.5rem;
                font-size: 0.75rem;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
        }

        /* Extra nagy desktop (1200px felett) */
        @media (min-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <main class="main-content">
            <div class="page-header">
                <h1 class="section-title">Admin felület</h1>
            </div>
            
            <div class="admin-container">
                <!-- Admin Sidebar - DIV alapú navigáció -->
                <div class="admin-sidebar">
                    <h3>
                        <i class="fas fa-cog"></i> 
                        Admin Menü
                    </h3>
                    <div class="admin-nav">
                        <div class="admin-nav-item <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>" 
                             onclick="window.location.href='?tab=dashboard'">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </div>
                        <div class="admin-nav-item <?php echo $active_tab == 'movies' ? 'active' : ''; ?>" 
                             onclick="window.location.href='?tab=movies'">
                            <i class="fas fa-film"></i> Filmek
                        </div>
                        <div class="admin-nav-item <?php echo $active_tab == 'screenings' ? 'active' : ''; ?>" 
                             onclick="window.location.href='?tab=screenings'">
                            <i class="fas fa-calendar-alt"></i> Vetítések
                        </div>
                        <div class="admin-nav-item <?php echo $active_tab == 'users' ? 'active' : ''; ?>" 
                             onclick="window.location.href='?tab=users'">
                            <i class="fas fa-users"></i> Felhasználók
                        </div>
                        <div class="admin-nav-item <?php echo $active_tab == 'tickets' ? 'active' : ''; ?>" 
                             onclick="window.location.href='?tab=tickets'">
                            <i class="fas fa-ticket-alt"></i> Jegyek
                        </div>
                        <div class="admin-nav-item <?php echo $active_tab == 'comments' ? 'active' : ''; ?>" 
                             onclick="window.location.href='?tab=comments'">
                            <i class="fas fa-comments"></i> Kommentek
                        </div>
                    </div>
                </div>
                
                <div class="admin-content">
                    <?php if(isset($success_message)): ?>
                        <div class="success-message">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
    
                    <?php if($active_tab == 'dashboard'): ?>
                        <h2 class="admin-title">Dashboard</h2>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <i class="fas fa-film"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['total_movies']; ?></div>
                                    <div class="stat-label">Aktív filmek</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                                    <div class="stat-label">Regisztrált felhasználó</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-calendar-alt"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['total_screenings']; ?></div>
                                    <div class="stat-label">Következő vetítés</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-ticket-alt"></i>
                                <div>
                                    <div class="stat-value"><?php echo $stats['total_tickets']; ?></div>
                                    <div class="stat-label">Aktív jegy</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>
                                    <div class="stat-value"><?php echo number_format($stats['revenue'] ?? 0, 0, ',', ' '); ?> Ft</div>
                                    <div class="stat-label">Bevétel</div>
                                </div>
                            </div>
                        </div>
                        
                    <?php elseif($active_tab == 'movies'): ?>
                        <div class="admin-header">
                            <h2 class="admin-title">Filmek kezelése</h2>
                            <a href="?tab=new_movie" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Új film hozzáadása
                            </a>
                        </div>

                        <?php
                        $stmt = $pdo->query("SELECT * FROM movies ORDER BY id DESC");
                        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if(count($movies) > 0): ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cím</th>
                                        <th>Rendező</th>
                                        <th>Év</th>
                                        <th>Értékelés</th>
                                        <th>Státusz</th>
                                        <th>Műveletek</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($movies as $movie): ?>
                                    <tr>
                                        <td><?php echo $movie['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($movie['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($movie['director']); ?></td>
                                        <td><?php echo $movie['release_year']; ?></td>
                                        <td>
                                            <span class="rating-stars">
                                                <i class="fas fa-star"></i> <?php echo $movie['rating']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="<?php echo $movie['active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $movie['active'] ? 'Aktív' : 'Inaktív'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?tab=edit_movie&id=<?php echo $movie['id']; ?>" class="btn-sm btn-edit">
                                                    <i class="fas fa-edit"></i> Szerkesztés
                                                </a>
                                                <a href="admin_action.php?action=delete_movie&id=<?php echo $movie['id']; ?>" 
                                                   class="btn-sm btn-delete"
                                                   onclick="return confirm('Biztosan törölni szeretné ezt a filmet?')">
                                                    <i class="fas fa-trash"></i> Törlés
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-film"></i>
                                <h3>Nincsenek filmek</h3>
                                <p>Még nincsenek filmek az adatbázisban.</p>
                            </div>
                        <?php endif; ?>
                        
                    <?php elseif($active_tab == 'edit_movie' && $edit_movie): ?>
                        <div class="admin-header">
                            <h2 class="admin-title">Film szerkesztése: <?php echo htmlspecialchars($edit_movie['title']); ?></h2>
                            <a href="?tab=movies" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Vissza
                            </a>
                        </div>
                        
                        <form action="admin_action.php" method="POST">
                            <input type="hidden" name="action" value="update_movie">
                            <input type="hidden" name="id" value="<?php echo $edit_movie['id']; ?>">
                            
                            <div class="form-group">
                                <label for="title">Film címe *</label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($edit_movie['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Leírás *</label>
                                <textarea id="description" name="description" required><?php echo htmlspecialchars($edit_movie['description']); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="director">Rendező</label>
                                    <input type="text" id="director" name="director" value="<?php echo htmlspecialchars($edit_movie['director']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="duration">Időtartam (perc) *</label>
                                    <input type="number" id="duration" name="duration" value="<?php echo $edit_movie['duration']; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="release_year">Megjelenés éve</label>
                                    <input type="number" id="release_year" name="release_year" min="1900" max="2030" value="<?php echo $edit_movie['release_year']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="genre">Műfaj</label>
                                    <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($edit_movie['genre']); ?>" placeholder="pl.: Akció, Dráma, Sci-Fi">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="rating">Értékelés (1-10)</label>
                                    <input type="number" id="rating" name="rating" min="1" max="10" step="0.1" value="<?php echo $edit_movie['rating']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="poster_url">Poszter URL</label>
                                    <input type="url" id="poster_url" name="poster_url" value="<?php echo htmlspecialchars($edit_movie['poster_url']); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="trailer_url">Trailer URL</label>
                                <input type="url" id="trailer_url" name="trailer_url" value="<?php echo htmlspecialchars($edit_movie['trailer_url']); ?>">
                            </div>
                            
                            <div class="checkbox-group">
                                <label for="active">Aktív:</label>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="active" name="active" value="1" <?php echo $edit_movie['active'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap;">
                                <button type="submit" class="btn btn-primary">Mentés</button>
                                <a href="?tab=movies" class="btn btn-secondary">Mégse</a>
                            </div>
                        </form>
                        
                    <?php elseif($active_tab == 'screenings'): ?>
                        <div class="admin-header">
                            <h2 class="admin-title">Vetítések kezelése</h2>
                            <a href="?tab=new_screening" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Új vetítés hozzáadása
                            </a>
                        </div>

                        <?php
                        $stmt = $pdo->query("
                            SELECT s.*, m.title as movie_title 
                            FROM screenings s 
                            JOIN movies m ON s.movie_id = m.id 
                            ORDER BY s.screening_date DESC, s.screening_time DESC
                        ");
                        $screenings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if(count($screenings) > 0): ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Film</th>
                                        <th>Dátum</th>
                                        <th>Idő</th>
                                        <th>Terem</th>
                                        <th>Ár</th>
                                        <th>Szabad helyek</th>
                                        <th>Műveletek</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($screenings as $screening): ?>
                                    <tr>
                                        <td><?php echo $screening['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($screening['movie_title']); ?></strong></td>
                                        <td><?php echo date('Y.m.d.', strtotime($screening['screening_date'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($screening['screening_time'])); ?></td>
                                        <td><?php echo $screening['hall_number']; ?></td>
                                        <td><strong><?php echo number_format($screening['price'], 0, ',', ' '); ?> Ft</strong></td>
                                        <td><?php echo $screening['available_seats']; ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?tab=edit_screening&id=<?php echo $screening['id']; ?>" class="btn-sm btn-edit">
                                                    <i class="fas fa-edit"></i> Szerkesztés
                                                </a>
                                                <a href="admin_action.php?action=delete_screening&id=<?php echo $screening['id']; ?>" 
                                                   class="btn-sm btn-delete"
                                                   onclick="return confirm('Biztosan törölni szeretné ezt a vetítést?')">
                                                    <i class="fas fa-trash"></i> Törlés
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-alt"></i>
                                <h3>Nincsenek vetítések</h3>
                                <p>Még nincsenek vetítések az adatbázisban.</p>
                            </div>
                        <?php endif; ?>
                        
                    <?php elseif($active_tab == 'users'): ?>
                        <h2 class="admin-title">Felhasználók kezelése</h2>
                        
                        <?php
                        $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
                        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if(count($users) > 0): ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Felhasználónév</th>
                                        <th>Teljes név</th>
                                        <th>Email</th>
                                        <th>Szerepkör</th>
                                        <th>Regisztráció dátuma</th>
                                        <th>Műveletek</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="<?php echo $user['role'] == 'admin' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $user['role'] == 'admin' ? 'Admin' : 'Felhasználó'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y.m.d.', strtotime($user['registration_date'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?tab=edit_user&id=<?php echo $user['id']; ?>" class="btn-sm btn-edit">
                                                    <i class="fas fa-edit"></i> Szerkesztés
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <h3>Nincsenek felhasználók</h3>
                                <p>Még nincsenek felhasználók az adatbázisban.</p>
                            </div>
                        <?php endif; ?>
                        
                    <?php elseif($active_tab == 'tickets'): ?>
                        <h2 class="admin-title">Jegyek kezelése</h2>
                        
                        <?php
                        $stmt = $pdo->query("
                            SELECT t.*, u.username, u.full_name, m.title as movie_title,
                                   s.screening_date, s.screening_time
                            FROM tickets t
                            JOIN users u ON t.user_id = u.id
                            JOIN screenings s ON t.screening_id = s.id
                            JOIN movies m ON s.movie_id = m.id
                            ORDER BY t.purchase_date DESC
                        ");
                        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if(count($tickets) > 0): ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Felhasználó</th>
                                        <th>Film</th>
                                        <th>Dátum/Idő</th>
                                        <th>Helyszám</th>
                                        <th>Ár</th>
                                        <th>Státusz</th>
                                        <th>Vásárlás dátuma</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($tickets as $ticket): ?>
                                    <tr>
                                        <td><?php echo $ticket['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($ticket['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($ticket['movie_title']); ?></td>
                                        <td>
                                            <?php echo date('Y.m.d.', strtotime($ticket['screening_date'])); ?><br>
                                            <?php echo date('H:i', strtotime($ticket['screening_time'])); ?>
                                        </td>
                                        <td><?php echo $ticket['seat_number']; ?></td>
                                        <td><strong><?php echo number_format($ticket['price_paid'], 0, ',', ' '); ?> Ft</strong></td>
                                        <td>
                                            <?php
                                            $status_classes = [
                                                'active' => 'status-active',
                                                'used' => 'status-inactive',
                                                'cancelled' => 'status-inactive',
                                                'expired' => 'status-inactive'
                                            ];
                                            
                                            $status_text = [
                                                'active' => 'Aktív',
                                                'used' => 'Felhasznált',
                                                'cancelled' => 'Törölt',
                                                'expired' => 'Lejárt'
                                            ];
                                            ?>
                                            <span class="<?php echo $status_classes[$ticket['status']] ?? 'status-inactive'; ?>">
                                                <?php echo $status_text[$ticket['status']] ?? $ticket['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y.m.d. H:i', strtotime($ticket['purchase_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-ticket-alt"></i>
                                <h3>Nincsenek jegyek</h3>
                                <p>Még nincsenek jegyek az adatbázisban.</p>
                            </div>
                        <?php endif; ?>

                    <?php elseif($active_tab == 'edit_screening' && isset($_GET['id'])): ?>
                        <?php
                        $screening_id = intval($_GET['id']);
                        $stmt = $pdo->prepare("SELECT * FROM screenings WHERE id = ?");
                        $stmt->execute([$screening_id]);
                        $screening = $stmt->fetch(PDO::FETCH_ASSOC);

                        if(!$screening) {
                            header("Location: admin.php?tab=screenings");
                            exit();
                        }

                        // Film címe
                        $stmt = $pdo->prepare("SELECT title FROM movies WHERE id = ?");
                        $stmt->execute([$screening['movie_id']]);
                        $movie_title = $stmt->fetchColumn();
                        ?>

                        <div class="admin-header">
                            <h2 class="admin-title">Vetítés szerkesztése</h2>
                            <a href="?tab=screenings" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Vissza
                            </a>
                        </div>

                        <form action="admin_action.php" method="POST">
                            <input type="hidden" name="action" value="update_screening">
                            <input type="hidden" name="id" value="<?php echo $screening['id']; ?>">

                            <div class="form-group">
                                <label for="movie_id">Film *</label>
                                <select id="movie_id" name="movie_id" required>
                                    <option value="">Válasszon filmet</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT id, title FROM movies WHERE active = 1 ORDER BY title");
                                    while($movie = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <option value="<?php echo $movie['id']; ?>" <?php echo $movie['id'] == $screening['movie_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                                    
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="screening_date">Dátum *</label>
                                    <input type="date" id="screening_date" name="screening_date" 
                                           value="<?php echo $screening['screening_date']; ?>" required>
                                </div>
                                    
                                <div class="form-group">
                                    <label for="screening_time">Idő *</label>
                                    <input type="time" id="screening_time" name="screening_time" 
                                           value="<?php echo date('H:i', strtotime($screening['screening_time'])); ?>" required>
                                </div>
                            </div>
                                    
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="hall_number">Terem száma *</label>
                                    <input type="number" id="hall_number" name="hall_number" 
                                           value="<?php echo $screening['hall_number']; ?>" min="1" max="10" required>
                                </div>
                                    
                                <div class="form-group">
                                    <label for="price">Jegyár (Ft) *</label>
                                    <input type="number" id="price" name="price" 
                                           value="<?php echo $screening['price']; ?>" min="0" step="100" required>
                                </div>
                            </div>
                                    
                            <div class="form-group">
                                <label for="available_seats">Szabad helyek száma</label>
                                <input type="number" id="available_seats" name="available_seats" 
                                       value="<?php echo $screening['available_seats']; ?>" min="1" max="200">
                            </div>
                                    
                            <div style="display: flex; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap;">
                                <button type="submit" class="btn btn-primary">Mentés</button>
                                <a href="?tab=screenings" class="btn btn-secondary">Mégse</a>
                            </div>
                        </form>    
                    
                    <?php elseif($active_tab == 'edit_user' && isset($_GET['id'])): ?>
                        <?php
                        $user_id = intval($_GET['id']);
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                                            
                        if(!$user) {
                            header("Location: admin.php?tab=users");
                            exit();
                        }
                        ?>
                        
                        <div class="admin-header">
                            <h2 class="admin-title">Felhasználó szerkesztése: <?php echo htmlspecialchars($user['full_name']); ?></h2>
                            <a href="?tab=users" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Vissza
                            </a>
                        </div>
                                            
                        <form action="admin_action.php" method="POST">
                            <input type="hidden" name="action" value="update_user">
                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            
                            <div class="form-group">
                                <label for="username">Felhasználónév *</label>
                                <input type="text" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                                            
                            <div class="form-group">
                                <label for="email">Email cím *</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                                            
                            <div class="form-group">
                                <label for="full_name">Teljes név *</label>
                                <input type="text" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                                            
                            <div class="form-group">
                                <label for="role">Szerepkör *</label>
                                <select id="role" name="role" required>
                                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>Felhasználó</option>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Adminisztrátor</option>
                                </select>
                            </div>
                                            
                            <div class="form-group">
                                <label for="new_password">Új jelszó (hagyd üresen, ha nem akarod változtatni)</label>
                                <input type="password" id="new_password" name="new_password">
                                <small style="color: #666; font-size: 0.9rem;">Csak akkor töltse ki, ha meg szeretné változtatni a jelszót</small>
                            </div>
                                            
                            <div style="display: flex; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap;">
                                <button type="submit" class="btn btn-primary">Mentés</button>
                                <a href="?tab=users" class="btn btn-secondary">Mégse</a>
                            </div>
                        </form>
                
                    <?php elseif($active_tab == 'comments'): ?>
                        <h2 class="admin-title">Kommentek kezelése</h2>
                        
                        <?php
                        $stmt = $pdo->query("
                            SELECT c.*, u.username, u.full_name, m.title as movie_title
                            FROM comments c
                            JOIN users u ON c.user_id = u.id
                            JOIN movies m ON c.movie_id = m.id
                            ORDER BY c.comment_date DESC
                        ");
                        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if(count($comments) > 0): ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Felhasználó</th>
                                        <th>Film</th>
                                        <th>Értékelés</th>
                                        <th>Komment</th>
                                        <th>Dátum</th>
                                        <th>Műveletek</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($comments as $comment): ?>
                                    <tr>
                                        <td><?php echo $comment['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($comment['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($comment['movie_title']); ?></td>
                                        <td>
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <?php if($i <= $comment['rating']): ?>
                                                    <i class="fas fa-star rating-stars"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star rating-stars"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($comment['comment'], 0, 100)); ?>...</td>
                                        <td><?php echo date('Y.m.d.', strtotime($comment['comment_date'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="admin_action.php?action=delete_comment&id=<?php echo $comment['id']; ?>" 
                                                   class="btn-sm btn-delete"
                                                   onclick="return confirm('Biztosan törölni szeretné ezt a kommentet?')">
                                                    <i class="fas fa-trash"></i> Törlés
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <h3>Nincsenek kommentek</h3>
                                <p>Még nincsenek kommentek az adatbázisban.</p>
                            </div>
                        <?php endif; ?>
                        
                    <?php elseif($active_tab == 'new_movie'): ?>
                        <div class="admin-header">
                            <h2 class="admin-title">Új film hozzáadása</h2>
                            <a href="?tab=movies" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Vissza
                            </a>
                        </div>
                        
                        <form action="admin_action.php" method="POST">
                            <input type="hidden" name="action" value="add_movie">
                            
                            <div class="form-group">
                                <label for="title">Film címe *</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Leírás *</label>
                                <textarea id="description" name="description" required></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="director">Rendező</label>
                                    <input type="text" id="director" name="director">
                                </div>
                                
                                <div class="form-group">
                                    <label for="duration">Időtartam (perc) *</label>
                                    <input type="number" id="duration" name="duration" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="release_year">Megjelenés éve</label>
                                    <input type="number" id="release_year" name="release_year" min="1900" max="2030">
                                </div>
                                
                                <div class="form-group">
                                    <label for="genre">Műfaj</label>
                                    <input type="text" id="genre" name="genre" placeholder="pl.: Akció, Dráma, Sci-Fi">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="rating">Értékelés (1-10)</label>
                                    <input type="number" id="rating" name="rating" min="1" max="10" step="0.1">
                                </div>
                                
                                <div class="form-group">
                                    <label for="poster_url">Poszter URL</label>
                                    <input type="url" id="poster_url" name="poster_url">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="trailer_url">Trailer URL</label>
                                <input type="url" id="trailer_url" name="trailer_url">
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap;">
                                <button type="submit" class="btn btn-primary">Film hozzáadása</button>
                                <a href="?tab=movies" class="btn btn-secondary">Mégse</a>
                            </div>
                        </form>
                        
                    <?php elseif($active_tab == 'new_screening'): ?>
                        <div class="admin-header">
                            <h2 class="admin-title">Új vetítés hozzáadása</h2>
                            <a href="?tab=screenings" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Vissza
                            </a>
                        </div>
                        
                        <form action="admin_action.php" method="POST">
                            <input type="hidden" name="action" value="add_screening">
                            
                            <div class="form-group">
                                <label for="movie_id">Film *</label>
                                <select id="movie_id" name="movie_id" required>
                                    <option value="">Válasszon filmet</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT id, title FROM movies WHERE active = 1 ORDER BY title");
                                    while($movie = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <option value="<?php echo $movie['id']; ?>"><?php echo htmlspecialchars($movie['title']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="screening_date">Dátum *</label>
                                    <input type="date" id="screening_date" name="screening_date" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="screening_time">Idő *</label>
                                    <input type="time" id="screening_time" name="screening_time" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="hall_number">Terem száma *</label>
                                    <input type="number" id="hall_number" name="hall_number" min="1" max="10" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="price">Jegyár (Ft) *</label>
                                    <input type="number" id="price" name="price" min="0" step="100" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="available_seats">Szabad helyek száma</label>
                                <input type="number" id="available_seats" name="available_seats" min="1" max="200" value="100">
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap;">
                                <button type="submit" class="btn btn-primary">Vetítés hozzáadása</button>
                                <a href="?tab=screenings" class="btn btn-secondary">Mégse</a>
                            </div>
                        </form>
                        
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Reszponzív admin navigációhoz - kattintás kezelés
        document.querySelectorAll('.admin-nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Az onclick már tartalmazza a navigációt
                // Itt nincs szükség extra kódra
            });
        });
    </script>
</body>
</html>