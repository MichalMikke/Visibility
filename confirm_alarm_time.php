<!-- confirm_alarm_time.php -->
<?php
session_start();
if (!isset($_SESSION['alarm_time'])) {
    header('Location: index.php?error=Brak danych do przetworzenia.');
    exit;
}
$alarm_time = $_SESSION['alarm_time'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Potwierdź Czas Alarmu</title>
</head>
<body>
    <h1>Potwierdź Czas Alarmu</h1>
    <form action="generate_plots.php" method="POST">
        <label for="alarm_time">Czas Alarmu:</label>
        <input type="text" name="alarm_time" id="alarm_time" value="<?php echo htmlspecialchars($alarm_time); ?>" required><br><br>
        <button type="submit">Generuj wykresy</button>
    </form>
</body>
</html>
