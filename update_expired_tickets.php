<?php
require_once __DIR__ . '/../config.php';

echo "🔍 Lejárt jegyek ellenőrzése...\n";

try {
    // Lekérjük az összes aktív jegyet, amihez tartozó vetítés már elkezdődött
    $stmt = $pdo->prepare("
        SELECT t.id, t.user_id, t.screening_id, 
               s.screening_date, s.screening_time,
               CONCAT(s.screening_date, ' ', s.screening_time) as screening_datetime
        FROM tickets t
        JOIN screenings s ON t.screening_id = s.id
        WHERE t.status = 'active' 
          AND CONCAT(s.screening_date, ' ', s.screening_time) < NOW()
    ");
    $stmt->execute();
    $expired_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = count($expired_tickets);
    echo "📊 Találat: $count lejárt jegy\n";
    
    if($count > 0) {
        // Státusz frissítése 'expired'-re
        $update_stmt = $pdo->prepare("
            UPDATE tickets 
            SET status = 'expired', 
                status_changed_at = NOW(),
                status_changed_reason = 'Vetítés lejárt'
            WHERE id = ?
        ");
        
        $updated = 0;
        foreach($expired_tickets as $ticket) {
            if($update_stmt->execute([$ticket['id']])) {
                $updated++;
                echo "  ✅ Jegy #{$ticket['id']} - lejártra állítva\n";
            }
        }
        
        echo "✅ Összesen $updated jegy státusza frissítve 'expired'-re\n";
    }
    
    // Naplózás fájlba
    $log_entry = date('Y-m-d H:i:s') . " - $count jegy lejártra állítva\n";
    file_put_contents(__DIR__ . '/expired_tickets.log', $log_entry, FILE_APPEND);
    
} catch (Exception $e) {
    echo "❌ HIBA: " . $e->getMessage() . "\n";
    error_log("Lejárt jegyek frissítési hiba: " . $e->getMessage());
}
?>