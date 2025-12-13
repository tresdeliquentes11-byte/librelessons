<?php
/**
 * © 2025 TresDeliquentes. Wszystkie prawa zastrzeżone.
 * LibreLessons - Panel Ucznia - Przegląd Ocen
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
$klasa_id = $uczen_dane['klasa_id'];

// Pobierz parametry
$semestr = isset($_GET['semestr']) ? $_GET['semestr'] : pobierz_aktualny_semestr();
$rok_szkolny = isset($_GET['rok']) ? $_GET['rok'] : pobierz_aktualny_rok_szkolny();

// Loguj dostęp (RODO)
loguj_dostep_ocen($uczen_id, 'przegladanie', "Przegląd własnych ocen - semestr: $semestr, rok: $rok_szkolny");

// Pobierz podsumowanie ocen
$podsumowanie = pobierz_podsumowanie_ocen_ucznia($uczen_id, $semestr, $rok_szkolny);

// Oblicz średnią ogólną
$srednia_ogolna = oblicz_srednia_ogolna($uczen_id, $semestr, $rok_szkolny);

// Oblicz statystyki ogólne
$liczba_ocen = 0;
$liczba_przedmiotow_z_ocenami = 0;
$najlepsza_srednia = 0;
$najgorsza_srednia = 10;
$przedmiot_najlepszy = '';
$przedmiot_najgorszy = '';

foreach ($podsumowanie as $p) {
    $liczba_ocen += $p['liczba_ocen'];
    if ($p['srednia_wazona'] !== null) {
        $liczba_przedmiotow_z_ocenami++;
        if ($p['srednia_wazona'] > $najlepsza_srednia) {
            $najlepsza_srednia = $p['srednia_wazona'];
            $przedmiot_najlepszy = $p['przedmiot_nazwa'];
        }
        if ($p['srednia_wazona'] < $najgorsza_srednia) {
            $najgorsza_srednia = $p['srednia_wazona'];
            $przedmiot_najgorszy = $p['przedmiot_nazwa'];
        }
    }
}

// Pobierz historię ocen do wykresu (ostatnie 30 ocen)
$historia_ocen = [];
foreach ($podsumowanie as $p) {
    foreach ($p['oceny'] as $ocena) {
        $historia_ocen[] = [
            'data' => $ocena['data_wystawienia'],
            'ocena' => $ocena['ocena'],
            'przedmiot' => $p['przedmiot_skrot'] ?? substr($p['przedmiot_nazwa'], 0, 3)
        ];
    }
}
usort($historia_ocen, function($a, $b) {
    return strtotime($a['data']) - strtotime($b['data']);
});
$historia_ocen = array_slice($historia_ocen, -30);

// Pobierz nazwę klasy
$stmt = $conn->prepare("SELECT nazwa FROM klasy WHERE id = ?");
$stmt->bind_param("i", $klasa_id);
$stmt->execute();
$klasa_nazwa = $stmt->get_result()->fetch_assoc()['nazwa'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje Oceny - LibreLessons</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/oceny.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="teacher-layout student-layout">
        <div class="teacher-container">
            <header class="teacher-header">
                <h1>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    Moje Oceny
                    <span class="teacher-badge student-badge">Klasa <?php echo e($klasa_nazwa); ?></span>
                </h1>
                <div class="user-info">
                    <span><?php echo e($_SESSION['user_name']); ?></span>
                    <a href="dashboard.php" class="btn-settings">Plan lekcji</a>
                    <a href="../logout.php" class="btn-logout">Wyloguj</a>
                </div>
            </header>

            <!-- Filtrowanie -->
            <div class="grade-card filter-card">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Semestr:</label>
                        <select name="semestr" onchange="this.form.submit()">
                            <option value="1" <?php echo $semestr == '1' ? 'selected' : ''; ?>>Semestr 1</option>
                            <option value="2" <?php echo $semestr == '2' ? 'selected' : ''; ?>>Semestr 2</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Rok szkolny:</label>
                        <select name="rok" onchange="this.form.submit()">
                            <option value="<?php echo $rok_szkolny; ?>" selected><?php echo e($rok_szkolny); ?></option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Główne statystyki -->
            <div class="stats-bar student-stats">
                <div class="stat-item main-stat">
                    <div class="number" style="color: <?php echo kolor_oceny($srednia_ogolna ?? 0); ?>; font-size: 36px;">
                        <?php echo $srednia_ogolna !== null ? number_format($srednia_ogolna, 2) : '-'; ?>
                    </div>
                    <div class="label">Średnia ogólna</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo $liczba_ocen; ?></div>
                    <div class="label">Wszystkich ocen</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo $liczba_przedmiotow_z_ocenami; ?></div>
                    <div class="label">Przedmiotów z ocenami</div>
                </div>
                <div class="stat-item best-subject">
                    <div class="number" style="color: #27ae60;">
                        <?php echo $najlepsza_srednia > 0 ? number_format($najlepsza_srednia, 2) : '-'; ?>
                    </div>
                    <div class="label">Najlepsza: <?php echo e($przedmiot_najlepszy); ?></div>
                </div>
                <div class="stat-item worst-subject">
                    <div class="number" style="color: <?php echo kolor_oceny($najgorsza_srednia); ?>;">
                        <?php echo $najgorsza_srednia < 10 ? number_format($najgorsza_srednia, 2) : '-'; ?>
                    </div>
                    <div class="label">Do poprawy: <?php echo e($przedmiot_najgorszy); ?></div>
                </div>
            </div>

            <!-- Wykres postępów -->
            <?php if (!empty($historia_ocen)): ?>
            <div class="grade-card">
                <h3>Wykres postępów w nauce</h3>
                <div class="chart-container">
                    <canvas id="progressChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Karty przedmiotów -->
            <div class="student-grades-grid">
                <?php foreach ($podsumowanie as $przedmiot): ?>
                    <div class="subject-card" style="border-left-color: <?php echo kolor_oceny($przedmiot['srednia_wazona'] ?? 3); ?>">
                        <div class="subject-card-header">
                            <div>
                                <h4 class="subject-name"><?php echo e($przedmiot['przedmiot_nazwa']); ?></h4>
                                <p class="teacher-name"><?php echo e($przedmiot['nauczyciel']); ?></p>
                            </div>
                            <div class="subject-average">
                                <span class="avg-value" style="color: <?php echo kolor_oceny($przedmiot['srednia_wazona'] ?? 0); ?>">
                                    <?php echo $przedmiot['srednia_wazona'] !== null ? number_format($przedmiot['srednia_wazona'], 2) : '-'; ?>
                                </span>
                                <span class="avg-label">średnia</span>
                            </div>
                        </div>

                        <div class="subject-grades">
                            <?php if (!empty($przedmiot['oceny'])): ?>
                                <?php foreach ($przedmiot['oceny'] as $ocena): ?>
                                    <span class="grade-badge"
                                          style="background-color: <?php echo e($ocena['kolor']); ?>"
                                          title="<?php echo e($ocena['kategoria_nazwa']); ?> (waga: <?php echo $ocena['kategoria_waga']; ?>)&#10;<?php echo e($ocena['komentarz'] ?? ''); ?>&#10;<?php echo formatuj_date($ocena['data_wystawienia']); ?>">
                                        <?php echo formatuj_ocene($ocena['ocena']); ?>
                                        <?php if ($ocena['czy_poprawa']): ?>
                                            <span class="poprawa-indicator">P</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="no-grades">Brak ocen</span>
                            <?php endif; ?>
                        </div>

                        <div class="predicted-section">
                            <span class="predicted-label">Przewidywana ocena końcowa:</span>
                            <span class="predicted-value">
                                <?php echo $przedmiot['przewidywana'] !== null ? $przedmiot['przewidywana'] : '-'; ?>
                            </span>
                        </div>

                        <?php if ($przedmiot['ocena_koncowa']): ?>
                            <div class="final-grade-section">
                                <span class="final-label">
                                    Ocena <?php echo $przedmiot['ocena_koncowa']['typ'] === 'srodroczna' ? 'śródroczna' : 'roczna'; ?>:
                                </span>
                                <span class="final-value"><?php echo $przedmiot['ocena_koncowa']['ocena']; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($podsumowanie)): ?>
                    <div class="empty-state">
                        <p>Nie masz jeszcze przypisanych przedmiotów lub ocen.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Historia ocen -->
            <div class="grade-card">
                <h3>Historia ocen (chronologicznie)</h3>
                <div class="grades-history">
                    <table class="grade-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Przedmiot</th>
                                <th>Ocena</th>
                                <th>Kategoria</th>
                                <th>Komentarz</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $wszystkie_oceny = [];
                            foreach ($podsumowanie as $p) {
                                foreach ($p['oceny'] as $ocena) {
                                    $ocena['przedmiot_nazwa'] = $p['przedmiot_nazwa'];
                                    $wszystkie_oceny[] = $ocena;
                                }
                            }
                            usort($wszystkie_oceny, function($a, $b) {
                                return strtotime($b['data_wystawienia']) - strtotime($a['data_wystawienia']);
                            });
                            ?>

                            <?php foreach (array_slice($wszystkie_oceny, 0, 20) as $ocena): ?>
                                <tr>
                                    <td><?php echo formatuj_date($ocena['data_wystawienia']); ?></td>
                                    <td><?php echo e($ocena['przedmiot_nazwa']); ?></td>
                                    <td>
                                        <span class="grade-badge" style="background-color: <?php echo e($ocena['kolor']); ?>">
                                            <?php echo formatuj_ocene($ocena['ocena']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($ocena['kategoria_nazwa']); ?></td>
                                    <td><?php echo e($ocena['komentarz'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($wszystkie_oceny)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">Brak ocen do wyświetlenia</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Informacja RODO -->
            <div class="rodo-notice">
                <strong>Informacja RODO:</strong> Twoje dane osobowe i oceny są przetwarzane zgodnie z
                Rozporządzeniem o Ochronie Danych Osobowych (RODO). Masz prawo dostępu do swoich danych,
                ich sprostowania oraz uzyskania kopii. W razie pytań skontaktuj się z administratorem szkoły.
                <br><br>
                <a href="eksport_danych.php" class="btn btn-secondary btn-small">Eksportuj moje dane (RODO)</a>
            </div>
        </div>
    </div>

    <script>
        <?php if (!empty($historia_ocen)): ?>
        // Wykres postępów
        const ctx = document.getElementById('progressChart').getContext('2d');

        const chartData = <?php echo json_encode($historia_ocen); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(d => d.data + ' (' + d.przedmiot + ')'),
                datasets: [{
                    label: 'Oceny',
                    data: chartData.map(d => d.ocena),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: chartData.map(d => {
                        const ocena = d.ocena;
                        if (ocena >= 5) return '#27ae60';
                        if (ocena >= 4) return '#2ecc71';
                        if (ocena >= 3) return '#f39c12';
                        if (ocena >= 2) return '#e67e22';
                        return '#e74c3c';
                    }),
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Ocena: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        min: 1,
                        max: 6,
                        ticks: {
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Ocena'
                        }
                    },
                    x: {
                        display: true,
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>

    <style>
        .student-layout {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .student-badge {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
        }

        .filter-card {
            padding: 15px 25px;
        }

        .filter-form {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group label {
            font-weight: 600;
            color: #2c3e50;
        }

        .filter-group select {
            padding: 8px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }

        .student-stats {
            background: white;
        }

        .main-stat {
            border-right: 2px solid #e9ecef;
            padding-right: 30px;
        }

        .final-grade-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            margin-top: 10px;
            border-top: 2px solid #667eea;
        }

        .final-label {
            font-size: 13px;
            color: #667eea;
            font-weight: 600;
        }

        .final-value {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }

        .grades-history {
            max-height: 400px;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            .main-stat {
                border-right: none;
                border-bottom: 2px solid #e9ecef;
                padding-right: 0;
                padding-bottom: 15px;
            }

            .filter-form {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</body>
</html>
