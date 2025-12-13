<?php
/**
 * © 2025 TresDeliquentes. Wszystkie prawa zastrzeżone.
 * LibreLessons - System Oceniania v4.0
 * Funkcje pomocnicze do zarządzania ocenami
 */

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

// ============================================================
// FUNKCJE POBIERANIA USTAWIEŃ
// ============================================================

/**
 * Pobiera ustawienie systemu oceniania
 */
function pobierz_ustawienie_oceniania($klucz, $domyslna = null) {
    global $conn;

    $stmt = $conn->prepare("SELECT wartosc FROM ustawienia_oceniania WHERE klucz = ?");
    $stmt->bind_param("s", $klucz);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['wartosc'];
    }

    return $domyslna;
}

/**
 * Pobiera aktualny rok szkolny
 */
function pobierz_aktualny_rok_szkolny() {
    return pobierz_ustawienie_oceniania('aktualny_rok_szkolny', date('Y') . '/' . (date('Y') + 1));
}

/**
 * Pobiera aktualny semestr
 */
function pobierz_aktualny_semestr() {
    return pobierz_ustawienie_oceniania('aktualny_semestr', '1');
}

/**
 * Pobiera dozwolone wartości ocen
 */
function pobierz_dozwolone_oceny() {
    $oceny_str = pobierz_ustawienie_oceniania('oceny_dopuszczalne', '1,1.5,2,2.5,3,3.5,4,4.5,5,5.5,6');
    return array_map('floatval', explode(',', $oceny_str));
}

// ============================================================
// FUNKCJE KATEGORII OCEN
// ============================================================

/**
 * Pobiera wszystkie aktywne kategorie ocen
 */
function pobierz_kategorie_ocen() {
    global $conn;

    $result = $conn->query("SELECT * FROM kategorie_ocen WHERE aktywna = 1 ORDER BY kolejnosc, nazwa");
    $kategorie = [];

    while ($row = $result->fetch_assoc()) {
        $kategorie[] = $row;
    }

    return $kategorie;
}

/**
 * Pobiera kategorię oceny po ID
 */
function pobierz_kategorie($id) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM kategorie_ocen WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// ============================================================
// FUNKCJE DODAWANIA/EDYCJI OCEN
// ============================================================

/**
 * Dodaje nową ocenę
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function dodaj_ocene($dane) {
    global $conn;

    // Walidacja wymaganych pól
    $wymagane = ['uczen_id', 'przedmiot_id', 'nauczyciel_id', 'klasa_id', 'kategoria_id', 'ocena'];
    foreach ($wymagane as $pole) {
        if (!isset($dane[$pole]) || $dane[$pole] === '') {
            return ['success' => false, 'message' => "Brak wymaganego pola: $pole", 'id' => null];
        }
    }

    // Walidacja wartości oceny
    $dozwolone = pobierz_dozwolone_oceny();
    if (!in_array(floatval($dane['ocena']), $dozwolone)) {
        return ['success' => false, 'message' => "Niedozwolona wartość oceny: {$dane['ocena']}", 'id' => null];
    }

    // Ustaw domyślne wartości
    $data_wystawienia = $dane['data_wystawienia'] ?? date('Y-m-d');
    $semestr = $dane['semestr'] ?? pobierz_aktualny_semestr();
    $rok_szkolny = $dane['rok_szkolny'] ?? pobierz_aktualny_rok_szkolny();
    $komentarz = $dane['komentarz'] ?? null;
    $waga_indywidualna = $dane['waga_indywidualna'] ?? null;
    $czy_poprawa = $dane['czy_poprawa'] ?? 0;
    $oryginalna_ocena_id = $dane['oryginalna_ocena_id'] ?? null;

    // Generuj hash RODO
    $hash_rodo = hash('sha256', $dane['uczen_id'] . $dane['przedmiot_id'] . time() . random_bytes(8));

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            INSERT INTO oceny (uczen_id, przedmiot_id, nauczyciel_id, klasa_id, kategoria_id,
                              ocena, waga_indywidualna, komentarz, data_wystawienia,
                              semestr, rok_szkolny, czy_poprawa, oryginalna_ocena_id, hash_rodo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param("iiiiddssssiiss",
            $dane['uczen_id'],
            $dane['przedmiot_id'],
            $dane['nauczyciel_id'],
            $dane['klasa_id'],
            $dane['kategoria_id'],
            $dane['ocena'],
            $waga_indywidualna,
            $komentarz,
            $data_wystawienia,
            $semestr,
            $rok_szkolny,
            $czy_poprawa,
            $oryginalna_ocena_id,
            $hash_rodo
        );

        if (!$stmt->execute()) {
            throw new Exception("Błąd dodawania oceny: " . $stmt->error);
        }

        $ocena_id = $conn->insert_id;

        // Zapisz do historii (audit trail RODO)
        loguj_zmiane_oceny($ocena_id, null, 'utworzenie', null, $dane);

        // Jeśli to poprawa, oznacz oryginalną ocenę jako nieliczoną
        if ($czy_poprawa && $oryginalna_ocena_id) {
            $stmt2 = $conn->prepare("UPDATE oceny SET czy_liczona = 0 WHERE id = ?");
            $stmt2->bind_param("i", $oryginalna_ocena_id);
            $stmt2->execute();
        }

        $conn->commit();

        return ['success' => true, 'message' => 'Ocena została dodana', 'id' => $ocena_id];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Błąd dodawania oceny: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage(), 'id' => null];
    }
}

/**
 * Edytuje istniejącą ocenę
 */
function edytuj_ocene($ocena_id, $dane, $powod_zmiany = null) {
    global $conn;

    // Pobierz starą wartość do historii
    $stmt = $conn->prepare("SELECT * FROM oceny WHERE id = ?");
    $stmt->bind_param("i", $ocena_id);
    $stmt->execute();
    $stara_ocena = $stmt->get_result()->fetch_assoc();

    if (!$stara_ocena) {
        return ['success' => false, 'message' => 'Ocena nie istnieje'];
    }

    // Walidacja oceny jeśli zmieniana
    if (isset($dane['ocena'])) {
        $dozwolone = pobierz_dozwolone_oceny();
        if (!in_array(floatval($dane['ocena']), $dozwolone)) {
            return ['success' => false, 'message' => "Niedozwolona wartość oceny"];
        }
    }

    $conn->begin_transaction();

    try {
        // Buduj zapytanie UPDATE dynamicznie
        $pola = [];
        $typy = "";
        $wartosci = [];

        $dozwolone_pola = ['ocena', 'kategoria_id', 'komentarz', 'waga_indywidualna',
                          'data_wystawienia', 'czy_liczona', 'widoczna_dla_ucznia'];

        foreach ($dozwolone_pola as $pole) {
            if (isset($dane[$pole])) {
                $pola[] = "$pole = ?";

                if (in_array($pole, ['ocena', 'waga_indywidualna'])) {
                    $typy .= "d";
                } elseif (in_array($pole, ['kategoria_id', 'czy_liczona', 'widoczna_dla_ucznia'])) {
                    $typy .= "i";
                } else {
                    $typy .= "s";
                }

                $wartosci[] = $dane[$pole];
            }
        }

        if (empty($pola)) {
            return ['success' => false, 'message' => 'Brak pól do aktualizacji'];
        }

        $wartosci[] = $ocena_id;
        $typy .= "i";

        $sql = "UPDATE oceny SET " . implode(", ", $pola) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($typy, ...$wartosci);

        if (!$stmt->execute()) {
            throw new Exception("Błąd edycji oceny: " . $stmt->error);
        }

        // Zapisz do historii
        loguj_zmiane_oceny($ocena_id, null, 'edycja', $stara_ocena, $dane, $powod_zmiany);

        $conn->commit();

        return ['success' => true, 'message' => 'Ocena została zaktualizowana'];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Błąd edycji oceny: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Usuwa ocenę (soft delete - oznacza jako nieliczoną)
 */
function usun_ocene($ocena_id, $powod = null) {
    global $conn;

    // Pobierz starą wartość do historii
    $stmt = $conn->prepare("SELECT * FROM oceny WHERE id = ?");
    $stmt->bind_param("i", $ocena_id);
    $stmt->execute();
    $stara_ocena = $stmt->get_result()->fetch_assoc();

    if (!$stara_ocena) {
        return ['success' => false, 'message' => 'Ocena nie istnieje'];
    }

    $conn->begin_transaction();

    try {
        // Soft delete - oznacz jako nieliczoną i niewidoczną
        $stmt = $conn->prepare("UPDATE oceny SET czy_liczona = 0, widoczna_dla_ucznia = 0 WHERE id = ?");
        $stmt->bind_param("i", $ocena_id);

        if (!$stmt->execute()) {
            throw new Exception("Błąd usuwania oceny");
        }

        // Zapisz do historii
        loguj_zmiane_oceny($ocena_id, null, 'usuniecie', $stara_ocena, null, $powod);

        $conn->commit();

        return ['success' => true, 'message' => 'Ocena została usunięta'];

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Dodaje poprawę oceny
 */
function dodaj_poprawe($oryginalna_ocena_id, $nowa_ocena, $nauczyciel_id, $komentarz = null) {
    global $conn;

    // Pobierz oryginalną ocenę
    $stmt = $conn->prepare("SELECT * FROM oceny WHERE id = ?");
    $stmt->bind_param("i", $oryginalna_ocena_id);
    $stmt->execute();
    $oryginalna = $stmt->get_result()->fetch_assoc();

    if (!$oryginalna) {
        return ['success' => false, 'message' => 'Oryginalna ocena nie istnieje'];
    }

    // Sprawdź limit popraw
    $max_popraw = intval(pobierz_ustawienie_oceniania('max_popraw', 1));
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM oceny WHERE oryginalna_ocena_id = ?");
    $stmt->bind_param("i", $oryginalna_ocena_id);
    $stmt->execute();
    $liczba_popraw = $stmt->get_result()->fetch_assoc()['cnt'];

    if ($liczba_popraw >= $max_popraw) {
        return ['success' => false, 'message' => "Przekroczono limit popraw ($max_popraw)"];
    }

    // Sprawdź czas na poprawę
    $czas_dni = intval(pobierz_ustawienie_oceniania('czas_na_poprawe_dni', 14));
    $data_graniczna = date('Y-m-d', strtotime($oryginalna['data_wystawienia'] . " +$czas_dni days"));

    if (date('Y-m-d') > $data_graniczna) {
        return ['success' => false, 'message' => "Minął termin na poprawę (do $data_graniczna)"];
    }

    // Dodaj poprawę
    return dodaj_ocene([
        'uczen_id' => $oryginalna['uczen_id'],
        'przedmiot_id' => $oryginalna['przedmiot_id'],
        'nauczyciel_id' => $nauczyciel_id,
        'klasa_id' => $oryginalna['klasa_id'],
        'kategoria_id' => $oryginalna['kategoria_id'],
        'ocena' => $nowa_ocena,
        'komentarz' => $komentarz ?? "Poprawa oceny z dnia " . formatuj_date($oryginalna['data_wystawienia']),
        'waga_indywidualna' => $oryginalna['waga_indywidualna'],
        'semestr' => $oryginalna['semestr'],
        'rok_szkolny' => $oryginalna['rok_szkolny'],
        'czy_poprawa' => 1,
        'oryginalna_ocena_id' => $oryginalna_ocena_id
    ]);
}

// ============================================================
// FUNKCJE OCEN KOŃCOWYCH
// ============================================================

/**
 * Wystawia ocenę końcową (śródroczną lub roczną)
 */
function wystaw_ocene_koncowa($dane) {
    global $conn;

    $wymagane = ['uczen_id', 'przedmiot_id', 'nauczyciel_id', 'klasa_id', 'typ', 'ocena'];
    foreach ($wymagane as $pole) {
        if (!isset($dane[$pole])) {
            return ['success' => false, 'message' => "Brak wymaganego pola: $pole"];
        }
    }

    // Walidacja oceny końcowej (1-6 lub 0 dla nieklasyfikowany)
    if ($dane['ocena'] < 0 || $dane['ocena'] > 6 || ($dane['ocena'] != 0 && $dane['ocena'] != intval($dane['ocena']))) {
        return ['success' => false, 'message' => 'Ocena końcowa musi być liczbą całkowitą 0-6'];
    }

    $rok_szkolny = $dane['rok_szkolny'] ?? pobierz_aktualny_rok_szkolny();
    $semestr = $dane['semestr'] ?? pobierz_aktualny_semestr();
    $data_wystawienia = $dane['data_wystawienia'] ?? date('Y-m-d');

    // Oblicz średnią ważoną
    $srednia = oblicz_srednia_wazona($dane['uczen_id'], $dane['przedmiot_id'], $semestr, $rok_szkolny);

    // Generuj hash RODO
    $hash_rodo = hash('sha256', $dane['uczen_id'] . $dane['przedmiot_id'] . $dane['typ'] . time());

    $conn->begin_transaction();

    try {
        // Sprawdź czy już istnieje
        $stmt = $conn->prepare("
            SELECT id FROM oceny_koncowe
            WHERE uczen_id = ? AND przedmiot_id = ? AND typ = ? AND rok_szkolny = ?
        ");
        $stmt->bind_param("iiss", $dane['uczen_id'], $dane['przedmiot_id'], $dane['typ'], $rok_szkolny);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            // Aktualizuj istniejącą
            $stmt = $conn->prepare("
                UPDATE oceny_koncowe
                SET ocena = ?, ocena_proponowana = ?, komentarz = ?, srednia_wazona = ?,
                    data_wystawienia = ?, nauczyciel_id = ?, semestr = ?
                WHERE id = ?
            ");
            $stmt->bind_param("iisdsiis",
                $dane['ocena'],
                $dane['ocena_proponowana'] ?? null,
                $dane['komentarz'] ?? null,
                $srednia,
                $data_wystawienia,
                $dane['nauczyciel_id'],
                $semestr,
                $existing['id']
            );
            $ocena_id = $existing['id'];
        } else {
            // Dodaj nową
            $stmt = $conn->prepare("
                INSERT INTO oceny_koncowe (uczen_id, przedmiot_id, nauczyciel_id, klasa_id, typ,
                                          ocena, ocena_proponowana, komentarz, srednia_wazona,
                                          data_wystawienia, semestr, rok_szkolny, hash_rodo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiiisissdsss",
                $dane['uczen_id'],
                $dane['przedmiot_id'],
                $dane['nauczyciel_id'],
                $dane['klasa_id'],
                $dane['typ'],
                $dane['ocena'],
                $dane['ocena_proponowana'] ?? null,
                $dane['komentarz'] ?? null,
                $srednia,
                $data_wystawienia,
                $semestr,
                $rok_szkolny,
                $hash_rodo
            );
            $ocena_id = $conn->insert_id;
        }

        if (!$stmt->execute()) {
            throw new Exception("Błąd zapisywania oceny końcowej: " . $stmt->error);
        }

        // Zapisz do historii
        loguj_zmiane_oceny(null, $ocena_id, $existing ? 'edycja' : 'utworzenie', null, $dane);

        $conn->commit();

        return ['success' => true, 'message' => 'Ocena końcowa została wystawiona', 'id' => $ocena_id];

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ============================================================
// FUNKCJE OBLICZANIA ŚREDNICH
// ============================================================

/**
 * Oblicza średnią ważoną dla ucznia z przedmiotu
 */
function oblicz_srednia_wazona($uczen_id, $przedmiot_id, $semestr = null, $rok_szkolny = null) {
    global $conn;

    $semestr = $semestr ?? pobierz_aktualny_semestr();
    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();

    $stmt = $conn->prepare("
        SELECT
            SUM(o.ocena * COALESCE(o.waga_indywidualna, ko.waga)) as suma_wazona,
            SUM(COALESCE(o.waga_indywidualna, ko.waga)) as suma_wag
        FROM oceny o
        JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
        WHERE o.uczen_id = ?
          AND o.przedmiot_id = ?
          AND o.semestr = ?
          AND o.rok_szkolny = ?
          AND o.czy_liczona = 1
    ");

    $stmt->bind_param("iiss", $uczen_id, $przedmiot_id, $semestr, $rok_szkolny);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['suma_wag'] > 0) {
        return round($result['suma_wazona'] / $result['suma_wag'], 2);
    }

    return null;
}

/**
 * Oblicza średnią ogólną ucznia ze wszystkich przedmiotów
 */
function oblicz_srednia_ogolna($uczen_id, $semestr = null, $rok_szkolny = null) {
    global $conn;

    $semestr = $semestr ?? pobierz_aktualny_semestr();
    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();

    $stmt = $conn->prepare("
        SELECT
            o.przedmiot_id,
            SUM(o.ocena * COALESCE(o.waga_indywidualna, ko.waga)) /
            SUM(COALESCE(o.waga_indywidualna, ko.waga)) as srednia_przedmiotu
        FROM oceny o
        JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
        WHERE o.uczen_id = ?
          AND o.semestr = ?
          AND o.rok_szkolny = ?
          AND o.czy_liczona = 1
        GROUP BY o.przedmiot_id
    ");

    $stmt->bind_param("iss", $uczen_id, $semestr, $rok_szkolny);
    $stmt->execute();
    $result = $stmt->get_result();

    $srednie = [];
    while ($row = $result->fetch_assoc()) {
        $srednie[] = $row['srednia_przedmiotu'];
    }

    if (count($srednie) > 0) {
        return round(array_sum($srednie) / count($srednie), 2);
    }

    return null;
}

/**
 * Oblicza średnią klasy z przedmiotu
 */
function oblicz_srednia_klasy($klasa_id, $przedmiot_id, $semestr = null, $rok_szkolny = null) {
    global $conn;

    $semestr = $semestr ?? pobierz_aktualny_semestr();
    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();

    $stmt = $conn->prepare("
        SELECT
            ROUND(
                SUM(o.ocena * COALESCE(o.waga_indywidualna, ko.waga)) /
                SUM(COALESCE(o.waga_indywidualna, ko.waga)),
            2) as srednia
        FROM oceny o
        JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
        WHERE o.klasa_id = ?
          AND o.przedmiot_id = ?
          AND o.semestr = ?
          AND o.rok_szkolny = ?
          AND o.czy_liczona = 1
    ");

    $stmt->bind_param("iiss", $klasa_id, $przedmiot_id, $semestr, $rok_szkolny);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc()['srednia'];
}

/**
 * Przewiduje ocenę końcową na podstawie średniej ważonej
 */
function przewiduj_ocene_koncowa($srednia_wazona) {
    if ($srednia_wazona === null) return null;

    // Progi ocen (można dostosować)
    if ($srednia_wazona >= 5.5) return 6;
    if ($srednia_wazona >= 4.5) return 5;
    if ($srednia_wazona >= 3.5) return 4;
    if ($srednia_wazona >= 2.5) return 3;
    if ($srednia_wazona >= 1.5) return 2;
    return 1;
}

// ============================================================
// FUNKCJE POBIERANIA OCEN
// ============================================================

/**
 * Pobiera oceny ucznia z przedmiotu
 */
function pobierz_oceny_ucznia($uczen_id, $przedmiot_id = null, $semestr = null, $rok_szkolny = null) {
    global $conn;

    $semestr = $semestr ?? pobierz_aktualny_semestr();
    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();

    $sql = "
        SELECT o.*, ko.nazwa as kategoria_nazwa, ko.waga as kategoria_waga, ko.kolor, ko.ikona,
               p.nazwa as przedmiot_nazwa,
               CONCAT(u.imie, ' ', u.nazwisko) as nauczyciel_nazwa
        FROM oceny o
        JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
        JOIN przedmioty p ON o.przedmiot_id = p.id
        JOIN nauczyciele n ON o.nauczyciel_id = n.id
        JOIN uzytkownicy u ON n.uzytkownik_id = u.id
        WHERE o.uczen_id = ?
          AND o.semestr = ?
          AND o.rok_szkolny = ?
          AND o.widoczna_dla_ucznia = 1
    ";

    $params = [$uczen_id, $semestr, $rok_szkolny];
    $types = "iss";

    if ($przedmiot_id) {
        $sql .= " AND o.przedmiot_id = ?";
        $params[] = $przedmiot_id;
        $types .= "i";
    }

    $sql .= " ORDER BY o.data_wystawienia DESC, o.data_utworzenia DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $oceny = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $oceny[] = $row;
    }

    return $oceny;
}

/**
 * Pobiera oceny klasy z przedmiotu (dla nauczyciela)
 */
function pobierz_oceny_klasy($klasa_id, $przedmiot_id, $semestr = null, $rok_szkolny = null) {
    global $conn;

    $semestr = $semestr ?? pobierz_aktualny_semestr();
    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();

    $stmt = $conn->prepare("
        SELECT o.*, ko.nazwa as kategoria_nazwa, ko.waga as kategoria_waga, ko.kolor,
               uc.id as uczen_id,
               CONCAT(u.imie, ' ', u.nazwisko) as uczen_nazwa
        FROM uczniowie uc
        JOIN uzytkownicy u ON uc.uzytkownik_id = u.id
        LEFT JOIN oceny o ON uc.id = o.uczen_id
            AND o.przedmiot_id = ?
            AND o.semestr = ?
            AND o.rok_szkolny = ?
        LEFT JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
        WHERE uc.klasa_id = ?
        ORDER BY u.nazwisko, u.imie, o.data_wystawienia DESC
    ");

    $stmt->bind_param("issi", $przedmiot_id, $semestr, $rok_szkolny, $klasa_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $dane = [];

    while ($row = $result->fetch_assoc()) {
        $uczen_id = $row['uczen_id'];
        if (!isset($dane[$uczen_id])) {
            $dane[$uczen_id] = [
                'uczen_id' => $uczen_id,
                'uczen_nazwa' => $row['uczen_nazwa'],
                'oceny' => []
            ];
        }
        if ($row['id']) {
            $dane[$uczen_id]['oceny'][] = $row;
        }
    }

    return array_values($dane);
}

/**
 * Pobiera wszystkie przedmioty ucznia z ocenami (podsumowanie)
 */
function pobierz_podsumowanie_ocen_ucznia($uczen_id, $semestr = null, $rok_szkolny = null) {
    global $conn;

    $semestr = $semestr ?? pobierz_aktualny_semestr();
    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();

    // Pobierz klasę ucznia
    $stmt = $conn->prepare("SELECT klasa_id FROM uczniowie WHERE id = ?");
    $stmt->bind_param("i", $uczen_id);
    $stmt->execute();
    $uczen = $stmt->get_result()->fetch_assoc();

    if (!$uczen) return [];

    // Pobierz przedmioty klasy
    $stmt = $conn->prepare("
        SELECT DISTINCT p.id, p.nazwa, p.skrot,
               CONCAT(u.imie, ' ', u.nazwisko) as nauczyciel_nazwa
        FROM klasa_przedmioty kp
        JOIN przedmioty p ON kp.przedmiot_id = p.id
        JOIN nauczyciele n ON kp.nauczyciel_id = n.id
        JOIN uzytkownicy u ON n.uzytkownik_id = u.id
        WHERE kp.klasa_id = ?
        ORDER BY p.nazwa
    ");

    $stmt->bind_param("i", $uczen['klasa_id']);
    $stmt->execute();
    $przedmioty_result = $stmt->get_result();

    $podsumowanie = [];

    while ($przedmiot = $przedmioty_result->fetch_assoc()) {
        $oceny = pobierz_oceny_ucznia($uczen_id, $przedmiot['id'], $semestr, $rok_szkolny);
        $srednia = oblicz_srednia_wazona($uczen_id, $przedmiot['id'], $semestr, $rok_szkolny);

        // Pobierz ocenę końcową jeśli istnieje
        $stmt2 = $conn->prepare("
            SELECT * FROM oceny_koncowe
            WHERE uczen_id = ? AND przedmiot_id = ? AND rok_szkolny = ?
            ORDER BY typ DESC LIMIT 1
        ");
        $stmt2->bind_param("iis", $uczen_id, $przedmiot['id'], $rok_szkolny);
        $stmt2->execute();
        $ocena_koncowa = $stmt2->get_result()->fetch_assoc();

        $podsumowanie[] = [
            'przedmiot_id' => $przedmiot['id'],
            'przedmiot_nazwa' => $przedmiot['nazwa'],
            'przedmiot_skrot' => $przedmiot['skrot'],
            'nauczyciel' => $przedmiot['nauczyciel_nazwa'],
            'oceny' => $oceny,
            'liczba_ocen' => count($oceny),
            'srednia_wazona' => $srednia,
            'przewidywana' => przewiduj_ocene_koncowa($srednia),
            'ocena_koncowa' => $ocena_koncowa
        ];
    }

    return $podsumowanie;
}

// ============================================================
// FUNKCJE STATYSTYK
// ============================================================

/**
 * Pobiera statystyki klasy (dla nauczyciela/dyrektora)
 */
function pobierz_statystyki_klasy($klasa_id, $przedmiot_id = null, $semestr = null, $rok_szkolny = null) {
    global $conn;

    $semestr = $semestr ?? pobierz_aktualny_semestr();
    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();

    $where_przedmiot = $przedmiot_id ? "AND o.przedmiot_id = ?" : "";

    $sql = "
        SELECT
            COUNT(DISTINCT o.uczen_id) as liczba_uczniow,
            COUNT(o.id) as liczba_ocen,
            ROUND(AVG(o.ocena), 2) as srednia_arytmetyczna,
            ROUND(
                SUM(o.ocena * COALESCE(o.waga_indywidualna, ko.waga)) /
                NULLIF(SUM(COALESCE(o.waga_indywidualna, ko.waga)), 0),
            2) as srednia_wazona,
            MIN(o.ocena) as najnizsza,
            MAX(o.ocena) as najwyzsza,
            SUM(CASE WHEN o.ocena >= 4.5 THEN 1 ELSE 0 END) as oceny_bardzo_dobre,
            SUM(CASE WHEN o.ocena >= 2.5 AND o.ocena < 4.5 THEN 1 ELSE 0 END) as oceny_srednie,
            SUM(CASE WHEN o.ocena < 2.5 THEN 1 ELSE 0 END) as oceny_slabe
        FROM oceny o
        JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
        WHERE o.klasa_id = ?
          AND o.semestr = ?
          AND o.rok_szkolny = ?
          AND o.czy_liczona = 1
          $where_przedmiot
    ";

    $stmt = $conn->prepare($sql);

    if ($przedmiot_id) {
        $stmt->bind_param("issi", $klasa_id, $semestr, $rok_szkolny, $przedmiot_id);
    } else {
        $stmt->bind_param("iss", $klasa_id, $semestr, $rok_szkolny);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Pobiera rozkład ocen dla klasy (do wykresów)
 */
function pobierz_rozklad_ocen($klasa_id, $przedmiot_id = null, $semestr = null, $rok_szkolny = null) {
    global $conn;

    $semestr = $semestr ?? pobierz_aktualny_semestr();
    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();

    $where_przedmiot = $przedmiot_id ? "AND przedmiot_id = ?" : "";

    $sql = "
        SELECT
            CASE
                WHEN ocena >= 5.5 THEN '6'
                WHEN ocena >= 4.5 THEN '5'
                WHEN ocena >= 3.5 THEN '4'
                WHEN ocena >= 2.5 THEN '3'
                WHEN ocena >= 1.5 THEN '2'
                ELSE '1'
            END as przedzial,
            COUNT(*) as liczba
        FROM oceny
        WHERE klasa_id = ?
          AND semestr = ?
          AND rok_szkolny = ?
          AND czy_liczona = 1
          $where_przedmiot
        GROUP BY przedzial
        ORDER BY przedzial DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($przedmiot_id) {
        $stmt->bind_param("issi", $klasa_id, $semestr, $rok_szkolny, $przedmiot_id);
    } else {
        $stmt->bind_param("iss", $klasa_id, $semestr, $rok_szkolny);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rozklad = ['6' => 0, '5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0];
    while ($row = $result->fetch_assoc()) {
        $rozklad[$row['przedzial']] = intval($row['liczba']);
    }

    return $rozklad;
}

/**
 * Identyfikuje uczniów zagrożonych (niska średnia)
 */
function pobierz_uczniow_zagrozonych($klasa_id = null, $przedmiot_id = null, $semestr = null, $rok_szkolny = null) {
    global $conn;

    $semestr = $semestr ?? pobierz_aktualny_semestr();
    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();
    $prog = floatval(pobierz_ustawienie_oceniania('prog_zagrozenia', 2.0));

    $where = ["o.semestr = ?", "o.rok_szkolny = ?", "o.czy_liczona = 1"];
    $params = [$semestr, $rok_szkolny];
    $types = "ss";

    if ($klasa_id) {
        $where[] = "o.klasa_id = ?";
        $params[] = $klasa_id;
        $types .= "i";
    }

    if ($przedmiot_id) {
        $where[] = "o.przedmiot_id = ?";
        $params[] = $przedmiot_id;
        $types .= "i";
    }

    $where_sql = implode(" AND ", $where);

    $sql = "
        SELECT
            u.id as uczen_id,
            CONCAT(uz.imie, ' ', uz.nazwisko) as uczen_nazwa,
            k.nazwa as klasa_nazwa,
            p.nazwa as przedmiot_nazwa,
            ROUND(
                SUM(o.ocena * COALESCE(o.waga_indywidualna, ko.waga)) /
                SUM(COALESCE(o.waga_indywidualna, ko.waga)),
            2) as srednia
        FROM oceny o
        JOIN uczniowie u ON o.uczen_id = u.id
        JOIN uzytkownicy uz ON u.uzytkownik_id = uz.id
        JOIN klasy k ON u.klasa_id = k.id
        JOIN przedmioty p ON o.przedmiot_id = p.id
        JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
        WHERE $where_sql
        GROUP BY u.id, uz.imie, uz.nazwisko, k.nazwa, p.nazwa, o.przedmiot_id
        HAVING srednia < ?
        ORDER BY srednia ASC, uz.nazwisko, uz.imie
    ";

    $params[] = $prog;
    $types .= "d";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $zagrozeni = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $zagrozeni[] = $row;
    }

    return $zagrozeni;
}

/**
 * Pobiera statystyki szkoły (dla dyrektora)
 */
function pobierz_statystyki_szkoly($rok_szkolny = null) {
    global $conn;

    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();

    // Średnie klas
    $stmt = $conn->prepare("
        SELECT
            k.id as klasa_id,
            k.nazwa as klasa_nazwa,
            COUNT(DISTINCT o.uczen_id) as liczba_uczniow,
            ROUND(AVG(o.ocena), 2) as srednia_arytmetyczna,
            ROUND(
                SUM(o.ocena * COALESCE(o.waga_indywidualna, ko.waga)) /
                NULLIF(SUM(COALESCE(o.waga_indywidualna, ko.waga)), 0),
            2) as srednia_wazona
        FROM klasy k
        LEFT JOIN oceny o ON k.id = o.klasa_id AND o.rok_szkolny = ? AND o.czy_liczona = 1
        LEFT JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
        GROUP BY k.id, k.nazwa
        ORDER BY srednia_wazona DESC
    ");

    $stmt->bind_param("s", $rok_szkolny);
    $stmt->execute();

    $klasy = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $klasy[] = $row;
    }

    // Ranking nauczycieli
    $stmt = $conn->prepare("
        SELECT
            n.id as nauczyciel_id,
            CONCAT(u.imie, ' ', u.nazwisko) as nauczyciel_nazwa,
            COUNT(DISTINCT o.uczen_id) as liczba_uczniow,
            COUNT(o.id) as liczba_ocen,
            ROUND(AVG(o.ocena), 2) as srednia_ocen
        FROM nauczyciele n
        JOIN uzytkownicy u ON n.uzytkownik_id = u.id
        LEFT JOIN oceny o ON n.id = o.nauczyciel_id AND o.rok_szkolny = ? AND o.czy_liczona = 1
        GROUP BY n.id, u.imie, u.nazwisko
        HAVING liczba_ocen > 0
        ORDER BY srednia_ocen DESC
    ");

    $stmt->bind_param("s", $rok_szkolny);
    $stmt->execute();

    $nauczyciele = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $nauczyciele[] = $row;
    }

    // Ogólne statystyki
    $stmt = $conn->prepare("
        SELECT
            COUNT(DISTINCT uczen_id) as liczba_uczniow,
            COUNT(*) as liczba_ocen,
            ROUND(AVG(ocena), 2) as srednia_szkoly
        FROM oceny
        WHERE rok_szkolny = ? AND czy_liczona = 1
    ");

    $stmt->bind_param("s", $rok_szkolny);
    $stmt->execute();
    $ogolne = $stmt->get_result()->fetch_assoc();

    return [
        'klasy' => $klasy,
        'nauczyciele' => $nauczyciele,
        'ogolne' => $ogolne
    ];
}

/**
 * Pobiera trendy ocen (porównanie okresów)
 */
function pobierz_trendy_ocen($klasa_id = null, $rok_szkolny = null) {
    global $conn;

    $rok_szkolny = $rok_szkolny ?? pobierz_aktualny_rok_szkolny();

    $where_klasa = $klasa_id ? "AND klasa_id = ?" : "";

    // Średnia miesięczna
    $sql = "
        SELECT
            DATE_FORMAT(data_wystawienia, '%Y-%m') as miesiac,
            ROUND(AVG(ocena), 2) as srednia,
            COUNT(*) as liczba_ocen
        FROM oceny
        WHERE rok_szkolny = ? AND czy_liczona = 1 $where_klasa
        GROUP BY miesiac
        ORDER BY miesiac
    ";

    $stmt = $conn->prepare($sql);

    if ($klasa_id) {
        $stmt->bind_param("si", $rok_szkolny, $klasa_id);
    } else {
        $stmt->bind_param("s", $rok_szkolny);
    }

    $stmt->execute();

    $trendy = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $trendy[] = $row;
    }

    return $trendy;
}

// ============================================================
// FUNKCJE RODO
// ============================================================

/**
 * Loguje zmianę oceny (audit trail)
 */
function loguj_zmiane_oceny($ocena_id, $ocena_koncowa_id, $typ_zmiany, $przed, $po, $powod = null) {
    global $conn;

    $user_id = $_SESSION['user_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO historia_ocen (ocena_id, ocena_koncowa_id, typ_zmiany, wartosc_przed, wartosc_po,
                                   uzytkownik_id, ip_address, user_agent, powod_zmiany)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $przed_json = $przed ? json_encode($przed, JSON_UNESCAPED_UNICODE) : null;
    $po_json = $po ? json_encode($po, JSON_UNESCAPED_UNICODE) : null;

    $stmt->bind_param("iisssssss",
        $ocena_id, $ocena_koncowa_id, $typ_zmiany,
        $przed_json, $po_json, $user_id, $ip, $ua, $powod
    );

    $stmt->execute();
}

/**
 * Loguje dostęp do danych ocen (RODO)
 */
function loguj_dostep_ocen($uczen_id, $typ_dostepu, $zakres = null) {
    global $conn;

    $user_id = $_SESSION['user_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO logi_dostepu_ocen (uzytkownik_id, uczen_id, typ_dostepu, zakres_danych, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("iissss", $user_id, $uczen_id, $typ_dostepu, $zakres, $ip, $ua);
    $stmt->execute();
}

/**
 * Eksportuje dane ucznia (RODO - prawo dostępu)
 */
function eksportuj_dane_ucznia_rodo($uczen_id) {
    global $conn;

    // Loguj eksport
    loguj_dostep_ocen($uczen_id, 'eksport', 'Pełny eksport danych RODO');

    // Dane osobowe
    $stmt = $conn->prepare("
        SELECT u.imie, u.nazwisko, u.email, u.login, u.data_utworzenia,
               k.nazwa as klasa
        FROM uczniowie uc
        JOIN uzytkownicy u ON uc.uzytkownik_id = u.id
        JOIN klasy k ON uc.klasa_id = k.id
        WHERE uc.id = ?
    ");
    $stmt->bind_param("i", $uczen_id);
    $stmt->execute();
    $dane_osobowe = $stmt->get_result()->fetch_assoc();

    // Wszystkie oceny
    $stmt = $conn->prepare("
        SELECT o.ocena, o.data_wystawienia, o.komentarz, o.semestr, o.rok_szkolny,
               ko.nazwa as kategoria, p.nazwa as przedmiot,
               CONCAT(u.imie, ' ', u.nazwisko) as wystawil
        FROM oceny o
        JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
        JOIN przedmioty p ON o.przedmiot_id = p.id
        JOIN nauczyciele n ON o.nauczyciel_id = n.id
        JOIN uzytkownicy u ON n.uzytkownik_id = u.id
        WHERE o.uczen_id = ?
        ORDER BY o.data_wystawienia DESC
    ");
    $stmt->bind_param("i", $uczen_id);
    $stmt->execute();

    $oceny = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $oceny[] = $row;
    }

    // Oceny końcowe
    $stmt = $conn->prepare("
        SELECT ok.ocena, ok.typ, ok.data_wystawienia, ok.komentarz, ok.semestr, ok.rok_szkolny,
               p.nazwa as przedmiot
        FROM oceny_koncowe ok
        JOIN przedmioty p ON ok.przedmiot_id = p.id
        WHERE ok.uczen_id = ?
        ORDER BY ok.data_wystawienia DESC
    ");
    $stmt->bind_param("i", $uczen_id);
    $stmt->execute();

    $oceny_koncowe = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $oceny_koncowe[] = $row;
    }

    // Historia dostępu do danych
    $stmt = $conn->prepare("
        SELECT ld.typ_dostepu, ld.zakres_danych, ld.data_dostepu,
               CONCAT(u.imie, ' ', u.nazwisko) as uzytkownik
        FROM logi_dostepu_ocen ld
        JOIN uzytkownicy u ON ld.uzytkownik_id = u.id
        WHERE ld.uczen_id = ?
        ORDER BY ld.data_dostepu DESC
        LIMIT 100
    ");
    $stmt->bind_param("i", $uczen_id);
    $stmt->execute();

    $logi_dostepu = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $logi_dostepu[] = $row;
    }

    return [
        'dane_osobowe' => $dane_osobowe,
        'oceny' => $oceny,
        'oceny_koncowe' => $oceny_koncowe,
        'logi_dostepu' => $logi_dostepu,
        'data_eksportu' => date('Y-m-d H:i:s'),
        'eksportujacy' => $_SESSION['user_name'] ?? 'System'
    ];
}

/**
 * Anonimizuje dane ucznia (RODO - prawo do bycia zapomnianym)
 * UWAGA: Wymaga autoryzacji dyrektora/administratora
 */
function anonimizuj_dane_ucznia($uczen_id) {
    global $conn;

    // Sprawdź czy użytkownik ma uprawnienia
    if (!in_array($_SESSION['user_type'], ['dyrektor', 'administrator'])) {
        return ['success' => false, 'message' => 'Brak uprawnień do anonimizacji danych'];
    }

    $conn->begin_transaction();

    try {
        // Zastąp dane osobowe placeholderami
        $anonimowy_hash = hash('sha256', $uczen_id . time());
        $anonimowe_dane = "ANONIMIZOWANY_" . substr($anonimowy_hash, 0, 8);

        // Aktualizuj dane użytkownika
        $stmt = $conn->prepare("
            UPDATE uzytkownicy u
            JOIN uczniowie uc ON u.id = uc.uzytkownik_id
            SET u.imie = ?, u.nazwisko = ?, u.email = NULL, u.login = ?, u.aktywny = 0
            WHERE uc.id = ?
        ");
        $stmt->bind_param("sssi", $anonimowe_dane, $anonimowe_dane, $anonimowe_dane, $uczen_id);
        $stmt->execute();

        // Usuń komentarze z ocen (mogą zawierać dane osobowe)
        $stmt = $conn->prepare("UPDATE oceny SET komentarz = NULL WHERE uczen_id = ?");
        $stmt->bind_param("i", $uczen_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE oceny_koncowe SET komentarz = NULL WHERE uczen_id = ?");
        $stmt->bind_param("i", $uczen_id);
        $stmt->execute();

        // Loguj operację
        loguj_dostep_ocen($uczen_id, 'anonimizacja', 'Pełna anonimizacja danych RODO');
        loguj_aktywnosc($_SESSION['user_id'], 'anonimizacja_rodo',
            "Anonimizacja danych ucznia ID: $uczen_id");

        $conn->commit();

        return ['success' => true, 'message' => 'Dane zostały zanonimizowane'];

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ============================================================
// FUNKCJE POMOCNICZE
// ============================================================

/**
 * Formatuje ocenę do wyświetlenia (z plusami/minusami)
 */
function formatuj_ocene($ocena) {
    $ocena = floatval($ocena);

    // Sprawdź czy to połówka
    $czesc_dziesietna = $ocena - floor($ocena);

    if ($czesc_dziesietna == 0) {
        return strval(intval($ocena));
    } elseif ($czesc_dziesietna == 0.5) {
        return intval($ocena) . '+';
    } elseif ($czesc_dziesietna == 0.75) {
        return (intval($ocena) + 1) . '-';
    } else {
        return number_format($ocena, 1);
    }
}

/**
 * Zwraca kolor dla oceny
 */
function kolor_oceny($ocena) {
    $ocena = floatval($ocena);

    if ($ocena >= 5) return '#27ae60';      // Zielony - bardzo dobra
    if ($ocena >= 4) return '#2ecc71';      // Jasny zielony - dobra
    if ($ocena >= 3) return '#f39c12';      // Pomarańczowy - dostateczna
    if ($ocena >= 2) return '#e67e22';      // Ciemny pomarańczowy - dopuszczająca
    return '#e74c3c';                        // Czerwony - niedostateczna
}

/**
 * Pobiera ID nauczyciela dla zalogowanego użytkownika
 */
function pobierz_id_nauczyciela($user_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT id FROM nauczyciele WHERE uzytkownik_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result ? $result['id'] : null;
}

/**
 * Pobiera ID ucznia dla zalogowanego użytkownika
 */
function pobierz_id_ucznia($user_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, klasa_id FROM uczniowie WHERE uzytkownik_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/**
 * Sprawdza czy nauczyciel uczy dany przedmiot w danej klasie
 */
function nauczyciel_uczy_przedmiot($nauczyciel_id, $klasa_id, $przedmiot_id) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT id FROM klasa_przedmioty
        WHERE nauczyciel_id = ? AND klasa_id = ? AND przedmiot_id = ?
    ");
    $stmt->bind_param("iii", $nauczyciel_id, $klasa_id, $przedmiot_id);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}

/**
 * Pobiera przedmioty nauczane przez nauczyciela
 */
function pobierz_przedmioty_nauczyciela($nauczyciel_id) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT DISTINCT kp.klasa_id, kp.przedmiot_id,
               k.nazwa as klasa_nazwa,
               p.nazwa as przedmiot_nazwa,
               p.skrot as przedmiot_skrot
        FROM klasa_przedmioty kp
        JOIN klasy k ON kp.klasa_id = k.id
        JOIN przedmioty p ON kp.przedmiot_id = p.id
        WHERE kp.nauczyciel_id = ?
        ORDER BY k.nazwa, p.nazwa
    ");

    $stmt->bind_param("i", $nauczyciel_id);
    $stmt->execute();

    $przedmioty = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $przedmioty[] = $row;
    }

    return $przedmioty;
}

/**
 * Pobiera uczniów klasy
 */
function pobierz_uczniow_klasy($klasa_id) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT u.id as uczen_id, uz.imie, uz.nazwisko,
               CONCAT(uz.imie, ' ', uz.nazwisko) as pelne_imie
        FROM uczniowie u
        JOIN uzytkownicy uz ON u.uzytkownik_id = uz.id
        WHERE u.klasa_id = ?
        ORDER BY uz.nazwisko, uz.imie
    ");

    $stmt->bind_param("i", $klasa_id);
    $stmt->execute();

    $uczniowie = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $uczniowie[] = $row;
    }

    return $uczniowie;
}
?>
