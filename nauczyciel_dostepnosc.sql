-- Tabela z godzinami dostępności nauczycieli
CREATE TABLE IF NOT EXISTS nauczyciel_dostepnosc (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nauczyciel_id INT NOT NULL,
    typ ENUM('stala', 'jednorazowa') NOT NULL COMMENT 'stala = regularna dostępność, jednorazowa = wyjątek/niedostępność',
    dzien_tygodnia INT NULL COMMENT '1=poniedziałek, 5=piątek - dla typu stala',
    data_konkretna DATE NULL COMMENT 'Konkretna data - dla typu jednorazowa',
    godzina_od TIME NOT NULL COMMENT 'Początek dostępności',
    godzina_do TIME NOT NULL COMMENT 'Koniec dostępności',
    opis TEXT NULL COMMENT 'Opcjonalny opis (np. powód niedostępności)',
    utworzono TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nauczyciel_id) REFERENCES nauczyciele(id) ON DELETE CASCADE,
    INDEX idx_nauczyciel (nauczyciel_id),
    INDEX idx_dzien (dzien_tygodnia),
    INDEX idx_data (data_konkretna)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Przykładowe dane: Nauczyciel dostępny każdy dzień od 8:00 do 16:00
-- INSERT INTO nauczyciel_dostepnosc (nauczyciel_id, typ, dzien_tygodnia, godzina_od, godzina_do, opis)
-- VALUES (1, 'stala', 1, '08:00', '16:00', 'Regularna dostępność - poniedziałek');

-- Przykład niedostępności jednorazowej (np. wizyta lekarska)
-- INSERT INTO nauczyciel_dostepnosc (nauczyciel_id, typ, data_konkretna, godzina_od, godzina_do, opis)
-- VALUES (1, 'jednorazowa', '2025-12-10', '10:00', '12:00', 'Wizyta lekarska');
