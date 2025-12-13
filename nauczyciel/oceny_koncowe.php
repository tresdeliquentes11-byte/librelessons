<?php
/**
 * © 2025 TresDeliquentes. Wszystkie prawa zastrzeżone.
 * LibreLessons - Panel Nauczyciela - Oceny Końcowe
 */
require_once '../includes/config.php';
require_once '../includes/oceny_functions.php';
sprawdz_uprawnienia('nauczyciel');

$nauczyciel_id = pobierz_id_nauczyciela($_SESSION['user_id']);
if (!$nauczyciel_id) {
    die("Błąd: Nie znaleziono danych nauczyciela");
}

$klasa_id = isset($_GET['klasa']) ? intval($_GET['klasa']) : null;
$przedmiot_id = isset($_GET['przedmiot']) ? intval($_GET['przedmiot']) : null;

if (!$klasa_id || !$przedmiot_id) {
    header('Location: oceny.php');
    exit();
}

if (!nauczyciel_uczy_przedmiot($nauczyciel_id, $klasa_id, $przedmiot_id)) {
    header('Location: oceny.php?error=brak_uprawnien');
    exit();
}

$semestr = pobierz_aktualny_semestr();
$rok_szkolny = pobierz_aktualny_rok_szkolny();
$typ_oceny = isset($_GET['typ']) ? $_GET['typ'] : 'srodroczna';

$komunikat = '';
$blad = '';

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $akcja = $_POST['akcja'] ?? '';

    if ($akcja === 'wystaw_koncowe') {
        $sukces = 0;
        $bledy = 0;

        foreach ($_POST['oceny'] as $uczen_id => $ocena_data) {
            if (!empty($ocena_data['ocena'])) {
                $wynik = wystaw_ocene_koncowa([
                    'uczen_id' => intval($uczen_id),
                    'przedmiot_id' => $przedmiot_id,
                    'nauczyciel_id' => $nauczyciel_id,
                    'klasa_id' => $klasa_id,
                    'typ' => $typ_oceny,
                    'ocena' => intval($ocena_data['ocena']),
                    'ocena_proponowana' => !empty($ocena_data['proponowana']) ? intval($ocena_data['proponowana']) : null,
                    'komentarz' => trim($ocena_data['komentarz'] ?? '')
                ]);

                if ($wynik['success']) {
                    $sukces++;
                } else {
                    $bledy++;
                }
            }
        }

        $komunikat = "Zapisano $sukces ocen końcowych" . ($bledy > 0 ? ", błędy: $bledy" : "");
    }
}

// Pobierz uczniów klasy z ich średnimi
$uczniowie = pobierz_uczniow_klasy($klasa_id);
$dane_uczniow = [];

foreach ($uczniowie as $uczen) {
    $srednia = oblicz_srednia_wazona($uczen['uczen_id'], $przedmiot_id, $semestr, $rok_szkolny);
    $przewidywana = przewiduj_ocene_koncowa($srednia);

    // Pobierz istniejącą ocenę końcową
    $stmt = $conn->prepare("
        SELECT * FROM oceny_koncowe
        WHERE uczen_id = ? AND przedmiot_id = ? AND typ = ? AND rok_szkolny = ?
    ");
    $stmt->bind_param("iiss", $uczen['uczen_id'], $przedmiot_id, $typ_oceny, $rok_szkolny);
    $stmt->execute();
    $ocena_koncowa = $stmt->get_result()->fetch_assoc();

    $dane_uczniow[] = [
        'uczen_id' => $uczen['uczen_id'],
        'nazwa' => $uczen['pelne_imie'],
        'srednia' => $srednia,
        'przewidywana' => $przewidywana,
        'ocena_koncowa' => $ocena_koncowa
    ];
}

// Pobierz nazwy
$stmt = $conn->prepare("SELECT nazwa FROM klasy WHERE id = ?");
$stmt->bind_param("i", $klasa_id);
$stmt->execute();
$klasa_nazwa = $stmt->get_result()->fetch_assoc()['nazwa'];

$stmt = $conn->prepare("SELECT nazwa FROM przedmioty WHERE id = ?");
$stmt->bind_param("i", $przedmiot_id);
$stmt->execute();
$przedmiot_nazwa = $stmt->get_result()->fetch_assoc()['nazwa'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oceny Końcowe - LibreLessons</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/oceny.css">
</head>
<body>
    <div class="teacher-layout">
        <div class="teacher-container">
            <header class="teacher-header">
                <h1>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    Oceny Końcowe
                    <span class="teacher-badge">
                        <?php echo $typ_oceny === 'srodroczna' ? 'Śródroczne' : 'Roczne'; ?>
                    </span>
                </h1>
                <div class="user-info">
                    <span><?php echo e($_SESSION['user_name']); ?></span>
                    <a href="oceny.php?klasa=<?php echo $klasa_id; ?>&przedmiot=<?php echo $przedmiot_id; ?>"
                       class="btn-settings">Powrót</a>
                    <a href="../logout.php" class="btn-logout">Wyloguj</a>
                </div>
            </header>

            <?php if ($komunikat): ?>
                <div class="alert alert-success"><?php echo e($komunikat); ?></div>
            <?php endif; ?>

            <?php if ($blad): ?>
                <div class="alert alert-error"><?php echo e($blad); ?></div>
            <?php endif; ?>

            <nav class="breadcrumb">
                <a href="oceny.php">Oceny</a> &raquo;
                <a href="oceny.php?klasa=<?php echo $klasa_id; ?>&przedmiot=<?php echo $przedmiot_id; ?>">
                    <?php echo e($klasa_nazwa); ?> - <?php echo e($przedmiot_nazwa); ?>
                </a> &raquo;
                <span>Oceny końcowe</span>
            </nav>

            <!-- Wybór typu oceny -->
            <div class="grade-card type-selector">
                <div class="type-buttons">
                    <a href="?klasa=<?php echo $klasa_id; ?>&przedmiot=<?php echo $przedmiot_id; ?>&typ=srodroczna"
                       class="type-btn <?php echo $typ_oceny === 'srodroczna' ? 'active' : ''; ?>">
                        Oceny śródroczne
                    </a>
                    <a href="?klasa=<?php echo $klasa_id; ?>&przedmiot=<?php echo $przedmiot_id; ?>&typ=roczna"
                       class="type-btn <?php echo $typ_oceny === 'roczna' ? 'active' : ''; ?>">
                        Oceny roczne
                    </a>
                </div>
                <div class="semester-info">
                    <strong>Rok szkolny:</strong> <?php echo e($rok_szkolny); ?> |
                    <strong>Semestr:</strong> <?php echo $semestr; ?>
                </div>
            </div>

            <!-- Formularz ocen końcowych -->
            <div class="grade-card">
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="akcja" value="wystaw_koncowe">

                    <table class="grade-table final-grade-table">
                        <thead>
                            <tr>
                                <th>Uczeń</th>
                                <th>Średnia ważona</th>
                                <th>Sugerowana</th>
                                <th>Ocena proponowana</th>
                                <th>Ocena końcowa</th>
                                <th>Komentarz</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dane_uczniow as $uczen): ?>
                                <tr>
                                    <td><strong><?php echo e($uczen['nazwa']); ?></strong></td>
                                    <td>
                                        <?php if ($uczen['srednia'] !== null): ?>
                                            <span class="average-badge" style="background-color: <?php echo kolor_oceny($uczen['srednia']); ?>">
                                                <?php echo number_format($uczen['srednia'], 2); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="no-grades">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="suggested-grade">
                                            <?php echo $uczen['przewidywana'] ?? '-'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select name="oceny[<?php echo $uczen['uczen_id']; ?>][proponowana]" class="grade-select small">
                                            <option value="">-</option>
                                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                                <option value="<?php echo $i; ?>"
                                                    <?php echo ($uczen['ocena_koncowa']['ocena_proponowana'] ?? '') == $i ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="oceny[<?php echo $uczen['uczen_id']; ?>][ocena]" class="grade-select"
                                                <?php echo $uczen['ocena_koncowa']['zatwierdzona'] ?? false ? 'disabled' : ''; ?>>
                                            <option value="">-- wybierz --</option>
                                            <option value="0"
                                                <?php echo ($uczen['ocena_koncowa']['ocena'] ?? '') === '0' ? 'selected' : ''; ?>>
                                                nkl
                                            </option>
                                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                                <option value="<?php echo $i; ?>"
                                                    <?php echo ($uczen['ocena_koncowa']['ocena'] ?? '') == $i ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <?php if ($uczen['ocena_koncowa']['zatwierdzona'] ?? false): ?>
                                            <span class="approved-badge">Zatwierdzona</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="text" name="oceny[<?php echo $uczen['uczen_id']; ?>][komentarz]"
                                               value="<?php echo e($uczen['ocena_koncowa']['komentarz'] ?? ''); ?>"
                                               placeholder="Opcjonalny komentarz"
                                               class="comment-input">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            Zapisz oceny końcowe
                        </button>
                    </div>
                </form>
            </div>

            <!-- Legenda -->
            <div class="grade-card legend-card">
                <h4>Legenda</h4>
                <ul class="legend-list">
                    <li><strong>Średnia ważona</strong> - obliczona na podstawie ocen cząstkowych i ich wag</li>
                    <li><strong>Sugerowana</strong> - propozycja systemu na podstawie średniej</li>
                    <li><strong>Proponowana</strong> - ocena proponowana uczniowi przed wystawieniem końcowej</li>
                    <li><strong>nkl</strong> - nieklasyfikowany (brak podstaw do wystawienia oceny)</li>
                </ul>
            </div>
        </div>
    </div>

    <style>
        .type-selector {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .type-buttons {
            display: flex;
            gap: 10px;
        }

        .type-btn {
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            color: #6c757d;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .type-btn:hover {
            background: #e9ecef;
        }

        .type-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .semester-info {
            color: #6c757d;
            font-size: 14px;
        }

        .final-grade-table th,
        .final-grade-table td {
            text-align: center;
        }

        .final-grade-table td:first-child {
            text-align: left;
        }

        .grade-select {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            min-width: 100px;
        }

        .grade-select.small {
            min-width: 60px;
            font-size: 14px;
        }

        .grade-select:focus {
            border-color: #667eea;
            outline: none;
        }

        .suggested-grade {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            font-weight: 700;
        }

        .comment-input {
            width: 100%;
            min-width: 150px;
            padding: 8px 12px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
        }

        .approved-badge {
            display: inline-block;
            margin-left: 10px;
            padding: 3px 8px;
            background: #27ae60;
            color: white;
            border-radius: 4px;
            font-size: 11px;
        }

        .legend-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .legend-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }

        .legend-list li:last-child {
            border-bottom: none;
        }

        .btn-large {
            padding: 15px 40px;
            font-size: 16px;
        }
    </style>
</body>
</html>
