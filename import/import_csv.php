<?php
/**
 * Skrypt importu danych testowych z CSV
 * 
 * Importuje:
 * - 30 nauczycieli z różnymi przedmiotami
 * - 360 uczniów (30 na każdą z 12 klas)
 */

require_once '../includes/config.php';

set_time_limit(300); // 5 minut na import

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Danych Testowych</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f5f5f5; 
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #333; }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px; 
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px; 
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px; 
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .progress {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover { background: #5568d3; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>📊 Import Danych Testowych</h1>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
        echo "<div class='progress'>";
        echo "<h2>Trwa import danych...</h2>";
        
        $stats = [
            'nauczyciele_dodani' => 0,
            'nauczyciele_bledy' => 0,
            'uczniowie_dodani' => 0,
            'uczniowie_bledy' => 0,
            'przedmioty_przypisane' => 0
        ];
        
        // IMPORT NAUCZYCIELI
        echo "<h3>1. Import nauczycieli</h3>";
        
        $csv_file = 'nauczyciele.csv';
        if (file_exists($csv_file)) {
            $file = fopen($csv_file, 'r');
            $header = fgetcsv($file); // Pomijamy nagłówek
            
            while (($data = fgetcsv($file)) !== FALSE) {
                $imie = $data[0];
                $nazwisko = $data[1];
                $email = $data[2];
                $login = $data[3];
                $haslo_plaintext = $data[4];
                $przedmioty_str = $data[5];
                
                $haslo = password_hash($haslo_plaintext, PASSWORD_DEFAULT);
                
                // Sprawdź czy użytkownik już istnieje
                $check = $conn->query("SELECT id FROM uzytkownicy WHERE login = '$login'");
                if ($check->num_rows > 0) {
                    echo "<p style='color: orange;'>⚠ Pomijam nauczyciela: $imie $nazwisko (login już istnieje)</p>";
                    continue;
                }
                
                $conn->begin_transaction();
                
                try {
                    // Dodaj użytkownika
                    $stmt = $conn->prepare("INSERT INTO uzytkownicy (login, haslo, typ, imie, nazwisko, email, aktywny) VALUES (?, ?, 'nauczyciel', ?, ?, ?, 1)");
                    $stmt->bind_param("sssss", $login, $haslo, $imie, $nazwisko, $email);
                    $stmt->execute();
                    $uzytkownik_id = $conn->insert_id;
                    
                    // Dodaj nauczyciela
                    $stmt = $conn->prepare("INSERT INTO nauczyciele (uzytkownik_id) VALUES (?)");
                    $stmt->bind_param("i", $uzytkownik_id);
                    $stmt->execute();
                    $nauczyciel_id = $conn->insert_id;
                    
                    // Przypisz przedmioty
                    $przedmioty_lista = explode(',', $przedmioty_str);
                    foreach ($przedmioty_lista as $przedmiot_nazwa) {
                        $przedmiot_nazwa = trim($przedmiot_nazwa);
                        $przedmiot = $conn->query("SELECT id FROM przedmioty WHERE nazwa = '$przedmiot_nazwa'")->fetch_assoc();
                        
                        if ($przedmiot) {
                            $stmt = $conn->prepare("INSERT INTO nauczyciel_przedmioty (nauczyciel_id, przedmiot_id) VALUES (?, ?)");
                            $stmt->bind_param("ii", $nauczyciel_id, $przedmiot['id']);
                            $stmt->execute();
                            $stats['przedmioty_przypisane']++;
                        }
                    }
                    
                    $conn->commit();
                    echo "<p style='color: green;'>✓ Dodano: $imie $nazwisko</p>";
                    $stats['nauczyciele_dodani']++;
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "<p style='color: red;'>✗ Błąd dla: $imie $nazwisko - {$e->getMessage()}</p>";
                    $stats['nauczyciele_bledy']++;
                }
            }
            fclose($file);
        } else {
            echo "<div class='error'>Plik nauczyciele.csv nie został znaleziony!</div>";
        }
        
        // IMPORT UCZNIÓW
        echo "<h3>2. Import uczniów</h3>";
        
        $csv_file = 'uczniowie.csv';
        if (file_exists($csv_file)) {
            $file = fopen($csv_file, 'r');
            $header = fgetcsv($file); // Pomijamy nagłówek
            
            while (($data = fgetcsv($file)) !== FALSE) {
                $imie = $data[0];
                $nazwisko = $data[1];
                $klasa_nazwa = $data[2];
                $login = $data[3];
                $haslo_plaintext = $data[4];
                
                $haslo = password_hash($haslo_plaintext, PASSWORD_DEFAULT);
                
                // Sprawdź czy użytkownik już istnieje
                $check = $conn->query("SELECT id FROM uzytkownicy WHERE login = '$login'");
                if ($check->num_rows > 0) {
                    $stats['uczniowie_bledy']++;
                    continue;
                }
                
                // Pobierz ID klasy
                $klasa = $conn->query("SELECT id FROM klasy WHERE nazwa = '$klasa_nazwa'")->fetch_assoc();
                if (!$klasa) {
                    echo "<p style='color: red;'>✗ Klasa $klasa_nazwa nie istnieje dla ucznia: $imie $nazwisko</p>";
                    $stats['uczniowie_bledy']++;
                    continue;
                }
                
                $conn->begin_transaction();
                
                try {
                    // Dodaj użytkownika
                    $stmt = $conn->prepare("INSERT INTO uzytkownicy (login, haslo, typ, imie, nazwisko, aktywny) VALUES (?, ?, 'uczen', ?, ?, 1)");
                    $stmt->bind_param("ssss", $login, $haslo, $imie, $nazwisko);
                    $stmt->execute();
                    $uzytkownik_id = $conn->insert_id;
                    
                    // Dodaj ucznia
                    $stmt = $conn->prepare("INSERT INTO uczniowie (uzytkownik_id, klasa_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $uzytkownik_id, $klasa['id']);
                    $stmt->execute();
                    
                    $conn->commit();
                    $stats['uczniowie_dodani']++;
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $stats['uczniowie_bledy']++;
                }
            }
            fclose($file);
            
            echo "<p style='color: green;'>✓ Dodano $stats[uczniowie_dodani] uczniów</p>";
            if ($stats['uczniowie_bledy'] > 0) {
                echo "<p style='color: orange;'>⚠ Pominięto $stats[uczniowie_bledy] uczniów (duplikaty)</p>";
            }
        } else {
            echo "<div class='error'>Plik uczniowie.csv nie został znaleziony!</div>";
        }
        
        echo "</div>";
        
        // Statystyki końcowe
        echo "<div class='stats'>";
        echo "<div class='stat-box'>";
        echo "<div class='stat-number'>{$stats['nauczyciele_dodani']}</div>";
        echo "<div class='stat-label'>Dodanych nauczycieli</div>";
        echo "</div>";
        
        echo "<div class='stat-box'>";
        echo "<div class='stat-number'>{$stats['uczniowie_dodani']}</div>";
        echo "<div class='stat-label'>Dodanych uczniów</div>";
        echo "</div>";
        
        echo "<div class='stat-box'>";
        echo "<div class='stat-number'>{$stats['przedmioty_przypisane']}</div>";
        echo "<div class='stat-label'>Przypisań przedmiotów</div>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='success'>";
        echo "<h3>✓ Import zakończony pomyślnie!</h3>";
        echo "<p><strong>Następne kroki:</strong></p>";
        echo "<ol>";
        echo "<li>Przejdź do panelu dyrektora</li>";
        echo "<li>W sekcji 'Klasy' przypisz nauczycieli do przedmiotów dla każdej klasy</li>";
        echo "<li>Wybierz rozszerzenia dla każdej klasy</li>";
        echo "<li>Wygeneruj plan lekcji</li>";
        echo "<li>Przetestuj system zastępstw</li>";
        echo "</ol>";
        echo "<a href='../index.php' class='btn'>Przejdź do logowania</a>";
        echo "<a href='auto_przypisz.php' class='btn'>Automatycznie przypisz nauczycieli do klas</a>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<strong>⚠ Ważne!</strong> Po zakończeniu testów, usuń ten plik (import_csv.php) ze względów bezpieczeństwa.";
        echo "</div>";
        
    } else {
        // Formularz
        echo "<div class='info'>";
        echo "<h3>Informacje o imporcie</h3>";
        echo "<p>Ten skrypt zaimportuje do bazy danych:</p>";
        echo "<ul>";
        echo "<li><strong>30 nauczycieli</strong> z różnymi przedmiotami</li>";
        echo "<li><strong>360 uczniów</strong> (30 dla każdej z 12 klas)</li>";
        echo "</ul>";
        echo "<p>Wszystkie konta będą miały hasło: <code>nauczyciel123</code> lub <code>uczen123</code></p>";
        echo "</div>";
        
        // Sprawdź czy pliki CSV istnieją
        $nauczyciele_exist = file_exists('nauczyciele.csv');
        $uczniowie_exist = file_exists('uczniowie.csv');
        
        if ($nauczyciele_exist && $uczniowie_exist) {
            echo "<div class='success'>";
            echo "✓ Pliki CSV zostały znalezione:<br>";
            echo "- nauczyciele.csv<br>";
            echo "- uczniowie.csv";
            echo "</div>";
            
            echo "<form method='POST'>";
            echo "<button type='submit' name='import' class='btn'>Rozpocznij import danych</button>";
            echo "</form>";
        } else {
            echo "<div class='error'>";
            echo "✗ Nie znaleziono plików CSV!<br><br>";
            echo "Upewnij się, że pliki znajdują się w katalogu: <code>/plan-lekcji/import/</code><br>";
            if (!$nauczyciele_exist) echo "- Brakuje: nauczyciele.csv<br>";
            if (!$uczniowie_exist) echo "- Brakuje: uczniowie.csv<br>";
            echo "</div>";
        }
        
        echo "<div style='margin-top: 30px;'>";
        echo "<a href='../dyrektor/dashboard.php' class='btn'>Powrót do panelu dyrektora</a>";
        echo "</div>";
    }
    ?>
</body>
</html>
