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
        
        .filters {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.1);
            border: 1px solid #e9ecef;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        @media (max-width: 992px) {
            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .movies-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .movie-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 15px 30px rgba(121, 6, 6, 0.48);
        }
        
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            border-color: #852727;
        }
        
        .movie-poster-container {
            height: 350px;
            overflow: hidden;
            position: relative;
        }
        
        .movie-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .movie-card:hover .movie-poster {
            transform: scale(1.05);
        }
        
        .movie-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .movie-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .movie-meta {
            display: flex;
            justify-content: space-between;
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .movie-description {
            color: #34495e;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }
        
        .movie-rating {
            color: #f39c12;
            font-weight: bold;
            background: linear-gradient(135deg, #f39c12, #e67e22);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .movie-rating i {
            color: #f39c12;
            -webkit-text-fill-color: #f39c12;
        }
        
        .movie-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }
        
        .movie-actions .btn {
            flex: 1;
            text-align: center;
            padding: 0.6rem 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
            color: #7f8c8d;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .no-results i {
            font-size: 3.5rem;
            color: #bdc3c7;
            margin-bottom: 1.5rem;
        }
        
        .no-results h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .results-count {
            color: #ffffff;
            font-weight: 600;
            padding: 0.5rem 0;
            border-bottom: 2px solid #ffffff;
            display: inline-block;
        }
        
        .search-spinner {
            position: relative;
        }
        
        .search-spinner i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #3498db;
            display: none;
        }
        
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .btn-clear-all {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-clear-all:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
            color: white;
        }
        
        .pagination-container {
            margin-top: 3rem;
            margin-bottom: 2rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .page-link {
            padding: 0.7rem 1.2rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #2c3e50;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .page-link:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }
        
        .page-link.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
            cursor: default;
            pointer-events: none;
        }
        
        .page-link.disabled {
            background: #f8f9fa;
            color: #6c757d;
            border-color: #dee2e6;
            cursor: not-allowed;
            pointer-events: none;
            opacity: 0.6;
        }
        
        .pagination-ellipsis {
            color: #ffffff;
            padding: 0.5rem;
        }
        
        /* Reszponzív design */
        @media (max-width: 768px) {
            .movies-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1.5rem;
            }
            
            .movie-poster-container {
                height: 300px;
            }
            
            .filter-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .results-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .pagination {
                gap: 0.3rem;
            }
            
            .page-link {
                padding: 0.5rem 0.9rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .movies-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .movie-poster-container {
                height: 280px;
            }
            
            .movie-actions {
                flex-direction: column;
            }
            
            .pagination {
                gap: 0.2rem;
            }
            
            .page-link {
                padding: 0.4rem 0.7rem;
                font-size: 0.85rem;
            }
        }

        .filter-group select {
            color: #2c3e50 !important;
            background-color: white !important;
            border: 1px solid #ddd !important;
        }

        .filter-group select option {
            color: #2c3e50 !important;
            background-color: white !important;
            padding: 8px;
        }

        .filter-group select option:hover,
        .filter-group select option:focus,
        .filter-group select option:checked {
            background-color: #3498db !important;
            color: white !important;
        }

        select option {
            color: #2c3e50;
            background: white;
        }

        .filter-group input::placeholder {
            color: #7f8c8d;  /* Szürke szín */
            opacity: 1;  /* Teljesen látható */
        }

        .page-link:hover {
            background: #e74c3c;  /* Piros hover */
            color: white;
            border-color: #e74c3c;
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
        }
        
        .page-link.active {
            background: #e74c3c;  /* Piros aktív */
            color: white;
            border-color: #e74c3c;
        }
    </style>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search');
            const genreSelect = document.getElementById('genre');
            const perPageSelect = document.getElementById('per_page');
            const moviesContainer = document.getElementById('moviesContainer');
            const spinner = document.querySelector('.search-spinner i');
            
            let searchTimeout = null;
            let currentPage = <?php echo $page; ?>;
            
            // Élő keresés - minden karakter után
            searchInput.addEventListener('input', function() {
                currentPage = 1;
                performSearch();
            });
            
            // Műfaj változás
            genreSelect.addEventListener('change', function() {
                currentPage = 1;
                performSearch();
            });
            
            // Oldalméret változás
            perPageSelect.addEventListener('change', function() {
                currentPage = 1;
                performSearch();
            });
            
            // Globális page változtató függvény
            window.changePage = function(page) {
                currentPage = page;
                performSearch();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };
            
            function performSearch() {
                const searchValue = searchInput.value;
                const genreValue = genreSelect.value;
                const perPageValue = perPageSelect.value;
                
                if (spinner) spinner.style.display = 'block';
                
                clearTimeout(searchTimeout);
                
                searchTimeout = setTimeout(function() {
                    fetch(`movies.php?ajax=1&search=${encodeURIComponent(searchValue)}&genre=${encodeURIComponent(genreValue)}&per_page=${perPageValue}&page=${currentPage}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Hálózati hiba');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) {
                                console.error('Szerver hiba:', data.error);
                                return;
                            }
                            updateMovies(data);
                            updateURL(searchValue, genreValue, perPageValue, currentPage);
                            if (spinner) spinner.style.display = 'none';
                        })
                        .catch(error => {
                            console.error('Hiba:', error);
                            if (spinner) spinner.style.display = 'none';
                        });
                }, 300);
            }
            
            function updateMovies(data) {
                const movies = data.movies || [];
                const totalMovies = data.total || 0;
                const currentPage = data.page || 1;
                const totalPages = data.total_pages || 1;
                const perPageValue = perPageSelect.value;
                const searchValue = searchInput.value;
                const genreValue = genreSelect.value;
                
                let html = '';
                
                updateClearButton(searchValue, genreValue, perPageValue);
                
                if (movies.length > 0) {
                    html += '<div class="results-header">';
                    html += '<div class="results-count">';
                    html += '<i class="fas fa-film"></i> ';
                    html += totalMovies + ' film található';
                    
                    if (searchValue) {
                        html += ' a "<strong>' + escapeHtml(searchValue) + '</strong>" kifejezésre';
                    }
                    if (genreValue) {
                        html += ' a(z) "<strong>' + escapeHtml(genreValue) + '</strong>" műfajban';
                    }
                    
                    html += ' (' + currentPage + '/' + totalPages + ' oldal)';
                    html += '</div>';
                    html += '</div>';
                    
                    html += '<div class="movies-container" id="moviesGrid">';
                    
                    movies.forEach(function(movie) {
                        html += '<div class="movie-card">';
                        html += '<div class="movie-poster-container">';
                        html += '<img src="' + escapeHtml(movie.poster_url || '') + '" ';
                        html += 'alt="' + escapeHtml(movie.title || 'Film') + '" ';
                        html += 'class="movie-poster" ';
                        html += 'onerror="this.src=\'https://via.placeholder.com/280x350/ecf0f1/2c3e50?text=' + encodeURIComponent(movie.title || 'Film') + '\'">';
                        html += '</div>';
                        html += '<div class="movie-info">';
                        html += '<h3 class="movie-title">' + escapeHtml(movie.title || 'Ismeretlen cím') + '</h3>';
                        html += '<div class="movie-meta">';
                        html += '<span><i class="fas fa-calendar-alt"></i> ' + escapeHtml(movie.release_year || 'N/A') + '</span>';
                        html += '<span><i class="fas fa-clock"></i> ' + escapeHtml(movie.duration || '0') + ' perc</span>';
                        html += '<span class="movie-rating">';
                        html += '<i class="fas fa-star"></i> ' + escapeHtml(movie.rating || '0.0');
                        html += '</span>';
                        html += '</div>';
                        html += '<p class="movie-description">' + escapeHtml((movie.description || '').substring(0, 150)) + '...</p>';
                        html += '<div class="movie-actions">';
                        html += '<a href="movie.php?id=' + (movie.id || 0) + '" class="btn btn-primary"><i class="fas fa-info-circle"></i> Részletek</a>';
                        html += '<a href="screenings.php?movie=' + (movie.id || 0) + '" class="btn btn-secondary"><i class="fas fa-ticket-alt"></i> Jegyfoglalás</a>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    });
                    
                    html += '</div>';
                    
                    if (totalPages > 1) {
                        html += '<div class="pagination-container">';
                        html += '<div class="pagination">';
                        
                        // Első oldal
                        if (currentPage > 1) {
                            html += '<a href="javascript:void(0)" onclick="changePage(1)" class="page-link"><i class="fas fa-angle-double-left"></i></a>';
                        } else {
                            html += '<span class="page-link disabled"><i class="fas fa-angle-double-left"></i></span>';
                        }
                        
                        // Előző oldal
                        if (currentPage > 1) {
                            html += '<a href="javascript:void(0)" onclick="changePage(' + (currentPage - 1) + ')" class="page-link"><i class="fas fa-angle-left"></i> Előző</a>';
                        } else {
                            html += '<span class="page-link disabled"><i class="fas fa-angle-left"></i> Előző</span>';
                        }
                        
                        // Oldalszámok
                        let startPage = Math.max(1, currentPage - 2);
                        let endPage = Math.min(totalPages, currentPage + 2);
                        
                        if (startPage > 1) {
                            html += '<a href="javascript:void(0)" onclick="changePage(1)" class="page-link">1</a>';
                            if (startPage > 2) {
                                html += '<span class="pagination-ellipsis">...</span>';
                            }
                        }
                        
                        for (let i = startPage; i <= endPage; i++) {
                            html += '<a href="javascript:void(0)" onclick="changePage(' + i + ')" class="page-link' + (i == currentPage ? ' active' : '') + '">' + i + '</a>';
                        }
                        
                        if (endPage < totalPages) {
                            if (endPage < totalPages - 1) {
                                html += '<span class="pagination-ellipsis">...</span>';
                            }
                            html += '<a href="javascript:void(0)" onclick="changePage(' + totalPages + ')" class="page-link">' + totalPages + '</a>';
                        }
                        
                        // Következő oldal
                        if (currentPage < totalPages) {
                            html += '<a href="javascript:void(0)" onclick="changePage(' + (currentPage + 1) + ')" class="page-link">Következő <i class="fas fa-angle-right"></i></a>';
                        } else {
                            html += '<span class="page-link disabled">Következő <i class="fas fa-angle-right"></i></span>';
                        }
                        
                        // Utolsó oldal
                        if (currentPage < totalPages) {
                            html += '<a href="javascript:void(0)" onclick="changePage(' + totalPages + ')" class="page-link"><i class="fas fa-angle-double-right"></i></a>';
                        } else {
                            html += '<span class="page-link disabled"><i class="fas fa-angle-double-right"></i></span>';
                        }
                        
                        html += '</div>';
                        html += '</div>';
                    }
                    
                } else {
                    html += '<div class="no-results">';
                    html += '<i class="fas fa-film"></i>';
                    html += '<h3>Nincs találat</h3>';
                    html += '<p>A keresési feltételeknek megfelelő film nem található.</p>';
                    
                    if (searchValue || genreValue || perPageValue != 12) {
                        html += '<a href="movies.php" class="btn btn-primary" style="margin-top: 1.5rem;">';
                        html += '<i class="fas fa-times-circle"></i> Szűrők törlése';
                        html += '</a>';
                    }
                    
                    html += '</div>';
                }
                
                moviesContainer.innerHTML = html;
            }
            
            function updateClearButton(searchValue, genreValue, perPageValue) {
                const filterHeader = document.querySelector('.filter-header');
                if (!filterHeader) return;
                
                const existingButton = document.querySelector('.btn-clear-all');
                
                if ((searchValue || genreValue || perPageValue != 12) && !existingButton) {
                    const clearButton = document.createElement('a');
                    clearButton.href = 'movies.php';
                    clearButton.className = 'btn-clear-all';
                    clearButton.innerHTML = '<i class="fas fa-times-circle"></i> Szűrők törlése';
                    
                    const title = filterHeader.querySelector('h1');
                    if (title) {
                        filterHeader.insertBefore(clearButton, title.nextSibling);
                    } else {
                        filterHeader.appendChild(clearButton);
                    }
                } else if (!searchValue && !genreValue && perPageValue == 12 && existingButton) {
                    existingButton.remove();
                }
            }
            
            function updateURL(searchValue, genreValue, perPageValue, pageValue) {
                const url = new URL(window.location);
                
                if (searchValue) {
                    url.searchParams.set('search', searchValue);
                } else {
                    url.searchParams.delete('search');
                }
                
                if (genreValue) {
                    url.searchParams.set('genre', genreValue);
                } else {
                    url.searchParams.delete('genre');
                }
                
                if (perPageValue != 12) {
                    url.searchParams.set('per_page', perPageValue);
                } else {
                    url.searchParams.delete('per_page');
                }
                
                if (pageValue > 1) {
                    url.searchParams.set('page', pageValue);
                } else {
                    url.searchParams.delete('page');
                }
                
                window.history.pushState({}, '', url);
            }
            
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    </script>
</body>
</html>