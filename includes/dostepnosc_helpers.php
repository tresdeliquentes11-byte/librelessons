<?php
/**
 * Funkcje pomocnicze do sprawdzania dostępności nauczycieli
 */

/**
 * Oblicza rzeczywisty czas rozpoczęcia i zakończenia lekcji
 *
 * @param int $numer_lekcji Numer lekcji (1-10)
 * @param mysqli $conn Połączenie z bazą danych
 * @return array ['start' => TIME, 'koniec' => TIME]
 */
function oblicz_czas_lekcji($numer_lekcji, $conn) {
    // Pobierz ustawienia
    $ustawienia = pobierz_ustawienia_czasu($conn);

    $godzina_start = $ustawienia['godzina_rozpoczecia'];
    $dlugosc_lekcji = $ustawienia['dlugosc_lekcji'];
    $przerwy = $ustawienia['przerwy'];

    // Oblicz czas rozpoczęcia
    $start_timestamp = strtotime($godzina_start);

    // Dodaj czas poprzednich lekcji i przerw
    for ($i = 1; $i < $numer_lekcji; $i++) {
        $start_timestamp += $dlugosc_lekcji * 60;
        if (isset($przerwy[$i])) {
            $start_timestamp += $przerwy[$i] * 60;
        }
    }

    $koniec_timestamp = $start_timestamp + ($dlugosc_lekcji * 60);

    return [
        'start' => date('H:i:s', $start_timestamp),
        'koniec' => date('H:i:s', $koniec_timestamp)
    ];
}

/**
 * Pobiera ustawienia czasu z bazy danych
 *
 * @param mysqli $conn Połączenie z bazą danych
 * @return array
 */
function pobierz_ustawienia_czasu($conn) {
    // Sprawdź czy tabela istnieje
    $check_table = $conn->query("SHOW TABLES LIKE 'ustawienia_planu'");
    if ($check_table->num_rows == 0) {
        // Wartości domyślne
        return [
            'godzina_rozpoczecia' => '08:00',
            'dlugosc_lekcji' => 45,
            'przerwy' => [1 => 10, 2 => 10, 3 => 15, 4 => 10, 5 => 10, 6 => 10, 7 => 10, 8 => 10, 9 => 10]
        ];
    }

    $godzina_rozpoczecia = '08:00';
    $dlugosc_lekcji = 45;
    $liczba_lekcji = 8;

    // Pobierz godzinę rozpoczęcia
    $result = $conn->query("SELECT wartosc FROM ustawienia_planu WHERE nazwa = 'godzina_rozpoczecia'");
    if ($row = $result->fetch_assoc()) {
        $godzina_rozpoczecia = $row['wartosc'];
    }

    // Pobierz długość lekcji
    $result = $conn->query("SELECT wartosc FROM ustawienia_planu WHERE nazwa = 'dlugosc_lekcji'");
    if ($row = $result->fetch_assoc()) {
        $dlugosc_lekcji = intval($row['wartosc']);
    }

    // Pobierz liczbę lekcji
    $result = $conn->query("SELECT wartosc FROM ustawienia_planu WHERE nazwa = 'liczba_lekcji'");
    if ($row = $result->fetch_assoc()) {
        $liczba_lekcji = intval($row['wartosc']);
    }

    // Pobierz długości przerw
    $przerwy = [];
    for ($i = 1; $i < $liczba_lekcji; $i++) {
        $result = $conn->query("SELECT wartosc FROM ustawienia_planu WHERE nazwa = 'przerwa_po_$i'");
        if ($row = $result->fetch_assoc()) {
            $przerwy[$i] = intval($row['wartosc']);
        } else {
            $przerwy[$i] = ($i == 3) ? 15 : 10; // Domyślne wartości
        }
    }

    return [
        'godzina_rozpoczecia' => $godzina_rozpoczecia,
        'dlugosc_lekcji' => $dlugosc_lekcji,
        'przerwy' => $przerwy
    ];
}

/**
 * Sprawdza czy nauczyciel jest dostępny w danym czasie
 *
 * @param int $nauczyciel_id ID nauczyciela
 * @param string $dzien_tygodnia Nazwa dnia ('poniedzialek', 'wtorek', etc.) lub NULL dla konkretnej daty
 * @param string $data Data w formacie YYYY-MM-DD (opcjonalnie)
 * @param int $numer_lekcji Numer lekcji
 * @param mysqli $conn Połączenie z bazą danych
 * @return bool True jeśli nauczyciel jest dostępny
 */
function sprawdz_dostepnosc_nauczyciela_w_czasie($nauczyciel_id, $dzien_tygodnia, $data, $numer_lekcji, $conn) {
    // Sprawdź czy tabela dostępności istnieje
    $check_table = $conn->query("SHOW TABLES LIKE 'nauczyciel_dostepnosc'");
    if ($check_table->num_rows == 0) {
        // Jeśli tabeli nie ma, zakładamy że wszyscy są dostępni
        return true;
    }

    // Oblicz rzeczywisty czas lekcji
    $czas_lekcji = oblicz_czas_lekcji($numer_lekcji, $conn);
    $lekcja_start = $czas_lekcji['start'];
    $lekcja_koniec = $czas_lekcji['koniec'];

    // Mapowanie nazwy dnia na numer
    $dni_mapping = [
        'poniedzialek' => 1,
        'wtorek' => 2,
        'sroda' => 3,
        'czwartek' => 4,
        'piatek' => 5
    ];
    $dzien_nr = isset($dni_mapping[$dzien_tygodnia]) ? $dni_mapping[$dzien_tygodnia] : null;

    // KROK 1: Sprawdź jednorazowe niedostępności (mają priorytet)
    if ($data) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as cnt
            FROM nauczyciel_dostepnosc
            WHERE nauczyciel_id = ?
            AND typ = 'jednorazowa'
            AND data_konkretna = ?
            AND NOT (
                godzina_do <= ? OR godzina_od >= ?
            )
        ");
        $stmt->bind_param("isss", $nauczyciel_id, $data, $lekcja_start, $lekcja_koniec);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // Jeśli znaleziono niedostępność jednorazową w tym czasie - nauczyciel NIE jest dostępny
        if ($result['cnt'] > 0) {
            return false;
        }
    }

    // KROK 2: Sprawdź stałą dostępność
    if ($dzien_nr) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as cnt
            FROM nauczyciel_dostepnosc
            WHERE nauczyciel_id = ?
            AND typ = 'stala'
            AND dzien_tygodnia = ?
            AND godzina_od <= ?
            AND godzina_do >= ?
        ");
        $stmt->bind_param("iiss", $nauczyciel_id, $dzien_nr, $lekcja_start, $lekcja_koniec);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // Jeśli znaleziono rekord stałej dostępności pokrywający czas lekcji - nauczyciel JEST dostępny
        if ($result['cnt'] > 0) {
            return true;
        }

        // Jeśli nauczyciel ma jakąkolwiek stałą dostępność dla tego dnia, ale nie pokrywa czasu lekcji
        $stmt = $conn->prepare("
            SELECT COUNT(*) as cnt
            FROM nauczyciel_dostepnosc
            WHERE nauczyciel_id = ?
            AND typ = 'stala'
            AND dzien_tygodnia = ?
        ");
        $stmt->bind_param("ii", $nauczyciel_id, $dzien_nr);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // Jeśli ma jakieś ustawienia dla tego dnia ale nie pokrywają lekcji - niedostępny
        if ($result['cnt'] > 0) {
            return false;
        }
    }

    // Jeśli nie ma żadnych ustawień dostępności - zakładamy że jest dostępny
    // (backward compatibility - dla nauczycieli bez ustawionych godzin)
    return true;
}

?>
