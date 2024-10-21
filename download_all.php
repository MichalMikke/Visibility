<?php
// download_all.php
session_start();
if (!isset($_SESSION['results_folder'])) {
    header('Location: index.php?error=Brak wyników do pobrania.');
    exit;
}
$results_folder = $_SESSION['results_folder'];
$zipname = 'results.zip';
$zip = new ZipArchive;
if ($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
    $files = scandir($results_folder);
    foreach ($files as $file) {
        if (is_file($results_folder . $file)) {
            $zip->addFile($results_folder . $file, $file);
        }
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=' . $zipname);
    header('Content-Length: ' . filesize($zipname));
    readfile($zipname);
    unlink($zipname);
    exit;
} else {
    header('Location: index.php?error=Nie udało się utworzyć archiwum ZIP.');
    exit;
}
