<?php
/**
 * Skrypt aktualizacji bazy danych - Moduł Sal
 * 
 * Ten skrypt automatycznie zaktualizuje bazę danych
 * dodając nowe tabele i kolumny potrzebne do zarządzania salami.
 */

require_once 'includes/config.php';

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktualizacja Bazy Danych - Moduł Sal</title>
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
    </style>
</head>
<body>
    <h1>🔄 Aktualizacja Bazy Danych - Moduł Sal</h1>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aktualizuj'])) {
        echo "<div class='info'><h2>Rozpoczynam aktualizację...</h2></div>";
        
        $errors = [];
        $success = [];
        
        // Krok 1: Dodaj kolumny do tabeli sale
        echo "<div class='step'>";
        echo "<h3>Krok 1: Aktualizacja tabeli 'sale'</h3>";
        
        // Sprawdź czy kolumna typ istnieje
        $result = $conn->query("SHOW COLUMNS FROM sale LIKE 'typ'");
        if ($result->num_rows == 0) {
            if ($conn->query("ALTER TABLE sale ADD COLUMN typ ENUM('standardowa', 'pracownia', 'sportowa', 'specjalna') DEFAULT 'standardowa' AFTER nazwa")) {
                echo "<p style='color: green;'>✓ Dodano kolumnę 'typ'</p>";
                $success[] = "Kolumna 'typ' dodana";
            } else {
                echo "<p style='color: red;'>✗ Błąd dodawania kolumny 'typ': " . $conn->error . "</p>";
                $errors[] = "Kolumna 'typ'";
            }
        } else {
            echo "<p style='color: blue;'>ℹ Kolumna 'typ' już istnieje</p>";
        }
        
        // Sprawdź czy kolumna pojemnosc istnieje
        $result = $conn->query("SHOW COLUMNS FROM sale LIKE 'pojemnosc'");
        if ($result->num_rows == 0) {
            if ($conn->query("ALTER TABLE sale ADD COLUMN pojemnosc INT DEFAULT 30 AFTER typ")) {
                echo "<p style='color: green;'>✓ Dodano kolumnę 'pojemnosc'</p>";
                $success[] = "Kolumna 'pojemnosc' dodana";
            } else {
                echo "<p style='color: red;'>✗ Błąd dodawania kolumny 'pojemnosc': " . $conn->error . "</p>";
                $errors[] = "Kolumna 'pojemnosc'";
            }
        } else {
            echo "<p style='color: blue;'>ℹ Kolumna 'pojemnosc' już istnieje</p>";
        }
        echo "</div>";
        
        // Krok 2: Utwórz tabelę sala_przedmioty
        echo "<div class='step'>";
        echo "<h3>Krok 2: Tworzenie tabeli 'sala_przedmioty'</h3>";
        
        $result = $conn->query("SHOW TABLES LIKE 'sala_przedmioty'");
        if ($result->num_rows == 0) {
            $sql = "CREATE TABLE sala_przedmioty (
                id INT PRIMARY KEY AUTO_INCREMENT,
                sala_id INT NOT NULL,
                przedmiot_id INT NOT NULL,
                FOREIGN KEY (sala_id) REFERENCES sale(id) ON DELETE CASCADE,
                FOREIGN KEY (przedmiot_id) REFERENCES przedmioty(id) ON DELETE CASCADE,
                UNIQUE KEY unique_sala_przedmiot (sala_id, przedmiot_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if ($conn->query($sql)) {
                echo "<p style='color: green;'>✓ Utworzono tabelę 'sala_przedmioty'</p>";
                $success[] = "Tabela 'sala_przedmioty' utworzona";
            } else {
                echo "<p style='color: red;'>✗ Błąd tworzenia tabeli 'sala_przedmioty': " . $conn->error . "</p>";
                $errors[] = "Tabela 'sala_przedmioty'";
            }
        } else {
            echo "<p style='color: blue;'>ℹ Tabela 'sala_przedmioty' już istnieje</p>";
        }
        echo "</div>";
        
        // Krok 3: Utwórz tabelę sala_nauczyciele
        echo "<div class='step'>";
        echo "<h3>Krok 3: Tworzenie tabeli 'sala_nauczyciele'</h3>";
        
        $result = $conn->query("SHOW TABLES LIKE 'sala_nauczyciele'");
        if ($result->num_rows == 0) {
            $sql = "CREATE TABLE sala_nauczyciele (
                id INT PRIMARY KEY AUTO_INCREMENT,
                sala_id INT NOT NULL,
                nauczyciel_id INT NOT NULL,
                FOREIGN KEY (sala_id) REFERENCES sale(id) ON DELETE CASCADE,
                FOREIGN KEY (nauczyciel_id) REFERENCES nauczyciele(id) ON DELETE CASCADE,
                UNIQUE KEY unique_sala_nauczyciel (sala_id, nauczyciel_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if ($conn->query($sql)) {
                echo "<p style='color: green;'>✓ Utworzono tabelę 'sala_nauczyciele'</p>";
                $success[] = "Tabela 'sala_nauczyciele' utworzona";
            } else {
                echo "<p style='color: red;'>✗ Błąd tworzenia tabeli 'sala_nauczyciele': " . $conn->error . "</p>";
                $errors[] = "Tabela 'sala_nauczyciele'";
            }
        } else {
            echo "<p style='color: blue;'>ℹ Tabela 'sala_nauczyciele' już istnieje</p>";
        }
        echo "</div>";
        
        // Krok 4: Zaktualizuj istniejące sale
        echo "<div class='step'>";
        echo "<h3>Krok 4: Aktualizacja istniejących sal</h3>";
        
        $conn->query("UPDATE sale SET typ = 'sportowa' WHERE numer = 'SALA-WF'");
        $conn->query("UPDATE sale SET typ = 'pracownia' WHERE numer IN ('201', '202', '203', '204')");
        $conn->query("UPDATE sale SET typ = 'standardowa' WHERE typ IS NULL");
        
        echo "<p style='color: green;'>✓ Zaktualizowano typy istniejących sal</p>";
        $success[] = "Typy sal zaktualizowane";
        echo "</div>";
        
        // Podsumowanie
        if (count($errors) == 0) {
            echo "<div class='success'>";
            echo "<h2>✓ Aktualizacja zakończona pomyślnie!</h2>";
            echo "<p>Wszystkie zmiany zostały zastosowane:</p>";
            echo "<ul>";
            foreach ($success as $s) {
                echo "<li>$s</li>";
            }
            echo "</ul>";
            echo "<p><strong>Co dalej?</strong></p>";
            echo "<ol>";
            echo "<li>Zaloguj się jako dyrektor</li>";
            echo "<li>Przejdź do zakładki 'Sale'</li>";
            echo "<li>Skonfiguruj sale lekcyjne</li>";
            echo "<li>Przypisz przedmioty i nauczycieli do sal</li>";
            echo "</ol>";
            echo "<a href='dyrektor/dashboard.php' class='btn'>Panel Dyrektora</a>";
            echo "<a href='dyrektor/sale.php' class='btn'>Zarządzanie Salami</a>";
            echo "</div>";
            
            echo "<div class='warning'>";
            echo "<strong>⚠ Pamiętaj!</strong> Po zakończeniu aktualizacji usuń ten plik (<code>aktualizacja_sale.php</code>) ze względów bezpieczeństwa.";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<h2>✗ Wystąpiły błędy podczas aktualizacji</h2>";
            echo "<p>Nie udało się zaktualizować:</p>";
            echo "<ul>";
            foreach ($errors as $e) {
                echo "<li>$e</li>";
            }
            echo "</ul>";
            echo "<p>Spróbuj uruchomić aktualizację ponownie lub skontaktuj się z administratorem.</p>";
            echo "</div>";
        }
        
    } else {
        // Formularz
        echo "<div class='info'>";
        echo "<h3>📋 Co zostanie zaktualizowane?</h3>";
        echo "<p>Ten skrypt doda do bazy danych:</p>";
        echo "<ul>";
        echo "<li>Kolumnę <code>typ</code> do tabeli <code>sale</code></li>";
        echo "<li>Kolumnę <code>pojemnosc</code> do tabeli <code>sale</code></li>";
        echo "<li>Tabelę <code>sala_przedmioty</code> do przypisywania przedmiotów do sal</li>";
        echo "<li>Tabelę <code>sala_nauczyciele</code> do przypisywania nauczycieli do sal</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<strong>⚠ Ważne!</strong> Przed uruchomieniem aktualizacji:";
        echo "<ul>";
        echo "<li>Utwórz kopię zapasową bazy danych</li>";
        echo "<li>Upewnij się, że masz prawa do modyfikacji struktury bazy</li>";
        echo "<li>Zamknij wszystkie inne sesje z systemem</li>";
        echo "</ul>";
        echo "</div>";
        
        // Sprawdź czy aktualizacja jest potrzebna
        $needs_update = false;
        $status = [];
        
        $result = $conn->query("SHOW COLUMNS FROM sale LIKE 'typ'");
        if ($result->num_rows == 0) {
            $needs_update = true;
            $status[] = "✗ Brakuje kolumny 'typ' w tabeli 'sale'";
        } else {
            $status[] = "✓ Kolumna 'typ' istnieje";
        }
        
        $result = $conn->query("SHOW COLUMNS FROM sale LIKE 'pojemnosc'");
        if ($result->num_rows == 0) {
            $needs_update = true;
            $status[] = "✗ Brakuje kolumny 'pojemnosc' w tabeli 'sale'";
        } else {
            $status[] = "✓ Kolumna 'pojemnosc' istnieje";
        }
        
        $result = $conn->query("SHOW TABLES LIKE 'sala_przedmioty'");
        if ($result->num_rows == 0) {
            $needs_update = true;
            $status[] = "✗ Brakuje tabeli 'sala_przedmioty'";
        } else {
            $status[] = "✓ Tabela 'sala_przedmioty' istnieje";
        }
        
        $result = $conn->query("SHOW TABLES LIKE 'sala_nauczyciele'");
        if ($result->num_rows == 0) {
            $needs_update = true;
            $status[] = "✗ Brakuje tabeli 'sala_nauczyciele'";
        } else {
            $status[] = "✓ Tabela 'sala_nauczyciele' istnieje";
        }
        
        echo "<div class='step'>";
        echo "<h3>Status bazy danych</h3>";
        foreach ($status as $s) {
            echo "<p>$s</p>";
        }
        echo "</div>";
        
        if ($needs_update) {
            echo "<form method='POST'>";
            echo "<button type='submit' name='aktualizuj' class='btn'>Uruchom aktualizację bazy danych</button>";
            echo "</form>";
        } else {
            echo "<div class='success'>";
            echo "<h3>✓ Baza danych jest aktualna!</h3>";
            echo "<p>Wszystkie niezbędne tabele i kolumny już istnieją. Możesz przejść do zarządzania salami.</p>";
            echo "<a href='dyrektor/sale.php' class='btn'>Zarządzanie Salami</a>";
            echo "</div>";
            
            echo "<div class='warning'>";
            echo "<strong>⚠ Pamiętaj!</strong> Usuń ten plik (<code>aktualizacja_sale.php</code>) ze względów bezpieczeństwa.";
            echo "</div>";
        }
        
        echo "<div style='margin-top: 30px;'>";
        echo "<a href='index.php' class='btn'>Strona logowania</a>";
        echo "<a href='dyrektor/dashboard.php' class='btn'>Panel Dyrektora</a>";
        echo "</div>";
    }
    ?>
</body>
</html>
