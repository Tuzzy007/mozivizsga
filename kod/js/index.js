
function showDay(dayId, element) {
    // Minden day-screenings elrejtése
    document.querySelectorAll('.day-screenings').forEach(el => {
        el.classList.remove('active');
    });
    
    // Kiválasztott megjelenítése
    document.getElementById(dayId).classList.add('active');
    
    // Minden date-tab active eltávolítása
    document.querySelectorAll('.date-tab').forEach(el => {
        el.classList.remove('active');
    });
    
    // Kiválasztott active hozzáadása
    element.classList.add('active');
    
    // SIMÁN GÖRDÜL
    document.getElementById(dayId).scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });
}

// URL hash kezelés (ha közvetlen napra akarunk ugrani)
window.addEventListener('load', function() {
    const hash = window.location.hash;
    if(hash && hash.includes('day-')) {
        const dayId = hash.substring(1);
        const tabElement = document.querySelector(`[onclick*="${dayId}"]`);
        if(tabElement) {
            showDay(dayId, tabElement);
        }
    }
});