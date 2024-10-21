<!-- index.php -->
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Przesyłanie Plików</title>
</head>
<body>
    <h1>Przesyłanie Plików</h1>
    <?php
    if (isset($_GET['error'])) {
        echo '<div style="color: red;">' . htmlspecialchars($_GET['error']) . '</div>';
    }
    ?>
    <form action="process_upload.php" method="POST" enctype="multipart/form-data">
        <label for="devc_file">Plik devc.csv:</label>
        <input type="file" name="devc_file" id="devc_file" accept=".csv" required><br><br>

        <label for="fds_file">Plik .fds:</label>
        <input type="file" name="fds_file" id="fds_file" accept=".fds,.txt" required><br><br>

        <button type="submit">Wyślij</button>
    </form>
</body>
</html>
