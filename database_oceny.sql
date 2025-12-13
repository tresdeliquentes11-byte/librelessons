-- © 2025 TresDeliquentes. Wszystkie prawa zastrzeżone.
-- LibreLessons działa na licencji TEUL (użytek edukacyjny).
-- Moduł: System Oceniania v4.0

-- --------------------------------------------------------
-- TABELE SYSTEMU OCENIANIA
-- --------------------------------------------------------

-- Kategorie ocen z wagami
CREATE TABLE `kategorie_ocen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazwa` varchar(50) NOT NULL,
  `waga` decimal(3,2) NOT NULL DEFAULT 1.00,
  `opis` varchar(200) DEFAULT NULL,
  `kolor` varchar(7) DEFAULT '#667eea',
  `ikona` varchar(50) DEFAULT 'star',
  `aktywna` tinyint(1) DEFAULT 1,
  `kolejnosc` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Domyślne kategorie ocen
INSERT INTO `kategorie_ocen` (`nazwa`, `waga`, `opis`, `kolor`, `ikona`, `kolejnosc`) VALUES
('Sprawdzian', 3.00, 'Sprawdzian z większej partii materiału', '#e74c3c', 'file-text', 1),
('Kartkówka', 2.00, 'Krótka kartkówka z ostatnich lekcji', '#f39c12', 'edit-3', 2),
('Odpowiedź ustna', 2.00, 'Odpowiedź ustna przy tablicy', '#9b59b6', 'mic', 3),
('Aktywność', 1.00, 'Aktywność na lekcji', '#27ae60', 'zap', 4),
('Praca domowa', 1.00, 'Zadanie domowe', '#3498db', 'home', 5),
('Projekt', 3.00, 'Projekt lub praca grupowa', '#e67e22', 'folder', 6),
('Praca klasowa', 2.00, 'Praca wykonana na lekcji', '#1abc9c', 'book-open', 7),
('Inne', 1.00, 'Inne formy oceniania', '#95a5a6', 'more-horizontal', 8);

-- --------------------------------------------------------

-- Główna tabela ocen
CREATE TABLE `oceny` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uczen_id` int(11) NOT NULL,
  `przedmiot_id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `klasa_id` int(11) NOT NULL,
  `kategoria_id` int(11) NOT NULL,
  `ocena` decimal(3,2) NOT NULL,
  `waga_indywidualna` decimal(3,2) DEFAULT NULL COMMENT 'Nadpisuje wagę kategorii jeśli ustawiona',
  `komentarz` varchar(500) DEFAULT NULL,
  `data_wystawienia` date NOT NULL,
  `data_utworzenia` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_modyfikacji` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `semestr` enum('1','2') NOT NULL,
  `rok_szkolny` varchar(9) NOT NULL COMMENT 'Format: 2024/2025',
  `czy_poprawa` tinyint(1) DEFAULT 0,
  `oryginalna_ocena_id` int(11) DEFAULT NULL COMMENT 'ID oryginalnej oceny jeśli to poprawa',
  `czy_liczona` tinyint(1) DEFAULT 1 COMMENT 'Czy ocena jest liczona do średniej',
  `widoczna_dla_ucznia` tinyint(1) DEFAULT 1,
  `hash_rodo` varchar(64) DEFAULT NULL COMMENT 'Hash do audytu RODO',
  PRIMARY KEY (`id`),
  KEY `idx_uczen_przedmiot` (`uczen_id`, `przedmiot_id`),
  KEY `idx_nauczyciel` (`nauczyciel_id`),
  KEY `idx_klasa_przedmiot` (`klasa_id`, `przedmiot_id`),
  KEY `idx_data` (`data_wystawienia`),
  KEY `idx_semestr_rok` (`semestr`, `rok_szkolny`),
  KEY `idx_kategoria` (`kategoria_id`),
  CONSTRAINT `oceny_ibfk_1` FOREIGN KEY (`uczen_id`) REFERENCES `uczniowie` (`id`) ON DELETE CASCADE,
  CONSTRAINT `oceny_ibfk_2` FOREIGN KEY (`przedmiot_id`) REFERENCES `przedmioty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `oceny_ibfk_3` FOREIGN KEY (`nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE CASCADE,
  CONSTRAINT `oceny_ibfk_4` FOREIGN KEY (`klasa_id`) REFERENCES `klasy` (`id`) ON DELETE CASCADE,
  CONSTRAINT `oceny_ibfk_5` FOREIGN KEY (`kategoria_id`) REFERENCES `kategorie_ocen` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `oceny_ibfk_6` FOREIGN KEY (`oryginalna_ocena_id`) REFERENCES `oceny` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Oceny końcowe (śródroczne i roczne)
CREATE TABLE `oceny_koncowe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uczen_id` int(11) NOT NULL,
  `przedmiot_id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `klasa_id` int(11) NOT NULL,
  `typ` enum('srodroczna','roczna') NOT NULL,
  `ocena` int(11) NOT NULL COMMENT '1-6 lub 0 dla nieklasyfikowany',
  `ocena_proponowana` int(11) DEFAULT NULL,
  `komentarz` varchar(500) DEFAULT NULL,
  `srednia_wazona` decimal(4,2) DEFAULT NULL COMMENT 'Średnia ważona w momencie wystawienia',
  `data_wystawienia` date NOT NULL,
  `data_utworzenia` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_modyfikacji` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `semestr` enum('1','2') NOT NULL,
  `rok_szkolny` varchar(9) NOT NULL,
  `zatwierdzona` tinyint(1) DEFAULT 0,
  `data_zatwierdzenia` datetime DEFAULT NULL,
  `zatwierdzil_id` int(11) DEFAULT NULL,
  `hash_rodo` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ocena_koncowa` (`uczen_id`, `przedmiot_id`, `typ`, `rok_szkolny`),
  KEY `idx_uczen` (`uczen_id`),
  KEY `idx_przedmiot` (`przedmiot_id`),
  KEY `idx_rok_typ` (`rok_szkolny`, `typ`),
  CONSTRAINT `oceny_koncowe_ibfk_1` FOREIGN KEY (`uczen_id`) REFERENCES `uczniowie` (`id`) ON DELETE CASCADE,
  CONSTRAINT `oceny_koncowe_ibfk_2` FOREIGN KEY (`przedmiot_id`) REFERENCES `przedmioty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `oceny_koncowe_ibfk_3` FOREIGN KEY (`nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE CASCADE,
  CONSTRAINT `oceny_koncowe_ibfk_4` FOREIGN KEY (`klasa_id`) REFERENCES `klasy` (`id`) ON DELETE CASCADE,
  CONSTRAINT `oceny_koncowe_ibfk_5` FOREIGN KEY (`zatwierdzil_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Historia zmian ocen (audit trail dla RODO)
CREATE TABLE `historia_ocen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ocena_id` int(11) DEFAULT NULL,
  `ocena_koncowa_id` int(11) DEFAULT NULL,
  `typ_zmiany` enum('utworzenie','edycja','usuniecie','poprawa') NOT NULL,
  `wartosc_przed` text DEFAULT NULL COMMENT 'JSON ze stanem przed zmianą',
  `wartosc_po` text DEFAULT NULL COMMENT 'JSON ze stanem po zmianie',
  `uzytkownik_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `data_zmiany` timestamp NOT NULL DEFAULT current_timestamp(),
  `powod_zmiany` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ocena` (`ocena_id`),
  KEY `idx_ocena_koncowa` (`ocena_koncowa_id`),
  KEY `idx_uzytkownik` (`uzytkownik_id`),
  KEY `idx_data` (`data_zmiany`),
  CONSTRAINT `historia_ocen_ibfk_1` FOREIGN KEY (`ocena_id`) REFERENCES `oceny` (`id`) ON DELETE SET NULL,
  CONSTRAINT `historia_ocen_ibfk_2` FOREIGN KEY (`ocena_koncowa_id`) REFERENCES `oceny_koncowe` (`id`) ON DELETE SET NULL,
  CONSTRAINT `historia_ocen_ibfk_3` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Logi dostępu do danych (RODO - prawo dostępu)
CREATE TABLE `logi_dostepu_ocen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uzytkownik_id` int(11) NOT NULL COMMENT 'Kto przeglądał',
  `uczen_id` int(11) DEFAULT NULL COMMENT 'Czyje dane przeglądano',
  `typ_dostepu` enum('przegladanie','eksport','wydruk','raport') NOT NULL,
  `zakres_danych` varchar(200) DEFAULT NULL COMMENT 'Opis jakie dane były przeglądane',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `data_dostepu` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_uzytkownik` (`uzytkownik_id`),
  KEY `idx_uczen` (`uczen_id`),
  KEY `idx_data` (`data_dostepu`),
  CONSTRAINT `logi_dostepu_ocen_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  CONSTRAINT `logi_dostepu_ocen_ibfk_2` FOREIGN KEY (`uczen_id`) REFERENCES `uczniowie` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Zgody RODO
CREATE TABLE `zgody_rodo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uzytkownik_id` int(11) NOT NULL,
  `typ_zgody` enum('przetwarzanie_ocen','udostepnianie_statystyk','eksport_danych') NOT NULL,
  `zgoda_udzielona` tinyint(1) NOT NULL DEFAULT 0,
  `data_zgody` timestamp NULL DEFAULT NULL,
  `data_wycofania` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `wersja_regulaminu` varchar(20) DEFAULT '1.0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_zgoda` (`uzytkownik_id`, `typ_zgody`),
  CONSTRAINT `zgody_rodo_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Ustawienia systemu oceniania
CREATE TABLE `ustawienia_oceniania` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `klucz` varchar(100) NOT NULL,
  `wartosc` text NOT NULL,
  `opis` varchar(200) DEFAULT NULL,
  `data_modyfikacji` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_klucz` (`klucz`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Domyślne ustawienia
INSERT INTO `ustawienia_oceniania` (`klucz`, `wartosc`, `opis`) VALUES
('aktualny_rok_szkolny', '2024/2025', 'Aktualny rok szkolny'),
('aktualny_semestr', '1', 'Aktualny semestr (1 lub 2)'),
('skala_ocen_min', '1', 'Minimalna ocena w skali'),
('skala_ocen_max', '6', 'Maksymalna ocena w skali'),
('oceny_dopuszczalne', '1,1.5,2,2.5,3,3.5,4,4.5,5,5.5,6', 'Lista dopuszczalnych ocen (plusy/minusy)'),
('czas_na_poprawe_dni', '14', 'Ile dni uczeń ma na poprawę oceny'),
('max_popraw', '1', 'Maksymalna liczba popraw jednej oceny'),
('prog_zagrozenia', '2.0', 'Średnia poniżej której uczeń jest zagrożony'),
('retencja_danych_lat', '5', 'Okres przechowywania danych ocen (RODO)'),
('automatyczne_srednie', '1', 'Czy automatycznie obliczać średnie');

-- --------------------------------------------------------

-- Widok: Statystyki ucznia (pomocniczy)
CREATE OR REPLACE VIEW `v_statystyki_ucznia` AS
SELECT
    u.id as uczen_id,
    u.klasa_id,
    o.przedmiot_id,
    p.nazwa as przedmiot_nazwa,
    o.semestr,
    o.rok_szkolny,
    COUNT(o.id) as liczba_ocen,
    ROUND(AVG(o.ocena), 2) as srednia_arytmetyczna,
    ROUND(
        SUM(o.ocena * COALESCE(o.waga_indywidualna, ko.waga)) /
        SUM(COALESCE(o.waga_indywidualna, ko.waga)),
    2) as srednia_wazona,
    MIN(o.ocena) as najnizsza_ocena,
    MAX(o.ocena) as najwyzsza_ocena,
    SUM(CASE WHEN o.ocena < 2 THEN 1 ELSE 0 END) as oceny_niedostateczne
FROM uczniowie u
JOIN oceny o ON u.id = o.uczen_id AND o.czy_liczona = 1
JOIN przedmioty p ON o.przedmiot_id = p.id
JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
GROUP BY u.id, u.klasa_id, o.przedmiot_id, p.nazwa, o.semestr, o.rok_szkolny;

-- --------------------------------------------------------

-- Widok: Statystyki klasy
CREATE OR REPLACE VIEW `v_statystyki_klasy` AS
SELECT
    k.id as klasa_id,
    k.nazwa as klasa_nazwa,
    o.przedmiot_id,
    p.nazwa as przedmiot_nazwa,
    o.semestr,
    o.rok_szkolny,
    COUNT(DISTINCT o.uczen_id) as liczba_uczniow,
    COUNT(o.id) as liczba_ocen,
    ROUND(AVG(o.ocena), 2) as srednia_klasy,
    ROUND(
        SUM(o.ocena * COALESCE(o.waga_indywidualna, ko.waga)) /
        SUM(COALESCE(o.waga_indywidualna, ko.waga)),
    2) as srednia_wazona_klasy
FROM klasy k
JOIN oceny o ON k.id = o.klasa_id AND o.czy_liczona = 1
JOIN przedmioty p ON o.przedmiot_id = p.id
JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
GROUP BY k.id, k.nazwa, o.przedmiot_id, p.nazwa, o.semestr, o.rok_szkolny;

-- --------------------------------------------------------

-- Widok: Ranking nauczycieli (dla dyrektora)
CREATE OR REPLACE VIEW `v_ranking_nauczycieli` AS
SELECT
    n.id as nauczyciel_id,
    uz.imie,
    uz.nazwisko,
    CONCAT(uz.imie, ' ', uz.nazwisko) as pelne_imie,
    o.rok_szkolny,
    COUNT(DISTINCT o.uczen_id) as liczba_uczniow,
    COUNT(DISTINCT o.klasa_id) as liczba_klas,
    COUNT(o.id) as liczba_wystawionych_ocen,
    ROUND(AVG(o.ocena), 2) as srednia_ocen,
    ROUND(
        SUM(o.ocena * COALESCE(o.waga_indywidualna, ko.waga)) /
        SUM(COALESCE(o.waga_indywidualna, ko.waga)),
    2) as srednia_wazona
FROM nauczyciele n
JOIN uzytkownicy uz ON n.uzytkownik_id = uz.id
LEFT JOIN oceny o ON n.id = o.nauczyciel_id AND o.czy_liczona = 1
LEFT JOIN kategorie_ocen ko ON o.kategoria_id = ko.id
GROUP BY n.id, uz.imie, uz.nazwisko, o.rok_szkolny;

COMMIT;
