<?php
require_once '../includes/config.php';
sprawdz_uprawnienia('nauczyciel');

// Pobierz ID nauczyciela
$nauczyciel = $conn->query("
    SELECT id FROM nauczyciele WHERE uzytkownik_id = {$_SESSION['user_id']}
")->fetch_assoc();

$nauczyciel_id = $nauczyciel['id'];

// Pobierz tydzień
$tydzien_offset = intval($_GET['tydzien'] ?? 0);

// Oblicz daty tygodnia - NAPRAWIONE
$dzisiaj = new DateTime();
$dzien_tygodnia = $dzisiaj->format('N'); // 1 = poniedziałek, 7 = niedziela

// Oblicz poniedziałek bieżącego tygodnia
$poniedzialek_tego_tygodnia = clone $dzisiaj;
$poniedzialek_tego_tygodnia->modify('-' . ($dzien_tygodnia - 1) . ' days');

// Dodaj offset tygodni
$poniedzialek_docelowy = clone $poniedzialek_tego_tygodnia;
$poniedzialek_docelowy->modify(($tydzien_offset > 0 ? '+' : '') . $tydzien_offset . ' weeks');

// Oblicz piątek tego samego tygodnia
$piatek_docelowy = clone $poniedzialek_docelowy;
$piatek_docelowy->modify('+4 days');

$poczatek_tygodnia = $poniedzialek_docelowy->format('Y-m-d');
$koniec_tygodnia = $piatek_docelowy->format('Y-m-d');

// Pobierz plan
$plan_query = $conn->query("
    SELECT pd.*, p.nazwa as przedmiot, p.skrot,
           k.nazwa as klasa, s.numer as sala
    FROM plan_dzienny pd
    JOIN przedmioty p ON pd.przedmiot_id = p.id
    JOIN klasy k ON pd.klasa_id = k.id
    LEFT JOIN sale s ON pd.sala_id = s.id
    WHERE pd.nauczyciel_id = $nauczyciel_id
    AND pd.data >= '$poczatek_tygodnia'
    AND pd.data <= '$koniec_tygodnia'
    ORDER BY pd.data, pd.numer_lekcji
");

$plan = [];
while ($lekcja = $plan_query->fetch_assoc()) {
    $data = $lekcja['data'];
    $numer = $lekcja['numer_lekcji'];
    $plan[$data][$numer] = $lekcja;
}

$dni_tygodnia = [
    $poczatek_tygodnia => 'Poniedziałek',
    date('Y-m-d', strtotime($poczatek_tygodnia . ' +1 day')) => 'Wtorek',
    date('Y-m-d', strtotime($poczatek_tygodnia . ' +2 days')) => 'Środa',
    date('Y-m-d', strtotime($poczatek_tygodnia . ' +3 days')) => 'Czwartek',
    date('Y-m-d', strtotime($poczatek_tygodnia . ' +4 days')) => 'Piątek',
];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mój Plan - Panel Nauczyciela</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>System Planu Lekcji - Panel Nauczyciela</h1>
            <div class="user-info">
                <span>Witaj, <?php echo e($_SESSION['user_name']); ?>!</span>
                <a href="../logout.php" class="btn-logout">Wyloguj</a>
            </div>
        </header>
        
        <div class="content">
            <h2 class="page-title">Mój Plan Lekcji</h2>
            
            <div class="plan-header">
                <div class="week-navigation">
                    <button onclick="location.href='?tydzien=<?php echo $tydzien_offset - 1; ?>'">← Poprzedni tydzień</button>
                    <span class="current-week">
                        <?php echo formatuj_date($poczatek_tygodnia); ?> - <?php echo formatuj_date($koniec_tygodnia); ?>
                    </span>
                    <button onclick="location.href='?tydzien=<?php echo $tydzien_offset + 1; ?>'">Następny tydzień →</button>
                </div>
            </div>
            
            <div class="timetable">
                <table>
                    <thead>
                        <tr>
                            <th class="time-cell">Godzina</th>
                            <?php foreach ($dni_tygodnia as $data => $dzien): ?>
                                <th><?php echo $dzien; ?><br><small><?php echo formatuj_date($data); ?></small></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($lekcja_nr = 1; $lekcja_nr <= 8; $lekcja_nr++): ?>
                            <?php
                            $start_time = strtotime('08:00') + (($lekcja_nr - 1) * 55 * 60);
                            $end_time = $start_time + (45 * 60);
                            ?>
                            <tr>
                                <td class="time-cell">
                                    <strong><?php echo $lekcja_nr; ?>.</strong><br>
                                    <small><?php echo date('H:i', $start_time); ?> - <?php echo date('H:i', $end_time); ?></small>
                                </td>
                                <?php foreach ($dni_tygodnia as $data => $dzien): ?>
                                    <td class="lesson-cell">
                                        <?php if (isset($plan[$data][$lekcja_nr])): ?>
                                            <?php $l = $plan[$data][$lekcja_nr]; ?>
                                            <div class="lesson-card">
                                                <div class="lesson-subject"><?php echo e($l['przedmiot']); ?></div>
                                                <div class="lesson-teacher">Klasa: <?php echo e($l['klasa']); ?></div>
                                                <?php if ($l['sala']): ?>
                                                    <div class="lesson-room">Sala: <?php echo e($l['sala']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
