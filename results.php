<!-- results.php -->
<?php
session_start();
if (!isset($_SESSION['result_files'])) {
    header('Location: index.php?error=Brak wyników do wyświetlenia.');
    exit;
}
$result_files = $_SESSION['result_files'];
$results_folder = $_SESSION['results_folder'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wyniki</title>
</head>
<body>
    <h1>Wyniki</h1>
    <ul>
        <?php foreach ($result_files as $file): ?>
            <li><a href="<?php echo $results_folder . urlencode($file); ?>" target="_blank"><?php echo htmlspecialchars($file); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <a href="download_all.php">Pobierz wszystkie jako ZIP</a><br><br>
    <form action="finish.php" method="post">
        <button type="submit">Zakończ</button>
    </form>
</body>
</html>
