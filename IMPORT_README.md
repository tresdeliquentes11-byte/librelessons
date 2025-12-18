# Funkcjonalność Importu Danych dla Dyrektora

## Opis

System umożliwia dyrektorowi importowanie danych z plików CSV i XLSX dla uczniów, nauczycieli, klas i sal. Funkcjonalność została zaimplementowana z uwzględnieniem bezpieczeństwa i zgodności z najlepszymi praktykami PHP.

## Lokalizacja plików

- Główny plik importu: `dyrektor/import.php`
- Funkcje importu: `includes/import_functions.php`
- Przykładowe pliki: `uploads/examples/`

## Wymagania systemowe

- PHP 7.4+ z włączonymi rozszerzeniami:
  - `ZipArchive` (do obsługi plików XLSX)
  - `SimpleXML` (do parsowania plików XLSX)
  - `mysqli` (do komunikacji z bazą danych)
- Serwer WWW z obsługą przesyłania plików

## Obsługiwane formaty

### CSV
- Separator: średnik (`;`)
- Kodowanie: UTF-8
- Pierwszy wiersz: nagłówki
- Pola wymagane: zależne od typu importu

### XLSX
- Obsługiwane przez wbudowane funkcje PHP (bez zewnętrznych bibliotek)
- Ograniczenie: podstawowe funkcje parsowania XML

## Struktury danych

### Uczniowie
```csv
imie;nazwisko;login;haslo;email;klasa
Jan;Kowalski;jkowalski;haslo123;jan.kowalski@szkola.pl;1A
Anna;Nowak;anowak;haslo123;anna.nowak@szkola.pl;2B
```

### Nauczyciele
```csv
imie;nazwisko;login;haslo;email
Marek;Nowak;mnowak;haslo123;marek.nowak@szkola.pl
Ewa;Kowalska;ekowalska;haslo123;ewa.kowalska@szkola.pl
```

### Klasy
```csv
nazwa;ilosc_godzin_dziennie;rozszerzenie_1;rozszerzenie_2
1A;7;matematyka;fizyka
1B;7;polski;historia
```

### Sale
```csv
numer;nazwa;typ;pojemnosc
1;Sala 1;standardowa;30
2;Pracownia informatyczna;pracownia;25
```

## Bezpieczeństwo

- Ochrona CSRF (tokeny)
- Walidacja typu i rozmiaru pliku
- Sprawdzanie MIME type pliku za pomocą `finfo`
- Unikalność loginów
- Transakcje bazodanowe
- Usuwanie tymczasowych plików
- Walidacja danych wejściowych

## Jak używać

1. Zaloguj się jako dyrektor
2. Przejdź do sekcji "Import Danych" w menu bocznym
3. Wybierz typ importu (uczniowie, nauczyciele, klasy lub sale)
4. Wybierz plik CSV lub XLSX z komputera
5. Kliknij "Importuj"
6. Przejrzyj wyniki importu

## Przykładowe pliki

W katalogu `uploads/examples/` znajdują się przykładowe pliki CSV dla każdego typu importu. Można je pobrać klikając na odpowiednie przyciski na stronie importu.

## Rozwiązywanie problemów

### Brak uprawnień do zapisu pliku
Upewnij się, że katalog `uploads/` ma uprawnienia do zapisu (chmod 755).

### Błąd "Plik jest zbyt duży"
Zmniejsz plik lub zwiększ limit w pliku `dyrektor/import.php` (obecnie 5MB).

### Błąd "Nieprawidłowy typ pliku"
Upewnij się, że plik ma prawidłowe rozszerzenie (.csv lub .xlsx) i jest kodowany w UTF-8.

### Błąd parsowania XLSX
Upewnij się, że serwer ma włączone rozszerzenia `ZipArchive` i `SimpleXML`.

### Błędy importu
Sprawdź szczegóły błędu w wynikach importu. Najczęstsze problemy:
- Brak wymaganych pól
- Duplikaty loginy
- Nieistniejące klasy
- Błędny format pliku

## Testowanie

Do celów testowych można użyć pliku `test_import.php` (należy usunąć po testach).

## Aktualizacja

W razie potrzeby modyfikacji funkcji importu, należy:
1. Dodać nowe pola w strukturach danych
2. Zaktualizować walidację w `includes/import_functions.php`
3. Przetestować z różnymi typami plików
4. Zaktualizować dokumentację

## Wsparcie techniczne

W przypadku problemów z implementacją należy skontaktować się z administratorem systemu.