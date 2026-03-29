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