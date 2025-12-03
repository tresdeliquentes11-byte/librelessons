<?php
require_once '../includes/config.php';
sprawdz_uprawnienia('dyrektor');

$message = '';
$message_type = '';

// Funkcja do pobierania ustawienia
function pobierz_ustawienie($nazwa, $domyslna = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT wartosc FROM ustawienia_planu WHERE nazwa = ?");
    $stmt->bind_param("s", $nazwa);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['wartosc'];
    }
    return $domyslna;
}

// Funkcja do zapisywania ustawienia
function zapisz_ustawienie($nazwa, $wartosc, $opis = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO ustawienia_planu (nazwa, wartosc, opis) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE wartosc = ?, data_modyfikacji = CURRENT_TIMESTAMP");
    $stmt->bind_param("ssss", $nazwa, $wartosc, $opis, $wartosc);
    return $stmt->execute();
}

// Zapisywanie ustawień
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zapisz'])) {
    try {
        zapisz_ustawienie('dlugosc_lekcji', $_POST['dlugosc_lekcji'], 'Długość jednej lekcji w minutach');
        zapisz_ustawienie('godzina_rozpoczecia', $_POST['godzina_rozpoczecia'], 'Godzina rozpoczęcia pierwszej lekcji');
        zapisz_ustawienie('przerwa_krotka', $_POST['przerwa_krotka'], 'Długość krótkiej przerwy w minutach');
        zapisz_ustawienie('przerwa_dluga', $_POST['przerwa_dluga'], 'Długość długiej przerwy w minutach');
        zapisz_ustawienie('przerwa_dluga_po_lekcji', $_POST['przerwa_dluga_po_lekcji'], 'Po której lekcji jest dłuższa przerwa');

        $message = 'Ustawienia zostały zapisane pomyślnie';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Błąd podczas zapisywania ustawień: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Pobierz aktualne ustawienia
$dlugosc_lekcji = pobierz_ustawienie('dlugosc_lekcji', '45');
$godzina_rozpoczecia = pobierz_ustawienie('godzina_rozpoczecia', '08:00');
$przerwa_krotka = pobierz_ustawienie('przerwa_krotka', '10');
$przerwa_dluga = pobierz_ustawienie('przerwa_dluga', '15');
$przerwa_dluga_po_lekcji = pobierz_ustawienie('przerwa_dluga_po_lekcji', '3');

// Oblicz przykładowe godziny lekcji
function oblicz_godziny_lekcji($start, $dlugosc_lekcji, $przerwa_krotka, $przerwa_dluga, $przerwa_dluga_po) {
    $godziny = [];
    $czas = strtotime($start);

    for ($i = 1; $i <= 8; $i++) {
        $start_lekcji = date('H:i', $czas);
        $czas += $dlugosc_lekcji * 60;
        $koniec_lekcji = date('H:i', $czas);

        $godziny[$i] = "$start_lekcji - $koniec_lekcji";

        // Dodaj przerwę
        if ($i < 8) {
            if ($i == $przerwa_dluga_po) {
                $czas += $przerwa_dluga * 60;
            } else {
                $czas += $przerwa_krotka * 60;
            }
        }
    }

    return $godziny;
}

$przykladowe_godziny = oblicz_godziny_lekcji($godzina_rozpoczecia, $dlugosc_lekcji, $przerwa_krotka, $przerwa_dluga, $przerwa_dluga_po_lekcji);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia Planu Lekcji</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .settings-preview {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .preview-table {
            width: 100%;
            max-width: 400px;
            margin-top: 15px;
        }

        .preview-table td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .preview-table td:first-child {
            font-weight: 600;
            width: 100px;
        }

        .form-group-inline {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group-inline input,
        .form-group-inline select {
            flex: 0 0 150px;
        }

        .help-text {
            color: #6c757d;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>System Planu Lekcji - Panel Dyrektora</h1>
            <div class="user-info">
                <span>Witaj, <?php echo e($_SESSION['user_name']); ?>!</span>
                <a href="../logout.php" class="btn-logout">Wyloguj</a>
            </div>
        </header>

        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="plan_generuj.php">Generuj Plan</a></li>
                <li><a href="zastepstwa.php">Zastępstwa</a></li>
                <li><a href="nauczyciele.php">Nauczyciele</a></li>
                <li><a href="uczniowie.php">Uczniowie</a></li>
                <li><a href="klasy.php">Klasy</a></li>
                <li><a href="przedmioty.php">Przedmioty</a></li>
                <li><a href="sale.php">Sale</a></li>
                <li><a href="kalendarz.php">Kalendarz</a></li>
                <li><a href="plan_podglad.php">Podgląd Planu</a></li>
                <li><a href="ustawienia.php" class="active">Ustawienia</a></li>
            </ul>
        </nav>

        <div class="content">
            <h2 class="page-title">Ustawienia Planu Lekcji</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo e($message); ?></div>
            <?php endif; ?>

            <div class="card">
                <h3 class="card-title">Konfiguracja godzin lekcyjnych i przerw</h3>

                <form method="POST" id="settingsForm">
                    <div class="form-group">
                        <label>Godzina rozpoczęcia zajęć *</label>
                        <div class="form-group-inline">
                            <input type="time" name="godzina_rozpoczecia" value="<?php echo e($godzina_rozpoczecia); ?>" required>
                            <span class="help-text">O której godzinie rozpoczyna się pierwsza lekcja</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Długość lekcji (w minutach) *</label>
                        <div class="form-group-inline">
                            <input type="number" name="dlugosc_lekcji" value="<?php echo e($dlugosc_lekcji); ?>" min="30" max="60" required>
                            <span class="help-text">Standardowo 45 minut</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Długość krótkiej przerwy (w minutach) *</label>
                        <div class="form-group-inline">
                            <input type="number" name="przerwa_krotka" value="<?php echo e($przerwa_krotka); ?>" min="5" max="20" required>
                            <span class="help-text">Standardowo 10 minut</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Długość długiej przerwy (w minutach) *</label>
                        <div class="form-group-inline">
                            <input type="number" name="przerwa_dluga" value="<?php echo e($przerwa_dluga); ?>" min="10" max="30" required>
                            <span class="help-text">Standardowo 15-20 minut</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Dłuższa przerwa po lekcji nr *</label>
                        <div class="form-group-inline">
                            <select name="przerwa_dluga_po_lekcji" required>
                                <?php for ($i = 2; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($przerwa_dluga_po_lekcji == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> lekcji
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <span class="help-text">Po której lekcji jest dłuższa przerwa (zwykle 3 lub 4)</span>
                        </div>
                    </div>

                    <button type="submit" name="zapisz" class="btn btn-primary" style="margin-top: 20px;">
                        Zapisz ustawienia
                    </button>
                </form>
            </div>

            <div class="card">
                <h3 class="card-title">Podgląd rozkładu godzin</h3>
                <div class="settings-preview">
                    <p><strong>Na podstawie aktualnych ustawień, godziny lekcji będą wyglądać następująco:</strong></p>
                    <table class="preview-table">
                        <?php foreach ($przykladowe_godziny as $numer => $godzina): ?>
                            <tr>
                                <td><?php echo $numer; ?> lekcja:</td>
                                <td><?php echo $godzina; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <p style="margin-top: 15px; color: #6c757d; font-size: 14px;">
                        <strong>Uwaga:</strong> Zmiany w ustawieniach będą widoczne w wyświetlanych planach lekcji.
                        Nie wpływają one na już wygenerowane dane w bazie, a jedynie na sposób ich prezentacji.
                    </p>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title">Informacje dodatkowe</h3>
                <ul style="line-height: 2; color: #495057;">
                    <li><strong>Długość lekcji:</strong> Określa ile minut trwa jedna lekcja. W Polsce standardowo jest to 45 minut.</li>
                    <li><strong>Godzina rozpoczęcia:</strong> Pierwsza lekcja rozpoczyna się o wskazanej godzinie. Kolejne godziny są automatycznie obliczane.</li>
                    <li><strong>Krótka przerwa:</strong> Standardowa przerwa między lekcjami (zwykle 5-10 minut).</li>
                    <li><strong>Długa przerwa:</strong> Przerwa obiadowa, dłuższa od standardowej (zwykle 15-20 minut).</li>
                    <li><strong>Położenie długiej przerwy:</strong> Zazwyczaj po 3 lub 4 lekcji, dając uczniom czas na posiłek.</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Aktualizuj podgląd przy zmianie wartości
        document.getElementById('settingsForm').addEventListener('input', function() {
            // Można dodać live preview, ale wymagałoby to AJAX lub JS do obliczeń
        });
    </script>
</body>
</html>
