// booking.js - Külső JavaScript fájl

// Kiválasztott helyek tömbje
let selectedSeats = [];

// Hely kiválasztása/eltávolítása
function toggleSeat(seatNumber, element) {
    const index = selectedSeats.indexOf(seatNumber);
    const warningElement = document.getElementById('seatLimitWarning');

    if(index === -1) {
        // Hely hozzáadása
        if(selectedSeats.length >= MAX_SEATS) {
            // Figyelmeztetés megjelenítése
            if (warningElement) {
                warningElement.style.display = 'block';
                // Automatikusan eltüntetjük 3 másodperc után
                setTimeout(() => {
                    warningElement.style.display = 'none';
                }, 10000);
            }
            return;
        }

        // Ha nincs elérve a limit, elrejtjük a figyelmeztetést (ha látszana)
        if (warningElement) {
            warningElement.style.display = 'none';
        }

        selectedSeats.push(seatNumber);
        element.classList.remove('available');
        element.classList.add('selected');
    } else {
        // Hely eltávolítása - ilyenkor is elrejtjük a figyelmeztetést
        if (warningElement) {
            warningElement.style.display = 'none';
        }
        selectedSeats.splice(index, 1);
        element.classList.remove('selected');
        element.classList.add('available');
    }

    // Felület frissítése
    updateSelectedSeatsDisplay();
}

// Kiválasztott helyek megjelenítésének frissítése
function updateSelectedSeatsDisplay() {
    // Lista frissítése
    const selectedList = document.getElementById('selectedSeatsList');
    const countElement = document.getElementById('selectedCount');
    const totalPriceElement = document.getElementById('totalPrice');
    const paymentButton = document.getElementById('paymentButton');
    const selectedSeatsInput = document.getElementById('selectedSeatsInput');
    
    // Badge-ek generálása - csak ha létezik a selectedList
    if (selectedList) {
        selectedList.innerHTML = '';
        selectedSeats.sort().forEach(seat => {
            const badge = document.createElement('span');
            badge.className = 'selected-seat-badge';
            badge.innerHTML = `${seat} <i class="fas fa-times" onclick="removeSeat('${seat}')"></i>`;
            selectedList.appendChild(badge);
        });
    }
    
    // Darabszám és végösszeg frissítése
    if (countElement) countElement.textContent = selectedSeats.length + ' db';
    const totalPrice = selectedSeats.length * TICKET_PRICE;
    if (totalPriceElement) totalPriceElement.textContent = totalPrice.toLocaleString('hu-HU') + ' Ft';
    
    // Payment gomb frissítése
    if (paymentButton) {
        const selectedPayment = document.querySelector('input[name="payment_method"]:checked')?.value;
        if(selectedSeats.length === 0) {
            paymentButton.disabled = true;
            paymentButton.style.opacity = '0.5';
            paymentButton.style.cursor = 'not-allowed';
            paymentButton.innerHTML = '<i class="fas fa-credit-card"></i> Fizetés bankkártyával';
        } else {
            paymentButton.disabled = false;
            paymentButton.style.opacity = '1';
            paymentButton.style.cursor = 'pointer';
            
            // Fizetési mód alapján gomb szövegének módosítása
            if (selectedPayment === 'cash') {
                paymentButton.innerHTML = '<i class="fas fa-money-bill-wave"></i> Foglalás készpénzzel: ' + 
                    totalPrice.toLocaleString('hu-HU') + ' Ft';
            } else {
                paymentButton.innerHTML = '<i class="fas fa-credit-card"></i> Fizetés bankkártyával: ' + 
                    totalPrice.toLocaleString('hu-HU') + ' Ft';
            }
        }
    }
    
    // Hidden input frissítése
    if (selectedSeatsInput) {
        selectedSeatsInput.value = JSON.stringify(selectedSeats);
    }
}

// Hely eltávolítása a badge-ről
function removeSeat(seatNumber) {
    const seatElement = document.querySelector(`.seat[data-seat="${seatNumber}"]`);
    if(seatElement) {
        const index = selectedSeats.indexOf(seatNumber);
        if(index !== -1) {
            selectedSeats.splice(index, 1);
            seatElement.classList.remove('selected');
            seatElement.classList.add('available');

            // Figyelmeztetés elrejtése
            const warningElement = document.getElementById('seatLimitWarning');
            if (warningElement) {
                warningElement.style.display = 'none';
            }

            updateSelectedSeatsDisplay();
        }
    }
}

// Lap betöltésekor ellenőrizzük, hogy van-e szabad hely
document.addEventListener('DOMContentLoaded', function() {
    const availableSeats = document.querySelectorAll('.seat.available');
    const paymentButton = document.getElementById('paymentButton');
    
    if(availableSeats.length === 0) {
        if(paymentButton) {
            paymentButton.disabled = true;
            paymentButton.innerHTML = '<i class="fas fa-times-circle"></i> Nincs szabad hely';
            paymentButton.style.backgroundColor = "#6C0808";
        }
        
        const maxSeatsWarning = document.querySelector('.max-seats-warning');
        if(maxSeatsWarning) {
            maxSeatsWarning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Nincsenek szabad helyek erre a vetítésre!';
            maxSeatsWarning.style.backgroundColor = 'rgba(210, 58, 58, 0.2)';
        }
    }
    
    // Fizetési módok kezelése inicializálása
    initPaymentMethods();
    updateSelectedSeatsDisplay();
});

// Fizetési módok kezelése
function initPaymentMethods() {
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const cardDetails = document.getElementById('cardDetails');
    const szepDetails = document.getElementById('szepDetails');
    const transferInfo = document.getElementById('transferInfo');
    const selectedPaymentInput = document.getElementById('selectedPaymentMethod');
    const paymentButton = document.getElementById('paymentButton');

    // SZÉP kártya mezők referenciái
    const szepSubaccount = document.getElementById('szep_subaccount');
    const szepNumber = document.getElementById('szep_number');

    if (paymentRadios.length > 0) {
        paymentRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Minden plusz mező elrejtése
                if (cardDetails) cardDetails.style.display = 'none';
                if (szepDetails) szepDetails.style.display = 'none';
                if (transferInfo) transferInfo.style.display = 'none';

                // SZÉP kártya mezők kötelező tulajdonságának eltávolítása
                if(szepSubaccount) szepSubaccount.required = false;
                if(szepNumber) szepNumber.required = false;

                // Kiválasztott érték mentése
                if (selectedPaymentInput) selectedPaymentInput.value = this.value;

                // Aktuális opcióhoz tartozó mezők megjelenítése
                switch(this.value) {
                    case 'card':
                        if (cardDetails) cardDetails.style.display = 'block';
                        break;
                    case 'szep':
                        if (szepDetails) szepDetails.style.display = 'block';
                        if(szepSubaccount) szepSubaccount.required = true;
                        if(szepNumber) szepNumber.required = true;
                        break;
                    case 'transfer':
                        if (transferInfo) transferInfo.style.display = 'block';
                        break;
                    case 'cash':
                        // Készpénz esetén nem kell semmit megjeleníteni
                        break;
                }
                
                // Gomb szövegének frissítése
                updateSelectedSeatsDisplay();
            });
        });
    }
}

// SZÉP kártya szám formázás
document.addEventListener('input', function(e) {
    if(e.target && e.target.id === 'szep_number') {
        e.target.value = e.target.value
            .replace(/\s/g, '')
            .replace(/\D/g, '')
            .replace(/(\d{4})/g, '$1 ')
            .trim()
            .substring(0, 19);
    }
});

function startPayment() {
    if(selectedSeats.length === 0) {
        alert('Kérem válasszon ki legalább egy helyet!');
        return;
    }

    const selectedPayment = document.querySelector('input[name="payment_method"]:checked')?.value;
    
    if (selectedPayment === 'card') {
        startStripePayment();
    } else if (selectedPayment === 'cash') {
        submitCashBooking();
    } else {
        alert('A választott fizetési mód jelenleg nem elérhető. Kérjük válasszon bankkártyát vagy készpénzt!');
    }
}

function submitCashBooking() {
    // Űrlap adatok összegyűjtése
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'cash_booking.php';
    
    const fields = {
        'screening_id': SCREENING_ID,
        'selected_seats': JSON.stringify(selectedSeats),
        'book_tickets': '1'
    };
    
    for(const [name, value] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
}

function startStripePayment() {
    const totalPriceFt = selectedSeats.length * TICKET_PRICE;
    if (totalPriceFt < 175) {
        alert('A fizetendő összeg minimum 175 Ft kell legyen! Válasszon több jegyet!');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'stripe_payment.php';

    const fields = {
        'screening_id': SCREENING_ID,
        'selected_seats': JSON.stringify(selectedSeats),
        'movie_title': MOVIE_TITLE,
        'screening_date': SCREENING_DATE,
        'screening_time': SCREENING_TIME,
        'poster_url': POSTER_URL
    };

    for(const [name, value] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}