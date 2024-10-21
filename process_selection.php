<?php
// process_selection.php
session_start();

if (!isset($_POST['beam_order'])) {
    header('Location: index.php?error=Nie wybrano żadnych elementów BEAM.');
    exit;
}

$beam_order = explode(',', $_POST['beam_order']);
$_SESSION['ordered_beams'] = $beam_order;

$devc_path = $_SESSION['devc_path'];
$fds_path = $_SESSION['fds_path'];
$user_id = $_SESSION['user_id'];

// Wywołanie skryptu Pythona z akcją 'process'
$command = escapeshellcmd("python app.py process --devc $devc_path --fds $fds_path");
$output = shell_exec($command);
$result = json_decode($output, true);

if ($result['success']) {
    $_SESSION['alarm_time'] = $result['alarm_time'];
    header('Location: confirm_alarm_time.php');
    exit;
} else {
    $error = $result['message'];
    header('Location: index.php?error=' . urlencode($error));
    exit;
}
