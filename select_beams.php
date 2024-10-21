<!-- select_beams.php -->
<?php
// select_beams.php
require_once 'session_handler.php';
check_inactivity();

if (!isset($_SESSION['beams'])) {
    header('Location: index.php?error=Brak danych do przetworzenia.');
    exit;
}
$beams = $_SESSION['beams'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wybór BEAM</title>
    <style>
        .beam-list {
            list-style-type: none;
            padding: 0;
            width: 300px;
        }
        .beam-list li {
            margin: 5px 0;
            padding: 10px;
            background-color: #ddd;
            position: relative;
            display: flex;
            align-items: center;
        }
        .move-buttons {
            margin-left: auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 90%;
        }
        .move-buttons button {
            width: 30px;
            height: 45%;
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            background: none;
            border: none;
        }
        .move-buttons button:focus {
            outline: none;
        }
    </style>
</head>
<body>
    <h1>Wybierz BEAM do przetworzenia</h1>
    <form action="process_selection.php" method="POST">
        <p>Użyj przycisków ze strzałkami, aby ustawić kolejność. Zaznacz lub odznacz, aby wybrać BEAM.</p>
        <ul class="beam-list" id="beamList">
            <?php foreach ($beams as $beam): ?>
                <li>
                    <input type="checkbox" name="beam_selection[]" value="<?php echo htmlspecialchars($beam); ?>" checked>
                    <?php echo htmlspecialchars($beam); ?>
                    <div class="move-buttons">
                        <button type="button" class="move-up">&#9650;</button>
                        <button type="button" class="move-down">&#9660;</button>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <input type="hidden" name="beam_order" id="beam_order">
        <button type="submit">Przetwórz wybrane BEAM</button>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const beamList = document.getElementById('beamList');

            beamList.addEventListener('click', function(e) {
                if (e.target.classList.contains('move-up')) {
                    const li = e.target.closest('li');
                    if (li.previousElementSibling) {
                        beamList.insertBefore(li, li.previousElementSibling);
                    }
                }
                if (e.target.classList.contains('move-down')) {
                    const li = e.target.closest('li');
                    if (li.nextElementSibling) {
                        beamList.insertBefore(li.nextElementSibling, li);
                    }
                }
            });

            document.querySelector('form').addEventListener('submit', function() {
                var selectedBeams = [];
                const listItems = beamList.querySelectorAll('li');
                listItems.forEach(function(li) {
                    var checkbox = li.querySelector('input[type="checkbox"]');
                    if (checkbox.checked) {
                        selectedBeams.push(checkbox.value);
                    }
                });
                document.getElementById('beam_order').value = selectedBeams.join(',');
            });
        });
    </script>
</body>
</html>
