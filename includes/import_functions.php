<?php
require_once __DIR__ . '/config.php';

/**
 * Funkcje do importowania danych z plików CSV i XLSX
 */

/**
 * Parsowanie pliku CSV
 * @param string $file_path Ścieżka do pliku
 * @param string $delimiter Separator (domyślnie przecinek)
 * @return array Tablica z danymi
 */
function parse_csv_file($file_path, $delimiter = ',') {
    $data = [];
    
    if (!file_exists($file_path)) {
        throw new Exception("Plik nie istnieje: $file_path");
    }
    
    $file = fopen($file_path, 'r');
    if ($file === false) {
        throw new Exception("Nie można otworzyć pliku: $file_path");
    }
    
    // Pomijamy pierwszy wiersz (nagłówki)
    $headers = fgetcsv($file, 0, $delimiter);
    if ($headers === false) {
        fclose($file);
        throw new Exception("Nie można odczytać nagłówków z pliku CSV");
    }
    
    // Czyścimy nagłówki z białych znaków
    $headers = array_map('trim', $headers);
    
    // Czytamy resztę wierszy
    while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
        if (count($row) >= count($headers)) {
            $data[] = array_combine($headers, array_slice($row, 0, count($headers)));
        }
    }
    
    fclose($file);
    return $data;
}

/**
 * Prosta funkcja do parsowania plików XLSX (bez zewnętrznych bibliotek)
 * Wymaga rozszerzenia zip i simplexml
 * @param string $file_path Ścieżka do pliku
 * @return array Tablica z danymi
 */
function parse_xlsx_file($file_path) {
    if (!class_exists('ZipArchive')) {
        throw new Exception("Rozszerzenie ZipArchive jest wymagane do obsługi plików XLSX");
    }
    
    $zip = new ZipArchive;
    if ($zip->open($file_path) !== TRUE) {
        throw new Exception("Nie można otworzyć pliku XLSX: $file_path");
    }
    
    // Odczytujemy plik z danymi
    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($xml === false) {
        $zip->close();
        throw new Exception("Nie można odczytać danych z pliku XLSX");
    }
    
    $zip->close();
    
    // Parsujemy XML
    $doc = new SimpleXMLElement($xml);
    $rows = [];
    
    // Odczytujemy wiersze
    foreach ($doc->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $value = '';
            if (isset($cell->v)) {
                $value = (string)$cell->v;
                // Konwersja z formatu daty Excela jeśli to konieczne
                if (isset($cell['t']) && (string)$cell['t'] === 's') {
                    // Wartość jest w shared strings - potrzebujemy dodatkowego parsowania
                    // Na razie używamy wartości bezpośrednio
                }
            }
            $rowData[] = $value;
        }
        $rows[] = $rowData;
    }
    
    if (empty($rows)) {
        throw new Exception("Plik XLSX jest pusty lub ma nieprawidłowy format");
    }
    
    // Pierwszy wiersz to nagłówki
    $headers = array_map('trim', array_shift($rows));
    
    // Konwertujemy resztę wierszy na tablicę asocjacyjną
    $data = [];
    foreach ($rows as $row) {
        if (count($row) >= count($headers)) {
            $data[] = array_combine($headers, array_slice($row, 0, count($headers)));
        }
    }
    
    return $data;
}

/**
 * Importowanie uczniów z danych
 * @param array $data Dane uczniów
 * @param mysqli $conn Połączenie z bazą danych
 * @return array Wynik operacji
 */
function import_students($data, $conn) {
    $results = [
        'success' => 0,
        'errors' => 0,
        'details' => []
    ];
    
    foreach ($data as $index => $student) {
        try {
            // Walidacja danych
            if (empty($student['imie']) || empty($student['nazwisko']) || empty($student['login'])) {
                throw new Exception("Brak wymaganych pól: imię, nazwisko lub login");
            }
            
            // Sprawdzamy czy klasa istnieje
            $class_name = trim($student['klasa'] ?? '');
            $klasa_id = null;
            
            if (!empty($class_name)) {
                $stmt = $conn->prepare("SELECT id FROM klasy WHERE nazwa = ?");
                $stmt->bind_param("s", $class_name);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    throw new Exception("Klasa '$class_name' nie istnieje");
                }
                $klasa_id = $result->fetch_assoc()['id'];
                $stmt->close();
            }
            
            // Sprawdzamy czy login jest unikalny
            $login = trim($student['login']);
            $stmt = $conn->prepare("SELECT id FROM uzytkownicy WHERE login = ?");
            $stmt->bind_param("s", $login);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Login '$login' już istnieje");
            }
            $stmt->close();
            
            $conn->begin_transaction();
            
            // Dodajemy użytkownika
            $imie = trim($student['imie']);
            $nazwisko = trim($student['nazwisko']);
            $email = trim($student['email'] ?? '');
            $haslo = password_hash(trim($student['haslo'] ?? 'haslo123'), PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO uzytkownicy (login, haslo, typ, imie, nazwisko, email) VALUES (?, ?, 'uczen', ?, ?, ?)");
            $stmt->bind_param("sssss", $login, $haslo, $imie, $nazwisko, $email);
            $stmt->execute();
            $uzytkownik_id = $conn->insert_id;
            $stmt->close();
            
            // Dodajemy ucznia
            if ($klasa_id) {
                $stmt = $conn->prepare("INSERT INTO uczniowie (uzytkownik_id, klasa_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $uzytkownik_id, $klasa_id);
                $stmt->execute();
                $stmt->close();
            } else {
                // Jeśli nie podano klasy, dodajemy ucznia bez klasy
                $stmt = $conn->prepare("INSERT INTO uczniowie (uzytkownik_id, klasa_id) VALUES (?, NULL)");
                $stmt->bind_param("i", $uzytkownik_id);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->commit();
            $results['success']++;
            $results['details'][] = "Dodano ucznia: $imie $nazwisko";
            
        } catch (Exception $e) {
            $conn->rollback();
            $results['errors']++;
            $results['details'][] = "Błąd w wierszu " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    
    return $results;
}

/**
 * Importowanie nauczycieli z danych
 * @param array $data Dane nauczycieli
 * @param mysqli $conn Połączenie z bazą danych
 * @return array Wynik operacji
 */
function import_teachers($data, $conn) {
    $results = [
        'success' => 0,
        'errors' => 0,
        'details' => []
    ];
    
    foreach ($data as $index => $teacher) {
        try {
            // Walidacja danych
            if (empty($teacher['imie']) || empty($teacher['nazwisko']) || empty($teacher['login'])) {
                throw new Exception("Brak wymaganych pól: imię, nazwisko lub login");
            }
            
            // Sprawdzamy czy login jest unikalny
            $login = trim($teacher['login']);
            $stmt = $conn->prepare("SELECT id FROM uzytkownicy WHERE login = ?");
            $stmt->bind_param("s", $login);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Login '$login' już istnieje");
            }
            $stmt->close();
            
            $conn->begin_transaction();
            
            // Dodajemy użytkownika
            $imie = trim($teacher['imie']);
            $nazwisko = trim($teacher['nazwisko']);
            $email = trim($teacher['email'] ?? '');
            $haslo = password_hash(trim($teacher['haslo'] ?? 'haslo123'), PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO uzytkownicy (login, haslo, typ, imie, nazwisko, email) VALUES (?, ?, 'nauczyciel', ?, ?, ?)");
            $stmt->bind_param("sssss", $login, $haslo, $imie, $nazwisko, $email);
            $stmt->execute();
            $uzytkownik_id = $conn->insert_id;
            $stmt->close();
            
            // Dodajemy nauczyciela
            $stmt = $conn->prepare("INSERT INTO nauczyciele (uzytkownik_id) VALUES (?)");
            $stmt->bind_param("i", $uzytkownik_id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            $results['success']++;
            $results['details'][] = "Dodano nauczyciela: $imie $nazwisko";
            
        } catch (Exception $e) {
            $conn->rollback();
            $results['errors']++;
            $results['details'][] = "Błąd w wierszu " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    
    return $results;
}

/**
 * Importowanie klas z danych
 * @param array $data Dane klas
 * @param mysqli $conn Połączenie z bazą danych
 * @return array Wynik operacji
 */
function import_classes($data, $conn) {
    $results = [
        'success' => 0,
        'errors' => 0,
        'details' => []
    ];
    
    foreach ($data as $index => $class) {
        try {
            // Walidacja danych
            if (empty($class['nazwa'])) {
                throw new Exception("Brak wymaganej nazwy klasy");
            }
            
            $nazwa = trim($class['nazwa']);
            
            // Sprawdzamy czy klasa już istnieje
            $stmt = $conn->prepare("SELECT id FROM klasy WHERE nazwa = ?");
            $stmt->bind_param("s", $nazwa);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Klasa '$nazwa' już istnieje");
            }
            $stmt->close();
            
            // Dodajemy klasę
            $ilosc_godzin = intval($class['ilosc_godzin_dziennie'] ?? 7);
            $rozszerzenie_1 = trim($class['rozszerzenie_1'] ?? '');
            $rozszerzenie_2 = trim($class['rozszerzenie_2'] ?? '');
            
            $stmt = $conn->prepare("INSERT INTO klasy (nazwa, ilosc_godzin_dziennie, rozszerzenie_1, rozszerzenie_2) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $nazwa, $ilosc_godzin, $rozszerzenie_1, $rozszerzenie_2);
            $stmt->execute();
            $stmt->close();
            
            $results['success']++;
            $results['details'][] = "Dodano klasę: $nazwa";
            
        } catch (Exception $e) {
            $results['errors']++;
            $results['details'][] = "Błąd w wierszu " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    
    return $results;
}

/**
 * Importowanie sal z danych
 * @param array $data Dane sal
 * @param mysqli $conn Połączenie z bazą danych
 * @return array Wynik operacji
 */
function import_rooms($data, $conn) {
    $results = [
        'success' => 0,
        'errors' => 0,
        'details' => []
    ];
    
    foreach ($data as $index => $room) {
        try {
            // Walidacja danych
            if (empty($room['numer'])) {
                throw new Exception("Brak wymaganego numeru sali");
            }
            
            $numer = trim($room['numer']);
            
            // Sprawdzamy czy sala już istnieje
            $stmt = $conn->prepare("SELECT id FROM sale WHERE numer = ?");
            $stmt->bind_param("s", $numer);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Sala '$numer' już istnieje");
            }
            $stmt->close();
            
            // Dodajemy salę
            $nazwa = trim($room['nazwa'] ?? '');
            $typ = trim($room['typ'] ?? 'standardowa');
            $pojemnosc = intval($room['pojemnosc'] ?? 30);
            
            // Walidacja typu
            $allowed_types = ['standardowa', 'pracownia', 'sportowa', 'specjalna'];
            if (!in_array($typ, $allowed_types)) {
                $typ = 'standardowa';
            }
            
            $stmt = $conn->prepare("INSERT INTO sale (numer, nazwa, typ, pojemnosc) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $numer, $nazwa, $typ, $pojemnosc);
            $stmt->execute();
            $stmt->close();
            
            $results['success']++;
            $results['details'][] = "Dodano salę: $numer";
            
        } catch (Exception $e) {
            $results['errors']++;
            $results['details'][] = "Błąd w wierszu " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    
    return $results;
}
?>