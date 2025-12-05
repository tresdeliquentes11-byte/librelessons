<?php
require_once '../includes/config.php';
sprawdz_uprawnienia('dyrektor');

$current_page = 'plan_edycja_ui.php';

// Pobierz listę klas
$klasy = [];
$result = $conn->query("SELECT id, nazwa FROM klasy ORDER BY nazwa");
while ($row = $result->fetch_assoc()) {
    $klasy[] = $row;
}

// Domyślna data - bieżący tydzień
$data_od = isset($_GET['data_od']) ? $_GET['data_od'] : pobierz_poczatek_tygodnia(date('Y-m-d'));
$data_do = isset($_GET['data_do']) ? $_GET['data_do'] : pobierz_koniec_tygodnia(date('Y-m-d'));
$klasa_id = isset($_GET['klasa_id']) ? (int)$_GET['klasa_id'] : null;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edycja Planu Lekcji - Panel Dyrektora</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        /* Dodatkowe style dla edytora */
        .editor-header {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .editor-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .editor-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .schedule-editor {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        .schedule-editor th,
        .schedule-editor td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .schedule-editor th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }

        .schedule-editor .lesson-cell {
            min-height: 80px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
            vertical-align: top;
            background: white;
        }

        .schedule-editor .lesson-cell.empty {
            background: #f9f9f9;
        }

        .schedule-editor .lesson-cell.empty:hover {
            background: #e3f2fd;
            border-color: #2196F3;
        }

        .schedule-editor .lesson-cell:not(.empty):hover {
            background-color: #f0f8ff;
            border-color: #4CAF50;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .schedule-editor .lesson-cell.dragging {
            opacity: 0.5;
            background: #ffebee;
        }

        .schedule-editor .lesson-cell.drag-over {
            background: #e8f5e9;
            border: 2px dashed #4CAF50;
        }

        .lesson-content {
            padding: 5px;
            font-size: 12px;
        }

        .lesson-content .przedmiot {
            font-weight: bold;
            color: #333;
            display: block;
            margin-bottom: 3px;
        }

        .lesson-content .nauczyciel {
            color: #666;
            display: block;
            margin-bottom: 3px;
        }

        .lesson-content .sala {
            color: #888;
            font-size: 11px;
            display: block;
        }

        .lesson-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            opacity: 0;
            transition: opacity 0.2s;
            display: flex;
            gap: 3px;
        }

        .lesson-cell:hover .lesson-actions {
            opacity: 1;
        }

        .lesson-actions button {
            background: white;
            border: 1px solid #ddd;
            border-radius: 3px;
            cursor: pointer;
            padding: 3px 6px;
            font-size: 14px;
        }

        .lesson-actions button:hover {
            background: #f5f5f5;
        }

        .lesson-actions .edit-btn:hover {
            color: #2196F3;
            border-color: #2196F3;
        }

        .lesson-actions .delete-btn:hover {
            color: #f44336;
            border-color: #f44336;
        }

        .time-column {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 120px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }

        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        .conflicts-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .conflict-item {
            padding: 10px;
            margin-bottom: 10px;
            border-left: 4px solid;
            background: #f9f9f9;
        }

        .conflict-nauczyciel {
            border-color: #f44336;
        }

        .conflict-sala {
            border-color: #ff9800;
        }

        .conflict-klasa {
            border-color: #2196F3;
        }

        .conflict-dostepnosc {
            border-color: #9c27b0;
        }

        .conflict-wymiar_godzin {
            border-color: #ff5722;
        }

        .loader {
            display: none;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4CAF50;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }

        .btn-primary:hover {
            background-color: #45a049;
        }

        .btn-warning {
            background-color: #ff9800;
            color: white;
        }

        .btn-warning:hover {
            background-color: #e68900;
        }

        .btn-info {
            background-color: #2196F3;
            color: white;
        }

        .btn-info:hover {
            background-color: #0b7dda;
        }

        .btn-success {
            background-color: #4CAF50;
            color: white;
        }

        .btn-success:hover {
            background-color: #45a049;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-main">
            <header class="admin-header">
                <h1>Edycja Planu Lekcji</h1>
                <div class="user-info">
                    <span>Witaj, <?php echo e($_SESSION['user_name']); ?>!</span>
                    <a href="../logout.php" class="btn-logout">Wyloguj</a>
                </div>
            </header>

            <div class="admin-content">
                <!-- Nagłówek edytora -->
                <div class="editor-header">
                    <div class="editor-controls">
                        <select id="klasa-select" class="form-control" style="min-width: 150px;">
                            <option value="">Wybierz klasę...</option>
                            <?php foreach ($klasy as $klasa): ?>
                                <option value="<?php echo $klasa['id']; ?>" <?php echo $klasa_id == $klasa['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($klasa['nazwa']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="date" id="data-od" value="<?php echo $data_od; ?>" class="form-control">
                        <input type="date" id="data-do" value="<?php echo $data_do; ?>" class="form-control">

                        <button id="zaladuj-plan" class="btn btn-primary">Załaduj Plan</button>
                    </div>

                    <div class="editor-actions">
                        <button id="cofnij-zmiane" class="btn btn-secondary" title="Cofnij ostatnią zmianę">
                            ↶ Cofnij
                        </button>
                        <button id="sprawdz-konflikty-btn" class="btn btn-warning">
                            ⚠ Sprawdź Konflikty
                        </button>
                    </div>
                </div>

                <!-- Alerty -->
                <div id="alert-container"></div>

                <!-- Loader -->
                <div id="loader" class="loader"></div>

                <!-- Tabela planu -->
                <div id="plan-container">
                    <p class="text-center" style="color: #999;">Wybierz klasę i okres, a następnie kliknij "Załaduj Plan"</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal edycji/dodawania lekcji -->
    <div id="lekcja-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Edytuj Lekcję</h3>
                <span class="close">&times;</span>
            </div>

            <form id="lekcja-form">
                <input type="hidden" id="form-plan-id">
                <input type="hidden" id="form-klasa-id">
                <input type="hidden" id="form-data">
                <input type="hidden" id="form-numer-lekcji">

                <div class="form-group">
                    <label for="form-przedmiot">Przedmiot:</label>
                    <select id="form-przedmiot" required>
                        <option value="">Wybierz przedmiot...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="form-nauczyciel">Nauczyciel:</label>
                    <select id="form-nauczyciel" required>
                        <option value="">Wybierz nauczyciela...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="form-sala">Sala:</label>
                    <select id="form-sala">
                        <option value="">Brak sali</option>
                    </select>
                </div>

                <div id="modal-alert-container"></div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">Zapisz</button>
                    <button type="button" id="sprawdz-konflikty-modal" class="btn btn-warning">Sprawdź Konflikty</button>
                    <button type="button" class="close-modal btn btn-secondary">Anuluj</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/plan_edytor.js"></script>
</body>
</html>
