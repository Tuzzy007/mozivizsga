<?php
require_once 'config.php';

echo "<h2>🔧 JEGY RENDSZER JAVÍTÁSA</h2>";

try {
    // 1. Ellenőrizzük, hogy léteznek-e már a mezők
    $stmt = $pdo->query("DESCRIBE tickets");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    echo "📊 Meglévő oszlopok: " . implode(', ', $existing_columns) . "<br><br>";
    
    // 2. Mezők hozzáadása egyesével - IF NOT EXISTS NÉLKÜL
    $columns_to_add = [
        'payment_id' => "ALTER TABLE tickets ADD COLUMN payment_id INT NULL AFTER price_paid",
        'payment_status' => "ALTER TABLE tickets ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending' AFTER payment_id",
        'status' => "ALTER TABLE tickets ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER payment_status"
    ];
    
    foreach ($columns_to_add as $column_name => $query) {
        if (!in_array($column_name, $existing_columns)) {
            try {
                $pdo->exec($query);
                echo "✅ Oszlop hozzáadva: $column_name<br>";
            } catch (PDOException $e) {
                echo "❌ Hiba a $column_name hozzáadásakor: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "⚠️ Oszlop már létezik: $column_name<br>";
        }
    }
    
    // 3. payments tábla létrehozása
    $create_payments = "
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            screening_id INT NOT NULL,
            stripe_session_id VARCHAR(255) NOT NULL,
            stripe_payment_intent VARCHAR(255) NULL,
            amount INT NOT NULL,
            seats JSON NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            paid_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (screening_id),
            INDEX (stripe_session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($create_payments);
    echo "✅ payments tábla létrehozva vagy már létezik.<br>";
    
    // 4. Külső kulcsok hozzáadása, ha még nincsenek
    try {
        $pdo->exec("ALTER TABLE payments ADD CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id)");
        echo "✅ Külső kulcs hozzáadva: payments.user_id -> users.id<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠️ Külső kulcs már létezik: payments.user_id<br>";
        } else {
            echo "❌ Külső kulcs hiba: " . $e->getMessage() . "<br>";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE payments ADD CONSTRAINT fk_payments_screening FOREIGN KEY (screening_id) REFERENCES screenings(id)");
        echo "✅ Külső kulcs hozzáadva: payments.screening_id -> screenings.id<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠️ Külső kulcs már létezik: payments.screening_id<br>";
        } else {
            echo "❌ Külső kulcs hiba: " . $e->getMessage() . "<br>";
        }
    }
    
    // 5. VÉGLEGES ELLENŐRZÉS
    echo "<br><br>📊 <strong>VÉGLEGES ELLENŐRZÉS:</strong><br>";
    
    $stmt = $pdo->query("DESCRIBE tickets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Oszlop</th><th>Típus</th><th>Null</th><th>Alapértelmezett</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><br><span style='color:green; font-weight:bold; font-size:1.2em;'>✅ JAVÍTÁS KÉSZ! Most már működnie kell a Stripe fizetésnek!</span>";
    echo "<br><br><a href='test_stripe.php' style='background:#3498db; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block;'>🔍 TESZTELÉS</a>";
    echo "&nbsp;&nbsp;";
    echo "<a href='screenings.php' style='background:#D23A3A; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block;'>🎬 VETÍTÉSEK</a>";
    
} catch (Exception $e) {
    echo "<span style='color:red; font-weight:bold;'>❌ HIBA: " . $e->getMessage() . "</span>";
    echo "<br><br>" . nl2br($e->getTraceAsString());
}
?>