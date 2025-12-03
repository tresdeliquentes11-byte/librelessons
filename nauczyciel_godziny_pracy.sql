-- Tabela z godzinami pracy nauczycieli
-- Jeśli nauczyciel nie ma wpisu dla danego dnia = jest niedostępny tego dnia

DROP TABLE IF EXISTS nauczyciel_dostepnosc;

CREATE TABLE IF NOT EXISTS nauczyciel_godziny_pracy (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nauczyciel_id INT NOT NULL,
    dzien_tygodnia INT NOT NULL COMMENT '1=poniedziałek, 2=wtorek, 3=środa, 4=czwartek, 5=piątek',
    godzina_od TIME NOT NULL COMMENT 'Początek pracy',
    godzina_do TIME NOT NULL COMMENT 'Koniec pracy',
    utworzono TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    zaktualizowano TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nauczyciel_id) REFERENCES nauczyciele(id) ON DELETE CASCADE,
    UNIQUE KEY unique_nauczyciel_dzien (nauczyciel_id, dzien_tygodnia),
    INDEX idx_nauczyciel (nauczyciel_id),
    INDEX idx_dzien (dzien_tygodnia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Przykładowe dane: Nauczyciel pracuje od poniedziałku do piątku 8:00-16:00
-- INSERT INTO nauczyciel_godziny_pracy (nauczyciel_id, dzien_tygodnia, godzina_od, godzina_do)
-- VALUES
--   (1, 1, '08:00', '16:00'),
--   (1, 2, '08:00', '16:00'),
--   (1, 3, '08:00', '16:00'),
--   (1, 4, '08:00', '16:00'),
--   (1, 5, '08:00', '16:00');
