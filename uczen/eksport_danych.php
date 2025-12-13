<?php
/**
 * © 2025 TresDeliquentes. Wszystkie prawa zastrzeżone.
 * LibreLessons - Eksport Danych RODO dla Ucznia
 */
require_once '../includes/config.php';
require_once '../includes/oceny_functions.php';
sprawdz_uprawnienia('uczen');

// Pobierz dane ucznia
$uczen_dane = pobierz_id_ucznia($_SESSION['user_id']);
if (!$uczen_dane) {
    die("Błąd: Nie znaleziono danych ucznia");
}

$uczen_id = $uczen_dane['id'];

// Obsługa eksportu
if (isset($_POST['eksportuj']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $format = $_POST['format'] ?? 'json';

    // Eksportuj dane
    $dane = eksportuj_dane_ucznia_rodo($uczen_id);

    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="moje_dane_' . date('Y-m-d') . '.json"');
        echo json_encode($dane, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    } elseif ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="moje_oceny_' . date('Y-m-d') . '.csv"');

        // BOM dla UTF-8
        echo "\xEF\xBB\xBF";

        // Nagłówki CSV
        echo "Data;Przedmiot;Ocena;Kategoria;Komentarz;Wystawił\n";

        foreach ($dane['oceny'] as $ocena) {
            echo implode(';', [
                $ocena['data_wystawienia'],
                '"' . str_replace('"', '""', $ocena['przedmiot']) . '"',
                $ocena['ocena'],
                '"' . str_replace('"', '""', $ocena['kategoria']) . '"',
                '"' . str_replace('"', '""', $ocena['komentarz'] ?? '') . '"',
                '"' . str_replace('"', '""', $ocena['wystawil']) . '"'
            ]) . "\n";
        }
        exit();
    }
}

// Pobierz nazwę klasy
$stmt = $conn->prepare("
    SELECT k.nazwa as klasa, u.imie, u.nazwisko, u.email, u.data_utworzenia
    FROM uczniowie uc
    JOIN klasy k ON uc.klasa_id = k.id
    JOIN uzytkownicy u ON uc.uzytkownik_id = u.id
    WHERE uc.id = ?
");
$stmt->bind_param("i", $uczen_id);
$stmt->execute();
$info_ucznia = $stmt->get_result()->fetch_assoc();

// Pobierz historię dostępu do danych
$stmt = $conn->prepare("
    SELECT ld.typ_dostepu, ld.zakres_danych, ld.data_dostepu, ld.ip_address,
           CONCAT(u.imie, ' ', u.nazwisko) as uzytkownik, u.typ
    FROM logi_dostepu_ocen ld
    JOIN uzytkownicy u ON ld.uzytkownik_id = u.id
    WHERE ld.uczen_id = ?
    ORDER BY ld.data_dostepu DESC
    LIMIT 50
");
$stmt->bind_param("i", $uczen_id);
$stmt->execute();
$logi_dostepu = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eksport Danych RODO - LibreLessons</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/oceny.css">
</head>
<body>
    <div class="teacher-layout student-layout">
        <div class="teacher-container">
            <header class="teacher-header">
                <h1>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    Moje Dane - RODO
                </h1>
                <div class="user-info">
                    <span><?php echo e($_SESSION['user_name']); ?></span>
                    <a href="oceny.php" class="btn-settings">Moje oceny</a>
                    <a href="../logout.php" class="btn-logout">Wyloguj</a>
                </div>
            </header>

            <!-- Informacje o RODO -->
            <div class="grade-card rodo-info-card">
                <h2>Twoje prawa zgodnie z RODO</h2>
                <div class="rodo-rights">
                    <div class="right-item">
                        <div class="right-icon">📋</div>
                        <div class="right-content">
                            <h4>Prawo dostępu do danych</h4>
                            <p>Masz prawo wiedzieć, jakie dane o Tobie gromadzimy i jak je przetwarzamy.</p>
                        </div>
                    </div>
                    <div class="right-item">
                        <div class="right-icon">📦</div>
                        <div class="right-content">
                            <h4>Prawo do przenoszenia danych</h4>
                            <p>Możesz pobrać swoje dane w formacie umożliwiającym ich przeniesienie.</p>
                        </div>
                    </div>
                    <div class="right-item">
                        <div class="right-icon">✏️</div>
                        <div class="right-content">
                            <h4>Prawo do sprostowania</h4>
                            <p>Jeśli Twoje dane są nieprawidłowe, masz prawo żądać ich poprawienia.</p>
                        </div>
                    </div>
                    <div class="right-item">
                        <div class="right-icon">🔒</div>
                        <div class="right-content">
                            <h4>Prawo do bycia zapomnianym</h4>
                            <p>W określonych przypadkach możesz żądać usunięcia swoich danych.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Podsumowanie danych -->
            <div class="grade-card">
                <h3>Twoje dane osobowe</h3>
                <table class="data-table">
                    <tr>
                        <td><strong>Imię i nazwisko:</strong></td>
                        <td><?php echo e($info_ucznia['imie'] . ' ' . $info_ucznia['nazwisko']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Klasa:</strong></td>
                        <td><?php echo e($info_ucznia['klasa']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo e($info_ucznia['email'] ?? 'Nie podano'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Data utworzenia konta:</strong></td>
                        <td><?php echo formatuj_date($info_ucznia['data_utworzenia']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Eksport danych -->
            <div class="grade-card export-card">
                <h3>Eksportuj swoje dane</h3>
                <p>Pobierz kopię wszystkich swoich danych przechowywanych w systemie.</p>

                <form method="POST" class="export-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="eksportuj" value="1">

                    <div class="export-options">
                        <label class="export-option">
                            <input type="radio" name="format" value="json" checked>
                            <div class="option-content">
                                <strong>Format JSON</strong>
                                <span>Pełne dane strukturalne - wszystkie informacje</span>
                            </div>
                        </label>

                        <label class="export-option">
                            <input type="radio" name="format" value="csv">
                            <div class="option-content">
                                <strong>Format CSV</strong>
                                <span>Tabela ocen - do otwarcia w Excel</span>
                            </div>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large">
                        Pobierz moje dane
                    </button>
                </form>

                <div class="export-info">
                    <p><strong>Zawartość eksportu:</strong></p>
                    <ul>
                        <li>Dane osobowe (imię, nazwisko, klasa)</li>
                        <li>Wszystkie oceny cząstkowe z komentarzami</li>
                        <li>Oceny końcowe (śródroczne i roczne)</li>
                        <li>Historia dostępu do Twoich danych</li>
                    </ul>
                </div>
            </div>

            <!-- Historia dostępu -->
            <div class="grade-card">
                <h3>Kto przeglądał Twoje dane</h3>
                <p class="info-text">
                    Zgodnie z RODO masz prawo wiedzieć, kto i kiedy uzyskiwał dostęp do Twoich danych.
                </p>

                <?php if (!empty($logi_dostepu)): ?>
                    <table class="grade-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Użytkownik</th>
                                <th>Rola</th>
                                <th>Typ dostępu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logi_dostepu as $log): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($log['data_dostepu'])); ?></td>
                                    <td><?php echo e($log['uzytkownik']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $log['typ']; ?>">
                                            <?php echo ucfirst($log['typ']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($log['typ_dostepu']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Brak zapisanych logów dostępu.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Kontakt -->
            <div class="grade-card contact-card">
                <h3>Masz pytania?</h3>
                <p>
                    Jeśli masz pytania dotyczące przetwarzania Twoich danych lub chcesz skorzystać z innych
                    praw wynikających z RODO, skontaktuj się z:
                </p>
                <div class="contact-info">
                    <div class="contact-item">
                        <strong>Inspektor Ochrony Danych:</strong>
                        <span>Skontaktuj się przez sekretariat szkoły</span>
                    </div>
                    <div class="contact-item">
                        <strong>Administrator systemu:</strong>
                        <span>Zgłoś problem przez formularz kontaktowy</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .student-layout {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .rodo-info-card {
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            border: 2px solid #667eea40;
        }

        .rodo-rights {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .right-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: white;
            border-radius: 10px;
        }

        .right-icon {
            font-size: 24px;
        }

        .right-content h4 {
            margin: 0 0 5px 0;
            color: #667eea;
            font-size: 14px;
        }

        .right-content p {
            margin: 0;
            font-size: 13px;
            color: #6c757d;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .data-table td:first-child {
            width: 200px;
            color: #6c757d;
        }

        .export-card {
            border-left: 4px solid #667eea;
        }

        .export-options {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .export-option {
            flex: 1;
            min-width: 200px;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .export-option:hover {
            border-color: #667eea;
        }

        .export-option input {
            display: none;
        }

        .export-option input:checked + .option-content {
            color: #667eea;
        }

        .export-option:has(input:checked) {
            border-color: #667eea;
            background: #667eea10;
        }

        .option-content strong {
            display: block;
            margin-bottom: 5px;
        }

        .option-content span {
            font-size: 13px;
            color: #6c757d;
        }

        .btn-large {
            padding: 15px 30px;
            font-size: 16px;
        }

        .export-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .export-info ul {
            margin: 10px 0 0 20px;
            color: #6c757d;
        }

        .export-info li {
            margin: 5px 0;
        }

        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }

        .role-dyrektor { background: #e74c3c; color: white; }
        .role-nauczyciel { background: #3498db; color: white; }
        .role-administrator { background: #9b59b6; color: white; }
        .role-uczen { background: #27ae60; color: white; }

        .contact-card {
            background: #f8f9fa;
        }

        .contact-info {
            display: flex;
            gap: 30px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .contact-item {
            flex: 1;
            min-width: 200px;
        }

        .contact-item strong {
            display: block;
            color: #667eea;
            margin-bottom: 5px;
        }

        .contact-item span {
            color: #6c757d;
            font-size: 14px;
        }

        .info-text {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
        }
    </style>
</body>
</html>
