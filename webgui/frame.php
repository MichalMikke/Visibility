<?php
require_once('words.php');

function showHeader($header){
    echo "<div class='header'>";
        echo "<a href='index.php'><img src='img/home.png' alt='Home' class='header-picture'></a>";
        echo "<a href='https://aamks.apoz.edu.pl/'><img src='img/tools_".$_SESSION['LANG'].".png' alt='Tools' class='header-picture'></a>";
        echo "<div class='header-text'>";
            echo "$header";
        echo "</div>";
    echo "</div>";
}
function showFooter($extra_content=""){
    echo "<div class='footer'>";
        echo getWord(["footer"])."<br>";
        echo "<a href=mailto:wkowalski@apoz.edu.pl>wkowalski@apoz.edu.pl</a>";
        echo "<br><br>".$extra_content;
    echo "</div>";
}
function showForm($head, $form){
    $form .= "";
    showHeader($head);
    echo "<div class='content'>
        $form
        </div>";
    showFooter();
}
?>
