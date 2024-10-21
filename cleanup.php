<?php
// cleanup.php

// Ustawienie limitu nieaktywności na 15 minut (900 sekund)
$inactivity_limit = 900;

// Ścieżki do głównych katalogów
$uploads_dir = __DIR__ . '/uploads/';
$results_dir = __DIR__ . '/results/';

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

// Funkcja do czyszczenia nieużywanych katalogów
function cleanup_directories($parent_dir, $inactivity_limit) {
    if (!is_dir($parent_dir)) return;
    $user_dirs = array_diff(scandir($parent_dir), array('.', '..'));
    $current_time = time();

    foreach ($user_dirs as $user_dir) {
        $dir_path = $parent_dir . $user_dir . '/';
        if (is_dir($dir_path)) {
            // Sprawdzenie czasu modyfikacji katalogu
            $last_modified = filemtime($dir_path);
            $inactive_time = $current_time - $last_modified;
            if ($inactive_time > $inactivity_limit) {
                // Usuwanie katalogu
                delete_directory($dir_path);
                echo "Usunięto katalog: $dir_path\n";
            }
        }
    }
}

// Czyszczenie katalogów uploads i results
cleanup_directories($uploads_dir, $inactivity_limit);
cleanup_directories($results_dir, $inactivity_limit);
?>
