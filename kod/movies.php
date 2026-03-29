<?php
require_once 'config.php';
$page_title = "Filmek";

// Oldalméret beállítása
$per_page_options = [12, 24, 48, 96];
$per_page = isset($_GET['per_page']) && in_array($_GET['per_page'], $per_page_options) ? (int)$_GET['per_page'] : 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

// Keresés kezelése AJAX kérésekhez
if(isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    
    try {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
        $per_page = isset($_GET['per_page']) && in_array($_GET['per_page'], $per_page_options) ? (int)$_GET['per_page'] : 12;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if($page < 1) $page = 1;
        $offset = ($page - 1) * $per_page;
        
        // Összes találat számlálása
        $count_sql = "SELECT COUNT(*) as total FROM movies WHERE active = 1";
        $count_params = [];
        
        if(!empty($search)) {
            $count_sql .= " AND (title LIKE ? OR description LIKE ? OR director LIKE ?)";
            $search_term = "%$search%";
            $count_params = array_merge($count_params, [$search_term, $search_term, $search_term]);
        }
        
        if(!empty($genre)) {
            $count_sql .= " AND genre LIKE ?";
            $count_params[] = "%$genre%";
        }
        
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($count_params);
        $total_movies = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Filmek lekérése oldaltördeléssel - KÜLÖN PARAMÉTEREKKEL
        $sql = "SELECT * FROM movies WHERE active = 1";
        $params = [];
        
        if(!empty($search)) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR director LIKE ?)";
            $search_term = "%$search%";
            $params = array_merge($params, [$search_term, $search_term, $search_term]);
        }
        
        if(!empty($genre)) {
            $sql .= " AND genre LIKE ?";
            $params[] = "%$genre%";
        }
        
        $sql .= " ORDER BY title LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_pages = $total_movies > 0 ? ceil($total_movies / $per_page) : 1;
        
        echo json_encode([
            'movies' => $movies,
            'total' => $total_movies,
            'page' => $page,
            'total_pages' => $total_pages,
            'per_page' => $per_page
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Normál oldalletöltés
try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';

    // Összes találat számlálása
    $count_sql = "SELECT COUNT(*) as total FROM movies WHERE active = 1";
    $count_params = [];

    if(!empty($search)) {
        $count_sql .= " AND (title LIKE ? OR description LIKE ? OR director LIKE ?)";
        $search_term = "%$search%";
        $count_params = array_merge($count_params, [$search_term, $search_term, $search_term]);
    }

    if(!empty($genre)) {
        $count_sql .= " AND genre LIKE ?";
        $count_params[] = "%$genre%";
    }

    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_movies = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = $total_movies > 0 ? ceil($total_movies / $per_page) : 1;

    // Aktuális oldal ellenőrzése
    if($page > $total_pages && $total_pages > 0) $page = $total_pages;
    $offset = ($page - 1) * $per_page;

    // Filmek lekérése oldaltördeléssel - KÜLÖN PARAMÉTEREKKEL
    $sql = "SELECT * FROM movies WHERE active = 1";
    $params = [];

    if(!empty($search)) {
        $sql .= " AND (title LIKE ? OR description LIKE ? OR director LIKE ?)";
        $search_term = "%$search%";
        $params = array_merge($params, [$search_term, $search_term, $search_term]);
    }

    if(!empty($genre)) {
        $sql .= " AND genre LIKE ?";
        $params[] = "%$genre%";
    }

    $sql .= " ORDER BY title LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Műfajok listája
    $genre_stmt = $pdo->query("SELECT DISTINCT genre FROM movies WHERE active = 1");
    $all_genres = [];
    while($row = $genre_stmt->fetch(PDO::FETCH_ASSOC)) {
        $genres = explode(',', $row['genre']);
        foreach($genres as $g) {
            $g = trim($g);
            if(!empty($g) && !in_array($g, $all_genres)) {
                $all_genres[] = $g;
            }
        }
    }
    sort($all_genres);
    
} catch (Exception $e) {
    // Hiba esetén üres eredmény
    $movies = [];
    $total_movies = 0;
    $total_pages = 1;
    $all_genres = [];
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('APP_NAME') ? APP_NAME : 'Mozi'; ?> - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php include 'style.css'; ?>
    </style>
    <link rel="stylesheet" href="css/movies.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <main class="main-content">
            <div class="filter-header">
                <h1 style="color: #ffffff; margin-bottom: 0;">Filmek</h1>
                <?php if(!empty($search) || !empty($genre) || $per_page != 12): ?>
                    <a href="movies.php" class="btn-clear-all">
                        <i class="fas fa-times-circle"></i> Szűrők törlése
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="filters">
                <form method="GET" action="" class="filter-form" id="filterForm">
                    <div class="filter-group search-spinner">
                        <label for="search">
                            Keresés
                        </label>
                        <input type="text" id="search" name="search" placeholder="Film címe, leírása, rendezője..." autocomplete="off">
                    </div>
                    
                    <div class="filter-group">
                        <label for="genre">
                            <i class="fas fa-tags"></i> Műfaj
                        </label>
                        <select id="genre" name="genre">
                            <option value="" style="color: white;">Összes műfaj</option>
                            <?php foreach($all_genres as $g): ?>
                                <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $genre == $g ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($g); ?>  <!-- Ez hiányzott! -->
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="per_page">
                            <i class="fas fa-list"></i> Oldalméret
                        </label>
                        <select id="per_page" name="per_page">
                            <?php foreach($per_page_options as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo $per_page == $option ? 'selected' : ''; ?>>
                                    <?php echo $option; ?> film / oldal
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <div id="moviesContainer">
                <?php if(count($movies) > 0): ?>
                    <div class="results-header">
                        <div class="results-count">
                            <i class="fas fa-film"></i>
                            <?php echo $total_movies; ?> film található
                            <?php if(!empty($search)): ?>
                                a "<strong><?php echo htmlspecialchars($search); ?></strong>" kifejezésre
                            <?php endif; ?>
                            <?php if(!empty($genre)): ?>
                                a(z) "<strong><?php echo htmlspecialchars($genre); ?></strong>" műfajban
                            <?php endif; ?>
                            (<?php echo $page; ?>/<?php echo $total_pages; ?> oldal)
                        </div>
                    </div>
                    
                    <div class="movies-container" id="moviesGrid">
                        <?php foreach($movies as $movie): ?>
                        <div class="movie-card">
                            <div class="movie-poster-container">
                                <img src="<?php echo htmlspecialchars($movie['poster_url'] ?? ''); ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title'] ?? 'Film'); ?>" 
                                     class="movie-poster"
                                     onerror="this.src='https://via.placeholder.com/280x350/ecf0f1/2c3e50?text=<?php echo urlencode($movie['title'] ?? 'Film'); ?>'">
                            </div>
                            <div class="movie-info">
                                <h3 class="movie-title"><?php echo htmlspecialchars($movie['title'] ?? 'Ismeretlen cím'); ?></h3>
                                <div class="movie-meta">
                                    <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($movie['release_year'] ?? 'N/A'); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($movie['duration'] ?? '0'); ?> perc</span>
                                    <span class="movie-rating">
                                        <i class="fas fa-star"></i> <?php echo htmlspecialchars($movie['rating'] ?? '0.0'); ?>
                                    </span>
                                </div>
                                <p class="movie-description"><?php echo htmlspecialchars(substr($movie['description'] ?? '', 0, 150)); ?>...</p>
                                <div class="movie-actions">
                                    <a href="movie.php?id=<?php echo $movie['id'] ?? 0; ?>" class="btn btn-primary">
                                        <i class="fas fa-info-circle"></i> Részletek
                                    </a>
                                    <a href="screenings.php?movie=<?php echo $movie['id'] ?? 0; ?>" class="btn btn-secondary">
                                        <i class="fas fa-ticket-alt"></i> Jegyfoglalás
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination">
                            <!-- Első oldal -->
                            <?php if($page > 1): ?>
                                <a href="javascript:void(0)" onclick="changePage(1)" class="page-link">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            <?php else: ?>
                                <span class="page-link disabled">
                                    <i class="fas fa-angle-double-left"></i>
                                </span>
                            <?php endif; ?>
                            
                            <!-- Előző oldal -->
                            <?php if($page > 1): ?>
                                <a href="javascript:void(0)" onclick="changePage(<?php echo $page - 1; ?>)" class="page-link">
                                    <i class="fas fa-angle-left"></i> Előző
                                </a>
                            <?php else: ?>
                                <span class="page-link disabled">
                                    <i class="fas fa-angle-left"></i> Előző
                                </span>
                            <?php endif; ?>
                            
                            <!-- Oldalszámok -->
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if($start_page > 1) {
                                echo '<a href="javascript:void(0)" onclick="changePage(1)" class="page-link">1</a>';
                                if($start_page > 2) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                            }
                            
                            for($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="javascript:void(0)" onclick="changePage(<?php echo $i; ?>)" 
                                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php
                            if($end_page < $total_pages) {
                                if($end_page < $total_pages - 1) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                                echo '<a href="javascript:void(0)" onclick="changePage(' . $total_pages . ')" class="page-link">' . $total_pages . '</a>';
                            }
                            ?>
                            
                            <!-- Következő oldal -->
                            <?php if($page < $total_pages): ?>
                                <a href="javascript:void(0)" onclick="changePage(<?php echo $page + 1; ?>)" class="page-link">
                                    Következő <i class="fas fa-angle-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="page-link disabled">
                                    Következő <i class="fas fa-angle-right"></i>
                                </span>
                            <?php endif; ?>
                            
                            <!-- Utolsó oldal -->
                            <?php if($page < $total_pages): ?>
                                <a href="javascript:void(0)" onclick="changePage(<?php echo $total_pages; ?>)" class="page-link">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="page-link disabled">
                                    <i class="fas fa-angle-double-right"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-film"></i>
                        <h3>Nincs találat</h3>
                        <p>A keresési feltételeknek megfelelő film nem található.</p>
                        <?php if(!empty($search) || !empty($genre) || $per_page != 12): ?>
                            <a href="movies.php" class="btn btn-primary" style="margin-top: 1.5rem;">
                                <i class="fas fa-times-circle"></i> Szűrők törlése
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script src="js/movies.js"></script>
</body>
</html>