<?php
// session_handler.php

function check_inactivity() {
    session_start();

    // Ustawienie limitu nieaktywności na 15 minut (900 sekund)
    $inactivity_limit = 900;

    // Sprawdzenie, czy ustawiono znacznik czasu ostatniej aktywności
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > $inactivity_limit) {
            // Użytkownik był nieaktywny przez więcej niż 15 minut
            // Usuwanie danych użytkownika i kończenie sesji

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

            // Przekierowanie do strony głównej z komunikatem
            header('Location: index.php?error=Twoja sesja wygasła z powodu nieaktywności.');
            exit;
        }
    }

    // Aktualizacja znacznika czasu ostatniej aktywności
    $_SESSION['last_activity'] = time();
}
?>
