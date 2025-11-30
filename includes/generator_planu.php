<?php
require_once '../includes/config.php';

class GeneratorPlanu {
    private $conn;
    private $dni = ['poniedzialek', 'wtorek', 'sroda', 'czwartek', 'piatek'];
    private $godzina_rozpoczecia = '08:00';
    private $czas_lekcji = 45; // minuty
    private $czas_przerwy = 10; // minuty
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Główna funkcja generująca plan
    public function generujPlan() {
        // Czyścimy stary plan
        $this->conn->query("DELETE FROM plan_lekcji");
        $this->conn->query("DELETE FROM plan_dzienny");
        
        // Pobieramy wszystkie klasy
        $klasy = $this->conn->query("SELECT * FROM klasy ORDER BY nazwa");
        
        $sukces = true;
        while ($klasa = $klasy->fetch_assoc()) {
            if (!$this->generujPlanDlaKlasy($klasa)) {
                $sukces = false;
            }
        }
        
        if ($sukces) {
            // Generujemy plan dzienny na cały rok
            $this->generujPlanRoczny();
        }
        
        return $sukces;
    }
    
    // Generowanie planu dla konkretnej klasy
    private function generujPlanDlaKlasy($klasa) {
        $klasa_id = $klasa['id'];
        
        // Pobieramy przedmioty przypisane do klasy
        $przedmioty = $this->conn->query("
            SELECT kp.*, p.nazwa, p.skrot
            FROM klasa_przedmioty kp
            JOIN przedmioty p ON kp.przedmiot_id = p.id
            WHERE kp.klasa_id = $klasa_id
            ORDER BY kp.ilosc_godzin_tydzien DESC
        ");
        
        if ($przedmioty->num_rows === 0) {
            return false;
        }
        
        // Tworzymy listę wszystkich lekcji do rozplanowania
        $lekcje_wszystkie = [];
        $suma_godzin = 0;
        
        while ($przedmiot = $przedmioty->fetch_assoc()) {
            $suma_godzin += $przedmiot['ilosc_godzin_tydzien'];
            for ($i = 0; $i < $przedmiot['ilosc_godzin_tydzien']; $i++) {
                $lekcje_wszystkie[] = [
                    'przedmiot_id' => $przedmiot['przedmiot_id'],
                    'nauczyciel_id' => $przedmiot['nauczyciel_id'],
                    'nazwa' => $przedmiot['nazwa'],
                    'ilosc_ogolna' => $przedmiot['ilosc_godzin_tydzien']
                ];
            }
        }
        
        // Sprawdź czy suma godzin jest rozsądna
        $max_godzin_tydzien = 5 * $klasa['ilosc_godzin_dziennie'];
        if ($suma_godzin > $max_godzin_tydzien) {
            error_log("BŁĄD: Klasa {$klasa['nazwa']} ma {$suma_godzin}h, ale maksimum to {$max_godzin_tydzien}h");
            return false;
        }
        
        // Inteligentne rozłożenie lekcji - unikaj skupiania tego samego przedmiotu
        $lekcje_rozlozone = $this->rozmieszczLekcjeRownomiernie($lekcje_wszystkie);
        
        // Oblicz ile godzin dziennie dla każdego dnia (równomiernie)
        $godziny_dzienne = $this->rozlozGodzinyNaDni($suma_godzin, 5);
        
        // Wygeneruj wzorzec startów dla tej klasy (różne dni = różne godziny startu)
        $godziny_startu = $this->generujWzorzecStartow($klasa_id, $godziny_dzienne);
        
        // Rozplanuj lekcje dla każdego dnia
        $index = 0;
        foreach ($this->dni as $dzien_idx => $dzien) {
            $godziny_dzisiaj = $godziny_dzienne[$dzien_idx];
            $godzina_startu = $godziny_startu[$dzien_idx];
            
            // Generuj lekcje od godziny_startu do (godzina_startu + godziny_dzisiaj - 1)
            for ($offset = 0; $offset < $godziny_dzisiaj; $offset++) {
                $lekcja_nr = $godzina_startu + $offset;
                
                if ($index >= count($lekcje_rozlozone)) {
                    break; // Wszystkie lekcje już przydzielone
                }
                
                // Próbuj przydzielić lekcję
                $przydzielono = false;
                $max_proby = min(20, count($lekcje_rozlozone)); // Zwiększone do 20 prób
                
                for ($proba = 0; $proba < $max_proby; $proba++) {
                    $idx_do_sprawdzenia = ($index + $proba) % count($lekcje_rozlozone);
                    $lekcja = $lekcje_rozlozone[$idx_do_sprawdzenia];
                    
                    // Sprawdź dostępność nauczyciela
                    if ($this->sprawdzDostepnoscNauczyciela($lekcja['nauczyciel_id'], $dzien, $lekcja_nr, $klasa_id)) {
                        // Przydziel salę
                        $sala_id = $this->przydzielSale($dzien, $lekcja_nr, $klasa_id, $lekcja['przedmiot_id']);
                        
                        // Oblicz godziny
                        $godziny = $this->obliczGodziny($lekcja_nr);
                        
                        // Dodaj do planu
                        $stmt = $this->conn->prepare("
                            INSERT INTO plan_lekcji 
                            (klasa_id, dzien_tygodnia, numer_lekcji, godzina_rozpoczecia, godzina_zakonczenia, 
                             przedmiot_id, nauczyciel_id, sala_id, szablon_tygodniowy)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                        ");
                        
                        $stmt->bind_param("isissiii",
                            $klasa_id,
                            $dzien,
                            $lekcja_nr,
                            $godziny['start'],
                            $godziny['koniec'],
                            $lekcja['przedmiot_id'],
                            $lekcja['nauczyciel_id'],
                            $sala_id
                        );
                        
                        if ($stmt->execute()) {
                            // Usuń przydzieloną lekcję z listy
                            array_splice($lekcje_rozlozone, $idx_do_sprawdzenia, 1);
                            $przydzielono = true;
                            break;
                        }
                    }
                }
                
                if (!$przydzielono) {
                    // KRYTYCZNA ZMIANA: Przesuń problematyczną lekcję na koniec listy
                    // aby spróbować ją przydzielić w innym dniu/godzinie
                    if (count($lekcje_rozlozone) > 0) {
                        $problematyczna_lekcja = array_shift($lekcje_rozlozone); // Usuń z początku
                        array_push($lekcje_rozlozone, $problematyczna_lekcja); // Dodaj na koniec
                    }
                    error_log("UWAGA: Nie można przydzielić lekcji dla klasy {$klasa['nazwa']}, $dzien, godzina $lekcja_nr - przesunięto na później");
                }
            }
        }
        
        // Sprawdź czy wszystkie lekcje zostały przydzielone
        if (count($lekcje_rozlozone) > 0) {
            error_log("FAZA 2: Próba wypełnienia okienek dla klasy {$klasa['nazwa']}. Pozostało: " . count($lekcje_rozlozone) . "/{$suma_godzin}");
            
            // FAZA 2: Wypełnij okienka
            // Znajdź wszystkie puste sloty w planie tej klasy
            $puste_sloty = [];
            foreach ($this->dni as $dzien_idx => $dzien) {
                $godziny_dzisiaj = $godziny_dzienne[$dzien_idx];
                $godzina_startu = $godziny_startu[$dzien_idx];
                
                for ($offset = 0; $offset < $godziny_dzisiaj; $offset++) {
                    $lekcja_nr = $godzina_startu + $offset;
                    
                    // Sprawdź czy jest lekcja w tym slocie
                    $check = $this->conn->query("
                        SELECT COUNT(*) as cnt
                        FROM plan_lekcji
                        WHERE klasa_id = $klasa_id
                        AND dzien_tygodnia = '$dzien'
                        AND numer_lekcji = $lekcja_nr
                        AND szablon_tygodniowy = 1
                    ")->fetch_assoc()['cnt'];
                    
                    if ($check == 0) {
                        $puste_sloty[] = ['dzien' => $dzien, 'lekcja_nr' => $lekcja_nr];
                    }
                }
            }
            
            // Próbuj wypełnić puste sloty pozostałymi lekcjami
            foreach ($puste_sloty as $slot) {
                if (count($lekcje_rozlozone) == 0) break;
                
                $dzien = $slot['dzien'];
                $lekcja_nr = $slot['lekcja_nr'];
                
                // Próbuj każdą pozostałą lekcję
                for ($i = 0; $i < count($lekcje_rozlozone); $i++) {
                    $lekcja = $lekcje_rozlozone[$i];
                    
                    if ($this->sprawdzDostepnoscNauczyciela($lekcja['nauczyciel_id'], $dzien, $lekcja_nr, $klasa_id)) {
                        $sala_id = $this->przydzielSale($dzien, $lekcja_nr, $klasa_id, $lekcja['przedmiot_id']);
                        $godziny = $this->obliczGodziny($lekcja_nr);
                        
                        $stmt = $this->conn->prepare("
                            INSERT INTO plan_lekcji 
                            (klasa_id, dzien_tygodnia, numer_lekcji, godzina_rozpoczecia, godzina_zakonczenia, 
                             przedmiot_id, nauczyciel_id, sala_id, szablon_tygodniowy)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                        ");
                        
                        $stmt->bind_param("isissiii",
                            $klasa_id,
                            $dzien,
                            $lekcja_nr,
                            $godziny['start'],
                            $godziny['koniec'],
                            $lekcja['przedmiot_id'],
                            $lekcja['nauczyciel_id'],
                            $sala_id
                        );
                        
                        if ($stmt->execute()) {
                            array_splice($lekcje_rozlozone, $i, 1);
                            error_log("FAZA 2 SUKCES: Wypełniono okienko $dzien lekcja $lekcja_nr dla klasy {$klasa['nazwa']}");
                            break; // Przejdź do następnego slotu
                        }
                    }
                }
            }
            
            // Raport końcowy
            if (count($lekcje_rozlozone) > 0) {
                error_log("UWAGA: Nie wszystkie lekcje przydzielone dla klasy {$klasa['nazwa']}. Pozostało: " . count($lekcje_rozlozone) . "/{$suma_godzin}");
                
                // Wyświetl które przedmioty nie zostały przydzielone
                $pozostale = [];
                foreach ($lekcje_rozlozone as $l) {
                    $pozostale[] = $l['nazwa'];
                }
                error_log("Pozostałe przedmioty: " . implode(", ", $pozostale));
            } else {
                error_log("SUKCES: Wszystkie {$suma_godzin} lekcji przydzielone dla klasy {$klasa['nazwa']} (włącznie z FAZĄ 2)");
            }
        } else {
            error_log("SUKCES: Wszystkie {$suma_godzin} lekcji przydzielone dla klasy {$klasa['nazwa']} (FAZA 1)");
        }
        
        return true;
    }
    
    // Generuj wzorzec godzin startu dla klasy - różne dni mają różne starty
    private function generujWzorzecStartow($klasa_id, $godziny_dzienne) {
        $starty = [];
        $max_godzin = max($godziny_dzienne);
        
        // Używamy klasa_id jako seed dla konsystentności
        $seed = $klasa_id % 5;
        
        foreach ($godziny_dzienne as $dzien_idx => $godziny_w_dniu) {
            // Rotuj wzorzec dla różnych klas
            $offset = ($dzien_idx + $seed) % 3; // Zmienność 0, 1, lub 2
            
            // Klasa może zacząć od lekcji 1, 2, lub 3
            // Im więcej godzin w tym dniu, tym wcześniej zaczynamy
            if ($godziny_w_dniu >= 8) {
                $starty[$dzien_idx] = 1; // Pełny dzień - start od 1
            } elseif ($godziny_w_dniu >= 6) {
                $starty[$dzien_idx] = 1 + ($offset > 0 ? 1 : 0); // Start od 1 lub 2
            } else {
                $starty[$dzien_idx] = 1 + $offset; // Start od 1, 2, lub 3
            }
            
            // Upewnij się że zmieści się w dniu
            $max_dostepny = 10; // Maksymalnie 10 godzin w dzień
            if ($starty[$dzien_idx] + $godziny_w_dniu > $max_dostepny + 1) {
                $starty[$dzien_idx] = $max_dostepny - $godziny_w_dniu + 1;
            }
            
            // Minimum od lekcji 1
            $starty[$dzien_idx] = max(1, $starty[$dzien_idx]);
        }
        
        return $starty;
    }
    
    // Inteligentne rozmieszczenie lekcji - rozdziela te same przedmioty
    private function rozmieszczLekcjeRownomiernie($lekcje) {
        // Grupuj lekcje według przedmiotu
        $grupy_przedmiotow = [];
        foreach ($lekcje as $lekcja) {
            $przedmiot_id = $lekcja['przedmiot_id'];
            if (!isset($grupy_przedmiotow[$przedmiot_id])) {
                $grupy_przedmiotow[$przedmiot_id] = [];
            }
            $grupy_przedmiotow[$przedmiot_id][] = $lekcja;
        }
        
        // Sortuj grupy - największe najpierw (ważniejsze przedmioty)
        uasort($grupy_przedmiotow, function($a, $b) {
            return count($b) - count($a);
        });
        
        // Rozplanuj równomiernie - "round-robin"
        $wynik = [];
        $max_size = max(array_map('count', $grupy_przedmiotow));
        
        for ($i = 0; $i < $max_size; $i++) {
            foreach ($grupy_przedmiotow as $grupa) {
                if (isset($grupa[$i])) {
                    $wynik[] = $grupa[$i];
                }
            }
        }
        
        return $wynik;
    }
    
    // Rozłóż godziny równomiernie na dni (każdy dzień ma podobną liczbę)
    private function rozlozGodzinyNaDni($suma_godzin, $liczba_dni) {
        $godziny_na_dzien = [];
        $podstawowa = floor($suma_godzin / $liczba_dni);
        $reszta = $suma_godzin % $liczba_dni;
        
        for ($i = 0; $i < $liczba_dni; $i++) {
            $godziny_na_dzien[$i] = $podstawowa;
            // Dodaj dodatkową godzinę do pierwszych N dni (gdzie N = reszta)
            if ($i < $reszta) {
                $godziny_na_dzien[$i]++;
            }
        }
        
        return $godziny_na_dzien;
    }
    
    // Sprawdzanie dostępności nauczyciela
    private function sprawdzDostepnoscNauczyciela($nauczyciel_id, $dzien, $lekcja_nr, $aktualna_klasa_id) {
        $result = $this->conn->query("
            SELECT COUNT(*) as count
            FROM plan_lekcji
            WHERE nauczyciel_id = $nauczyciel_id
            AND dzien_tygodnia = '$dzien'
            AND numer_lekcji = $lekcja_nr
            AND klasa_id != $aktualna_klasa_id
        ");
        
        $row = $result->fetch_assoc();
        return $row['count'] == 0;
    }
    
    // Przydzielanie sali - z preferencją dla przypisanych sal
    private function przydzielSale($dzien, $lekcja_nr, $klasa_id, $przedmiot_id = null) {
        // Najpierw sprawdź czy są sale przypisane do tego przedmiotu
        if ($przedmiot_id !== null) {
            $result = $this->conn->query("
                SELECT s.id
                FROM sale s
                JOIN sala_przedmioty sp ON s.id = sp.sala_id
                WHERE sp.przedmiot_id = $przedmiot_id
                AND s.id NOT IN (
                    SELECT sala_id 
                    FROM plan_lekcji 
                    WHERE dzien_tygodnia = '$dzien' 
                    AND numer_lekcji = $lekcja_nr
                    AND sala_id IS NOT NULL
                )
                LIMIT 1
            ");
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['id'];
            }
        }
        
        // Jeśli nie ma przypisanej sali lub jest zajęta, pobierz pierwszą wolną
        $result = $this->conn->query("
            SELECT s.id
            FROM sale s
            WHERE s.id NOT IN (
                SELECT sala_id 
                FROM plan_lekcji 
                WHERE dzien_tygodnia = '$dzien' 
                AND numer_lekcji = $lekcja_nr
                AND sala_id IS NOT NULL
            )
            LIMIT 1
        ");
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        return null;
    }
    
    // Obliczanie godzin rozpoczęcia i zakończenia lekcji
    private function obliczGodziny($numer_lekcji) {
        $start_timestamp = strtotime($this->godzina_rozpoczecia);
        
        // Każda lekcja + przerwa to 55 minut (45 + 10)
        $minutes_offset = ($numer_lekcji - 1) * ($this->czas_lekcji + $this->czas_przerwy);
        
        $start = date('H:i:s', strtotime("+$minutes_offset minutes", $start_timestamp));
        $end = date('H:i:s', strtotime("+" . ($minutes_offset + $this->czas_lekcji) . " minutes", $start_timestamp));
        
        return ['start' => $start, 'koniec' => $end];
    }
    
    // Generowanie planu dziennego na cały rok szkolny
    public function generujPlanRoczny() {
        // Rok szkolny: 1 września - 30 czerwca
        $rok_biezacy = date('Y');
        $rok_nastepny = $rok_biezacy + 1;
        
        $data_poczatek = "$rok_biezacy-09-01";
        $data_koniec = "$rok_nastepny-06-30";
        
        // Pobieramy szablon planu
        $plan_szablon = $this->conn->query("SELECT * FROM plan_lekcji ORDER BY klasa_id, dzien_tygodnia, numer_lekcji");
        
        $szablony = [];
        while ($lekcja = $plan_szablon->fetch_assoc()) {
            $szablony[] = $lekcja;
        }
        
        // Pobieramy dni wolne
        $dni_wolne_result = $this->conn->query("SELECT data FROM dni_wolne");
        $dni_wolne = [];
        while ($dzien = $dni_wolne_result->fetch_assoc()) {
            $dni_wolne[] = $dzien['data'];
        }
        
        // Generujemy plan dla każdego dnia roboczego
        $current_date = strtotime($data_poczatek);
        $end_date = strtotime($data_koniec);
        
        while ($current_date <= $end_date) {
            $date_string = date('Y-m-d', $current_date);
            $day_of_week = date('N', $current_date); // 1 = poniedziałek, 7 = niedziela
            
            // Pomijamy weekendy i dni wolne
            if ($day_of_week <= 5 && !in_array($date_string, $dni_wolne)) {
                $dzien_nazwa = $this->getDzienNazwa($day_of_week);
                
                // Dodajemy lekcje z szablonu dla tego dnia
                foreach ($szablony as $szablon) {
                    if ($szablon['dzien_tygodnia'] === $dzien_nazwa) {
                        $stmt = $this->conn->prepare("
                            INSERT INTO plan_dzienny 
                            (plan_lekcji_id, data, klasa_id, numer_lekcji, godzina_rozpoczecia, 
                             godzina_zakonczenia, przedmiot_id, nauczyciel_id, sala_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->bind_param("isiisssii",
                            $szablon['id'],
                            $date_string,
                            $szablon['klasa_id'],
                            $szablon['numer_lekcji'],
                            $szablon['godzina_rozpoczecia'],
                            $szablon['godzina_zakonczenia'],
                            $szablon['przedmiot_id'],
                            $szablon['nauczyciel_id'],
                            $szablon['sala_id']
                        );
                        
                        $stmt->execute();
                    }
                }
            }
            
            $current_date = strtotime('+1 day', $current_date);
        }
        
        return true;
    }
    
    // Mapowanie numeru dnia na nazwę
    private function getDzienNazwa($day_number) {
        $mapping = [
            1 => 'poniedzialek',
            2 => 'wtorek',
            3 => 'sroda',
            4 => 'czwartek',
            5 => 'piatek'
        ];
        return $mapping[$day_number] ?? '';
    }
}
?>
