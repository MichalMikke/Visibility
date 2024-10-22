<?php
function showFilesSubmissionForm(){
    $lang = "pl";
    $head = array(
        "pl" => "<h1>Przesyłanie Plików</h1>",
        "en" => "<h1>Files Submission</h1>",
    );
    $send = array(
        "pl" => "Wyślij",
        "en" => "Send",
    );

    $form = "<form method='POST' enctype='multipart/form-data' action='?stage=send_files'>
        <table>
            <tr><td><i>*_devc.csv</i>   </td>
              <td><input type='file' name='devc_file' id='devc_file' accept='.csv' required></td></tr>
            <tr><td><i>*.fds</i>
              <td><input type='file' name='fds_file' id='fds_file' accept='.fds,.txt' required></td></tr>
            </table>
        <input type='submit' name='send_files' value='$send[$lang]'>
        </form>";
    echo $head[$lang];
    echo $form;
}

function showBeamSelectionForm(){
    $lang = "pl";
    $head = array(
        "pl" => "<h1>Wybranie BEAM</h1>",
        "en" => "<h1>BEAM Selection</h1>",
    );
    $send = array(
        "pl" => "Wyślij",
        "en" => "Send",
    );
    $form = "
        <form method='POST' action='?stage=select_beams'>
            <p>Zaznacz lub odznacz, aby wybrać BEAM.</p>
            ";
    foreach ($_SESSION['beams'] as $beam){
        $spec_beam = htmlspecialchars($beam);
        $form .= "
            <input type='checkbox' name='beam_selection[]' value='$spec_beam' checked>
            $spec_beam
        ";
        }
    $form .= "
            <input type='hidden' name='beam_order' id='beam_order'>
            <br>
            <button type='submit'>Przetwórz wybrane BEAM</button>
        </form>
    ";

    echo $head[$lang];
    echo $form;
}

function showAlarmTimeForm(){
    if (!isset($_SESSION['alarm_time'])) {
        header('Location: index.php?error=Brak danych do przetworzenia.');
        exit;
    }
    $alarm_time = $_SESSION['alarm_time'];


    $head = array(
        "pl" => "<h1>Potwierdź czas alarmowania</h1>",
        "en" => "<h1>Confirm alarm time</h1>",
    );

    $form = "
    <form action='?stage=confirm_alarm' method='POST'>
        <label for='alarm_time'>Czas Alarmu:</label>
        <input type='text' name='alarm_time' id='alarm_time' value='$alarm_time' required><br><br>
        <button type='submit'>Generuj wykresy</button>
    </form>";

    echo $head[$lang];
    echo $form;

}

function showDownloadForm(){
    $form = "<form action='?stage=download' method='POST'>
        <input type='checkbox' name='purge' value='purge' checked>Usuń moje pliki z serwera po ich pobraniu
        <input type='hidden' name='download' value='download'/>
        <br>
        <button type='submit'>Pobierz wyniki</button>
        <br>
    ";
    $pic = "<img src='uploads/".$_SESSION['name']."/visibility_cut.png' alt='visibility_cut.png'>";

    echo $form;
    echo $pic;
}

// Generowanie unikalnego identyfikatora dla użytkownika
function setUID(){
    if (!isset($_SESSION['name'])) {
        $_SESSION['name'] = uniqid();
    }
    return $_SESSION['name'];
}

function checkSubmission(){
    if (!isset($_FILES['devc_file']) || !isset($_FILES['fds_file'])) {
        raiseError('Attach both files first!');
        exit;
    }

    // Sprawdzenie rozszerzenia pliku devc.csv
    $devc_extension = strtolower(pathinfo($_FILES['devc_file']['name'], PATHINFO_EXTENSION));
    if ($devc_extension !== 'csv') {
        raiseError('CSV extension required for *_devc.csv file!');
        exit;
    }

    // Sprawdzenie rozszerzenia pliku fds.txt
    $fds_extension = strtolower(pathinfo($_FILES['fds_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($fds_extension, ['fds', 'txt'])) {
        raiseError('FDS extension required for *.fds file!');
        exit;
    }
}

function launchPreProcess(){
    // Tworzenie folderów uploads/{user_id}
    $upload_dir = 'uploads/' . $_SESSION['name'] . '/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Zapisywanie plików
    $_SESSION['devc_path'] = $upload_dir . 'devc.csv';
    $_SESSION['fds_path'] = $upload_dir . 'fds.txt';

    if (move_uploaded_file($_FILES['devc_file']['tmp_name'], $_SESSION['devc_path']) &&
        move_uploaded_file($_FILES['fds_file']['tmp_name'], $_SESSION['fds_path'])) {
        // Aktualizacja czasu modyfikacji katalogu
        touch($upload_dir);

        // Wywołanie skryptu Pythona z akcją 'preprocess'
        $command = escapeshellcmd("../venv/bin/python3 ../app.py preprocess --devc ".$_SESSION['devc_path']." --fds ".$_SESSION['fds_path']);
        $output = shell_exec($command);
        $result = json_decode($output, true);

        if ($result['success']) {
            $_SESSION['beams'] = $result['beams'];
            showBeamSelectionForm();
            exit;
        } else {
            $error = $result['message'];
            echo "py-error $command $error";
            exit;
        }
    } else {
        echo "send-err";//header('Location: index.php?error=send-err');
        exit;
    }
}

function launchProcess(){
    // Aktualizacja czasu modyfikacji katalogu
    touch($upload_dir);
    // Wywołanie skryptu Pythona z akcją 'preprocess'
    $command = escapeshellcmd("../venv/bin/python3 ../app.py process --devc ".$_SESSION['devc_path']." --fds ".$_SESSION['fds_path']);
    $output = shell_exec($command);
    $result = json_decode($output, true);

    if ($result['success']) {
        $_SESSION['alarm_time'] = $result['alarm_time'];
        showAlarmTimeForm();
        exit;
    } else {
            $error = $result['message'];
            echo "py-error $command $error";
        exit;
    }
}

function launchPostProcess(){
    // Aktualizacja czasu modyfikacji katalogu
    touch($upload_dir);

    // Wywołanie skryptu Pythona z akcją 'preprocess'
    $command = "../venv/bin/python3 ../app.py generate -b ";
    $command .= " --devc ".$_SESSION['devc_path'];
    $command .= " --fds ".$_SESSION['fds_path'];
    $command .= " --devc ".$_SESSION['devc_path'];
    $command .= " --alarm_time ".$_SESSION['alarm_time'];
    $beams_string = implode(",",$_SESSION['beam_selection']);
    $command .= " --ordered_beams ".$beams_string;    // no need for order?
    $command .= " --results_folder uploads/".$_SESSION['name'];
    $command = escapeshellcmd($command);
    $output = shell_exec($command);
    $result = json_decode($output, true);

    if ($result['success']) {
        showDownloadForm ();
        exit;
    } else {
            $error = $result['message'];
            echo "py-error $command $error";
        exit;
    }
}

function zipAndDownload(){
    $results_folder = "uploads/".$_SESSION['name'];
    $zipname = $results_folder."/visibility.zip";
    $zip = new ZipArchive;
    if ($zip->open($zipname, ZipArchive::CREATE|ZipArchive::OVERWRITE) === TRUE) {
        $files = scandir($results_folder);
        foreach ($files as $file) {
            if (is_file($results_folder."/".$file)) {
                $zip->addFile($results_folder."/".$file, $file);
            }
        }
    }
    $zip->close();

    ob_clean();
    ob_end_flush();
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=visibility.zip');
    header('Content-Length: '.filesize($zipname));
    readfile($zipname);
    unlink($zipname);
}

function purgeData(){
    $results_folder = "uploads/".$_SESSION['name'];
    $files = scandir($results_folder);
    foreach ($files as $file) {
            echo $file;
            unlink($results_folder."/".$file);
    }
    rmdir($results_folder);
    header('Location: index.php');
    exit;
}

function raiseError(){
    echo '<div style="color: red;">' . htmlspecialchars($_GET['error']) . '</div>';
    unset($_GET['error']);
}

function echoArrays(){
    echo "<br>post ";
    print_r($_POST);
    echo "<br>get ";
    print_r($_GET);
    echo "<br>session ";
    print_r($_SESSION);
    echo "<br>";
}

function showHeader(){
    echo "<a href='index.php'><img src='home.svg' alt='Home'></a>";
    echo "<a href='https://aamks.apoz.edu.pl/'><img src='tools.svg' alt='Tools'></a>";
}

function main(){
    if(!isset($_SESSION['name'])){
        session_start();
        setUID();
    }

    showHeader();
    //echoArrays();  //for developers only

    if(isset($_GET['stage'])){
        switch ($_GET['stage']){
            case 'send_files':
                checkSubmission();
                launchPreProcess();
                break;
            case 'select_beams':
                $_SESSION['beam_selection'] = $_POST['beam_selection'];
                launchProcess();
                break;
            case 'confirm_alarm':
                $_SESSION['alarm_time'] = $_POST['alarm_time'];
                launchPostProcess();
                break;
            case 'download':
                zipAndDownload();
                if (isset($_POST['purge'])){ purgeData();}
                break;
                }
    }elseif(isset($_GET['error'])){
        raiseError();
    }else{
        showFilesSubmissionForm();

    }
}

main();

?>