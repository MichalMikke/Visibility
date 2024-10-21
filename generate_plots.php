<?php
// generate_plots.php
session_start();

if (!isset($_POST['alarm_time'])) {
    header('Location: index.php?error=Nie podano czasu alarmu.');
    exit;
}

$alarm_time = $_POST['alarm_time'];

if (!is_numeric($alarm_time)) {
    header('Location: index.php?error=Nieprawidłowy format czasu alarmu.');
    exit;
}

$ordered_beams = $_SESSION['ordered_beams'];
$devc_path = $_SESSION['devc_path'];
$fds_path = $_SESSION['fds_path'];
$user_id = $_SESSION['user_id'];

$ordered_beams_str = implode(',', $ordered_beams);

// Tworzenie unikalnego katalogu na wyniki
$results_folder = 'results/' . $user_id . '/';
if (!is_dir($results_folder)) {
    mkdir($results_folder, 0777, true);
}

// Wywołanie skryptu Pythona z akcją 'generate'
$command = escapeshellcmd("python app.py generate --devc $devc_path --fds $fds_path --alarm_time $alarm_time --ordered_beams $ordered_beams_str --results_folder $results_folder");
$output = shell_exec($command);
$result = json_decode($output, true);

if ($result['success']) {
    $_SESSION['result_files'] = $result['result_files'];
    $_SESSION['results_folder'] = $results_folder;
    header('Location: results.php');
    exit;
} else {
    $error = $result['message'];
    header('Location: index.php?error=' . urlencode($error));
    exit;
}
