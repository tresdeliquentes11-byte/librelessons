<?php
/**
 * Automatyczne przypisanie nauczycieli do klas
 * 
 * Ten skrypt automatycznie przypisze nauczycieli do wszystkich klas
 * zgodnie z wymaganymi przedmiotami, aby można było od razu wygenerować plan.
 */

require_once '../includes/config.php';

set_time_limit(300);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automatyczne Przypisanie Nauczycieli</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f5f5f5; 
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #333; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #5568d3; }
        table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
    </style>
</head>
<body>
    <h1>🎯 Automatyczne Przypisanie Nauczycieli do Klas</h1>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['przypisz'])) {
        echo "<div class='info'><h2>Trwa przypisywanie...</h2></div>";
        
        $stats = [
            'klasy_przetworzone' => 0,
            'przypisania' => 0,
            'bledy' => 0
        ];
        
        // Definicja przedmiotów wymaganych dla każdej klasy
        $przedmioty_podstawowe = [
            'Matematyka' => 5,
            'Język polski' => 5,
            'Język angielski' => 4,
            'Geografia' => 3,
            'Biologia' => 3,
            'Chemia' => 3,
            'Fizyka' => 3,
            'Historia' => 2,
            'WOS' => 2,
            'WF' => 4,
            'Informatyka' => 2
        ];
        
        // Rozszerzenia dla klas
        $rozszerzenia_config = [
            '1A' => ['Matematyka rozszerzona', 'Fizyka rozszerzona'],
            '1B' => ['Matematyka rozszerzona', 'Język angielski rozszerzony'],
            '1C' => ['Fizyka rozszerzona', 'Język angielski rozszerzony'],
            '2A' => ['Matematyka rozszerzona', 'Fizyka rozszerzona'],
            '2B' => ['Matematyka rozszerzona', 'Język angielski rozszerzony'],
            '2C' => ['Fizyka rozszerzona', 'Język angielski rozszerzony'],
            '3A' => ['Matematyka rozszerzona', 'Fizyka rozszerzona'],
            '3B' => ['Matematyka rozszerzona', 'Język angielski rozszerzony'],
            '3C' => ['Fizyka rozszerzona', 'Język angielski rozszerzony'],
            '4A' => ['Matematyka rozszerzona', 'Fizyka rozszerzona'],
            '4B' => ['Matematyka rozszerzona', 'Język angielski rozszerzony'],
            '4C' => ['Fizyka rozszerzona', 'Język angielski rozszerzony']
        ];
        
        // Język obcy do wyboru
        $jezyki_obce = ['Język niemiecki', 'Język hiszpański'];
        
        // Pobierz wszystkie klasy
        $klasy = $conn->query("SELECT * FROM klasy ORDER BY nazwa");
        
        while ($klasa = $klasy->fetch_assoc()) {
            $klasa_id = $klasa['id'];
            $klasa_nazwa = $klasa['nazwa'];
            
            echo "<h3>Klasa $klasa_nazwa</h3>";
            
            // Ustaw rozszerzenia
            $rozszerzenia = $rozszerzenia_config[$klasa_nazwa];
            $conn->query("UPDATE klasy SET rozszerzenie_1 = '{$rozszerzenia[0]}', rozszerzenie_2 = '{$rozszerzenia[1]}' WHERE id = $klasa_id");
            
            // Usuń stare przypisania
            $conn->query("DELETE FROM klasa_przedmioty WHERE klasa_id = $klasa_id");
            
            // Przypisz przedmioty podstawowe
            foreach ($przedmioty_podstawowe as $przedmiot_nazwa => $godziny) {
                $przedmiot = $conn->query("SELECT id FROM przedmioty WHERE nazwa = '$przedmiot_nazwa'")->fetch_assoc();
                
                if ($przedmiot) {
                    // Znajdź nauczyciela dla tego przedmiotu
                    $nauczyciel = $conn->query("
                        SELECT n.id 
                        FROM nauczyciele n
                        JOIN nauczyciel_przedmioty np ON n.id = np.nauczyciel_id
                        WHERE np.przedmiot_id = {$przedmiot['id']}
                        ORDER BY RAND()
                        LIMIT 1
                    ")->fetch_assoc();
                    
                    if ($nauczyciel) {
                        $stmt = $conn->prepare("INSERT INTO klasa_przedmioty (klasa_id, przedmiot_id, nauczyciel_id, ilosc_godzin_tydzien) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiii", $klasa_id, $przedmiot['id'], $nauczyciel['id'], $godziny);
                        $stmt->execute();
                        $stats['przypisania']++;
                        echo "<p style='color: green;'>✓ $przedmiot_nazwa - $godziny godz.</p>";
                    } else {
                        echo "<p style='color: red;'>✗ Brak nauczyciela dla: $przedmiot_nazwa</p>";
                        $stats['bledy']++;
                    }
                }
            }
            
            // Przypisz język obcy (losowy)
            $jezyk = $jezyki_obce[array_rand($jezyki_obce)];
            $przedmiot = $conn->query("SELECT id FROM przedmioty WHERE nazwa = '$jezyk'")->fetch_assoc();
            
            if ($przedmiot) {
                $nauczyciel = $conn->query("
                    SELECT n.id 
                    FROM nauczyciele n
                    JOIN nauczyciel_przedmioty np ON n.id = np.nauczyciel_id
                    WHERE np.przedmiot_id = {$przedmiot['id']}
                    ORDER BY RAND()
                    LIMIT 1
                ")->fetch_assoc();
                
                if ($nauczyciel) {
                    $stmt = $conn->prepare("INSERT INTO klasa_przedmioty (klasa_id, przedmiot_id, nauczyciel_id, ilosc_godzin_tydzien) VALUES (?, ?, ?, 3)");
                    $stmt->bind_param("iii", $klasa_id, $przedmiot['id'], $nauczyciel['id']);
                    $stmt->execute();
                    $stats['przypisania']++;
                    echo "<p style='color: green;'>✓ $jezyk - 3 godz.</p>";
                }
            }
            
            // Przypisz rozszerzenia
            foreach ($rozszerzenia as $rozszerzenie_nazwa) {
                $przedmiot = $conn->query("SELECT id FROM przedmioty WHERE nazwa = '$rozszerzenie_nazwa'")->fetch_assoc();
                
                if ($przedmiot) {
                    $nauczyciel = $conn->query("
                        SELECT n.id 
                        FROM nauczyciele n
                        JOIN nauczyciel_przedmioty np ON n.id = np.nauczyciel_id
                        WHERE np.przedmiot_id = {$przedmiot['id']}
                        ORDER BY RAND()
                        LIMIT 1
                    ")->fetch_assoc();
                    
                    if ($nauczyciel) {
                        $stmt = $conn->prepare("INSERT INTO klasa_przedmioty (klasa_id, przedmiot_id, nauczyciel_id, ilosc_godzin_tydzien) VALUES (?, ?, ?, 3)");
                        $stmt->bind_param("iii", $klasa_id, $przedmiot['id'], $nauczyciel['id']);
                        $stmt->execute();
                        $stats['przypisania']++;
                        echo "<p style='color: green;'>✓ $rozszerzenie_nazwa - 3 godz.</p>";
                    } else {
                        echo "<p style='color: red;'>✗ Brak nauczyciela dla: $rozszerzenie_nazwa</p>";
                        $stats['bledy']++;
                    }
                }
            }
            
            // Przypisz losowego wychowawcę
            $wychowawca = $conn->query("SELECT id FROM nauczyciele ORDER BY RAND() LIMIT 1")->fetch_assoc();
            if ($wychowawca) {
                $conn->query("UPDATE klasy SET wychowawca_id = {$wychowawca['id']} WHERE id = $klasa_id");
                echo "<p style='color: blue;'>👤 Przypisano wychowawcę</p>";
            }
            
            $stats['klasy_przetworzone']++;
        }
        
        echo "<div class='success'>";
        echo "<h2>✓ Przypisywanie zakończone!</h2>";
        echo "<p>Przetworzono klas: <strong>{$stats['klasy_przetworzone']}</strong></p>";
        echo "<p>Utworzono przypisań: <strong>{$stats['przypisania']}</strong></p>";
        if ($stats['bledy'] > 0) {
            echo "<p>Błędów: <strong>{$stats['bledy']}</strong></p>";
        }
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>🎓 Gotowe do wygenerowania planu!</h3>";
        echo "<p><strong>Następne kroki:</strong></p>";
        echo "<ol>";
        echo "<li>Zaloguj się jako dyrektor</li>";
        echo "<li>Przejdź do 'Generuj Plan'</li>";
        echo "<li>Kliknij 'Wygeneruj plan lekcji'</li>";
        echo "<li>Po wygenerowaniu możesz testować zastępstwa</li>";
        echo "</ol>";
        echo "<a href='../dyrektor/dashboard.php' class='btn'>Panel Dyrektora</a>";
        echo "<a href='../dyrektor/plan_generuj.php' class='btn'>Generuj Plan</a>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<strong>⚠ Pamiętaj!</strong> Po zakończeniu testów usuń pliki importu ze względów bezpieczeństwa.";
        echo "</div>";
        
    } else {
        // Formularz
        echo "<div class='info'>";
        echo "<h3>Co zrobi ten skrypt?</h3>";
        echo "<p>Automatycznie:</p>";
        echo "<ul>";
        echo "<li>Przypisze nauczycieli do wszystkich 12 klas</li>";
        echo "<li>Ustawi przedmioty z odpowiednią liczbą godzin</li>";
        echo "<li>Wybierze 2 rozszerzenia dla każdej klasy</li>";
        echo "<li>Przypisze losowy język obcy (niemiecki lub hiszpański)</li>";
        echo "<li>Przypisze wychowawców do klas</li>";
        echo "</ul>";
        echo "<p><strong>Po wykonaniu tego skryptu będziesz mógł od razu wygenerować plan lekcji!</strong></p>";
        echo "</div>";
        
        // Sprawdź czy są nauczyciele w bazie
        $nauczyciele_count = $conn->query("SELECT COUNT(*) as cnt FROM nauczyciele")->fetch_assoc()['cnt'];
        
        if ($nauczyciele_count > 0) {
            echo "<div class='success'>";
            echo "✓ Znaleziono <strong>$nauczyciele_count</strong> nauczycieli w bazie";
            echo "</div>";
            
            echo "<form method='POST'>";
            echo "<button type='submit' name='przypisz' class='btn'>Automatycznie przypisz nauczycieli do klas</button>";
            echo "</form>";
        } else {
            echo "<div class='error'>";
            echo "✗ Brak nauczycieli w bazie!<br><br>";
            echo "Najpierw musisz zaimportować nauczycieli używając <a href='import_csv.php'>import_csv.php</a>";
            echo "</div>";
        }
        
        echo "<div style='margin-top: 30px;'>";
        echo "<a href='import_csv.php' class='btn'>Import nauczycieli i uczniów</a>";
        echo "<a href='../dyrektor/dashboard.php' class='btn'>Panel Dyrektora</a>";
        echo "</div>";
    }
    ?>
</body>
</html>
