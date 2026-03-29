document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const genreSelect = document.getElementById('genre');
    const perPageSelect = document.getElementById('per_page');
    const moviesContainer = document.getElementById('moviesContainer');
    const spinner = document.querySelector('.search-spinner i');
    
    let searchTimeout = null;
    let currentPage = "Filmek"
    
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