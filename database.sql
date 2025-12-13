-- © 2025 TresDeliquentes. Wszystkie prawa zastrzeżone.
-- LibreLessons działa na licencji TEUL (użytek edukacyjny).
-- Zakazana dystrybucja, publikacja i użycie komercyjne bez zgody autora.

-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `plan_lekcji`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `dni_wolne`
--

CREATE TABLE `dni_wolne` (
  `id` int(11) NOT NULL,
  `data` date NOT NULL,
  `opis` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `klasa_przedmioty`
--

CREATE TABLE `klasa_przedmioty` (
  `id` int(11) NOT NULL,
  `klasa_id` int(11) NOT NULL,
  `przedmiot_id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `ilosc_godzin_tydzien` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `klasy`
--

CREATE TABLE `klasy` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(10) NOT NULL,
  `wychowawca_id` int(11) DEFAULT NULL,
  `ilosc_godzin_dziennie` int(11) DEFAULT 7,
  `rozszerzenie_1` varchar(50) DEFAULT NULL,
  `rozszerzenie_2` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `logi_aktywnosci`
--

CREATE TABLE `logi_aktywnosci` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) DEFAULT NULL,
  `typ_akcji` varchar(50) NOT NULL,
  `opis` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `data_akcji` timestamp NOT NULL DEFAULT current_timestamp(),
  `dodatkowe_dane` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nauczyciele`
--

CREATE TABLE `nauczyciele` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nauczyciel_godziny_pracy`
--

CREATE TABLE `nauczyciel_godziny_pracy` (
  `id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `dzien_tygodnia` int(11) NOT NULL COMMENT '1-5',
  `godzina_od` time NOT NULL,
  `godzina_do` time NOT NULL,
  `utworzono` timestamp NOT NULL DEFAULT current_timestamp(),
  `zaktualizowano` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nauczyciel_przedmioty`
--

CREATE TABLE `nauczyciel_przedmioty` (
  `id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `przedmiot_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `nieobecnosci`
--

CREATE TABLE `nieobecnosci` (
  `id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `data_od` date NOT NULL,
  `data_do` date NOT NULL,
  `powod` varchar(200) DEFAULT NULL,
  `data_zgloszenia` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `plan_dzienny`
--

CREATE TABLE `plan_dzienny` (
  `id` int(11) NOT NULL,
  `plan_lekcji_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `klasa_id` int(11) NOT NULL,
  `numer_lekcji` int(11) NOT NULL,
  `godzina_rozpoczecia` time NOT NULL,
  `godzina_zakonczenia` time NOT NULL,
  `przedmiot_id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `sala_id` int(11) DEFAULT NULL,
  `czy_zastepstwo` tinyint(1) DEFAULT 0,
  `oryginalny_nauczyciel_id` int(11) DEFAULT NULL,
  `utworzony_recznie` tinyint(1) DEFAULT 0,
  `ostatnia_modyfikacja` timestamp NULL DEFAULT NULL,
  `zmodyfikowany_przez` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `plan_lekcji`
--

CREATE TABLE `plan_lekcji` (
  `id` int(11) NOT NULL,
  `klasa_id` int(11) NOT NULL,
  `dzien_tygodnia` enum('poniedzialek','wtorek','sroda','czwartek','piatek') NOT NULL,
  `numer_lekcji` int(11) NOT NULL,
  `godzina_rozpoczecia` time NOT NULL,
  `godzina_zakonczenia` time NOT NULL,
  `przedmiot_id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL,
  `sala_id` int(11) DEFAULT NULL,
  `szablon_tygodniowy` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `przedmioty`
--

CREATE TABLE `przedmioty` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(100) NOT NULL,
  `skrot` varchar(20) DEFAULT NULL,
  `czy_rozszerzony` tinyint(1) DEFAULT 0,
  `domyslna_ilosc_godzin` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `sala_nauczyciele`
--

CREATE TABLE `sala_nauczyciele` (
  `id` int(11) NOT NULL,
  `sala_id` int(11) NOT NULL,
  `nauczyciel_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `sala_przedmioty`
--

CREATE TABLE `sala_przedmioty` (
  `id` int(11) NOT NULL,
  `sala_id` int(11) NOT NULL,
  `przedmiot_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `sale`
--

CREATE TABLE `sale` (
  `id` int(11) NOT NULL,
  `numer` varchar(20) NOT NULL,
  `nazwa` varchar(100) DEFAULT NULL,
  `typ` enum('standardowa','pracownia','sportowa','specjalna') DEFAULT 'standardowa',
  `pojemnosc` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `sesje_uzytkownikow`
--

CREATE TABLE `sesje_uzytkownikow` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ostatnia_aktywnosc` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `data_logowania` timestamp NOT NULL DEFAULT current_timestamp(),
  `aktywna` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `statystyki_generowania`
--

CREATE TABLE `statystyki_generowania` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `typ_generowania` enum('plan_tygodniowy','plan_dzienny','zastepstwa') NOT NULL,
  `data_generowania` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sukces','blad','przerwane') DEFAULT 'sukces',
  `czas_trwania_sekundy` decimal(10,2) DEFAULT NULL,
  `ilosc_wygenerowanych_lekcji` int(11) DEFAULT 0,
  `komunikat_bledu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `statystyki_uzytkownikow`
--

CREATE TABLE `statystyki_uzytkownikow` (
  `id` int(11) NOT NULL,
  `administrator_id` int(11) NOT NULL,
  `typ_operacji` enum('dodanie','edycja','usuniecie','blokada','odblokowanie') NOT NULL,
  `uzytkownik_docelowy_id` int(11) DEFAULT NULL,
  `typ_uzytkownika_docelowego` enum('dyrektor','administrator','nauczyciel','uczen') DEFAULT NULL,
  `data_operacji` timestamp NOT NULL DEFAULT current_timestamp(),
  `opis_zmian` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `uczniowie`
--

CREATE TABLE `uczniowie` (
  `id` int(11) NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `klasa_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `ustawienia_planu`
--

CREATE TABLE `ustawienia_planu` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(100) NOT NULL,
  `wartosc` varchar(255) NOT NULL,
  `opis` text DEFAULT NULL,
  `data_modyfikacji` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `uzytkownicy`
--

CREATE TABLE `uzytkownicy` (
  `id` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  `haslo` varchar(255) NOT NULL,
  `typ` enum('dyrektor','administrator','nauczyciel','uczen') NOT NULL,
  `imie` varchar(100) NOT NULL,
  `nazwisko` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `aktywny` tinyint(1) DEFAULT 1,
  `data_utworzenia` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uzytkownicy`
--

INSERT INTO `uzytkownicy` (`id`, `login`, `haslo`, `typ`, `imie`, `nazwisko`, `email`, `aktywny`, `data_utworzenia`) VALUES
(1, 'dyrektor', '$2y$10$avKUzcz9kJE408oj0pu4suhTTVXHyTGnRVyHGzswoxNT/.wyLbCl6', 'dyrektor', 'Jan', 'Kowalski', 'dyrektor@szkola.pl', 1, '2025-11-30 11:38:04'),
(2, 'admin', '$2y$10$VnMkJR.T.ASSa8i49XwuyOx2A3VrMPx8IuNAw0i0roZiqU.eMxm6e', 'administrator', 'Anna', 'Nowak', 'admin@szkola.pl', 1, '2025-11-30 11:38:04');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zastepstwa`
--

CREATE TABLE `zastepstwa` (
  `id` int(11) NOT NULL,
  `plan_dzienny_id` int(11) NOT NULL,
  `nieobecnosc_id` int(11) NOT NULL,
  `nauczyciel_zastepujacy_id` int(11) NOT NULL,
  `data_utworzenia` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `historia_zmian_planu`
--

CREATE TABLE `historia_zmian_planu` (
  `id` int(11) NOT NULL,
  `plan_dzienny_id` int(11) DEFAULT NULL,
  `typ_zmiany` enum('utworzenie','edycja','usuniecie','przesuniecie') NOT NULL,
  `uzytkownik_id` int(11) NOT NULL,
  `stan_przed` text DEFAULT NULL COMMENT 'JSON snapshot before change',
  `stan_po` text DEFAULT NULL COMMENT 'JSON snapshot after change',
  `data_zmiany` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `komentarz` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `konflikty_planu`
--

CREATE TABLE `konflikty_planu` (
  `id` int(11) NOT NULL,
  `plan_dzienny_id` int(11) NOT NULL,
  `typ_konfliktu` enum('nauczyciel','sala','klasa','wymiar_godzin','dostepnosc') NOT NULL,
  `opis` text NOT NULL,
  `konflikty_z` text DEFAULT NULL COMMENT 'JSON array of conflicting plan_dzienny_id',
  `czy_rozwiazany` tinyint(1) DEFAULT 0,
  `data_wykrycia` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_rozwiazania` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `dni_wolne`
--
ALTER TABLE `dni_wolne`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_data` (`data`);

--
-- Indeksy dla tabeli `klasa_przedmioty`
--
ALTER TABLE `klasa_przedmioty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_klasa_przedmiot` (`klasa_id`,`przedmiot_id`),
  ADD KEY `przedmiot_id` (`przedmiot_id`),
  ADD KEY `nauczyciel_id` (`nauczyciel_id`);

--
-- Indeksy dla tabeli `klasy`
--
ALTER TABLE `klasy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nazwa` (`nazwa`),
  ADD KEY `klasy_ibfk_1` (`wychowawca_id`);

--
-- Indeksy dla tabeli `logi_aktywnosci`
--
ALTER TABLE `logi_aktywnosci`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `typ_akcji` (`typ_akcji`),
  ADD KEY `data_akcji` (`data_akcji`),
  ADD KEY `idx_logi_ostatnie` (`data_akcji`);

--
-- Indeksy dla tabeli `nauczyciele`
--
ALTER TABLE `nauczyciele`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `nauczyciel_godziny_pracy`
--
ALTER TABLE `nauczyciel_godziny_pracy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nauczyciel_dzien` (`nauczyciel_id`,`dzien_tygodnia`),
  ADD KEY `idx_nauczyciel` (`nauczyciel_id`),
  ADD KEY `idx_dzien` (`dzien_tygodnia`);

--
-- Indeksy dla tabeli `nauczyciel_przedmioty`
--
ALTER TABLE `nauczyciel_przedmioty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nauczyciel_przedmiot` (`nauczyciel_id`,`przedmiot_id`),
  ADD KEY `przedmiot_id` (`przedmiot_id`);

--
-- Indeksy dla tabeli `nieobecnosci`
--
ALTER TABLE `nieobecnosci`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nauczyciel_id` (`nauczyciel_id`);

--
-- Indeksy dla tabeli `plan_dzienny`
--
ALTER TABLE `plan_dzienny`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_plan_data` (`data`,`klasa_id`,`numer_lekcji`),
  ADD KEY `plan_lekcji_id` (`plan_lekcji_id`),
  ADD KEY `klasa_id` (`klasa_id`),
  ADD KEY `przedmiot_id` (`przedmiot_id`),
  ADD KEY `nauczyciel_id` (`nauczyciel_id`),
  ADD KEY `sala_id` (`sala_id`),
  ADD KEY `oryginalny_nauczyciel_id` (`oryginalny_nauczyciel_id`);

--
-- Indeksy dla tabeli `plan_lekcji`
--
ALTER TABLE `plan_lekcji`
  ADD PRIMARY KEY (`id`),
  ADD KEY `klasa_id` (`klasa_id`),
  ADD KEY `przedmiot_id` (`przedmiot_id`),
  ADD KEY `nauczyciel_id` (`nauczyciel_id`),
  ADD KEY `sala_id` (`sala_id`);

--
-- Indeksy dla tabeli `przedmioty`
--
ALTER TABLE `przedmioty`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `sala_nauczyciele`
--
ALTER TABLE `sala_nauczyciele`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_sala_nauczyciel` (`sala_id`,`nauczyciel_id`),
  ADD KEY `nauczyciel_id` (`nauczyciel_id`);

--
-- Indeksy dla tabeli `sala_przedmioty`
--
ALTER TABLE `sala_przedmioty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_sala_przedmiot` (`sala_id`,`przedmiot_id`),
  ADD KEY `przedmiot_id` (`przedmiot_id`);

--
-- Indeksy dla tabeli `sale`
--
ALTER TABLE `sale`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numer` (`numer`);

--
-- Indeksy dla tabeli `sesje_uzytkownikow`
--
ALTER TABLE `sesje_uzytkownikow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `aktywna` (`aktywna`),
  ADD KEY `idx_sesje_aktywne` (`aktywna`,`ostatnia_aktywnosc`);

--
-- Indeksy dla tabeli `statystyki_generowania`
--
ALTER TABLE `statystyki_generowania`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `data_generowania` (`data_generowania`),
  ADD KEY `typ_generowania` (`typ_generowania`);

--
-- Indeksy dla tabeli `statystyki_uzytkownikow`
--
ALTER TABLE `statystyki_uzytkownikow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `administrator_id` (`administrator_id`),
  ADD KEY `uzytkownik_docelowy_id` (`uzytkownik_docelowy_id`),
  ADD KEY `data_operacji` (`data_operacji`);

--
-- Indeksy dla tabeli `uczniowie`
--
ALTER TABLE `uczniowie`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `klasa_id` (`klasa_id`);

--
-- Indeksy dla tabeli `ustawienia_planu`
--
ALTER TABLE `ustawienia_planu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nazwa` (`nazwa`);

--
-- Indeksy dla tabeli `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- Indeksy dla tabeli `zastepstwa`
--
ALTER TABLE `zastepstwa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_dzienny_id` (`plan_dzienny_id`),
  ADD KEY `nieobecnosc_id` (`nieobecnosc_id`),
  ADD KEY `nauczyciel_zastepujacy_id` (`nauczyciel_zastepujacy_id`);

--
-- Indeksy dla tabeli `historia_zmian_planu`
--
ALTER TABLE `historia_zmian_planu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_dzienny_id` (`plan_dzienny_id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `data_zmiany` (`data_zmiany`),
  ADD KEY `typ_zmiany` (`typ_zmiany`);

--
-- Indeksy dla tabeli `konflikty_planu`
--
ALTER TABLE `konflikty_planu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_dzienny_id` (`plan_dzienny_id`),
  ADD KEY `typ_konfliktu` (`typ_konfliktu`),
  ADD KEY `czy_rozwiazany` (`czy_rozwiazany`),
  ADD KEY `data_wykrycia` (`data_wykrycia`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dni_wolne`
--
ALTER TABLE `dni_wolne`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `klasa_przedmioty`
--
ALTER TABLE `klasa_przedmioty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `klasy`
--
ALTER TABLE `klasy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `logi_aktywnosci`
--
ALTER TABLE `logi_aktywnosci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nauczyciele`
--
ALTER TABLE `nauczyciele`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `nauczyciel_godziny_pracy`
--
ALTER TABLE `nauczyciel_godziny_pracy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `nauczyciel_przedmioty`
--
ALTER TABLE `nauczyciel_przedmioty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `nieobecnosci`
--
ALTER TABLE `nieobecnosci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `plan_dzienny`
--
ALTER TABLE `plan_dzienny`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=269985;

--
-- AUTO_INCREMENT for table `plan_lekcji`
--
ALTER TABLE `plan_lekcji`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6421;

--
-- AUTO_INCREMENT for table `przedmioty`
--
ALTER TABLE `przedmioty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sala_nauczyciele`
--
ALTER TABLE `sala_nauczyciele`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `sala_przedmioty`
--
ALTER TABLE `sala_przedmioty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `sale`
--
ALTER TABLE `sale`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `sesje_uzytkownikow`
--
ALTER TABLE `sesje_uzytkownikow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `statystyki_generowania`
--
ALTER TABLE `statystyki_generowania`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `statystyki_uzytkownikow`
--
ALTER TABLE `statystyki_uzytkownikow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uczniowie`
--
ALTER TABLE `uczniowie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=364;

--
-- AUTO_INCREMENT for table `ustawienia_planu`
--
ALTER TABLE `ustawienia_planu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=443;

--
-- AUTO_INCREMENT for table `zastepstwa`
--
ALTER TABLE `zastepstwa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `historia_zmian_planu`
--
ALTER TABLE `historia_zmian_planu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `konflikty_planu`
--
ALTER TABLE `konflikty_planu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `klasa_przedmioty`
--
ALTER TABLE `klasa_przedmioty`
  ADD CONSTRAINT `klasa_przedmioty_ibfk_1` FOREIGN KEY (`klasa_id`) REFERENCES `klasy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `klasa_przedmioty_ibfk_2` FOREIGN KEY (`przedmiot_id`) REFERENCES `przedmioty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `klasa_przedmioty_ibfk_3` FOREIGN KEY (`nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `klasy`
--
ALTER TABLE `klasy`
  ADD CONSTRAINT `klasy_ibfk_1` FOREIGN KEY (`wychowawca_id`) REFERENCES `nauczyciele` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `logi_aktywnosci`
--
ALTER TABLE `logi_aktywnosci`
  ADD CONSTRAINT `logi_aktywnosci_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `nauczyciele`
--
ALTER TABLE `nauczyciele`
  ADD CONSTRAINT `nauczyciele_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nauczyciel_godziny_pracy`
--
ALTER TABLE `nauczyciel_godziny_pracy`
  ADD CONSTRAINT `nauczyciel_godziny_pracy_ibfk_1` FOREIGN KEY (`nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nauczyciel_przedmioty`
--
ALTER TABLE `nauczyciel_przedmioty`
  ADD CONSTRAINT `nauczyciel_przedmioty_ibfk_1` FOREIGN KEY (`nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nauczyciel_przedmioty_ibfk_2` FOREIGN KEY (`przedmiot_id`) REFERENCES `przedmioty` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nieobecnosci`
--
ALTER TABLE `nieobecnosci`
  ADD CONSTRAINT `nieobecnosci_ibfk_1` FOREIGN KEY (`nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `plan_dzienny`
--
ALTER TABLE `plan_dzienny`
  ADD CONSTRAINT `plan_dzienny_ibfk_1` FOREIGN KEY (`plan_lekcji_id`) REFERENCES `plan_lekcji` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_dzienny_ibfk_2` FOREIGN KEY (`klasa_id`) REFERENCES `klasy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_dzienny_ibfk_3` FOREIGN KEY (`przedmiot_id`) REFERENCES `przedmioty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_dzienny_ibfk_4` FOREIGN KEY (`nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_dzienny_ibfk_5` FOREIGN KEY (`sala_id`) REFERENCES `sale` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `plan_dzienny_ibfk_6` FOREIGN KEY (`oryginalny_nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `plan_dzienny_ibfk_7` FOREIGN KEY (`zmodyfikowany_przez`) REFERENCES `uzytkownicy` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `plan_lekcji`
--
ALTER TABLE `plan_lekcji`
  ADD CONSTRAINT `plan_lekcji_ibfk_1` FOREIGN KEY (`klasa_id`) REFERENCES `klasy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_lekcji_ibfk_2` FOREIGN KEY (`przedmiot_id`) REFERENCES `przedmioty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_lekcji_ibfk_3` FOREIGN KEY (`nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plan_lekcji_ibfk_4` FOREIGN KEY (`sala_id`) REFERENCES `sale` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sala_nauczyciele`
--
ALTER TABLE `sala_nauczyciele`
  ADD CONSTRAINT `sala_nauczyciele_ibfk_1` FOREIGN KEY (`sala_id`) REFERENCES `sale` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sala_nauczyciele_ibfk_2` FOREIGN KEY (`nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sala_przedmioty`
--
ALTER TABLE `sala_przedmioty`
  ADD CONSTRAINT `sala_przedmioty_ibfk_1` FOREIGN KEY (`sala_id`) REFERENCES `sale` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sala_przedmioty_ibfk_2` FOREIGN KEY (`przedmiot_id`) REFERENCES `przedmioty` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sesje_uzytkownikow`
--
ALTER TABLE `sesje_uzytkownikow`
  ADD CONSTRAINT `sesje_uzytkownikow_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `statystyki_generowania`
--
ALTER TABLE `statystyki_generowania`
  ADD CONSTRAINT `statystyki_generowania_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `statystyki_uzytkownikow`
--
ALTER TABLE `statystyki_uzytkownikow`
  ADD CONSTRAINT `statystyki_uzytkownikow_ibfk_1` FOREIGN KEY (`administrator_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `statystyki_uzytkownikow_ibfk_2` FOREIGN KEY (`uzytkownik_docelowy_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `uczniowie`
--
ALTER TABLE `uczniowie`
  ADD CONSTRAINT `uczniowie_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `uczniowie_ibfk_2` FOREIGN KEY (`klasa_id`) REFERENCES `klasy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `zastepstwa`
--
ALTER TABLE `zastepstwa`
  ADD CONSTRAINT `zastepstwa_ibfk_1` FOREIGN KEY (`plan_dzienny_id`) REFERENCES `plan_dzienny` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zastepstwa_ibfk_2` FOREIGN KEY (`nieobecnosc_id`) REFERENCES `nieobecnosci` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zastepstwa_ibfk_3` FOREIGN KEY (`nauczyciel_zastepujacy_id`) REFERENCES `nauczyciele` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `historia_zmian_planu`
--
ALTER TABLE `historia_zmian_planu`
  ADD CONSTRAINT `historia_zmian_planu_ibfk_1` FOREIGN KEY (`plan_dzienny_id`) REFERENCES `plan_dzienny` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `historia_zmian_planu_ibfk_2` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `konflikty_planu`
--
ALTER TABLE `konflikty_planu`
  ADD CONSTRAINT `konflikty_planu_ibfk_1` FOREIGN KEY (`plan_dzienny_id`) REFERENCES `plan_dzienny` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- MODUŁ: SYSTEM OCENIANIA v4.0
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

-- Domyślne ustawienia systemu oceniania
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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
