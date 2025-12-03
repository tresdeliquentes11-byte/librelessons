<?php
require_once '../includes/config.php';
sprawdz_uprawnienia('dyrektor');

$message = '';
$message_type = '';

// Sprawdź czy tabela istnieje, jeśli nie - utwórz ją
$check_table = $conn->query("SHOW TABLES LIKE 'nauczyciel_dostepnosc'");
if ($check_table->num_rows == 0) {
    $conn->query("
        CREATE TABLE nauczyciel_dostepnosc (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nauczyciel_id INT NOT NULL,
            typ ENUM('stala', 'jednorazowa') NOT NULL,
            dzien_tygodnia INT NULL,
            data_konkretna DATE NULL,
            godzina_od TIME NOT NULL,
            godzina_do TIME NOT NULL,
            opis TEXT NULL,
            utworzono TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (nauczyciel_id) REFERENCES nauczyciele(id) ON DELETE CASCADE,
            INDEX idx_nauczyciel (nauczyciel_id),
            INDEX idx_dzien (dzien_tygodnia),
            INDEX idx_data (data_konkretna)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

// Dodawanie dostępności
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dodaj'])) {
    $nauczyciel_id = intval($_POST['nauczyciel_id']);
    $typ = $_POST['typ'];
    $godzina_od = $_POST['godzina_od'];
    $godzina_do = $_POST['godzina_do'];
    $opis = $_POST['opis'] ?? '';

    if ($typ === 'stala') {
        $dzien_tygodnia = intval($_POST['dzien_tygodnia']);
        $stmt = $conn->prepare("INSERT INTO nauczyciel_dostepnosc (nauczyciel_id, typ, dzien_tygodnia, godzina_od, godzina_do, opis) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isisss", $nauczyciel_id, $typ, $dzien_tygodnia, $godzina_od, $godzina_do, $opis);
    } else {
        $data_konkretna = $_POST['data_konkretna'];
        $stmt = $conn->prepare("INSERT INTO nauczyciel_dostepnosc (nauczyciel_id, typ, data_konkretna, godzina_od, godzina_do, opis) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $nauczyciel_id, $typ, $data_konkretna, $godzina_od, $godzina_do, $opis);
    }

    if ($stmt->execute()) {
        $message = 'Dostępność została dodana pomyślnie';
        $message_type = 'success';
        loguj_aktywnosc($conn, $_SESSION['user_id'], 'dodanie', 'dostepnosc', "Dodano dostępność dla nauczyciela ID: $nauczyciel_id");
    } else {
        $message = 'Błąd podczas dodawania dostępności';
        $message_type = 'error';
    }
}

// Usuwanie dostępności
if (isset($_GET['usun'])) {
    $id = intval($_GET['usun']);
    $stmt = $conn->prepare("DELETE FROM nauczyciel_dostepnosc WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = 'Dostępność została usunięta';
        $message_type = 'success';
        loguj_aktywnosc($conn, $_SESSION['user_id'], 'usuniecie', 'dostepnosc', "Usunięto dostępność ID: $id");
    }
}

// Pobierz wybranego nauczyciela
$wybrany_nauczyciel = isset($_GET['nauczyciel']) ? intval($_GET['nauczyciel']) : null;

// Pobierz listę nauczycieli
$nauczyciele = $conn->query("
    SELECT n.id, u.imie, u.nazwisko
    FROM nauczyciele n
    JOIN uzytkownicy u ON n.uzytkownik_id = u.id
    ORDER BY u.nazwisko, u.imie
");

// Pobierz dostępności dla wybranego nauczyciela
$dostepnosci = null;
if ($wybrany_nauczyciel) {
    $dostepnosci = $conn->query("
        SELECT nd.*
        FROM nauczyciel_dostepnosc nd
        WHERE nd.nauczyciel_id = $wybrany_nauczyciel
        ORDER BY
            CASE WHEN nd.typ = 'stala' THEN 0 ELSE 1 END,
            nd.dzien_tygodnia,
            nd.data_konkretna,
            nd.godzina_od
    ");
}

$dni_tygodnia = [
    1 => 'Poniedziałek',
    2 => 'Wtorek',
    3 => 'Środa',
    4 => 'Czwartek',
    5 => 'Piątek'
];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dostępność Nauczycieli - Panel Dyrektora</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .availability-table {
            margin-top: 20px;
        }
        .availability-type {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .type-stala {
            background-color: #d4edda;
            color: #155724;
        }
        .type-jednorazowa {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
    <script>
        function zmienTyp() {
            const typ = document.getElementById('typ').value;
            const stalaDiv = document.getElementById('stala-options');
            const jednorazowaDiv = document.getElementById('jednorazowa-options');

            if (typ === 'stala') {
                stalaDiv.style.display = 'block';
                jednorazowaDiv.style.display = 'none';
                document.getElementById('dzien_tygodnia').required = true;
                document.getElementById('data_konkretna').required = false;
            } else {
                stalaDiv.style.display = 'none';
                jednorazowaDiv.style.display = 'block';
                document.getElementById('dzien_tygodnia').required = false;
                document.getElementById('data_konkretna').required = true;
            }
        }
    </script>
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
                <li><a href="dostepnosc.php" class="active">Dostępność</a></li>
                <li><a href="ustawienia.php">Ustawienia</a></li>
            </ul>
        </nav>

        <div class="content">
            <h2 class="page-title">Zarządzanie Dostępnością Nauczycieli</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>"><?php echo e($message); ?></div>
            <?php endif; ?>

            <div class="card">
                <h3 class="card-title">Wybierz Nauczyciela</h3>
                <form method="GET">
                    <div class="form-group">
                        <label for="nauczyciel">Nauczyciel</label>
                        <select id="nauczyciel" name="nauczyciel" onchange="this.form.submit()">
                            <option value="">-- Wybierz nauczyciela --</option>
                            <?php while ($n = $nauczyciele->fetch_assoc()): ?>
                                <option value="<?php echo $n['id']; ?>" <?php echo ($wybrany_nauczyciel == $n['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($n['imie'] . ' ' . $n['nazwisko']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($wybrany_nauczyciel): ?>
                <div class="card">
                    <h3 class="card-title">Dodaj Dostępność / Niedostępność</h3>

                    <div class="alert alert-info">
                        <strong>Informacja:</strong><br>
                        • <strong>Stała dostępność</strong> - regularne godziny pracy w danym dniu tygodnia (np. każdy poniedziałek 8:00-16:00)<br>
                        • <strong>Jednorazowa niedostępność</strong> - wyjątek w konkretnym dniu (np. wizyta lekarska, szkolenie)
                    </div>

                    <form method="POST">
                        <input type="hidden" name="nauczyciel_id" value="<?php echo $wybrany_nauczyciel; ?>">

                        <div class="form-grid">
                            <div class="full-width">
                                <div class="form-group">
                                    <label for="typ">Typ</label>
                                    <select id="typ" name="typ" required onchange="zmienTyp()">
                                        <option value="stala">Stała dostępność</option>
                                        <option value="jednorazowa">Jednorazowa niedostępność</option>
                                    </select>
                                </div>
                            </div>

                            <div id="stala-options" style="display: block; grid-column: 1 / -1;">
                                <div class="form-group">
                                    <label for="dzien_tygodnia">Dzień tygodnia</label>
                                    <select id="dzien_tygodnia" name="dzien_tygodnia" required>
                                        <?php foreach ($dni_tygodnia as $nr => $nazwa): ?>
                                            <option value="<?php echo $nr; ?>"><?php echo $nazwa; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div id="jednorazowa-options" style="display: none; grid-column: 1 / -1;">
                                <div class="form-group">
                                    <label for="data_konkretna">Data</label>
                                    <input type="date" id="data_konkretna" name="data_konkretna">
                                </div>
                            </div>

                            <div>
                                <div class="form-group">
                                    <label for="godzina_od">Godzina od</label>
                                    <input type="time" id="godzina_od" name="godzina_od" value="08:00" required>
                                </div>
                            </div>

                            <div>
                                <div class="form-group">
                                    <label for="godzina_do">Godzina do</label>
                                    <input type="time" id="godzina_do" name="godzina_do" value="16:00" required>
                                </div>
                            </div>

                            <div class="full-width">
                                <div class="form-group">
                                    <label for="opis">Opis (opcjonalnie)</label>
                                    <input type="text" id="opis" name="opis" placeholder="np. wizyta lekarska, szkolenie">
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="dodaj" class="btn btn-primary">Dodaj</button>
                    </form>
                </div>

                <div class="card">
                    <h3 class="card-title">Aktualna Dostępność</h3>

                    <?php if ($dostepnosci && $dostepnosci->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Typ</th>
                                    <th>Dzień / Data</th>
                                    <th>Godzina od</th>
                                    <th>Godzina do</th>
                                    <th>Opis</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($d = $dostepnosci->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="availability-type type-<?php echo $d['typ']; ?>">
                                                <?php echo $d['typ'] === 'stala' ? 'Stała' : 'Jednorazowa'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            if ($d['typ'] === 'stala') {
                                                echo $dni_tygodnia[$d['dzien_tygodnia']];
                                            } else {
                                                echo formatuj_date($d['data_konkretna']);
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo substr($d['godzina_od'], 0, 5); ?></td>
                                        <td><?php echo substr($d['godzina_do'], 0, 5); ?></td>
                                        <td><?php echo e($d['opis'] ?: '-'); ?></td>
                                        <td>
                                            <a href="?nauczyciel=<?php echo $wybrany_nauczyciel; ?>&usun=<?php echo $d['id']; ?>"
                                               class="btn btn-danger"
                                               style="padding: 5px 10px; font-size: 12px;"
                                               onclick="return confirm('Czy na pewno chcesz usunąć ten wpis?')">
                                                Usuń
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">Brak ustawionych godzin dostępności dla tego nauczyciela</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Wybierz nauczyciela z listy powyżej</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
