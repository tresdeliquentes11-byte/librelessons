<?php
/**
 * Naprawa błędu Foreign Key w tabeli klasy
 * 
 * Naprawia błąd: wychowawca_id powinien wskazywać na nauczycieli(id), 
 * nie na uzytkownicy(id)
 */

require_once 'includes/config.php';

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naprawa Błędu Wychowawcy</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f5f5f5; 
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 { color: #333; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #5568d3; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .step { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Naprawa Błędu Foreign Key - Wychowawcy</h1>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['napraw'])) {
        echo "<div class='info'><h2>Rozpoczynam naprawę...</h2></div>";
        
        $errors = [];
        $success = [];
        
        try {
            // Krok 1: Sprawdź czy błąd istnieje
            echo "<div class='step'>";
            echo "<h3>Krok 1: Diagnoza problemu</h3>";
            
            $result = $conn->query("
                SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = 'plan_lekcji'
                AND TABLE_NAME = 'klasy'
                AND COLUMN_NAME = 'wychowawca_id'
                AND CONSTRAINT_NAME != 'PRIMARY'
            ");
            
            if ($result->num_rows > 0) {
                $constraint = $result->fetch_assoc();
                if ($constraint['REFERENCED_TABLE_NAME'] == 'uzytkownicy') {
                    echo "<p style='color: red;'>✗ Znaleziono błędny foreign key: {$constraint['CONSTRAINT_NAME']} wskazuje na tabelę 'uzytkownicy'</p>";
                    $has_error = true;
                } elseif ($constraint['REFERENCED_TABLE_NAME'] == 'nauczyciele') {
                    echo "<p style='color: green;'>✓ Foreign key jest już poprawny - wskazuje na tabelę 'nauczyciele'</p>";
                    $has_error = false;
                } else {
                    echo "<p style='color: orange;'>? Foreign key wskazuje na: {$constraint['REFERENCED_TABLE_NAME']}</p>";
                    $has_error = true;
                }
            } else {
                echo "<p style='color: orange;'>? Nie znaleziono foreign key dla wychowawca_id</p>";
                $has_error = true;
            }
            echo "</div>";
            
            if ($has_error) {
                // Krok 2: Usuń błędny foreign key
                echo "<div class='step'>";
                echo "<h3>Krok 2: Usuwanie błędnego foreign key</h3>";
                
                try {
                    $conn->query("ALTER TABLE klasy DROP FOREIGN KEY klasy_ibfk_1");
                    echo "<p style='color: green;'>✓ Usunięto błędny foreign key</p>";
                    $success[] = "Usunięto błędny foreign key";
                } catch (Exception $e) {
                    // Może już nie istnieć - to OK
                    echo "<p style='color: blue;'>ℹ Foreign key już nie istnieje lub ma inną nazwę</p>";
                }
                echo "</div>";
                
                // Krok 3: Wyczyść nieprawidłowe dane
                echo "<div class='step'>";
                echo "<h3>Krok 3: Czyszczenie nieprawidłowych danych</h3>";
                
                $result = $conn->query("
                    SELECT COUNT(*) as cnt
                    FROM klasy
                    WHERE wychowawca_id IS NOT NULL
                    AND wychowawca_id NOT IN (SELECT id FROM nauczyciele)
                ");
                $invalid_count = $result->fetch_assoc()['cnt'];
                
                if ($invalid_count > 0) {
                    $conn->query("
                        UPDATE klasy 
                        SET wychowawca_id = NULL 
                        WHERE wychowawca_id IS NOT NULL 
                        AND wychowawca_id NOT IN (SELECT id FROM nauczyciele)
                    ");
                    echo "<p style='color: orange;'>⚠ Wyczyszczono {$invalid_count} nieprawidłowych przypisań wychowawców</p>";
                    $success[] = "Wyczyszczono nieprawidłowe dane";
                } else {
                    echo "<p style='color: green;'>✓ Wszystkie dane są prawidłowe</p>";
                }
                echo "</div>";
                
                // Krok 4: Dodaj poprawny foreign key
                echo "<div class='step'>";
                echo "<h3>Krok 4: Dodawanie poprawnego foreign key</h3>";
                
                try {
                    $conn->query("
                        ALTER TABLE klasy 
                        ADD CONSTRAINT klasy_ibfk_1 
                        FOREIGN KEY (wychowawca_id) REFERENCES nauczyciele(id) ON DELETE SET NULL
                    ");
                    echo "<p style='color: green;'>✓ Dodano poprawny foreign key</p>";
                    $success[] = "Dodano poprawny foreign key";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>✗ Błąd dodawania foreign key: " . $e->getMessage() . "</p>";
                    $errors[] = "Dodawanie foreign key";
                }
                echo "</div>";
            }
            
            // Podsumowanie
            if (count($errors) == 0) {
                echo "<div class='success'>";
                echo "<h2>✓ Naprawa zakończona pomyślnie!</h2>";
                echo "<p>Teraz możesz bezproblemowo przypisywać wychowawców do klas.</p>";
                echo "<p><strong>Co dalej?</strong></p>";
                echo "<ol>";
                echo "<li>Przejdź do zakładki 'Klasy'</li>";
                echo "<li>Wybierz klasę</li>";
                echo "<li>Przypisz wychowawcę</li>";
                echo "<li>Zapisz - teraz powinno działać!</li>";
                echo "</ol>";
                echo "<a href='dyrektor/klasy.php' class='btn'>Przejdź do Klas</a>";
                echo "</div>";
                
                echo "<div class='warning'>";
                echo "<strong>⚠ Pamiętaj!</strong> Po zakończeniu naprawy usuń ten plik (<code>naprawa_wychowawcy.php</code>) ze względów bezpieczeństwa.";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "<h2>✗ Wystąpiły błędy podczas naprawy</h2>";
                echo "<p>Nie udało się naprawić:</p>";
                echo "<ul>";
                foreach ($errors as $e) {
                    echo "<li>$e</li>";
                }
                echo "</ul>";
                echo "<p>Spróbuj uruchomić naprawę ponownie lub skontaktuj się z administratorem.</p>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<h2>✗ Wystąpił błąd</h2>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
        
    } else {
        // Formularz
        echo "<div class='error'>";
        echo "<h3>🐛 Znaleziono błąd w bazie danych</h3>";
        echo "<p><strong>Błąd:</strong></p>";
        echo "<pre>Cannot add or update a child row: a foreign key constraint fails
(`plan_lekcji`.`klasy`, CONSTRAINT `klasy_ibfk_1` 
FOREIGN KEY (`wychowawca_id`) REFERENCES `uzytkownicy` (`id`))</pre>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>📋 Co spowodowało ten błąd?</h3>";
        echo "<p>W bazie danych jest błąd w definicji klucza obcego:</p>";
        echo "<ul>";
        echo "<li><code>wychowawca_id</code> w tabeli <code>klasy</code> wskazuje na tabelę <code>uzytkownicy</code></li>";
        echo "<li>Ale powinien wskazywać na tabelę <code>nauczyciele</code></li>";
        echo "<li>Gdy próbujesz przypisać wychowawcę, system przekazuje ID z tabeli nauczyciele</li>";
        echo "<li>To ID nie pasuje do tabeli uzytkownicy → błąd!</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h3>🔧 Co zrobi ta naprawa?</h3>";
        echo "<ol>";
        echo "<li>Usunie błędny foreign key (<code>REFERENCES uzytkownicy</code>)</li>";
        echo "<li>Wyczyści ewentualne nieprawidłowe dane</li>";
        echo "<li>Doda poprawny foreign key (<code>REFERENCES nauczyciele</code>)</li>";
        echo "</ol>";
        echo "<p><strong>Jest to bezpieczna operacja</strong> - nie usunie żadnych ważnych danych.</p>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<strong>⚠ Przed naprawą:</strong>";
        echo "<ul>";
        echo "<li>Utwórz kopię zapasową bazy danych (zalecane)</li>";
        echo "<li>Upewnij się, że nie trwa generowanie planu</li>";
        echo "<li>Zamknij wszystkie inne sesje z systemem</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<form method='POST'>";
        echo "<button type='submit' name='napraw' class='btn'>Napraw bazę danych</button>";
        echo "</form>";
        
        echo "<div style='margin-top: 30px;'>";
        echo "<h3>🛠 Alternatywnie: Naprawa przez MySQL</h3>";
        echo "<p>Możesz też uruchomić ten kod SQL bezpośrednio w phpMyAdmin lub konsoli MySQL:</p>";
        echo "<pre>USE plan_lekcji;

ALTER TABLE klasy DROP FOREIGN KEY klasy_ibfk_1;

UPDATE klasy 
SET wychowawca_id = NULL 
WHERE wychowawca_id IS NOT NULL 
AND wychowawca_id NOT IN (SELECT id FROM nauczyciele);

ALTER TABLE klasy 
ADD CONSTRAINT klasy_ibfk_1 
FOREIGN KEY (wychowawca_id) REFERENCES nauczyciele(id) ON DELETE SET NULL;</pre>";
        echo "</div>";
        
        echo "<div style='margin-top: 30px;'>";
        echo "<a href='index.php' class='btn'>Strona logowania</a>";
        echo "<a href='dyrektor/dashboard.php' class='btn'>Panel Dyrektora</a>";
        echo "</div>";
    }
    ?>
</body>
</html>
