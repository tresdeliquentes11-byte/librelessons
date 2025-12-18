<?php
// Prosty test importu - można uruchomić tylko w celach deweloperskich
// W normalnym użytkowaniu należy usunąć ten plik

require_once 'includes/config.php';
require_once 'includes/import_functions.php';

echo "<h1>Test Importu Danych</h1>";

// Test importu klas
echo "<h2>Test importu klas</h2>";
try {
    $classes_data = [
        ['nazwa' => '1A', 'ilosc_godzin_dziennie' => 7, 'rozszerzenie_1' => 'matematyka', 'rozszerzenie_2' => 'fizyka'],
        ['nazwa' => '1B', 'ilosc_godzin_dziennie' => 7, 'rozszerzenie_1' => 'polski', 'rozszerzenie_2' => 'historia'],
        ['nazwa' => '2A', 'ilosc_godzin_dziennie' => 7, 'rozszerzenie_1' => 'biologia', 'rozszerzenie_2' => 'chemia']
    ];
    
    $result = import_classes($classes_data, $conn);
    echo "<pre>" . print_r($result, true) . "</pre>";
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage();
}

// Test importu sal
echo "<h2>Test importu sal</h2>";
try {
    $rooms_data = [
        ['numer' => '10', 'nazwa' => 'Sala 10', 'typ' => 'standardowa', 'pojemnosc' => 30],
        ['numer' => '11', 'nazwa' => 'Pracownia testowa', 'typ' => 'pracownia', 'pojemnosc' => 25],
        ['numer' => '12', 'nazwa' => 'Sala sportowa', 'typ' => 'sportowa', 'pojemnosc' => 40]
    ];
    
    $result = import_rooms($rooms_data, $conn);
    echo "<pre>" . print_r($result, true) . "</pre>";
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage();
}

// Test importu nauczycieli
echo "<h2>Test importu nauczycieli</h2>";
try {
    $teachers_data = [
        ['imie' => 'Testowy', 'nazwisko' => 'Nauczyciel', 'login' => 'tnauczyciel', 'haslo' => 'test123', 'email' => 'test@szkola.pl'],
        ['imie' => 'Testowa', 'nazwisko' => 'Nauczycielka', 'login' => 'tnauczycielka', 'haslo' => 'test123', 'email' => 'testowa@szkola.pl']
    ];
    
    $result = import_teachers($teachers_data, $conn);
    echo "<pre>" . print_r($result, true) . "</pre>";
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage();
}

// Test importu uczniów (po dodaniu klas)
echo "<h2>Test importu uczniów</h2>";
try {
    $students_data = [
        ['imie' => 'Testowy', 'nazwisko' => 'Uczeń', 'login' => 'tuczen', 'haslo' => 'test123', 'email' => 'uczen@szkola.pl', 'klasa' => '1A'],
        ['imie' => 'Testowa', 'nazwisko' => 'Uczennica', 'login' => 'tuczennica', 'haslo' => 'test123', 'email' => 'uczennica@szkola.pl', 'klasa' => '1B']
    ];
    
    $result = import_students($students_data, $conn);
    echo "<pre>" . print_r($result, true) . "</pre>";
} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage();
}

echo "<h2>Test zakończony</h2>";
echo "<p>Jeśli widzisz wyniki powyżej, import działa poprawnie. Pamiętaj aby usunąć ten plik test_import.php z serwera!</p>";
?>