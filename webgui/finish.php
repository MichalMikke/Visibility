<?php
// finish.php
session_start();

// Usuwanie plików użytkownika
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Ścieżki do katalogów użytkownika
    $upload_dir = 'uploads/' . $user_id . '/';
    $results_dir = 'results/' . $user_id . '/';

    // Funkcja do usuwania katalogów
    function delete_directory($dir) {
        if (!file_exists($dir)) return;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = "$dir/$file";
            if (is_dir($path)) {
                delete_directory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    // Usuwanie katalogów użytkownika
    delete_directory($upload_dir);
    delete_directory($results_dir);
}

// Zniszczenie sesji
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zakończono</title>
</head>
<body>
    <h1>Dziękujemy za skorzystanie z aplikacji.</h1>
    <p>Wszystkie Twoje dane zostały usunięte.</p>
    <a href="index.php">Powrót do strony głównej</a>
</body>
</html>
