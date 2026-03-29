function printTicket(ticketId) {
    const ticketElement = document.getElementById('ticket-' + ticketId);
    if (!ticketElement) return;
    
    // Készítsünk egy másolatot a jegyből a nyomtatáshoz
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    const ticketClone = ticketElement.cloneNode(true);
    
    // Eltávolítjuk a nyomtatás gombot a másolatból
    const buttonsToRemove = ticketClone.querySelectorAll('.ticket-actions, .no-print');
    buttonsToRemove.forEach(btn => btn.remove());

    
    // Nyomtatási tartalom
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Jegy nyomtatás - SzalkaCinema</title>
            <link rel="stylesheet" href="css/print.css">
        </head>
        <body>
            ${ticketClone.outerHTML}
            <div class="print-footer">
                SzalkaCinema - Érvényes belépésre jogosít<br>
                Kérjük, őrizze meg a jegyet a vetítés végéig!
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// Nyomtatás előtti ellenőrzés
window.onbeforeprint = function() {
    // Opcionális: nyomtatás előtti előkészületek
    console.log('Nyomtatás előkészítése...');
};

window.onafterprint = function() {
    // Nyomtatás utáni visszaállítás
    console.log('Nyomtatás befejeződött');
};