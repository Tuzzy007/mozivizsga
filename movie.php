<?php
require_once 'config.php';

if(!isset($_GET['id'])) {
    header("Location: movies.php");
    exit();
}

$movie_id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$movie) {
    header("Location: movies.php");
    exit();
}

$page_title = $movie['title'];

// Kommentek lekérdezése
$stmt = $pdo->prepare("SELECT c.*, u.username, u.full_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.movie_id = ? ORDER BY c.comment_date DESC");
$stmt->execute([$movie_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Átlagos értékelés
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM comments WHERE movie_id = ?");
$stmt->execute([$movie_id]);
$rating_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Vetítések lekérdezése (következő 7 nap)
$stmt = $pdo->prepare("SELECT * FROM screenings WHERE movie_id = ? AND screening_date >= CURDATE() AND screening_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY screening_date, screening_time");
$stmt->execute([$movie_id]);
$screenings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Új komment hozzáadása
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment']) && $current_user) {
    $comment = trim($_POST['comment']);
    $rating = intval($_POST['rating']);
    
    if(!empty($comment) && $rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, movie_id, comment, rating) VALUES (?, ?, ?, ?)");
        $stmt->execute([$current_user['id'], $movie_id, $comment, $rating]);
        header("Location: movie.php?id=$movie_id");
        exit();
    }
}

// Oldal specifikus CSS - SÖTÉT VÁLTOZAT
$additional_css = '
    /* Film részletek oldal - SÖTÉT PIROS TÉMA */
    .movie-detail {
        background: linear-gradient(135deg, #1a0a0a, #2d1a1a);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
        margin-bottom: 3rem;
        border: 1px solid #8B0000;
    }
    
    .movie-header {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 2rem;
        padding: 2rem;
        background: linear-gradient(135deg, #1f0f0f, #2d1a1a);
        border-bottom: 3px solid #8B0000;
    }
    
    @media (max-width: 768px) {
        .movie-header {
            grid-template-columns: 1fr;
            text-align: center;
        }
    }
    
    .movie-poster-large {
        width: 100%;
        height: 450px;
        object-fit: cover;
        border-radius: 8px;
        border: 3px solid #8B0000;
        box-shadow: 0 5px 20px rgba(139, 0, 0, 0.4);
    }
    
    .movie-info-large h1 {
        font-family: "Poppins", sans-serif;
        font-size: 2.5rem;
        color: #F0E6E6;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    
    .movie-meta-large {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
        color: #B76E6E;
        flex-wrap: wrap;
    }
    
    .movie-meta-large span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(0, 0, 0, 0.4);
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        color: #F0E0E0;
        border: 1px solid #630000;
    }
    
    .movie-meta-large i {
        color: #D23A3A;
    }
    
    .rating-badge-large {
        background: linear-gradient(135deg, #8B0000, #630000);
        color: #FFE6E6;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        border: 1px solid #D23A3A;
    }
    
    .movie-description-full {
        line-height: 1.8;
        color: #E0CECE;
        margin-bottom: 1.5rem;
        background: rgba(0, 0, 0, 0.3);
        padding: 1.5rem;
        border-radius: 8px;
        border-left: 4px solid #8B0000;
    }
    
    .section {
        background: #1f1212;
        border-radius: 10px;
        padding: 2rem;
        margin: 2rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);
        border: 1px solid #630000;
    }
    
    .section h2 {
        font-family: "Poppins", sans-serif;
        font-size: 1.8rem;
        color: #F0D8D8;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #8B0000;
        display: inline-block;
    }
    
    .screenings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .screening-card {
        background: #251515;
        border-radius: 8px;
        padding: 1.5rem;
        border-left: 4px solid #8B0000;
        border: 1px solid #630000;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .screening-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(139, 0, 0, 0.3);
        border-color: #D23A3A;
    }
    
    .screening-date {
        font-weight: bold;
        color: #F0D0D0;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .screening-date i {
        color: #D23A3A;
    }
    
    .screening-time {
        font-size: 1.3rem;
        color: #FF6B6B;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .screening-card div {
        color: #D0B0B0;
    }
    
    .screening-card i {
        color: #8B0000;
    }
    
    .comments-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .comment-card {
        background: #1f1212;
        border-radius: 8px;
        padding: 1.5rem;
        border: 1px solid #630000;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.3);
        border-left: 4px solid #8B0000;
    }
    
    .comment-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .comment-author {
        font-weight: bold;
        color: #F0D0D0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .comment-author i {
        color: #D23A3A;
    }
    
    .comment-rating {
        color: #FF6B6B;
        font-weight: bold;
    }
    
    .comment-rating i {
        color: #FF6B6B;
        margin-right: 2px;
    }
    
    .comment-text {
        color: #D0B8B8;
        line-height: 1.6;
    }
    
    .comment-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .comment-form textarea {
        width: 100%;
        padding: 1rem;
        background: #2a1a1a;
        border: 1px solid #630000;
        border-radius: 6px;
        font-size: 1rem;
        min-height: 120px;
        resize: vertical;
        transition: all 0.3s ease;
        color: #F0E0E0;
    }
    
    .comment-form textarea:focus {
        outline: none;
        border-color: #D23A3A;
        box-shadow: 0 0 0 3px rgba(210, 58, 58, 0.2);
        background: #2a1a1a;
    }
    
    .comment-form textarea::placeholder {
        color: #B08C8C;
    }
    
    .rating-select {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .rating-select label {
        color: #E0C0C0;
    }
    
    .stars {
        display: flex;
        gap: 0.3rem;
    }
    
    .star {
        font-size: 1.8rem;
        color: #4A2A2A;
        cursor: pointer;
        transition: color 0.2s ease;
    }
    
    .star.active,
    .star.hover {
        color: #FF6B6B;
    }
    
    .no-screenings {
        text-align: center;
        padding: 3rem;
        color: #D0B0B0;
        background: #1f1212;
        border-radius: 8px;
        border: 1px solid #630000;
    }
    
    .no-screenings i {
        font-size: 3rem;
        color: #8B0000;
        margin-bottom: 1rem;
    }
    
    .no-screenings h3 {
        color: #F0D0D0;
        margin-bottom: 0.5rem;
    }
    
    .trailer-btn {
        background: linear-gradient(135deg, #8B0000, #4A0000);
        color: #FFE6E6;
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 30px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-top: 1rem;
        border: 1px solid #D23A3A;
    }
    
    .trailer-btn:hover {
        background: linear-gradient(135deg, #630000, #380000);
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(139, 0, 0, 0.5);
        color: white;
    }
    
    .avg-rating-display {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        background: #1f1212;
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid #630000;
    }
    
    .avg-rating-number {
        font-size: 2rem;
        font-weight: bold;
        color: #F0D8D8;
        background: #2a1a1a;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        border: 2px solid #8B0000;
    }
    
    .avg-rating-stars i {
        color: #FF6B6B;
        font-size: 1.2rem;
    }
    
    .avg-rating-count {
        color: #D0B0B0;
        margin-left: auto;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #8B0000, #4A0000);
        border: 1px solid #D23A3A;
        color: #FFE6E6;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #630000, #380000);
        box-shadow: 0 5px 15px rgba(139, 0, 0, 0.4);
    }
    
    @media (max-width: 768px) {
        .movie-header {
            padding: 1.5rem;
        }
        
        .movie-info-large h1 {
            font-size: 2rem;
        }
        
        .movie-meta-large {
            gap: 0.8rem;
        }
        
        .section {
            margin: 1rem;
            padding: 1.5rem;
        }
        
        .avg-rating-display {
            flex-wrap: wrap;
        }
    }
    
    @media (max-width: 480px) {
        .movie-info-large h1 {
            font-size: 1.6rem;
        }
        
        .movie-meta-large span {
            font-size: 0.9rem;
        }
        
        .section h2 {
            font-size: 1.5rem;
        }
        
        .screenings-grid {
            grid-template-columns: 1fr;
        }
    }
';
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php 
        // Globális CSS betöltése
        if (file_exists('style.css')) {
            include 'style.css';
        }
        
        // Oldal specifikus CSS hozzáadása
        echo $additional_css; 
        ?>
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <main class="main-content">
            <div class="movie-detail">
                <div class="movie-header">
                    <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster-large">
                    
                    <div class="movie-info-large">
                        <h1><?php echo htmlspecialchars($movie['title']); ?></h1>
                        
                        <div class="movie-meta-large">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($movie['release_year']); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($movie['duration']); ?> perc</span>
                            <span><i class="fas fa-film"></i> <?php echo htmlspecialchars($movie['genre']); ?></span>
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($movie['director']); ?></span>
                            <span class="rating-badge-large">
                                <i class="fas fa-star"></i> <?php echo htmlspecialchars($movie['rating']); ?>
                            </span>
                        </div>
                        
                        <p class="movie-description-full"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                        
                        <?php if($movie['trailer_url']): ?>
                        <a href="<?php echo htmlspecialchars($movie['trailer_url']); ?>" target="_blank" class="trailer-btn">
                            <i class="fab fa-youtube"></i> Trailer megtekintése
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="movie-body">
                    <?php if(count($screenings) > 0): ?>
                    <div class="section">
                        <h2><i class="fas fa-calendar-week" style="color: #D23A3A; margin-right: 10px;"></i> Következő vetítések</h2>
                        <div class="screenings-grid">
                            <?php foreach($screenings as $screening): ?>
                            <div class="screening-card">
                                <div class="screening-date">
                                    <i class="fas fa-calendar-day"></i> <?php echo date('Y.m.d.', strtotime($screening['screening_date'])); ?>
                                </div>
                                <div class="screening-time">
                                    <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($screening['screening_time'])); ?>
                                </div>
                                <div style="margin-bottom: 0.5rem;"><i class="fas fa-door-closed"></i> Terem: <?php echo $screening['hall_number']; ?></div>
                                <div style="margin-bottom: 0.5rem;"><i class="fas fa-ticket-alt"></i> Ár: <?php echo number_format($screening['price'], 0, ',', ' '); ?> Ft</div>
                                <div style="margin-bottom: 1rem;"><i class="fas fa-chair"></i> Szabad helyek: <?php echo $screening['available_seats']; ?></div>
                                <a href="booking.php?screening=<?php echo $screening['id']; ?>" class="btn btn-primary" style="width:100%;">Jegyfoglalás</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="section">
                        <div class="no-screenings">
                            <i class="fas fa-calendar-times"></i>
                            <h3>Nincs jelenleg tervezett vetítés</h3>
                            <p>Kérjük, látogasson vissza később a vetítési időpontokért.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="section">
                        <h2><i class="fas fa-star" style="color: #D23A3A; margin-right: 10px;"></i> Értékelések (<?php echo $rating_info['review_count'] ?? 0; ?>)</h2>
                        
                        <?php if($rating_info && $rating_info['avg_rating']): ?>
                        <div class="avg-rating-display">
                            <div class="avg-rating-number">
                                <?php echo number_format($rating_info['avg_rating'], 1); ?>
                            </div>
                            <div class="avg-rating-stars">
                                <?php
                                $full_stars = floor($rating_info['avg_rating']);
                                $half_star = $rating_info['avg_rating'] - $full_stars >= 0.5;
                                $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                                ?>
                                <?php for($i = 0; $i < $full_stars; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                                <?php if($half_star): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php endif; ?>
                                <?php for($i = 0; $i < $empty_stars; $i++): ?>
                                    <i class="far fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="avg-rating-count">
                                <?php echo $rating_info['review_count']; ?> értékelés alapján
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($current_user): ?>
                        <div style="background: #1f1212; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #630000;">
                            <h3 style="color: #F0D0D0; margin-bottom: 1.5rem; font-family: Poppins, sans-serif;">Értékelés hozzáadása</h3>
                            <form method="POST" class="comment-form">
                                <textarea name="comment" placeholder="Írja meg véleményét..." required></textarea>
                                
                                <div class="rating-select">
                                    <label>Értékelés:</label>
                                    <div class="stars" id="star-rating">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="far fa-star star" data-rating="<?php echo $i; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="rating" id="rating-value" value="5" required>
                                </div>
                                
                                <button type="submit" name="add_comment" class="btn btn-primary" style="align-self: flex-start;">
                                    <i class="fas fa-paper-plane"></i> Értékelés elküldése
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div style="background: #1f1212; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; border: 1px solid #630000;">
                            <p style="color: #D0B0B0;">Az értékeléshez <a href="login.php" style="color: #FF6B6B; font-weight: 600;">jelentkezzen be</a>.</p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="comments-list">
                            <?php foreach($comments as $comment): ?>
                            <div class="comment-card">
                                <div class="comment-header">
                                    <div class="comment-author">
                                        <i class="fas fa-user-circle"></i>
                                        <?php echo htmlspecialchars($comment['full_name']); ?> (@<?php echo htmlspecialchars($comment['username']); ?>)
                                    </div>
                                    <div class="comment-rating">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= $comment['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="comment-text">
                                    <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                </div>
                                <div style="font-size:0.9rem; color:#B08C8C; margin-top:0.8rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-clock"></i> <?php echo date('Y.m.d. H:i', strtotime($comment['comment_date'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if(count($comments) == 0): ?>
                                <div style="text-align:center; padding: 2rem; color: #D0B0B0; background: #1f1212; border-radius: 8px; border: 1px solid #630000;">
                                    <i class="fas fa-comment-slash" style="font-size: 2rem; color: #8B0000; margin-bottom: 1rem;"></i>
                                    <p>Még nincsenek értékelések. Legyen Ön az első!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Csillagos értékelés
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const ratingInput = document.getElementById('rating-value');
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    ratingInput.value = rating;
                    
                    stars.forEach(s => {
                        const starRating = s.getAttribute('data-rating');
                        if(starRating <= rating) {
                            s.classList.remove('far');
                            s.classList.add('fas', 'active');
                        } else {
                            s.classList.remove('fas', 'active');
                            s.classList.add('far');
                        }
                    });
                });
                
                star.addEventListener('mouseover', function() {
                    const rating = this.getAttribute('data-rating');
                    
                    stars.forEach(s => {
                        const starRating = s.getAttribute('data-rating');
                        if(starRating <= rating) {
                            s.classList.add('hover');
                        } else {
                            s.classList.remove('hover');
                        }
                    });
                });
                
                star.addEventListener('mouseout', function() {
                    stars.forEach(s => {
                        s.classList.remove('hover');
                    });
                });
            });
            
            // Alapértelmezett érték beállítása
            stars.forEach(s => {
                const starRating = s.getAttribute('data-rating');
                if(starRating <= ratingInput.value) {
                    s.classList.remove('far');
                    s.classList.add('fas', 'active');
                }
            });
        });
    </script>
</body>
</html>