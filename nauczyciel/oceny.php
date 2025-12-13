<?php
/**
 * © 2025 TresDeliquentes. Wszystkie prawa zastrzeżone.
 * LibreLessons - Panel Nauczyciela - Zarządzanie Ocenami
 */
require_once '../includes/config.php';
require_once '../includes/oceny_functions.php';
sprawdz_uprawnienia('nauczyciel');

// Pobierz dane nauczyciela
$nauczyciel_id = pobierz_id_nauczyciela($_SESSION['user_id']);
if (!$nauczyciel_id) {
    die("Błąd: Nie znaleziono danych nauczyciela");
}

// Pobierz parametry
$klasa_id = isset($_GET['klasa']) ? intval($_GET['klasa']) : null;
$przedmiot_id = isset($_GET['przedmiot']) ? intval($_GET['przedmiot']) : null;

// Pobierz przedmioty nauczyciela
$przedmioty = pobierz_przedmioty_nauczyciela($nauczyciel_id);

// Jeśli nie wybrano klasy/przedmiotu, pokaż listę
$uczniowie = [];
$oceny_klasy = [];
$statystyki = null;
$kategorie = pobierz_kategorie_ocen();

if ($klasa_id && $przedmiot_id) {
    // Sprawdź czy nauczyciel uczy ten przedmiot w tej klasie
    if (!nauczyciel_uczy_przedmiot($nauczyciel_id, $klasa_id, $przedmiot_id)) {
        header('Location: oceny.php?error=brak_uprawnien');
        exit();
    }

    $uczniowie = pobierz_uczniow_klasy($klasa_id);
    $oceny_klasy = pobierz_oceny_klasy($klasa_id, $przedmiot_id);
    $statystyki = pobierz_statystyki_klasy($klasa_id, $przedmiot_id);

    // Loguj dostęp (RODO)
    foreach ($uczniowie as $uczen) {
        loguj_dostep_ocen($uczen['uczen_id'], 'przegladanie', "Przegląd ocen klasy - przedmiot ID: $przedmiot_id");
    }
}

// Obsługa formularzy
$komunikat = '';
$blad = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $akcja = $_POST['akcja'] ?? '';

    switch ($akcja) {
        case 'dodaj_ocene':
            $wynik = dodaj_ocene([
                'uczen_id' => intval($_POST['uczen_id']),
                'przedmiot_id' => intval($_POST['przedmiot_id']),
                'nauczyciel_id' => $nauczyciel_id,
                'klasa_id' => intval($_POST['klasa_id']),
                'kategoria_id' => intval($_POST['kategoria_id']),
                'ocena' => floatval($_POST['ocena']),
                'komentarz' => trim($_POST['komentarz'] ?? ''),
                'waga_indywidualna' => !empty($_POST['waga_indywidualna']) ? floatval($_POST['waga_indywidualna']) : null,
                'data_wystawienia' => $_POST['data_wystawienia'] ?? date('Y-m-d')
            ]);

            if ($wynik['success']) {
                $komunikat = $wynik['message'];
                // Odśwież dane
                $oceny_klasy = pobierz_oceny_klasy($klasa_id, $przedmiot_id);
                $statystyki = pobierz_statystyki_klasy($klasa_id, $przedmiot_id);
            } else {
                $blad = $wynik['message'];
            }
            break;

        case 'edytuj_ocene':
            $wynik = edytuj_ocene(intval($_POST['ocena_id']), [
                'ocena' => floatval($_POST['ocena']),
                'kategoria_id' => intval($_POST['kategoria_id']),
                'komentarz' => trim($_POST['komentarz'] ?? ''),
                'waga_indywidualna' => !empty($_POST['waga_indywidualna']) ? floatval($_POST['waga_indywidualna']) : null
            ], $_POST['powod_zmiany'] ?? null);

            if ($wynik['success']) {
                $komunikat = $wynik['message'];
                $oceny_klasy = pobierz_oceny_klasy($klasa_id, $przedmiot_id);
                $statystyki = pobierz_statystyki_klasy($klasa_id, $przedmiot_id);
            } else {
                $blad = $wynik['message'];
            }
            break;

        case 'usun_ocene':
            $wynik = usun_ocene(intval($_POST['ocena_id']), $_POST['powod'] ?? 'Usunięcie przez nauczyciela');

            if ($wynik['success']) {
                $komunikat = $wynik['message'];
                $oceny_klasy = pobierz_oceny_klasy($klasa_id, $przedmiot_id);
                $statystyki = pobierz_statystyki_klasy($klasa_id, $przedmiot_id);
            } else {
                $blad = $wynik['message'];
            }
            break;

        case 'dodaj_poprawe':
            $wynik = dodaj_poprawe(
                intval($_POST['oryginalna_ocena_id']),
                floatval($_POST['nowa_ocena']),
                $nauczyciel_id,
                trim($_POST['komentarz'] ?? '')
            );

            if ($wynik['success']) {
                $komunikat = $wynik['message'];
                $oceny_klasy = pobierz_oceny_klasy($klasa_id, $przedmiot_id);
                $statystyki = pobierz_statystyki_klasy($klasa_id, $przedmiot_id);
            } else {
                $blad = $wynik['message'];
            }
            break;

        case 'dodaj_wiele':
            // Dodawanie ocen dla wielu uczniów naraz
            $uczen_ids = $_POST['uczniowie'] ?? [];
            $sukces = 0;
            $bledy = 0;

            foreach ($uczen_ids as $uid) {
                $wynik = dodaj_ocene([
                    'uczen_id' => intval($uid),
                    'przedmiot_id' => intval($_POST['przedmiot_id']),
                    'nauczyciel_id' => $nauczyciel_id,
                    'klasa_id' => intval($_POST['klasa_id']),
                    'kategoria_id' => intval($_POST['kategoria_id']),
                    'ocena' => floatval($_POST['ocena']),
                    'komentarz' => trim($_POST['komentarz'] ?? ''),
                    'data_wystawienia' => $_POST['data_wystawienia'] ?? date('Y-m-d')
                ]);

                if ($wynik['success']) {
                    $sukces++;
                } else {
                    $bledy++;
                }
            }

            $komunikat = "Dodano $sukces ocen" . ($bledy > 0 ? ", błędy: $bledy" : "");
            $oceny_klasy = pobierz_oceny_klasy($klasa_id, $przedmiot_id);
            $statystyki = pobierz_statystyki_klasy($klasa_id, $przedmiot_id);
            break;
    }
}

// Pobierz nazwy dla breadcrumb
$klasa_nazwa = '';
$przedmiot_nazwa = '';
if ($klasa_id && $przedmiot_id) {
    foreach ($przedmioty as $p) {
        if ($p['klasa_id'] == $klasa_id && $p['przedmiot_id'] == $przedmiot_id) {
            $klasa_nazwa = $p['klasa_nazwa'];
            $przedmiot_nazwa = $p['przedmiot_nazwa'];
            break;
        }
    }
}

$dozwolone_oceny = pobierz_dozwolone_oceny();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oceny - Panel Nauczyciela - LibreLessons</title>
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
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Dziennik Ocen
                    <span class="teacher-badge">Nauczyciel</span>
                </h1>
                <div class="user-info">
                    <span><?php echo e($_SESSION['user_name']); ?></span>
                    <a href="dashboard.php" class="btn-settings">Plan lekcji</a>
                    <a href="../logout.php" class="btn-logout">Wyloguj</a>
                </div>
            </header>

            <?php if ($komunikat): ?>
                <div class="alert alert-success"><?php echo e($komunikat); ?></div>
            <?php endif; ?>

            <?php if ($blad): ?>
                <div class="alert alert-error"><?php echo e($blad); ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'brak_uprawnien'): ?>
                <div class="alert alert-error">Nie masz uprawnień do wystawiania ocen z tego przedmiotu w tej klasie.</div>
            <?php endif; ?>

            <?php if (!$klasa_id || !$przedmiot_id): ?>
                <!-- WIDOK: Wybór klasy i przedmiotu -->
                <div class="grade-card">
                    <h2>Wybierz klasę i przedmiot</h2>
                    <div class="class-grid">
                        <?php
                        $grupy = [];
                        foreach ($przedmioty as $p) {
                            $grupy[$p['klasa_nazwa']][] = $p;
                        }
                        ?>

                        <?php foreach ($grupy as $klasa => $lista_przedmiotow): ?>
                            <div class="class-box">
                                <h3><?php echo e($klasa); ?></h3>
                                <div class="subject-list">
                                    <?php foreach ($lista_przedmiotow as $p): ?>
                                        <a href="?klasa=<?php echo $p['klasa_id']; ?>&przedmiot=<?php echo $p['przedmiot_id']; ?>"
                                           class="subject-link">
                                            <?php echo e($p['przedmiot_nazwa']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($przedmioty)): ?>
                            <div class="empty-state">
                                <p>Nie masz przypisanych przedmiotów do nauczania.</p>
                                <p>Skontaktuj się z administratorem lub dyrektorem.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- WIDOK: Dziennik ocen klasy -->
                <nav class="breadcrumb">
                    <a href="oceny.php">Oceny</a> &raquo;
                    <span><?php echo e($klasa_nazwa); ?> - <?php echo e($przedmiot_nazwa); ?></span>
                </nav>

                <!-- Statystyki klasy -->
                <?php if ($statystyki): ?>
                <div class="stats-bar">
                    <div class="stat-item">
                        <div class="number"><?php echo $statystyki['liczba_uczniow'] ?? 0; ?></div>
                        <div class="label">Uczniów</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><?php echo $statystyki['liczba_ocen'] ?? 0; ?></div>
                        <div class="label">Ocen</div>
                    </div>
                    <div class="stat-item">
                        <div class="number" style="color: <?php echo kolor_oceny($statystyki['srednia_wazona'] ?? 0); ?>">
                            <?php echo $statystyki['srednia_wazona'] ?? '-'; ?>
                        </div>
                        <div class="label">Średnia ważona</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><?php echo $statystyki['oceny_slabe'] ?? 0; ?></div>
                        <div class="label">Ocen poniżej 2.5</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Przyciski akcji -->
                <div class="action-bar">
                    <button class="btn btn-primary" onclick="pokazModalDodajOcene()">
                        + Dodaj ocenę
                    </button>
                    <button class="btn btn-secondary" onclick="pokazModalDodajWiele()">
                        ++ Dodaj wielu
                    </button>
                    <a href="oceny_koncowe.php?klasa=<?php echo $klasa_id; ?>&przedmiot=<?php echo $przedmiot_id; ?>"
                       class="btn btn-info">
                        Oceny końcowe
                    </a>
                    <a href="statystyki_klasy.php?klasa=<?php echo $klasa_id; ?>&przedmiot=<?php echo $przedmiot_id; ?>"
                       class="btn btn-secondary">
                        Statystyki
                    </a>
                </div>

                <!-- Tabela ocen -->
                <div class="grade-card">
                    <div class="grade-table-container">
                        <table class="grade-table">
                            <thead>
                                <tr>
                                    <th class="student-col">Uczeń</th>
                                    <th class="grades-col">Oceny</th>
                                    <th class="avg-col">Średnia</th>
                                    <th class="predicted-col">Przewidywana</th>
                                    <th class="actions-col">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($oceny_klasy as $uczen_dane): ?>
                                    <?php
                                    $srednia = oblicz_srednia_wazona($uczen_dane['uczen_id'], $przedmiot_id);
                                    $przewidywana = przewiduj_ocene_koncowa($srednia);
                                    ?>
                                    <tr data-uczen-id="<?php echo $uczen_dane['uczen_id']; ?>">
                                        <td class="student-col">
                                            <strong><?php echo e($uczen_dane['uczen_nazwa']); ?></strong>
                                        </td>
                                        <td class="grades-col">
                                            <div class="grades-row">
                                                <?php foreach ($uczen_dane['oceny'] as $ocena): ?>
                                                    <span class="grade-badge"
                                                          style="background-color: <?php echo e($ocena['kolor'] ?? kolor_oceny($ocena['ocena'])); ?>"
                                                          title="<?php echo e($ocena['kategoria_nazwa']); ?> (waga: <?php echo $ocena['kategoria_waga']; ?>)&#10;<?php echo e($ocena['komentarz'] ?? ''); ?>&#10;<?php echo formatuj_date($ocena['data_wystawienia']); ?>"
                                                          onclick="pokazSzczegolyOceny(<?php echo htmlspecialchars(json_encode($ocena), ENT_QUOTES); ?>)">
                                                        <?php echo formatuj_ocene($ocena['ocena']); ?>
                                                        <?php if ($ocena['czy_poprawa']): ?>
                                                            <span class="poprawa-indicator">P</span>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                                <?php if (empty($uczen_dane['oceny'])): ?>
                                                    <span class="no-grades">brak ocen</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="avg-col">
                                            <?php if ($srednia !== null): ?>
                                                <span class="average-badge" style="background-color: <?php echo kolor_oceny($srednia); ?>">
                                                    <?php echo number_format($srednia, 2); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="no-grades">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="predicted-col">
                                            <?php if ($przewidywana !== null): ?>
                                                <span class="predicted-badge"><?php echo $przewidywana; ?></span>
                                            <?php else: ?>
                                                <span class="no-grades">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-col">
                                            <button class="btn-small btn-primary"
                                                    onclick="pokazModalDodajOcene(<?php echo $uczen_dane['uczen_id']; ?>, '<?php echo e($uczen_dane['uczen_nazwa']); ?>')">
                                                +
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($oceny_klasy)): ?>
                                    <tr>
                                        <td colspan="5" class="empty-state">
                                            Brak uczniów w tej klasie lub nie masz uprawnień.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Legenda kategorii -->
                <div class="grade-card legend-card">
                    <h3>Legenda kategorii</h3>
                    <div class="category-legend">
                        <?php foreach ($kategorie as $kat): ?>
                            <div class="category-item">
                                <span class="category-dot" style="background-color: <?php echo e($kat['kolor']); ?>"></span>
                                <span class="category-name"><?php echo e($kat['nazwa']); ?></span>
                                <span class="category-weight">(waga: <?php echo $kat['waga']; ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Modal: Dodaj ocenę -->
                <div id="modalDodajOcene" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Dodaj ocenę</h2>
                            <button class="modal-close" onclick="zamknijModal('modalDodajOcene')">&times;</button>
                        </div>
                        <form method="POST" class="grade-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="akcja" value="dodaj_ocene">
                            <input type="hidden" name="klasa_id" value="<?php echo $klasa_id; ?>">
                            <input type="hidden" name="przedmiot_id" value="<?php echo $przedmiot_id; ?>">

                            <div class="form-group">
                                <label for="uczen_id">Uczeń</label>
                                <select name="uczen_id" id="uczen_id" required>
                                    <option value="">-- Wybierz ucznia --</option>
                                    <?php foreach ($uczniowie as $u): ?>
                                        <option value="<?php echo $u['uczen_id']; ?>">
                                            <?php echo e($u['pelne_imie']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="ocena">Ocena</label>
                                    <select name="ocena" id="ocena" required>
                                        <?php foreach ($dozwolone_oceny as $o): ?>
                                            <option value="<?php echo $o; ?>"><?php echo formatuj_ocene($o); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="kategoria_id">Kategoria</label>
                                    <select name="kategoria_id" id="kategoria_id" required>
                                        <?php foreach ($kategorie as $kat): ?>
                                            <option value="<?php echo $kat['id']; ?>"
                                                    data-waga="<?php echo $kat['waga']; ?>">
                                                <?php echo e($kat['nazwa']); ?> (waga: <?php echo $kat['waga']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="data_wystawienia">Data</label>
                                    <input type="date" name="data_wystawienia" id="data_wystawienia"
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="waga_indywidualna">Waga indywidualna (opcjonalnie)</label>
                                    <input type="number" name="waga_indywidualna" id="waga_indywidualna"
                                           step="0.5" min="0.5" max="10" placeholder="Domyślna z kategorii">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="komentarz">Komentarz (opcjonalnie)</label>
                                <textarea name="komentarz" id="komentarz" rows="2"
                                          placeholder="Np. temat sprawdzianu, uwagi..."></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary"
                                        onclick="zamknijModal('modalDodajOcene')">Anuluj</button>
                                <button type="submit" class="btn btn-primary">Dodaj ocenę</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal: Dodaj wiele ocen -->
                <div id="modalDodajWiele" class="modal">
                    <div class="modal-content modal-large">
                        <div class="modal-header">
                            <h2>Dodaj oceny dla wielu uczniów</h2>
                            <button class="modal-close" onclick="zamknijModal('modalDodajWiele')">&times;</button>
                        </div>
                        <form method="POST" class="grade-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="akcja" value="dodaj_wiele">
                            <input type="hidden" name="klasa_id" value="<?php echo $klasa_id; ?>">
                            <input type="hidden" name="przedmiot_id" value="<?php echo $przedmiot_id; ?>">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="ocena_wiele">Ocena</label>
                                    <select name="ocena" id="ocena_wiele" required>
                                        <?php foreach ($dozwolone_oceny as $o): ?>
                                            <option value="<?php echo $o; ?>"><?php echo formatuj_ocene($o); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="kategoria_wiele">Kategoria</label>
                                    <select name="kategoria_id" id="kategoria_wiele" required>
                                        <?php foreach ($kategorie as $kat): ?>
                                            <option value="<?php echo $kat['id']; ?>">
                                                <?php echo e($kat['nazwa']); ?> (waga: <?php echo $kat['waga']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="data_wiele">Data</label>
                                    <input type="date" name="data_wystawienia" id="data_wiele"
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="komentarz_wiele">Komentarz</label>
                                <input type="text" name="komentarz" id="komentarz_wiele"
                                       placeholder="Np. temat sprawdzianu">
                            </div>

                            <div class="form-group">
                                <label>Wybierz uczniów:</label>
                                <div class="checkbox-actions">
                                    <button type="button" onclick="zaznaczWszystkich(true)">Zaznacz wszystkich</button>
                                    <button type="button" onclick="zaznaczWszystkich(false)">Odznacz wszystkich</button>
                                </div>
                                <div class="students-checkboxes">
                                    <?php foreach ($uczniowie as $u): ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="uczniowie[]" value="<?php echo $u['uczen_id']; ?>">
                                            <?php echo e($u['pelne_imie']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary"
                                        onclick="zamknijModal('modalDodajWiele')">Anuluj</button>
                                <button type="submit" class="btn btn-primary">Dodaj oceny</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal: Szczegóły/Edycja oceny -->
                <div id="modalSzczegolyOceny" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Szczegóły oceny</h2>
                            <button class="modal-close" onclick="zamknijModal('modalSzczegolyOceny')">&times;</button>
                        </div>
                        <div id="szczegolyOcenyContent">
                            <!-- Wypełniane przez JavaScript -->
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Funkcje modali
        function pokazModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function zamknijModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function pokazModalDodajOcene(uczenId = null, uczenNazwa = null) {
            const select = document.getElementById('uczen_id');
            if (uczenId) {
                select.value = uczenId;
            } else {
                select.value = '';
            }
            pokazModal('modalDodajOcene');
        }

        function pokazModalDodajWiele() {
            pokazModal('modalDodajWiele');
        }

        function zaznaczWszystkich(zaznacz) {
            const checkboxy = document.querySelectorAll('.students-checkboxes input[type="checkbox"]');
            checkboxy.forEach(cb => cb.checked = zaznacz);
        }

        function pokazSzczegolyOceny(ocena) {
            const container = document.getElementById('szczegolyOcenyContent');
            const dozwoloneOceny = <?php echo json_encode($dozwolone_oceny); ?>;
            const kategorie = <?php echo json_encode($kategorie); ?>;

            let kategoriOptions = kategorie.map(k =>
                `<option value="${k.id}" ${k.id == ocena.kategoria_id ? 'selected' : ''}>${k.nazwa} (waga: ${k.waga})</option>`
            ).join('');

            let ocenyOptions = dozwoloneOceny.map(o =>
                `<option value="${o}" ${o == ocena.ocena ? 'selected' : ''}>${formatujOcene(o)}</option>`
            ).join('');

            container.innerHTML = `
                <div class="grade-details">
                    <div class="detail-row">
                        <span class="label">Ocena:</span>
                        <span class="value grade-big" style="background-color: ${ocena.kolor || kolorOceny(ocena.ocena)}">
                            ${formatujOcene(ocena.ocena)}
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Kategoria:</span>
                        <span class="value">${ocena.kategoria_nazwa} (waga: ${ocena.kategoria_waga})</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Data wystawienia:</span>
                        <span class="value">${ocena.data_wystawienia}</span>
                    </div>
                    ${ocena.komentarz ? `
                    <div class="detail-row">
                        <span class="label">Komentarz:</span>
                        <span class="value">${escapeHtml(ocena.komentarz)}</span>
                    </div>
                    ` : ''}
                    ${ocena.czy_poprawa == 1 ? `
                    <div class="detail-row">
                        <span class="value poprawa-info">Ta ocena jest poprawą</span>
                    </div>
                    ` : ''}
                </div>

                <hr>

                <h3>Edytuj ocenę</h3>
                <form method="POST" class="grade-form">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="akcja" value="edytuj_ocene">
                    <input type="hidden" name="ocena_id" value="${ocena.id}">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nowa ocena</label>
                            <select name="ocena" required>${ocenyOptions}</select>
                        </div>
                        <div class="form-group">
                            <label>Kategoria</label>
                            <select name="kategoria_id" required>${kategoriOptions}</select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Komentarz</label>
                        <textarea name="komentarz" rows="2">${ocena.komentarz || ''}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Powód zmiany (wymagane przy edycji)</label>
                        <input type="text" name="powod_zmiany" placeholder="Np. błąd przy wpisywaniu" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                    </div>
                </form>

                ${ocena.czy_poprawa == 0 ? `
                <hr>
                <h3>Poprawa oceny</h3>
                <form method="POST" class="grade-form">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="akcja" value="dodaj_poprawe">
                    <input type="hidden" name="oryginalna_ocena_id" value="${ocena.id}">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nowa ocena (poprawa)</label>
                            <select name="nowa_ocena" required>${ocenyOptions}</select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Komentarz do poprawy</label>
                        <input type="text" name="komentarz" placeholder="Np. poprawa sprawdzianu">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-info">Dodaj poprawę</button>
                    </div>
                </form>
                ` : ''}

                <hr>
                <h3>Usuń ocenę</h3>
                <form method="POST" class="grade-form" onsubmit="return confirm('Czy na pewno chcesz usunąć tę ocenę?')">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="akcja" value="usun_ocene">
                    <input type="hidden" name="ocena_id" value="${ocena.id}">

                    <div class="form-group">
                        <label>Powód usunięcia</label>
                        <input type="text" name="powod" placeholder="Np. błędnie wpisana" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-danger">Usuń ocenę</button>
                    </div>
                </form>
            `;

            pokazModal('modalSzczegolyOceny');
        }

        function formatujOcene(ocena) {
            ocena = parseFloat(ocena);
            const czescDziesietna = ocena - Math.floor(ocena);

            if (czescDziesietna === 0) {
                return Math.floor(ocena).toString();
            } else if (czescDziesietna === 0.5) {
                return Math.floor(ocena) + '+';
            } else if (czescDziesietna === 0.75) {
                return (Math.floor(ocena) + 1) + '-';
            } else {
                return ocena.toFixed(1);
            }
        }

        function kolorOceny(ocena) {
            ocena = parseFloat(ocena);
            if (ocena >= 5) return '#27ae60';
            if (ocena >= 4) return '#2ecc71';
            if (ocena >= 3) return '#f39c12';
            if (ocena >= 2) return '#e67e22';
            return '#e74c3c';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Zamykanie modali kliknięciem poza zawartością
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
