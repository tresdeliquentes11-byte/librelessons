-- Zeby utworzyc baze dla aplikacji
-- Stworz baze w phpmyadmin o nazwie 'plan_lekcji'
-- I zaimportuj ten plik

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

--
-- Dumping data for table `klasy`
--

INSERT INTO `klasy` (`id`, `nazwa`, `wychowawca_id`, `ilosc_godzin_dziennie`, `rozszerzenie_1`, `rozszerzenie_2`) VALUES
(1, '1A', NULL, 8, 'Matematyka rozszerzona', 'Fizyka rozszerzona'),
(2, '1B', NULL, 8, 'Matematyka rozszerzona', 'Język angielski rozszerzony'),
(3, '1C', NULL, 8, 'Fizyka rozszerzona', 'Język angielski rozszerzony'),
(4, '2A', NULL, 8, 'Matematyka rozszerzona', 'Fizyka rozszerzona'),
(5, '2B', NULL, 8, 'Matematyka rozszerzona', 'Język angielski rozszerzony'),
(6, '2C', NULL, 8, 'Fizyka rozszerzona', 'Język angielski rozszerzony'),
(7, '3A', NULL, 8, 'Matematyka rozszerzona', 'Fizyka rozszerzona'),
(8, '3B', NULL, 8, 'Matematyka rozszerzona', 'Język angielski rozszerzony'),
(9, '3C', NULL, 8, 'Fizyka rozszerzona', 'Język angielski rozszerzony'),
(10, '4A', NULL, 8, 'Matematyka rozszerzona', 'Fizyka rozszerzona'),
(11, '4B', NULL, 8, 'Matematyka rozszerzona', 'Język angielski rozszerzony'),
(12, '4C', NULL, 8, 'Fizyka rozszerzona', 'Język angielski rozszerzony');

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
  `oryginalny_nauczyciel_id` int(11) DEFAULT NULL
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

--
-- Dumping data for table `przedmioty`
--

INSERT INTO `przedmioty` (`id`, `nazwa`, `skrot`, `czy_rozszerzony`, `domyslna_ilosc_godzin`) VALUES
(1, 'Matematyka', 'MAT', 0, 3),
(2, 'Matematyka rozszerzona', 'MAT-R', 1, 2),
(3, 'Język polski', 'POL', 0, 4),
(4, 'Język angielski', 'ANG', 0, 3),
(5, 'Język angielski rozszerzony', 'ANG-R', 1, 2),
(6, 'Geografia', 'GEO', 0, 1),
(8, 'Chemia', 'CHEM', 0, 1),
(9, 'Fizyka', 'FIZ', 0, 1),
(10, 'Fizyka rozszerzona', 'FIZ-R', 1, 1),
(12, 'Język hiszpański', 'HISZ', 0, 1),
(13, 'Historia', 'HIST', 0, 2),
(14, 'WOS', 'WOS', 0, 2),
(15, 'WF', 'WF', 0, 3),
(16, 'Informatyka', 'INF', 0, 1);

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

--
-- Dumping data for table `sala_przedmioty`
--

INSERT INTO `sala_przedmioty` (`id`, `sala_id`, `przedmiot_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(40, 2, 4),
(41, 2, 5),
(42, 2, 12),
(8, 3, 3),
(9, 4, 13),
(10, 5, 6),
(33, 6, 9),
(34, 6, 10),
(35, 7, 8),
(37, 9, 16),
(38, 10, 15),
(19, 11, 1),
(20, 11, 2),
(13, 12, 4),
(14, 12, 5),
(15, 12, 12),
(17, 13, 3),
(18, 14, 13),
(21, 15, 6),
(22, 16, 6),
(23, 17, 1),
(24, 17, 2),
(25, 18, 9),
(26, 18, 10),
(27, 19, 9),
(28, 19, 10),
(29, 20, 8),
(31, 22, 16),
(32, 23, 15),
(39, 24, 14);

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

--
-- Dumping data for table `sale`
--

INSERT INTO `sale` (`id`, `numer`, `nazwa`, `typ`, `pojemnosc`) VALUES
(1, '101', 'Sala matematyczna', 'standardowa', 30),
(2, '102', 'Sala językowa', 'standardowa', 30),
(3, '103', 'Sala polonistyczna', 'standardowa', 30),
(4, '104', 'Sala historyczna', 'standardowa', 30),
(5, '105', 'Sala geograficzna', 'standardowa', 30),
(6, '201', 'Pracownia fizyczna', 'pracownia', 30),
(7, '202', 'Pracownia chemiczna', 'pracownia', 30),
(8, '203', 'Pracownia biologiczna', 'pracownia', 30),
(9, '204', 'Pracownia informatyczna', 'pracownia', 30),
(10, 'SALA-WF', 'Sala gimnastyczna', 'sportowa', 30),
(11, '106', 'Sala matematyczna', 'standardowa', 30),
(12, '107', 'Sala językowa', 'standardowa', 30),
(13, '108', 'Sala polonistyczna', 'standardowa', 30),
(14, '109', 'Sala historyczna', 'standardowa', 30),
(15, '110', 'Sala geograficzna', 'standardowa', 30),
(16, '111', 'Sala geograficzna', 'standardowa', 30),
(17, '112', 'Sala matematyczna', 'standardowa', 30),
(18, '113', 'Pracownia fizyczna', 'pracownia', 30),
(19, '114', 'Pracownia fizyczna', 'pracownia', 30),
(20, '115', 'Pracownia chemiczna', 'pracownia', 30),
(21, '116', 'Pracownia biologiczna', 'pracownia', 30),
(22, '117', 'Pracownia informatyczna', 'pracownia', 30),
(23, '118', 'Sala gimnastyczna', 'sportowa', 30),
(24, '119', 'Sala kulturoznawcza', 'specjalna', 30);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `logi_aktywnosci`
--
ALTER TABLE `logi_aktywnosci`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nauczyciele`
--
ALTER TABLE `nauczyciele`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `nauczyciel_przedmioty`
--
ALTER TABLE `nauczyciel_przedmioty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=367;

--
-- AUTO_INCREMENT for table `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=445;

--
-- AUTO_INCREMENT for table `zastepstwa`
--
ALTER TABLE `zastepstwa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  ADD CONSTRAINT `plan_dzienny_ibfk_6` FOREIGN KEY (`oryginalny_nauczyciel_id`) REFERENCES `nauczyciele` (`id`) ON DELETE SET NULL;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
