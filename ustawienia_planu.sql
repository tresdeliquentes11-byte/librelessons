-- Tabela z ustawieniami planu lekcji
CREATE TABLE IF NOT EXISTS `ustawienia_planu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazwa` varchar(100) NOT NULL,
  `wartosc` varchar(255) NOT NULL,
  `opis` text,
  `data_modyfikacji` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nazwa` (`nazwa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Domyślne ustawienia
INSERT INTO `ustawienia_planu` (`nazwa`, `wartosc`, `opis`) VALUES
('dlugosc_lekcji', '45', 'Długość jednej lekcji w minutach'),
('godzina_rozpoczecia', '08:00', 'Godzina rozpoczęcia pierwszej lekcji'),
('przerwa_krotka', '10', 'Długość krótkiej przerwy w minutach'),
('przerwa_dluga', '15', 'Długość długiej przerwy w minutach'),
('przerwa_dluga_po_lekcji', '3', 'Po której lekcji jest dłuższa przerwa')
ON DUPLICATE KEY UPDATE `wartosc`=VALUES(`wartosc`);
