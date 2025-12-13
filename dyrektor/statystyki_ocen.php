<?php
/**
 * © 2025 TresDeliquentes. Wszystkie prawa zastrzeżone.
 * LibreLessons - Panel Dyrektora - Statystyki Ocen Szkoły
 */
require_once '../includes/config.php';
require_once '../includes/oceny_functions.php';
sprawdz_uprawnienia('dyrektor');

// Parametry
$rok_szkolny = isset($_GET['rok']) ? $_GET['rok'] : pobierz_aktualny_rok_szkolny();
$semestr = isset($_GET['semestr']) ? $_GET['semestr'] : pobierz_aktualny_semestr();
$widok = isset($_GET['widok']) ? $_GET['widok'] : 'ogolne';

// Pobierz statystyki szkoły
$statystyki = pobierz_statystyki_szkoly($rok_szkolny);

// Pobierz uczniów zagrożonych
$zagrozeni = pobierz_uczniow_zagrozonych(null, null, $semestr, $rok_szkolny);

// Pobierz trendy
$trendy = pobierz_trendy_ocen(null, $rok_szkolny);

// Pobierz wszystkie klasy dla filtrowania
$klasy_result = $conn->query("SELECT id, nazwa FROM klasy ORDER BY nazwa");
$klasy = [];
while ($row = $klasy_result->fetch_assoc()) {
    $klasy[] = $row;
}

// Oblicz porównanie między klasami
$porownanie_klas = [];
foreach ($statystyki['klasy'] as $klasa) {
    if ($klasa['srednia_wazona'] !== null) {
        $rozklad = pobierz_rozklad_ocen($klasa['klasa_id'], null, $semestr, $rok_szkolny);
        $porownanie_klas[] = [
            'klasa_id' => $klasa['klasa_id'],
            'klasa_nazwa' => $klasa['klasa_nazwa'],
            'srednia' => $klasa['srednia_wazona'],
            'liczba_uczniow' => $klasa['liczba_uczniow'],
            'rozklad' => $rozklad
        ];
    }
}
usort($porownanie_klas, function($a, $b) {
    return $b['srednia'] <=> $a['srednia'];
});

// Loguj dostęp (RODO)
loguj_dostep_ocen(null, 'raport', "Przegląd statystyk szkoły - rok: $rok_szkolny");
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statystyki Ocen Szkoły - LibreLessons</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/oceny.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Statystyki Ocen Szkoły</h1>
                <div class="header-actions">
                    <span class="user-name"><?php echo e($_SESSION['user_name']); ?></span>
                    <a href="../logout.php" class="btn-logout">Wyloguj</a>
                </div>
            </header>

            <div class="admin-content">
                <!-- Filtry -->
                <div class="grade-card filter-card">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <label>Rok szkolny:</label>
                            <select name="rok" onchange="this.form.submit()">
                                <option value="<?php echo $rok_szkolny; ?>" selected><?php echo e($rok_szkolny); ?></option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Semestr:</label>
                            <select name="semestr" onchange="this.form.submit()">
                                <option value="1" <?php echo $semestr == '1' ? 'selected' : ''; ?>>Semestr 1</option>
                                <option value="2" <?php echo $semestr == '2' ? 'selected' : ''; ?>>Semestr 2</option>
                            </select>
                        </div>
                        <input type="hidden" name="widok" value="<?php echo e($widok); ?>">
                    </form>
                </div>

                <!-- Nawigacja zakładek -->
                <div class="tabs-navigation">
                    <a href="?widok=ogolne&rok=<?php echo urlencode($rok_szkolny); ?>&semestr=<?php echo $semestr; ?>"
                       class="tab-link <?php echo $widok === 'ogolne' ? 'active' : ''; ?>">
                        Podsumowanie ogólne
                    </a>
                    <a href="?widok=klasy&rok=<?php echo urlencode($rok_szkolny); ?>&semestr=<?php echo $semestr; ?>"
                       class="tab-link <?php echo $widok === 'klasy' ? 'active' : ''; ?>">
                        Porównanie klas
                    </a>
                    <a href="?widok=nauczyciele&rok=<?php echo urlencode($rok_szkolny); ?>&semestr=<?php echo $semestr; ?>"
                       class="tab-link <?php echo $widok === 'nauczyciele' ? 'active' : ''; ?>">
                        Ranking nauczycieli
                    </a>
                    <a href="?widok=zagrozeni&rok=<?php echo urlencode($rok_szkolny); ?>&semestr=<?php echo $semestr; ?>"
                       class="tab-link <?php echo $widok === 'zagrozeni' ? 'active' : ''; ?>">
                        Uczniowie zagrożeni
                    </a>
                    <a href="?widok=trendy&rok=<?php echo urlencode($rok_szkolny); ?>&semestr=<?php echo $semestr; ?>"
                       class="tab-link <?php echo $widok === 'trendy' ? 'active' : ''; ?>">
                        Trendy
                    </a>
                </div>

                <?php if ($widok === 'ogolne'): ?>
                <!-- ============================================================
                     WIDOK: PODSUMOWANIE OGÓLNE
                     ============================================================ -->
                <div class="stats-bar director-stats">
                    <div class="stat-item main-stat">
                        <div class="number" style="color: <?php echo kolor_oceny($statystyki['ogolne']['srednia_szkoly'] ?? 0); ?>; font-size: 42px;">
                            <?php echo $statystyki['ogolne']['srednia_szkoly'] ?? '-'; ?>
                        </div>
                        <div class="label">Średnia szkoły</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><?php echo $statystyki['ogolne']['liczba_uczniow'] ?? 0; ?></div>
                        <div class="label">Uczniów z ocenami</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><?php echo $statystyki['ogolne']['liczba_ocen'] ?? 0; ?></div>
                        <div class="label">Wszystkich ocen</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><?php echo count($statystyki['klasy']); ?></div>
                        <div class="label">Klas</div>
                    </div>
                    <div class="stat-item warning">
                        <div class="number" style="color: #e74c3c;"><?php echo count($zagrozeni); ?></div>
                        <div class="label">Uczniów zagrożonych</div>
                    </div>
                </div>

                <div class="stats-grid">
                    <!-- Top 5 klas -->
                    <div class="grade-card">
                        <h3>Top 5 klas wg średniej</h3>
                        <table class="ranking-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Klasa</th>
                                    <th>Uczniów</th>
                                    <th>Średnia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($porownanie_klas, 0, 5) as $i => $klasa): ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge <?php echo $i < 3 ? 'rank-' . ($i + 1) : 'rank-other'; ?>">
                                                <?php echo $i + 1; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo e($klasa['klasa_nazwa']); ?></strong></td>
                                        <td><?php echo $klasa['liczba_uczniow']; ?></td>
                                        <td>
                                            <span style="color: <?php echo kolor_oceny($klasa['srednia']); ?>; font-weight: 700;">
                                                <?php echo number_format($klasa['srednia'], 2); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Top 5 nauczycieli -->
                    <div class="grade-card">
                        <h3>Top 5 nauczycieli wg średniej ocen</h3>
                        <table class="ranking-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nauczyciel</th>
                                    <th>Ocen</th>
                                    <th>Średnia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($statystyki['nauczyciele'], 0, 5) as $i => $nauczyciel): ?>
                                    <tr>
                                        <td>
                                            <span class="rank-badge <?php echo $i < 3 ? 'rank-' . ($i + 1) : 'rank-other'; ?>">
                                                <?php echo $i + 1; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo e($nauczyciel['nauczyciel_nazwa']); ?></strong></td>
                                        <td><?php echo $nauczyciel['liczba_ocen']; ?></td>
                                        <td>
                                            <span style="color: <?php echo kolor_oceny($nauczyciel['srednia_ocen']); ?>; font-weight: 700;">
                                                <?php echo number_format($nauczyciel['srednia_ocen'], 2); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Wykres trendów -->
                <?php if (!empty($trendy)): ?>
                <div class="grade-card">
                    <h3>Trend średnich ocen w czasie</h3>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php elseif ($widok === 'klasy'): ?>
                <!-- ============================================================
                     WIDOK: PORÓWNANIE KLAS
                     ============================================================ -->
                <div class="grade-card">
                    <h3>Porównanie wszystkich klas</h3>
                    <div class="chart-container" style="height: 400px;">
                        <canvas id="classCompareChart"></canvas>
                    </div>
                </div>

                <div class="grade-card">
                    <h3>Szczegółowe statystyki klas</h3>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th>Pozycja</th>
                                <th>Klasa</th>
                                <th>Uczniów</th>
                                <th>Średnia</th>
                                <th>Rozkład ocen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($porownanie_klas as $i => $klasa): ?>
                                <tr>
                                    <td>
                                        <span class="rank-badge <?php echo $i < 3 ? 'rank-' . ($i + 1) : 'rank-other'; ?>">
                                            <?php echo $i + 1; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo e($klasa['klasa_nazwa']); ?></strong></td>
                                    <td><?php echo $klasa['liczba_uczniow']; ?></td>
                                    <td>
                                        <span style="color: <?php echo kolor_oceny($klasa['srednia']); ?>; font-weight: 700; font-size: 18px;">
                                            <?php echo number_format($klasa['srednia'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $suma = array_sum($klasa['rozklad']);
                                        if ($suma > 0):
                                        ?>
                                        <div class="distribution-bar mini">
                                            <?php foreach (['6' => '#27ae60', '5' => '#2ecc71', '4' => '#f39c12', '3' => '#e67e22', '2' => '#e74c3c', '1' => '#c0392b'] as $ocena => $kolor): ?>
                                                <?php $procent = ($klasa['rozklad'][$ocena] / $suma) * 100; ?>
                                                <?php if ($procent > 0): ?>
                                                    <div class="distribution-segment" style="flex: <?php echo $procent; ?>; background-color: <?php echo $kolor; ?>;"
                                                         title="Ocena <?php echo $ocena; ?>: <?php echo $klasa['rozklad'][$ocena]; ?> (<?php echo round($procent); ?>%)">
                                                        <?php if ($procent > 8): ?><?php echo $ocena; ?><?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php elseif ($widok === 'nauczyciele'): ?>
                <!-- ============================================================
                     WIDOK: RANKING NAUCZYCIELI
                     ============================================================ -->
                <div class="grade-card">
                    <h3>Ranking nauczycieli według średniej ocen uczniów</h3>
                    <p class="info-text">
                        Ranking prezentuje średnie ocen wystawionych przez nauczycieli.
                        Wysoka średnia może oznaczać zarówno dobre wyniki uczniów, jak i łagodniejsze ocenianie.
                    </p>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th>Pozycja</th>
                                <th>Nauczyciel</th>
                                <th>Uczniów</th>
                                <th>Klas</th>
                                <th>Wystawionych ocen</th>
                                <th>Średnia ocen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statystyki['nauczyciele'] as $i => $nauczyciel): ?>
                                <tr>
                                    <td>
                                        <span class="rank-badge <?php echo $i < 3 ? 'rank-' . ($i + 1) : 'rank-other'; ?>">
                                            <?php echo $i + 1; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo e($nauczyciel['nauczyciel_nazwa']); ?></strong></td>
                                    <td><?php echo $nauczyciel['liczba_uczniow']; ?></td>
                                    <td>-</td>
                                    <td><?php echo $nauczyciel['liczba_ocen']; ?></td>
                                    <td>
                                        <span style="color: <?php echo kolor_oceny($nauczyciel['srednia_ocen']); ?>; font-weight: 700; font-size: 18px;">
                                            <?php echo number_format($nauczyciel['srednia_ocen'], 2); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($statystyki['nauczyciele'])): ?>
                                <tr>
                                    <td colspan="6" class="empty-state">Brak danych o nauczycielach</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Wykres porównawczy nauczycieli -->
                <?php if (!empty($statystyki['nauczyciele'])): ?>
                <div class="grade-card">
                    <h3>Porównanie nauczycieli</h3>
                    <div class="chart-container" style="height: 400px;">
                        <canvas id="teacherChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <?php elseif ($widok === 'zagrozeni'): ?>
                <!-- ============================================================
                     WIDOK: UCZNIOWIE ZAGROŻENI
                     ============================================================ -->
                <div class="grade-card warning-card">
                    <h3>Uczniowie wymagający wsparcia</h3>
                    <p class="info-text">
                        Lista uczniów ze średnią poniżej progu <?php echo pobierz_ustawienie_oceniania('prog_zagrozenia', 2.0); ?>.
                        Wymaga natychmiastowej interwencji pedagogicznej.
                    </p>

                    <?php if (!empty($zagrozeni)): ?>
                        <table class="ranking-table">
                            <thead>
                                <tr>
                                    <th>Uczeń</th>
                                    <th>Klasa</th>
                                    <th>Przedmiot</th>
                                    <th>Średnia</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($zagrozeni as $uczen): ?>
                                    <tr class="warning-row">
                                        <td><strong><?php echo e($uczen['uczen_nazwa']); ?></strong></td>
                                        <td><?php echo e($uczen['klasa_nazwa']); ?></td>
                                        <td><?php echo e($uczen['przedmiot_nazwa']); ?></td>
                                        <td>
                                            <span style="color: #e74c3c; font-weight: 700; font-size: 16px;">
                                                <?php echo number_format($uczen['srednia'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($uczen['srednia'] < 1.5): ?>
                                                <span class="status-badge critical">KRYTYCZNY</span>
                                            <?php else: ?>
                                                <span class="status-badge warning">ZAGROŻONY</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="summary-box">
                            <strong>Podsumowanie:</strong>
                            Łącznie <?php echo count($zagrozeni); ?> uczniów wymaga wsparcia.
                            <?php
                            $krytyczni = array_filter($zagrozeni, fn($u) => $u['srednia'] < 1.5);
                            if (count($krytyczni) > 0):
                            ?>
                            W tym <?php echo count($krytyczni); ?> w stanie krytycznym (średnia poniżej 1.5).
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="success-state">
                            <p>Brak uczniów zagrożonych w tym okresie.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($widok === 'trendy'): ?>
                <!-- ============================================================
                     WIDOK: TRENDY
                     ============================================================ -->
                <div class="grade-card">
                    <h3>Analiza trendów ocen</h3>
                    <?php if (!empty($trendy)): ?>
                        <div class="chart-container" style="height: 350px;">
                            <canvas id="trendDetailChart"></canvas>
                        </div>

                        <table class="ranking-table" style="margin-top: 30px;">
                            <thead>
                                <tr>
                                    <th>Miesiąc</th>
                                    <th>Średnia</th>
                                    <th>Liczba ocen</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $poprzednia_srednia = null;
                                foreach ($trendy as $t):
                                    $trend = null;
                                    if ($poprzednia_srednia !== null) {
                                        $roznica = $t['srednia'] - $poprzednia_srednia;
                                        if ($roznica > 0.1) $trend = 'up';
                                        elseif ($roznica < -0.1) $trend = 'down';
                                        else $trend = 'stable';
                                    }
                                ?>
                                    <tr>
                                        <td><strong><?php echo e($t['miesiac']); ?></strong></td>
                                        <td style="color: <?php echo kolor_oceny($t['srednia']); ?>; font-weight: 700;">
                                            <?php echo number_format($t['srednia'], 2); ?>
                                        </td>
                                        <td><?php echo $t['liczba_ocen']; ?></td>
                                        <td>
                                            <?php if ($trend === 'up'): ?>
                                                <span class="trend-indicator trend-up">▲ Poprawa</span>
                                            <?php elseif ($trend === 'down'): ?>
                                                <span class="trend-indicator trend-down">▼ Spadek</span>
                                            <?php elseif ($trend === 'stable'): ?>
                                                <span class="trend-indicator trend-stable">● Stabilnie</span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php
                                    $poprzednia_srednia = $t['srednia'];
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Brak danych do analizy trendów.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php endif; ?>

                <!-- Informacja RODO -->
                <div class="rodo-notice">
                    <strong>Informacja RODO:</strong> Dostęp do statystyk ocen jest rejestrowany zgodnie z wymogami
                    Rozporządzenia o Ochronie Danych Osobowych. Dane są przetwarzane wyłącznie w celach edukacyjnych
                    i zarządczych. W razie pytań skontaktuj się z Inspektorem Ochrony Danych.
                </div>
            </div>
        </main>
    </div>

    <script>
        <?php if ($widok === 'ogolne' && !empty($trendy)): ?>
        // Wykres trendów
        new Chart(document.getElementById('trendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($trendy, 'miesiac')); ?>,
                datasets: [{
                    label: 'Średnia szkoły',
                    data: <?php echo json_encode(array_column($trendy, 'srednia')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { min: 2, max: 5 }
                }
            }
        });
        <?php endif; ?>

        <?php if ($widok === 'klasy' && !empty($porownanie_klas)): ?>
        // Wykres porównania klas
        new Chart(document.getElementById('classCompareChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($porownanie_klas, 'klasa_nazwa')); ?>,
                datasets: [{
                    label: 'Średnia ważona',
                    data: <?php echo json_encode(array_column($porownanie_klas, 'srednia')); ?>,
                    backgroundColor: <?php echo json_encode(array_map(function($k) {
                        return kolor_oceny($k['srednia']) . '99';
                    }, $porownanie_klas)); ?>,
                    borderColor: <?php echo json_encode(array_map(function($k) {
                        return kolor_oceny($k['srednia']);
                    }, $porownanie_klas)); ?>,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { min: 2, max: 5 }
                }
            }
        });
        <?php endif; ?>

        <?php if ($widok === 'nauczyciele' && !empty($statystyki['nauczyciele'])): ?>
        // Wykres nauczycieli
        new Chart(document.getElementById('teacherChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column(array_slice($statystyki['nauczyciele'], 0, 15), 'nauczyciel_nazwa')); ?>,
                datasets: [{
                    label: 'Średnia ocen',
                    data: <?php echo json_encode(array_column(array_slice($statystyki['nauczyciele'], 0, 15), 'srednia_ocen')); ?>,
                    backgroundColor: 'rgba(102, 126, 234, 0.7)',
                    borderColor: '#667eea',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { min: 2, max: 5 }
                }
            }
        });
        <?php endif; ?>

        <?php if ($widok === 'trendy' && !empty($trendy)): ?>
        // Wykres szczegółowy trendów
        new Chart(document.getElementById('trendDetailChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($trendy, 'miesiac')); ?>,
                datasets: [{
                    label: 'Średnia',
                    data: <?php echo json_encode(array_column($trendy, 'srednia')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y'
                }, {
                    label: 'Liczba ocen',
                    data: <?php echo json_encode(array_column($trendy, 'liczba_ocen')); ?>,
                    borderColor: '#e67e22',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.3,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        min: 2,
                        max: 5,
                        title: { display: true, text: 'Średnia' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Liczba ocen' }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>

    <style>
        .tabs-navigation {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            background: white;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            flex-wrap: wrap;
        }

        .tab-link {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tab-link:hover {
            background: #f8f9fa;
            color: #667eea;
        }

        .tab-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .director-stats {
            background: white;
        }

        .main-stat {
            border-right: 2px solid #e9ecef;
            padding-right: 30px;
        }

        .warning-card {
            border-left: 4px solid #e74c3c;
        }

        .warning-row {
            background: #fff5f5 !important;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.critical {
            background: #e74c3c;
            color: white;
        }

        .status-badge.warning {
            background: #f39c12;
            color: white;
        }

        .summary-box {
            margin-top: 20px;
            padding: 15px 20px;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #f39c12;
        }

        .success-state {
            text-align: center;
            padding: 40px;
            background: #d4edda;
            border-radius: 10px;
            color: #155724;
        }

        .info-text {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .distribution-bar.mini {
            height: 20px;
            min-width: 150px;
        }

        .filter-card {
            padding: 15px 25px;
        }

        .filter-form {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group label {
            font-weight: 600;
        }

        .filter-group select {
            padding: 8px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .tabs-navigation {
                flex-direction: column;
            }

            .tab-link {
                text-align: center;
            }

            .main-stat {
                border-right: none;
                border-bottom: 2px solid #e9ecef;
                padding-right: 0;
                padding-bottom: 15px;
            }
        }
    </style>
</body>
</html>
