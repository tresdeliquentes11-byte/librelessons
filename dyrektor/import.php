<?php
require_once '../includes/config.php';
require_once '../includes/import_functions.php';
sprawdz_uprawnienia('dyrektor');

$message = '';
$message_type = '';
$import_results = null;

// Obsługa przesyłania pliku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Nieprawidłowe żądanie. Odśwież stronę i spróbuj ponownie.';
        $message_type = 'error';
    } else {
        $import_type = $_POST['import_type'] ?? '';
        $file = $_FILES['import_file'];
        
        // Walidacja pliku
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = 'Błąd przesyłania pliku: ' . $file['error'];
            $message_type = 'error';
        } elseif (!in_array($file['type'], ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
            $message = 'Nieprawidłowy typ pliku. Dozwolone są tylko pliki CSV i XLSX.';
            $message_type = 'error';
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $message = 'Plik jest zbyt duży. Maksymalny rozmiar to 5MB.';
            $message_type = 'error';
        } else {
            // Przesunięcie pliku do tymczasowej lokalizacji
            $temp_file = '../uploads/import_' . time() . '_' . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $temp_file)) {
                try {
                    // Parsowanie pliku
                    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if ($file_extension === 'csv') {
                        $data = parse_csv_file($temp_file, ';'); // Używamy średnika jako separatora dla polskich plików
                    } elseif ($file_extension === 'xlsx') {
                        $data = parse_xlsx_file($temp_file);
                    } else {
                        throw new Exception('Nieobsługiwany format pliku');
                    }
                    
                    if (empty($data)) {
                        throw new Exception('Plik jest pusty lub ma nieprawidłowy format');
                    }
                    
                    // Import danych w zależności od typu
                    switch ($import_type) {
                        case 'students':
                            $import_results = import_students($data, $conn);
                            break;
                        case 'teachers':
                            $import_results = import_teachers($data, $conn);
                            break;
                        case 'classes':
                            $import_results = import_classes($data, $conn);
                            break;
                        case 'rooms':
                            $import_results = import_rooms($data, $conn);
                            break;
                        default:
                            throw new Exception('Nieprawidłowy typ importu');
                    }
                    
                    $message = 'Import zakończony. Pomyślnie dodano: ' . $import_results['success'] . ' rekordów. Błędów: ' . $import_results['errors'];
                    $message_type = $import_results['errors'] > 0 ? 'warning' : 'success';
                    
                } catch (Exception $e) {
                    $message = 'Błąd podczas importu: ' . $e->getMessage();
                    $message_type = 'error';
                } finally {
                    // Usunięcie tymczasowego pliku
                    if (file_exists($temp_file)) {
                        unlink($temp_file);
                    }
                }
            } else {
                $message = 'Błąd podczas przesyłania pliku.';
                $message_type = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Danych</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .import-guide h4 {
            color: #495057;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            margin-top: 20px;
        }
        
        .import-guide h5 {
            color: #6c757d;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        
        .import-structure ul {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .import-structure li {
            margin-bottom: 5px;
        }
        
        .import-structure strong {
            color: #007bff;
        }
        
        pre {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-main">
            <header class="admin-header">
                <h1>Import Danych</h1>
                <div class="user-info">
                    <span>Witaj, <?php echo e($_SESSION['user_name']); ?>!</span>
                    <a href="../logout.php" class="btn-logout">Wyloguj</a>
                </div>
            </header>

            <div class="admin-content">
                <h2 class="page-title">Import Danych z Plików CSV/XLSX</h2>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>"><?php echo e($message); ?></div>
                <?php endif; ?>

                <!-- Formularz importu -->
                <div class="card">
                    <h3 class="card-title">Wybierz plik do importu</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        
                        <div class="form-group">
                            <label>Typ importu *</label>
                            <select name="import_type" required>
                                <option value="">Wybierz typ importu</option>
                                <option value="students">Uczniowie</option>
                                <option value="teachers">Nauczyciele</option>
                                <option value="classes">Klasy</option>
                                <option value="rooms">Sale</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Plik (CSV lub XLSX) *</label>
                            <input type="file" name="import_file" accept=".csv,.xlsx" required>
                            <small>Dozwolone formaty: CSV (rozdzielany średnikiem), XLSX. Maksymalny rozmiar: 5MB</small>
                            <div style="margin-top: 10px;">
                                <a href="../uploads/examples/students_example.csv" download class="btn btn-secondary" style="font-size: 12px; padding: 5px 10px;">Pobierz przykład uczniowie</a>
                                <a href="../uploads/examples/teachers_example.csv" download class="btn btn-secondary" style="font-size: 12px; padding: 5px 10px;">Pobierz przykład nauczyciele</a>
                                <a href="../uploads/examples/classes_example.csv" download class="btn btn-secondary" style="font-size: 12px; padding: 5px 10px;">Pobierz przykład klasy</a>
                                <a href="../uploads/examples/rooms_example.csv" download class="btn btn-secondary" style="font-size: 12px; padding: 5px 10px;">Pobierz przykład sale</a>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Importuj</button>
                    </form>
                </div>

                <!-- Wyniki importu -->
                <?php if ($import_results): ?>
                    <div class="card">
                        <h3 class="card-title">Wyniki importu</h3>
                        
                        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: bold; color: #28a745;">
                                    <?php echo $import_results['success']; ?>
                                </div>
                                <div>Pomyślnie dodano</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: bold; color: #dc3545;">
                                    <?php echo $import_results['errors']; ?>
                                </div>
                                <div>Błędów</div>
                            </div>
                        </div>

                        <?php if (!empty($import_results['details'])): ?>
                            <h4>Szczegóły:</h4>
                            <div style="max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                                <?php foreach ($import_results['details'] as $detail): ?>
                                    <div style="padding: 5px 0; border-bottom: 1px solid #dee2e6;">
                                        <?php echo e($detail); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Poradnik -->
                <div class="card">
                    <h3 class="card-title">Poradnik - Jak przygotować plik do importu</h3>
                    
                    <div class="import-guide">
                        <h4>1. Format pliku</h4>
                        <p>Możesz użyć plików CSV (rozdzielanych średnikiem) lub XLSX. Plik powinien zawierać nagłówki w pierwszym wierszu.</p>
                        
                        <h4>2. Struktura danych dla poszczególnych typów importu:</h4>
                        
                        <div class="import-structure">
                            <h5>Uczniowie:</h5>
                            <ul>
                                <li><strong>imie</strong> - Imię ucznia (wymagane)</li>
                                <li><strong>nazwisko</strong> - Nazwisko ucznia (wymagane)</li>
                                <li><strong>login</strong> - Login do systemu (wymagane, unikalny)</li>
                                <li><strong>haslo</strong> - Hasło (opcjonalne, domyślnie: haslo123)</li>
                                <li><strong>email</strong> - Adres e-mail (opcjonalny)</li>
                                <li><strong>klasa</strong> - Nazwa klasy (opcjonalne, musi istnieć w systemie)</li>
                            </ul>
                            
                            <h5>Nauczyciele:</h5>
                            <ul>
                                <li><strong>imie</strong> - Imię nauczyciela (wymagane)</li>
                                <li><strong>nazwisko</strong> - Nazwisko nauczyciela (wymagane)</li>
                                <li><strong>login</strong> - Login do systemu (wymagane, unikalny)</li>
                                <li><strong>haslo</strong> - Hasło (opcjonalne, domyślnie: haslo123)</li>
                                <li><strong>email</strong> - Adres e-mail (opcjonalny)</li>
                            </ul>
                            
                            <h5>Klasy:</h5>
                            <ul>
                                <li><strong>nazwa</strong> - Nazwa klasy (wymagana, unikalna)</li>
                                <li><strong>ilosc_godzin_dziennie</strong> - Liczba godzin dziennie (opcjonalne, domyślnie: 7)</li>
                                <li><strong>rozszerzenie_1</strong> - Pierwsze rozszerzenie (opcjonalne)</li>
                                <li><strong>rozszerzenie_2</strong> - Drugie rozszerzenie (opcjonalne)</li>
                            </ul>
                            
                            <h5>Sale:</h5>
                            <ul>
                                <li><strong>numer</strong> - Numer sali (wymagany, unikalny)</li>
                                <li><strong>nazwa</strong> - Pełna nazwa sali (opcjonalna)</li>
                                <li><strong>typ</strong> - Typ sali (opcjonalny: standardowa, pracownia, sportowa, specjalna)</li>
                                <li><strong>pojemnosc</strong> - Pojemność sali (opcjonalna, domyślnie: 30)</li>
                            </ul>
                        </div>
                        
                        <h4>3. Przykładowy plik CSV dla uczniów:</h4>
                        <pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;">
imie;nazwisko;login;haslo;email;klasa
Jan;Kowalski;jkowalski;haslo123;jan.kowalski@szkola.pl;1A
Anna;Nowak;anowak;haslo123;anna.nowak@szkola.pl;2B
Piotr;Wiśniewski;pwisniewski;haslo123;piotr.wisniewski@szkola.pl;1A</pre>
                        
                        <h4>4. Ważne uwagi:</h4>
                        <ul>
                            <li>Plik CSV powinien być kodowany w UTF-8</li>
                            <li>Separator w pliku CSV to średnik (;)</li>
                            <li>Pierwszy wiersz powinien zawierać nazwy kolumn</li>
                            <li>Pola oznaczone jako "wymagane" muszą być wypełnione</li>
                            <li>Loginy muszą być unikalne w całym systemie</li>
                            <li>Nazwy klas i numerów sal muszą być unikalne</li>
                            <li>Przy importowaniu uczniów, podane klasy muszą już istnieć w systemie</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>