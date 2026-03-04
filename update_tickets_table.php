<?php
require_once 'config.php';

echo "🔧 Jegy tábla frissítése...\n\n";

try {
    // Meglévő oszlopok lekérdezése
    $stmt = $pdo->query("DESCRIBE tickets");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    echo "📊 Meglévő oszlopok: " . implode(', ', $existing_columns) . "\n\n";
    
    // status_changed_at mező hozzáadása
    if (!in_array('status_changed_at', $existing_columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN status_changed_at DATETIME NULL AFTER status");
        echo "✅ status_changed_at mező hozzáadva\n";
    } else {
        echo "⚠️ status_changed_at mező már létezik\n";
    }
    
    // status_changed_reason mező hozzáadása
    if (!in_array('status_changed_reason', $existing_columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN status_changed_reason VARCHAR(255) NULL AFTER status_changed_at");
        echo "✅ status_changed_reason mező hozzáadva\n";
    } else {
        echo "⚠️ status_changed_reason mező már létezik\n";
    }
    
    // Expired státusz bevezetése a meglévő adatokra
    $stmt = $pdo->prepare("
        UPDATE tickets t
        JOIN screenings s ON t.screening_id = s.id
        SET t.status = 'expired',
            t.status_changed_at = NOW(),
            t.status_changed_reason = 'Rendszerfrissítés - lejárt vetítés'
        WHERE t.status = 'active'
          AND CONCAT(s.screening_date, ' ', s.screening_time) < NOW()
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "\n✅ $updated régi jegy státusza frissítve 'expired'-re\n";
    
    echo "\n🎉 Adatbázis frissítés sikeresen befejeződött!\n";
    
} catch (PDOException $e) {
    echo "❌ Hiba: " . $e->getMessage() . "\n";
}
?>