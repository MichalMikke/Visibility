<?php
// all words echoed on the website
$GLOBALS['words'] = array(
        // tools.php
        "toolbox" => array(
            "pl" => "Baza Narzędzi Inżynierskich",
            "en" => "Fire Engineering Toolbox"
        ),
        "footer" => array(
           "pl" => "Instytut Inżynierii Bezpieczeństwa, Akademia Pożarnicza w Warszawie",
           "en" => "Institute of Safety Engineering, Fire University, Warsaw"
        ),
        "graphics" => array(
           "pl" => "Grafiki autorstwa mimooh (Karol Kreński) CC BY-SA 3.0",
           "en" => "Graphics by mimooh (Karol Kreński) CC BY-SA 3.0"
        ),
        // visibility/index.php
        "send" => array(
            "head" => array(
                "pl" => "Przesyłanie Plików",
                "en" => "File Transfer"
            ),
            "hint" => array(
                "pl" => "Wgraj pliki niezbędne do obliczenia zasięgu widzialności",
                "en" => "Upload files necessary to adjusted visibility calculation"
            ),
            "send" => array(
                "pl" => "Wyślij",
                "en" => "Send"
            )
        ),
        "beam" => array(
            "head" => array(
                "pl" => "Wybór BEAM",
                "en" => "BEAM Selection"
            ),
            "hint" => array(
                "pl" => "Zaznacz lub odznacz, aby wybrać BEAM",
                "en" => "Check BEAMs to be processed"
            ),
            "send" => array(
                "pl" => "Przetwórz wybrane BEAM",
                "en" => "Process selected BEAMs"
            )
        ),
        "alarm" => array(
            "head" => array(
                "pl" => "Czas Alarmowania",
                "en" => "Alarm Time"
            ),
            "hint" => array(
                "pl" => "Potwierdź lub wprowadź czas alarmowania",
                "en" => "Confirm or alter alarm time"
            ),
            "alarm_text" => array(
                "pl" => "Czas alarmowania",
                "en" => "Alarm time"
            ),
            "send" => array(
                "pl" => "Generuj wykresy",
                "en" => "Generate plots"
            )
        ),
        "results" => array(
            "head" => array(
                "pl" => "Wyniki",
                "en" => "Results"
            ),
            "hint" => array(
                "pl" => "Dane przetworzone. Możesz teraz pobrać wykresy oraz usunąć swoje dane z serwera",
                "en" => "Data has been processed. You can download the plots and purge your data from the server"
            ),
            "check" => array(
                "pl" => "Usuń moje pliki z serwera natychmiast po ich pobraniu",
                "en" => "Remove my files from the server immediately after the download"
            ),
            "send" => array(
                "pl" => "Pobierz wyniki",
                "en" => "Download plots"
            )
        )
    );
function detectLang(){
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    if ($lang != 'pl'){
        $lang = 'en';
    }
    $_SESSION['LANG'] = $lang;
    return $lang;
}
function getWord($word){
    if (!isset($_SESSION['LANG'])){
        detectLang();
    }
    if (count($word) == 1) {
        return $GLOBALS['words'][$word[0]][$_SESSION['LANG']];
    }else{
        return $GLOBALS['words'][$word[0]][$word[1]][$_SESSION['LANG']];
    }

}

?>
