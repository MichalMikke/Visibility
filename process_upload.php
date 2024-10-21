<?php
// process_upload.php
require_once 'session_handler.php';
check_inactivity();

// Generowanie unikalnego identyfikatora dla użytkownika
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = uniqid();
}
$user_id = $_SESSION['user_id'];

if (!isset($_FILES['devc_file']) || !isset($_FILES['fds_file'])) {
    header('Location: index.php?error=Nie wybrano plików.');
    exit;
}

// Sprawdzenie rozszerzenia pliku devc.csv
$devc_extension = strtolower(pathinfo($_FILES['devc_file']['name'], PATHINFO_EXTENSION));
if ($devc_extension !== 'csv') {
    header('Location: index.php?error=Plik devc.csv musi mieć rozszerzenie .csv.');
    exit;
}

// Sprawdzenie rozszerzenia pliku fds.txt
$fds_extension = strtolower(pathinfo($_FILES['fds_file']['name'], PATHINFO_EXTENSION));
if (!in_array($fds_extension, ['fds', 'txt'])) {
    header('Location: index.php?error=Plik .fds musi mieć rozszerzenie .fds lub .txt.');
    exit;
}

// Tworzenie folderów uploads/{user_id}
$upload_dir = 'uploads/' . $user_id . '/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Zapisywanie plików
$devc_path = $upload_dir . 'devc.csv';
$fds_path = $upload_dir . 'fds.txt';

if (move_uploaded_file($_FILES['devc_file']['tmp_name'], $devc_path) &&
    move_uploaded_file($_FILES['fds_file']['tmp_name'], $fds_path)) {

    // Aktualizacja czasu modyfikacji katalogu
    touch($upload_dir);

    // Wywołanie skryptu Pythona z akcją 'preprocess'
    $command = escapeshellcmd("python app.py preprocess --devc $devc_path --fds $fds_path");
    $output = shell_exec($command);
    $result = json_decode($output, true);

    if ($result['success']) {
        $_SESSION['devc_path'] = $devc_path;
        $_SESSION['fds_path'] = $fds_path;
        $_SESSION['beams'] = $result['beams'];
        header('Location: select_beams.php');
        exit;
    } else {
        $error = $result['message'];
        header('Location: index.php?error=' . urlencode($error));
        exit;
    }
} else {
    header('Location: index.php?error=Nie udało się przesłać plików.');
    exit;
}
