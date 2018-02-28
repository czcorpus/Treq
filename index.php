<?php
require_once (__DIR__ . "/db.php");
require_once (__DIR__ . "/lib/t8r.php");
require_once (__DIR__ . "/definitions.php");
$jsonTr = file_get_contents(__DIR__ . "/tr/en.json");


$version = VERSION;
$corpus = CORPUS;
$log = null;
$query = null;

T8r::loadTranslations(json_decode($jsonTr, true));

ini_set('error_reporting', 'E_ALL & ~E_NOTICE');
# Session initialization
session_name('Treq_SID');
session_start();
$ctimeout = time() + 60 * 60 * 24 * 30 * 12;
setcookie(session_name(), session_id(), $ctimeout);
$cnc_toolbar_lang = filter_input(INPUT_COOKIE, 'cnc_toolbar_lang', FILTER_SANITIZE_STRING);
$cnc_toolbar_sid = filter_input(INPUT_COOKIE, 'cnc_toolbar_sid', FILTER_SANITIZE_STRING);
$cnc_toolbar_at = filter_input(INPUT_COOKIE, 'cnc_toolbar_at', FILTER_SANITIZE_STRING);
$cnc_toolbar_rmme = filter_input(INPUT_COOKIE, 'cnc_toolbar_rmme', FILTER_SANITIZE_STRING);

// Language
$cnc_toolbar_lang = ((isset($cnc_toolbar_lang) && $cnc_toolbar_lang != "" ) ? $cnc_toolbar_lang : "cs");
// Session ID
$cnc_toolbar_sid  = (isset($cnc_toolbar_sid)  ? $cnc_toolbar_sid  : "");
// Authentication token
$cnc_toolbar_at   = (isset($cnc_toolbar_at)   ? $cnc_toolbar_at   : "");
// Remember me
$cnc_toolbar_rmme = (isset($cnc_toolbar_rmme) ? $cnc_toolbar_rmme : "");

$toolbarRequest = "https://www.korpus.cz/toolbar/toolbar?" . http_build_query(array(
	"sid"      => $cnc_toolbar_sid,
	"current"  => "treq",
	"lang"     => $cnc_toolbar_lang,
	"continue" => "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
	"at"       => $cnc_toolbar_at,
	"rmme"     => $cnc_toolbar_rmme,
));
$toolbar = json_decode(file_get_contents($toolbarRequest), TRUE);

if (isset($toolbar["redirect"])) {
	header("Location: " . $toolbar["redirect"]);
}

T8r::setDomain("global");
T8r::setLang($cnc_toolbar_lang);

// TODO: V případě zavedení dalších primárních jazyků už by to asi chtělo načítat z databáze, zatím to stačí takhle
$_SESSION["primaries"]["en"] = T8r::tr("Angličtina");
$_SESSION["primaries"]["cs"] = T8r::tr("Čeština");

if (!isset($_SESSION["leftLang"])) {
    $_SESSION["leftLang"] = 'cs';
    $_SESSION["rightLang"] = 'en';
    if(isset($_COOKIE["Treq_left_lang"])) {
        $_SESSION["leftLang"] = filter_input(INPUT_COOKIE, 'Treq_left_lang', FILTER_SANITIZE_STRING);
        $_SESSION["rightLang"] = filter_input(INPUT_COOKIE, 'Treq_right_lang', FILTER_SANITIZE_STRING);
        $_SESSION["lemma"] = filter_input(INPUT_COOKIE, 'Treq_lemma', FILTER_SANITIZE_STRING);
        $_SESSION["viceslovne"] = filter_input(INPUT_COOKIE, 'Treq_viceslovne', FILTER_SANITIZE_STRING);
        $_SESSION["hledejCo"] = filter_input(INPUT_COOKIE, 'Treq_hledejCo', FILTER_SANITIZE_STRING);
        $_SESSION["regularni"] = filter_input(INPUT_COOKIE, 'Treq_regularni', FILTER_SANITIZE_STRING);
        $_SESSION["hledejKde"] = filter_input(INPUT_COOKIE, 'Treq_hledejKde', FILTER_SANITIZE_STRING);
        $_SESSION["caseInsen"] = filter_input(INPUT_COOKIE, 'Treq_aJeA', FILTER_SANITIZE_STRING);
    }
}

if (filter_input(INPUT_POST, 'jazyk2') != null) {
    $_SESSION["leftLang"] = filter_input(INPUT_POST, 'jazyk1', FILTER_SANITIZE_SPECIAL_CHARS);
    $_SESSION["rightLang"] = filter_input(INPUT_POST, 'jazyk2', FILTER_SANITIZE_SPECIAL_CHARS);
    $_SESSION["hledejCo"] = filter_input(INPUT_POST, 'hledejCo', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
    $tmp;
    if(($tmp = filter_input(INPUT_POST, 'lemma', FILTER_SANITIZE_SPECIAL_CHARS)) == null) {
        $_SESSION["lemma"] = 0;
    } else {
        $_SESSION["lemma"] = $tmp;
    }
    if(($tmp = filter_input(INPUT_POST, 'viceslovne', FILTER_SANITIZE_SPECIAL_CHARS)) == null) {
        $_SESSION["viceslovne"] = 0;
    } else {
        $_SESSION["viceslovne"] = $tmp;
    }
    if(($tmp = filter_input(INPUT_POST, 'regularni', FILTER_SANITIZE_SPECIAL_CHARS)) == null) {
        $_SESSION["regularni"] = 0;
    } else {
        $_SESSION["regularni"] = $tmp;
    }
    if(($tmp = filter_input(INPUT_POST, 'hledejKde', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY)) == null) {
        $_SESSION["hledejKde"] = array();
    } else {
        $_SESSION["hledejKde"] = $tmp;
    }
    if(($tmp = filter_input(INPUT_POST, 'caseInsen', FILTER_SANITIZE_SPECIAL_CHARS)) == null) {
        $_SESSION["caseInsen"] = 0;
    } else {
        $_SESSION["caseInsen"] = $tmp;
    }
   
    setcookie('Treq_primary', $_SESSION["leftLang"], $ctimeout);
    setcookie('Treq_lang', $_SESSION["rightLang"], $ctimeout);
    setcookie('Treq_lemma', $_SESSION["lemma"], $ctimeout);
    setcookie('Treq_viceslovne', $_SESSION["viceslovne"], $ctimeout);
    setcookie('Treq_hledejCo', $_SESSION["hledejCo"], $ctimeout);
    setcookie('Treq_regularni', $_SESSION["regularni"], $ctimeout);
    setcookie('Treq_hledejKde', $_SESSION["hledejKde"], $ctimeout);
    setcookie('Treq_aJeA', $_SESSION["caseInsen"], $ctimeout);
    // log Dotaz
    $log = 'D';
}


$_SESSION["leftLangs"] = db::getInstance()->getLeftLangs($_SESSION["primaries"], $cnc_toolbar_lang);

if (isset($_SESSION["primaries"][$_SESSION["leftLang"]])) {
    $primary = $_SESSION["leftLang"];
    $lang = $_SESSION["rightLang"];
    $leftColl = 'primary';
    $rightColl = 'other';
    $_SESSION["rightLangs"] = db::getInstance()->getRightLangs($primary, $cnc_toolbar_lang);
}
else {
    $primary = $_SESSION["rightLang"];
    $lang = $_SESSION["leftLang"];
    $leftColl = 'other';
    $rightColl = 'primary';
    $_SESSION["rightLangs"] = array_merge($_SESSION["primaries"]);
}  

if (filter_input(INPUT_POST, 'jazyk2') != null) {
//    if($_SESSION["hledejKde"] != "All") {
//        $collections = db::getInstance()->getCollections($primary, $lang, $cnc_toolbar_lang);
//        $hkde = $collections[$_SESSION["hledejKde"]];
//        if ($hkde=="Presseurop") {
//            $hkde = "PressEurop";
//        }
//    } else {
//        $hkde = "All"; 
//    }
    //leftLang\trightLang\tviceslovne\tlemma\tsubcorpus\tregularni\tcaseInsen\thledejCo\tleft(prazdne)\tright(prazdne)
    $DdataPack = "";
    $temp = $_SESSION["hledejKde"];
    foreach ($temp as $value) {
        $DdataPack .= $value . "|";
    }
    $DdataPack = substr($DdataPack, 0, -1);
    
    $query = $_SESSION["leftLang"] . "\t" . $_SESSION["rightLang"] . "\t" . $_SESSION["viceslovne"] . "\t" . $_SESSION["lemma"] . "\t" . $DdataPack . "\t" . $_SESSION["regularni"] . "\t" . $_SESSION["caseInsen"] . "\t" . $_SESSION["hledejCo"] . "\t\t";
}

if (filter_input(INPUT_GET, 'order') == null) {
    $_SESSION["sort"] = "freq DESC";
} else {
    switch (filter_input(INPUT_GET, 'order')) {
        case "freqAsc":
            $_SESSION["sort"] = "freq ASC";
            break;
        case "freqDesc":
            $_SESSION["sort"] = "freq DESC";
            break;
        case "percAsc":
            $_SESSION["sort"] = "perc ASC";
            break;
        case "percDesc":
            $_SESSION["sort"] = "perc DESC";
            break;
        case "leftAsc":
            $_SESSION["sort"] = "`$leftColl` COLLATE " . ( $_SESSION["leftLang"] == "cs" ? "utf8_czech_ci" : "utf8_general_ci" ) . " ASC, freq DESC";
            break;
        case "leftDesc":
            $_SESSION["sort"] = "`$leftColl` COLLATE " . ( $_SESSION["leftLang"] == "cs" ? "utf8_czech_ci" : "utf8_general_ci" ) . " DESC, freq DESC";
            break;
        case "rightAsc":
            $_SESSION["sort"] = "`$rightColl` COLLATE " . ( $_SESSION["rightLang"] == "cs" ? "utf8_czech_ci" : "utf8_general_ci" ) . " ASC, freq DESC";
            break;
        case "rightDesc":
            $_SESSION["sort"] = "`$rightColl` COLLATE " . ( $_SESSION["rightLang"] == "cs" ? "utf8_czech_ci" : "utf8_general_ci" ) . " DESC, freq DESC";
            break;
        default:
            $_SESSION["sort"] = "freq DESC";
            break;
    }
}

$link = null;

if (filter_input(INPUT_GET, 'link') == "true") {
    $Gcorpus = filter_input(INPUT_GET, 'corpname', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
    $Glemma = filter_input(INPUT_GET, 'lemma', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
    $Gquery1 = filter_input(INPUT_GET, 'query1', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
    $Gquery2 = filter_input(INPUT_GET, 'query2', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
    $Gleft = filter_input(INPUT_GET, 'left', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
    $Gright = filter_input(INPUT_GET, 'right', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
    $GdataPack = filter_input(INPUT_GET, 'dataPack', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
    
    // Prep CQL query
    $temp = explode(" ", $GdataPack);
    $GdataPack = "";
    foreach ($temp as $value) {
        $GdataPack .= $value . "|";
    }
    $GdataPack = substr($GdataPack, 0, -1);
    
    $temp1 = explode(" ", $Gquery1);
    $temp2 = explode(" ", $Gquery2);
    // Left side main
    $Gquery = "aword,[" . ($Glemma==1?"lemma":"word") . "=\"" . $temp1[0] . "\"] ";
    // within div group datapack
    $Gquery .= "within <div group=\"$GdataPack\" /> ";
    // right side main
    $Gquery .= "within ${Gcorpus}_${Gright}:[" . ($Glemma==1?"lemma":"word") . "=\"" . $temp2[0] . "\"]";
    $Gquery = urlencode($Gquery);
    $Gquery = "q=" . $Gquery;
    // left rest of the words
    for ($i=1; $i < count($temp1); $i++) {
        $GqueryTmp = "P-1:s 1:s 1 [" . ($Glemma==1?"lemma":"word") . "=\"" . $temp1[$i] . "\"]";
        $Gquery .= "&q=" . urlencode($GqueryTmp);
    }
    // switch to right side
    if (count($temp2) > 1) {
        $Gquery .= "&q=x-${Gcorpus}_${Gright}";
    }
    // right rest of the words
    $GqueryTmp = "";
    for ($i=1; $i < count($temp2); $i++) {
        $GqueryTmp = "P-1:s 1:s 1 [" . ($Glemma==1?"lemma":"word") . "=\"" . $temp2[$i] . "\"]";
        $Gquery .= "&q=" . urlencode($GqueryTmp);
    }
    
    $link = "//kontext.korpus.cz/view?corpname=${Gcorpus}_${Gleft}";
    // viewmode=sen - zobrazovat zarovnani v kontextu po vetach
    // async=1
    $link .= "&align=${Gcorpus}_${Gright}&sel_aligned=${Gcorpus}_${Gright}&pcq_pos_neg_${Gcorpus}_${Gright}=pos&viewmode=sen&async=1&";
    $link .= $Gquery;
    
    // log Link
    $log = 'L';
    //leftLang\trightLang\tlemma\tsubcorpus\thledejV(prazdne)\thledejCo(prazdne)\tleft\tright
    $query = $Gleft . "\t" . $Gright . "\t" . $Glemma . "\t" . $GdataPack . "\t\t\t" . $Gquery1 . "\t" . $Gquery2;
}


##### LOG ACCESS ####
if ($log == 'D' || $log == 'L') {
    $timestamp = date('c');
    $ip = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
    $user = "-";

    if (isset($toolbar["user"]["id"])) {
        $user = $toolbar["user"]["id"];
    }
    file_put_contents(LOGFILE, "$timestamp\t$ip\t$user\t$log\t$query\n", FILE_APPEND);
    
    // open Kontext
    if($log == 'L') {
        header("Location: $link ");
        exit();
    }
}
?>


<!DOCTYPE html>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <meta name="ROBOTS" content="NOINDEX, NOFOLLOW"/>
        <meta property="fb:page_id" content="1425254967730730" />
        <!--<meta name="viewport" content="width=device-width, initial-scale=1.0">-->

        <title>Treq - <?php echo T8r::tr("Databáze překladových ekvivalentů");?></title>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.full.min.js"></script>
        <script type="text/javascript" src="js/jquery.multiselect.js"></script>
        
        <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
        <link type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
        <link type="text/css" href="css/jquery.multiselect.css?v=15" rel="stylesheet" />
        <link type="text/css" href="css/treq.css?v=42" rel="stylesheet" />
<?php
// Styles
foreach ($toolbar["styles"] as $style) {
?>
	<link rel="stylesheet" type="text/css" href="<?= $style["url"] ?>" media="all">
<?php
}

// Scripts
foreach ($toolbar["scripts"]["depends"] as $script) {
    if ($script["package"] != "jquery/jquery") {
?>
	<script src="<?= $script["url"] ?>"></script>
<?php
    }
}

?>
        <script src="<?= $toolbar["scripts"]["main"] ?>"></script>
        <script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-9255139-6', 'auto');
    ga('send', 'pageview');
    
    $(function() {
        Toolbar.init();
    });

            function toggleInfo(targetid, divid, show) {
                if (show) {
                    $('#' + targetid).fadeOut('fast', function () {
                        $('#' + divid).fadeIn('fast');
                    });
                } else {
                    $('#' + divid).fadeOut('fast', function () {
                        $('#' + targetid).fadeIn('fast');
                    });
                }
            }

<?php
$collText = "";
$langsText = "";
foreach (array_keys($_SESSION["leftLangs"]) as $keyLL) {
    if (isset($_SESSION["primaries"][$keyLL])) {
        $rightLangs = db::getInstance()->getRightLangs($keyLL, $cnc_toolbar_lang);
    }
    else {
        $rightLangs = array_merge($_SESSION["primaries"]);
    }
    
    $collText .= "        $keyLL: {\n";
    foreach (array_keys($rightLangs) as $keyRL) {
        if (isset($_SESSION["primaries"][$keyLL])) {
            $collections = db::getInstance()->getCollections($keyLL, $keyRL, $cnc_toolbar_lang);
        }
        else {
            $collections = db::getInstance()->getCollections($keyRL, $keyLL, $cnc_toolbar_lang);
        }
    $collText .= "            $keyRL:\n               {name:\"$rightLangs[$keyRL]\", lemma:\"" . db::getInstance()->getLemma( (isset($_SESSION["primaries"][$keyLL]))? $keyRL : $keyLL ) . "\", collections:\n                  {\n                     ";
        foreach ($collections as $key => $value) {
            $collText .= "$key: \"$value\", ";
        }
        $collText = substr($collText, 0, -2) . "\n                  }\n               },\n";
    }
    $collText = substr($collText, 0, -2) . "\n            },\n";
}
$collText = substr($collText, 0, -2);
?>
            var collections = {<?php echo $collText . "\n"; ?>};

            function zmena_jazyk1(co)
            {
                var before_change = $("#jazyk1").data('pre');//get the pre data
                $("#jazyk1").data('pre', co);
                var rightLangsTS = collections[co];
                var rightSel = $("#jazyk2").val();
                if(rightSel == co) {
                    rightSel = before_change;
                }
                var s = '';
                for (var prop in rightLangsTS) {
                    if (!rightLangsTS.hasOwnProperty(prop)) {
                        continue;
                    }                
                    if(rightSel == prop) {
                        s += '<option value=\"' + prop + '\" selected>' + rightLangsTS[prop].name + '</option>\n';
                    } else {
                        s += '<option value=\"' + prop + '\">' + rightLangsTS[prop].name + '</option>\n';
                    }
                }
                document.getElementById('jazyk2').innerHTML = s;
                $("#jazyk2").trigger("change");
            }

            function zmena_jazyk2(co)
            {
                var leftLang = document.getElementById('jazyk1').options[document.getElementById('jazyk1').selectedIndex].value;
                var lemmaSellected = document.getElementById('lemma').checked;
//                console.log("lemma: " + document.getElementById('lemma').checked); // Debug
                var s = '';
//                alert("lemma: " + collections[leftLang][co].lemma); // Debug 
// TODO: Doplnit help otaznik
                switch (collections[leftLang][co].lemma) {
                    case "1":
                        s += '<input type="checkbox" class="css-checkbox" name="lemma" id="lemma" ' + (lemmaSellected ? 'checked="checked" ' : '') + ' value="1" />';
                        s += '<label for="lemma"  class="css-checkbox-label"><?php echo T8r::tr("Lemmata");?></label> ';
                        s += '<a class="context-help" href="#help2">?</a>';
                        break;
                    case "0":
                        s += '<input type="hidden" name="lemma" value="0" />';
                        s += '<input type="checkbox" class="css-checkbox" disabled readonly id="lemma" />';
                        s += '<label for="lemma"  class="css-checkbox-label"><?php echo T8r::tr("Lemmata");?></label> ';
                        s += '<a class="context-help" href="#help2">?</a>';
                        break;
                }
                document.getElementById('lemma-collumn').innerHTML = s;
                $("#lemma-collumn").trigger("change");

                s = '';
                var collect = collections[leftLang][co].collections;
                for (var prop in collect) {
                    if (!collect.hasOwnProperty(prop)) {
                        continue;
                    }
                    s += '<option value=\"' + prop + '\" selected>' + collect[prop] + '</option>\n';
                }
              
                document.getElementById('hledejKde').innerHTML = s;
                $("#hledejKde").multiselect("refresh");
                $("#hledejKde").trigger("change");
            }
            
            function switchLang() {
                var langLeft = $("#jazyk1").val();
                var langRight = $("#jazyk2").val();
                $("#jazyk1").val(langRight).trigger('change.select2');
                //$("#jazyk1").trigger("change");
                $("#jazyk2").val(langLeft).trigger('change.select2');
            }
            
        </script>
    </head>
    <body>

        <div id="canvas">
        <?php
        $url = "//treq.korpus.cz";
        echo $toolbar["html"];
        ?>

            <div id="wrapper">
                <div id="titlelogo">
                    <img src="graphics/<?php echo T8r::tr("treq_logo_v2.svg");?>" alt="Treq" width="320"/>
                </div>
        
                <div id="changelog">
                    <a href="#" onclick="toggleInfo('main', 'changelog', false)" title="<?php echo T8r::tr("Zavřít");?>" class="helpCloseButton"></a>

                    <h4><?php echo T8r::tr("Kdo Treq vyvíjí?");?></h4>
                    <p><b><?php echo T8r::tr("Návrh a realizace");?></b>: <a href="https://ucnk.ff.cuni.cz/cs/ustav/lide/martin-vavrin/" target="_blank">Martin Vavřín</a>, <a href="https://ucnk.ff.cuni.cz/cs/ustav/lide/alexandr-rosen/" target="_blank">Alexandr Rosen</a> <br></p>
                    <p><b><?php echo T8r::tr("Nástroj pro zarovnání slov a extrakci slovních párů");?>:</b> <a href="http://dx.doi.org/10.1162/089120103321337421" target="_blank">GIZA++</a> (Och, F. J. – Ney, H. (2003). A systematic comparison of various statistical alignment models. Computational Linguistics, 29(1), 19–51.)<?php echo T8r::tr(", s díky Ondřeji Bojarovi a Davidu Marečkovi za pomoc s instalací. Výsledek automatické excerpce už nebyl nijak revidován.");?></p>
                    <p><?php echo T8r::tr("Za podněty k vývoji trequ vděčíme Elżbietě Kaczmarské.");?><br></p>
                    <p><b><?php echo T8r::tr("Uživatelské rozhraní");?>:</b> <a href="https://ucnk.ff.cuni.cz/cs/ustav/lide/martin-vavrin/" target="_blank">Martin Vavřín</a> <br></p>
                    <p><b><?php echo T8r::tr("Příprava dat");?>:</b> <a href="https://ucnk.ff.cuni.cz/cs/ustav/lide/pavel-prochazka/" target="_blank">Pavel Procházka</a>, <a href="https://ucnk.ff.cuni.cz/cs/ustav/lide/martin-vavrin/" target="_blank">Martin Vavřín</a> <br></p>
                    <p><b><?php echo T8r::tr("Grafická podpora"); ?>:</b> <a href="https://ucnk.ff.cuni.cz/cs/ustav/lide/jan-kocek/" target="_blank">Jan Kocek</a> <br></p>
                    <p><b><?php echo T8r::tr("Jak citovat Treq"); ?>:</b></p>
                    <ul>
                        <li>Vavřín, M. – Rosen, A.: Treq. FF UK. Praha 2015. <?php echo T8r::tr("Dostupný z WWW"); ?>: <i>"http://treq.korpus.cz"</i>.</li>
                        <li><?php echo T8r::tr("Škrabal, M. – Vavřín, M. (2017): Databáze překladových ekvivalentů Treq. Časopis pro moderní filologii 99&nbsp;(2), s. 245–260."); ?></li>
                    </ul>
                    <br>

                    <h4 class="zeromb"><?php echo T8r::tr("Verze");?> 0.1 (alpha)</h4>
                    <small><b><?php echo T8r::tr("Datum zveřejnění");?>:</b> <?php echo T8r::tr("září");?> 2014</small>
                    <ul>
                        <li> <?php echo T8r::tr("prohledávání výsledků z jádra Intercorpu");?></li>
                    </ul>

                    <h4 class="zeromb"><?php echo T8r::tr("Verze");?> 0.2 (beta)</h4>
                    <small><b><?php echo T8r::tr("Datum zveřejnění");?>:</b> <?php echo T8r::tr("srpen");?> 2015</small>
                    <ul>
                        <li> <?php echo T8r::tr("doplnění dat z dalších částí Intercorpu (balíčky)");?> </li>
                        <li> <?php echo T8r::tr("nový design uživatelského prostředí");?> </li>
                        <li> <?php echo T8r::tr("integrace do lišty nástrojů ÚČNK na portálu korpus.cz");?></li>
                    </ul>

                    <h4 class="zeromb"><?php echo T8r::tr("Verze");?> 0.3 (beta)</h4>
                    <small><b><?php echo T8r::tr("Datum zveřejnění");?>:</b> <?php echo T8r::tr("září");?> 2015</small>
                    <ul>
                        <li> <?php echo T8r::tr("překlad rozhraní do angličtiny");?> </li>
                        <li> <?php echo T8r::tr("odkazy na adekvátní dotaz do Kontextu");?> </li>
                    </ul>

                    <h4 class="zeromb"><?php echo T8r::tr("Verze");?> 1.0</h4>
                    <small><b><?php echo T8r::tr("Datum zveřejnění");?>:</b> <?php echo T8r::tr("říjen");?> 2015</small>
                    <ul>
                        <li> <?php echo T8r::tr("drobné úpravy rozhraní a oficiální zveřejnění");?> </li>
                    </ul>

                    <h4 class="zeromb"><?php echo T8r::tr("Verze");?> 1.1</h4>
                    <small><b><?php echo T8r::tr("Datum zveřejnění");?>:</b> <?php echo T8r::tr("leden");?> 2016</small>
                    <ul>
                        <li> <?php echo T8r::tr("doplněn sloupeček procenta v tabulce výsledků");?> </li>
                        <li> <?php echo T8r::tr("logování dotazů");?> </li>
                    </ul>                

                    <h4 class="zeromb"><?php echo T8r::tr("Verze");?> 2.0</h4>
                    <small><b><?php echo T8r::tr("Datum zveřejnění");?>:</b> <?php echo T8r::tr("březen");?> 2017</small>
                    <ul>
                        <li> <?php echo T8r::tr("přepracován výběr jazyků dotazu a výsledku");?> </li>
                        <li> <?php echo T8r::tr("přidány anglicko-cizojazyčné slovníky");?> </li>
                        <li> <?php echo T8r::tr("přidány slovníky s víceslovnými jednotkami");?> </li>
                        <li> <?php echo T8r::tr("přibyla možnost vybírat skupiny kolekcí");?> </li>
                        <li> <?php echo T8r::tr("ke kladení dotazu lze použít regulární výrazy");?> </li>
                        <li> <?php echo T8r::tr("lze ignorovat velká/malá písma v dotazu");?> </li>
                        <li> <?php echo T8r::tr("přibyl součet výskytů na konci seznamu");?> </li>
                    </ul>                
                    
                    <br><br>© 2015, <?php echo T8r::tr("ÚČNK");?>

                </div>

                <div id="help1" class="overlay">
                    <div class="popup">
                        <a class="helpCloseButton" href="#"></a>
                        <div id="help_header">
                            <p><?php echo T8r::tr("Omezit hledání na jádro/kolekce");?></p>
                        </div>
                        <div id="help_body">
                            <p><?php echo T8r::tr("Vyberte typy dat, které chcete zahrnout do prohledávání.");?></p>
                            <p><a href="http://wiki.korpus.cz/doku.php/cnk:intercorp:verze8#obsah_korpusu" target="blank"><?php echo T8r::tr("Co je to jádro a co jsou to kolekce?"); ?></a></p>
                        </div>  
                    </div>
                </div>

                <div id="help2" class="overlay">
                    <div class="popup">
                        <a class="helpCloseButton" href="#"></a>
                        <div id="help_header">
                            <p><?php echo T8r::tr("Vyhledávat v lemmatizované verzi databáze");?></p>
                        </div>
                        <div id="help_body">
                            <p><?php echo T8r::tr("Při zaškrtnutí pracuje se základními slovníkovými tvary slov (<i>kočka-cat, hezký-hübsch, psát-pisać</i>) na rozdíl od nelemmatizované verze (<i>kočky-cats, hezkého-hübschen, psal jsem-pisałem</i>).");?></p>
                            <p><a href="http://wiki.korpus.cz/doku.php/pojmy:lemma" target="blank"><?php echo T8r::tr("Co je to lemma?"); ?></a></p>
                            <p><a href="http://wiki.korpus.cz/doku.php/cnk:intercorp:verze9#morfosyntakticka_anotace" target="blank"><?php echo T8r::tr("Které jazyky jsou lemmatizované?"); ?></a></p>
                        </div>  
                    </div>
                </div>

                <div id="help3" class="overlay">
                    <div class="popup">
                        <a class="helpCloseButton" href="#"></a>
                        <div id="help_header">
                            <p><?php echo T8r::tr("Zahrnout víceslovné jednotky");?></p>
                        </div>
                        <div id="help_body">
                            <p><?php echo T8r::tr("Při zaškrtnutí pracuje s verzí databáze, která obsahuje i víceslovné jednotky. (Upozornění: Při vyhledávání víceslovných jednotek je nutné počítat se zvýšenou mírou chybovosti.)");?></p>
                        </div>  
                    </div>
                </div>

                <div id="help4" class="overlay">
                    <div class="popup">
                        <a class="helpCloseButton" href="#"></a>
                        <div id="help_header">
                            <p><?php echo T8r::tr("Použít regulární výrazy v dotazu");?></p>
                        </div>
                        <div id="help_body">
                            <p><?php echo T8r::tr("Regulární výrazy jsou speciální znaky se zvláštním významem (např. tečka zastupuje jeden libovolný znak), které umožňují klást složitější typy dotazů. (Upozornění: Oproti běžnému dotazu může jejich vyhodnocování trvat déle.). I bez zaškrtnutí této volby lze použít znak % pro libovolné slovo nebo jeho část (např. <i>hezk%</i> najde tvary: <i>hezký, hezká, hezké, hezkou, hezkými, hezky aj.</i>).");?></p>
                            <p><a href="https://dev.mysql.com/doc/refman/5.7/en/regexp.html" target="blank"><?php echo T8r::tr("Které regulární výrazy mohu v Trequ použít?"); ?></a></p>
                        </div>  
                    </div>
                </div>

                <div id="help5" class="overlay">
                    <div class="popup">
                        <a class="helpCloseButton" href="#"></a>
                        <div id="help_header">
                            <p><?php echo T8r::tr("Nerozlišovat velikost písmen v dotazu");?></p>
                        </div>
                        <div id="help_body">
                            <p><?php echo T8r::tr("Při zaškrtnutí budou na dotaz <i>kočka</i> nalezena slova <i>kočka, Kočka, KOČKA atp.</i>");?></p>
                        </div>  
                    </div>
                </div>

                <div id="main">

                    <div id="titleform">

                        <div class="ribbon-wrapper">
                            <a href="#" onclick="toggleInfo('main', 'changelog', true)" title="<?php echo T8r::tr("Info o verzi");?>">
                                <div class="ribbon"><?php echo T8r::tr("Ver.") . " $version";?></div>
                            </a>
                        </div>

                        <form class="query" name="Langs" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>" method="POST">
                            <div class="TFtableHome">
                                <table class="query">
                                    <tbody>
                                        <tr>
                                            <td class="queryLabel"><?php echo T8r::tr("Výchozí jazyk");?></td>
                                            <td class="queryFormGap" />
                                            <td class="queryLabel"><?php echo T8r::tr("Cílový jazyk");?></td>
                                            <td class="queryFormGap" />
                                            <td class="queryLabel"><?php echo T8r::tr("Omezit na");?> <a class="context-help" href="#help1">?</a></td>
                                        </tr>
                                        <tr>
                                            <td class="queryData">
                                                <select class="querySelect" id="jazyk1" name="jazyk1" onchange="zmena_jazyk1(this.value);">
    <?php
    foreach ($_SESSION["leftLangs"] as $key => $value) {
        echo "                                          <option value=\"$key\"" . ($_SESSION["leftLang"] == $key ? " selected" : "") . ">$value</option>\n";
    }
    ?>                                              
                                                </select>
                                            </td>
                                            <td class="queryFormGap">
                                                <!--<image src="graphics/switch.svg" alt="switch" data-alt-img="graphics/switch_h.svg"/>-->
                                                <button type="button" onclick="javascript:switchLang()"></button>
                                            </td>
                                            <td class="queryData">
                                                <select class="querySelect" id="jazyk2" name="jazyk2" onchange="zmena_jazyk2(this.value);">
    <?php
    foreach ($_SESSION["rightLangs"] as $key => $value) {
        echo "                                          <option value=\"$key\"" . ($_SESSION["rightLang"] == $key ? " selected" : "") . ">$value</option>\n";
    }
    ?>                                          
                                                </select>
                                            </td>
                                            <td class="queryFormGap" />
                                            <td class="queryData">
                                                <select class="querySelectMulti" name="hledejKde[]" id="hledejKde" multiple="multiple">
    <?php
    $collections = db::getInstance()->getCollections($primary, $lang, $cnc_toolbar_lang);
    foreach ($collections as $key => $value) {
        if(!isset($_SESSION["hledejKde"])) {
            echo "                                          <option value=\"$key\" selected>$value</option>\n";
        } else {
            echo "                                          <option value=\"$key\"" . ( ( in_array($key, $_SESSION["hledejKde"]) ) ? " selected" : "") . ">$value</option>\n";
        }
    }
    ?>   
                                                </select>
<script>
$("select.querySelectMulti").multiselect({
        checkAllText: "<?php echo T8r::tr("Vyber vše");?>",
        uncheckAllText: "<?php echo T8r::tr("Odeber vše");?>",
        noneSelectedText: "<?php echo T8r::tr("Vyber kolekce");?>",
        selectedText: "<?php echo T8r::tr("Kolekce: #");?>",
        selectedList: "0",
        minWidth: "100%"
    });
</script>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <table class="query">
                                    <tbody>
                                        <tr>
                                            <td class="queryData" colspan="4">
                                                <div  class="search-bar">
                                                    <table class="search-bar-layout">
                                                        <tr>
                                                            <td>
                                                                <input class="search-bar-input" id="search-bar-input" type="text" name="hledejCo" value="<?php echo (isset($_SESSION["hledejCo"]) ? $_SESSION["hledejCo"] : ""); ?>" maxlength="80" autofocus="autofocus" placeholder="<?php echo T8r::tr("Dotaz");?>"/>
                                                            </td>
                                                            <td>
                                                                <button class="search-bar-submit" id="search-bar-submit" type="submit" name="searchGo"><?php echo T8r::tr("Hledej");?></button>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="queryCheckbox" id="lemma-collumn">
    <?php
        $hasLemma = 0;
        if ($_SESSION["rightLang"] != null) {
            $hasLemma = db::getInstance()->getLemma($lang);
        }

        if ($hasLemma == 1) {
            echo '                                        <input type="checkbox" class="css-checkbox" name="lemma" id="lemma"';
            if(!(!isset($_SESSION["lemma"]) || $_SESSION["lemma"] == null || ($_SESSION["lemma"] == 0))) {
                echo ' checked="checked"';
            }
            echo " value=\"1\" />\n";
        } else {
            echo '                                        <input type="hidden" name="lemma" value="0" />' . "\n";
            echo '                                        <input type="checkbox" class="css-checkbox" disabled readonly id="lemma" />' . "\n";
        }
    ?>
                                                <label for="lemma"  class="css-checkbox-label"><?php echo T8r::tr("Lemmata");?></label>
                                                <a class="context-help" href="#help2">?</a>
                                            </td>
                                            <td class="queryCheckbox">
                                                <input type="checkbox" class="css-checkbox" name="viceslovne" id="viceslovne"<?php if(!(!isset($_SESSION["viceslovne"]) || $_SESSION["viceslovne"] == null || ($_SESSION["viceslovne"] == 0))) { echo " checked=\"checked\"";} ?> value="1" />
                                                <label for="viceslovne"  class="css-checkbox-label"><?php echo T8r::tr("Víceslovné");?></label>
                                                <a class="context-help" href="#help3">?</a>
                                            </td>
                                            <td class="queryCheckbox">
                                                <input type="checkbox" class="css-checkbox" name="regularni" id="regularni"<?php if(!(!isset($_SESSION["regularni"]) || $_SESSION["regularni"] == null || ($_SESSION["regularni"] == 0))) { echo " checked=\"checked\"";} ?> value="1" />
                                                <label for="regularni"  class="css-checkbox-label"><?php echo T8r::tr("Regulární");?></label>
                                                <a class="context-help" href="#help4">?</a>
                                            </td>
                                            <td class="queryCheckbox">
                                                <input type="checkbox" class="css-checkbox" name="caseInsen" id="caseInsen"<?php if(!(!isset($_SESSION["caseInsen"]) || $_SESSION["caseInsen"] == null || ($_SESSION["caseInsen"] == 0))) { echo " checked=\"checked\"";} ?> value="1" />
                                                <label for="caseInsen"  class="css-checkbox-label"><?php echo T8r::tr("A = a");?></label>
                                                <a class="context-help" href="#help5">?</a>
                                            </td>
                                        </tr>    
                                    </tbody>
                                </table>
                            </div>
                        </form>

                    </div>
                    <?php
                    if (isset($_SESSION["hledejCo"])) {
                        $lines = db::getInstance()->getWord($leftColl, $rightColl, $primary, $lang, $_SESSION["viceslovne"], $_SESSION["hledejKde"], $_SESSION["lemma"], $_SESSION["hledejCo"], $_SESSION["regularni"], $_SESSION["caseInsen"], $_SESSION["sort"]);
                        if ($lines=="") {
                            $lines = "<tr>\n\t<td>" . T8r::tr("Není ve slovníku.") . "</td><td/><td/><td/>\n</tr>";
                        }
                        echo "\t\t<div id=\"contents\">";
                        echo "\t\t\t<table class=\"std\">";
                        echo "\t\t\t\t<tr>\n";
                        echo "<th><a class=\"asc\" href=\"?order=freqAsc\" title=\"" . T8r::tr("Setřídit podle frekvence, vzestupně") . "\" > </a>" . T8r::tr("Frekvence") . "<a class=\"desc\" href=\"?order=freqDesc\" title=\"" . T8r::tr("Setřídit podle frekvence, sestupně") . "\" > </a></th>\n";
                        echo "<th><a class=\"asc\" href=\"?order=percAsc\" title=\"" . T8r::tr("Setřídit podle procent, vzestupně") . "\" > </a>" . T8r::tr("Procenta") . "<a class=\"desc\" href=\"?order=freqDesc\" title=\"" . T8r::tr("Setřídit podle procent, sestupně") . "\" > </a></th>\n";
                        echo "<th><a class=\"asc\" href=\"?order=leftAsc\" title=\"" . T8r::tr("Setřídit podle") . " - " . $_SESSION["leftLangs"][$_SESSION["leftLang"]] . ", " . T8r::tr("vzestupně") . "\" > </a>" . $_SESSION["leftLangs"][$_SESSION["leftLang"]] . "<a class=\"desc\" href=\"?order=leftDesc\" title=\"" . T8r::tr("Setřídit podle") . " - " . $_SESSION["leftLangs"][$_SESSION["leftLang"]] . ", " . T8r::tr("sestupně") . "\" > </a></th>\n";
                        echo "<th><a class=\"asc\" href=\"?order=rightAsc\" title=\"" . T8r::tr("Setřídit podle") . " - " . $_SESSION["rightLangs"][$_SESSION["rightLang"]] . ", " . T8r::tr("vzestupně") . "\" > </a>" . $_SESSION["rightLangs"][$_SESSION["rightLang"]] . "<a class=\"desc\" href=\"?order=rightDesc\" title=\"" . T8r::tr("Setřídit podle") . " - " . $_SESSION["rightLangs"][$_SESSION["rightLang"]] . ", " . T8r::tr("sestupně") . "\" > </a></th>\n";
//                        echo "<th><a href=\"?order=freqAsc\"><img class=\"asc\" src=\"graphics/asc.svg\" alt=\"freq-asc\" title=\"" . T8r::tr("Setřídit podle frekvence, vzestupně") . "\"/></a>" . T8r::tr("Frekvence") . "<a href=\"?order=freqDesc\"><img class=\"desc\" src=\"graphics/desc.svg\" alt=\"freq-desc\" title=\"" . T8r::tr("Setřídit podle frekvence, sestupně") . "\"/></a></th>\n";
//                        echo "<th><a href=\"?order=percAsc\"><img class=\"asc\" src=\"graphics/asc.svg\" alt=\"perc-asc\" title=\"" . T8r::tr("Setřídit podle procent, vzestupně") . "\"/></a>" . T8r::tr("Procenta") . "<a href=\"?order=freqDesc\"><img class=\"desc\" src=\"graphics/desc.svg\" alt=\"freq-desc\" title=\"" . T8r::tr("Setřídit podle procent, sestupně") . "\"/></a></th>\n";
//                        echo "<th><a href=\"?order=leftAsc\"><img class=\"asc\" src=\"graphics/asc.svg\" alt=\"left-asc\" title=\"" . T8r::tr("Setřídit podle") . " - " . $_SESSION["leftLangs"][$_SESSION["leftLang"]] . ", " . T8r::tr("vzestupně") . "\"/></a>" . $_SESSION["leftLangs"][$_SESSION["leftLang"]] . "<a href=\"?order=leftDesc\"><img class=\"desc\" src=\"graphics/desc.svg\" alt=\"left-desc\" title=\"" . T8r::tr("Setřídit podle") . " - " . $_SESSION["leftLangs"][$_SESSION["leftLang"]] . ", " . T8r::tr("sestupně") . "\"/></a></th>\n";
//                        echo "<th><a href=\"?order=rightAsc\"><img class=\"asc\" src=\"graphics/asc.svg\" alt=\"right-asc\" title=\"" . T8r::tr("Setřídit podle") . " - " . $_SESSION["rightLangs"][$_SESSION["rightLang"]] . ", " . T8r::tr("vzestupně") . "\"/></a>" . $_SESSION["rightLangs"][$_SESSION["rightLang"]] . "<a href=\"?order=rightDesc\"><img class=\"desc\" src=\"graphics/desc.svg\" alt=\"right-desc\" title=\"" . T8r::tr("Setřídit podle") . " - " . $_SESSION["rightLangs"][$_SESSION["rightLang"]] . ", " . T8r::tr("sestupně") . "\"/></a></th>\n";
                        echo "\t\t\t\t</tr>\n";
    //                    echo "\n\t\t\t\t<tbody>\n";
                        echo $lines;
                        echo "\n\t\t\t</table>";
                        echo "\t\t</div>\n";
    //                    echo "\t\t\t\t</tbody>\n\t\t\t</table>";
                    }
                    ?>
                </div>

                <div id="titleinfo">
                    <div id="titleinfo_header">
                        <p><?php echo T8r::tr("Nápověda");?></p>
                    </div>
                    <div id="titleinfo_body">
                        <!--<p><?php echo T8r::tr("<b>Upozornění</b>: společně s vydáním nové verze korpusu InterCorp byla 9. 9. 2016 aktualizována i data pro Treq. Treq tedy nyní pracuje s daty korpusu InterCorp verze 9.");?></p> <hr>-->

                        <p><?php echo T8r::tr("Nevíte, jak nejlépe přeložit nějaké slovo? Potřebujete vymyslet vhodné synonymum? Zkuste Treq! Treq je sbírka oboustranných česko-cizojazyčných a anglicko-cizojazyčných slovníků, vytvořených automaticky z paralelního korpusu InterCorp.");?></p>

                        <p><?php echo T8r::tr("Nejdříve zvolíme výchozí jazyk, v němž je hledaný výraz, a cílový jazyk, do něhož jej chceme přeložit. Slovo můžeme zadat v konkrétním tvaru, v základním slovníkovém tvaru (Lemmata), lze vyhledávat i víceslovnou jednotku (Víceslovné), využít při hledání regulární výrazy (Regulární) nebo v dotazu nerozlišovat velikost písmen (A = a). Můžeme si také vybrat, zda má být výsledek založen na překladech beletristického jádra, jednotlivých kolekcí, nebo všech textů v InterCorpu (Omezit na:). Pak slovo zadáme (Dotaz:) a klikneme na Hledej. Výsledkem dotazu je seznam nalezených překladů zadaného slova, defaultně setříděných sestupně podle frekvence. Pro ověření výskytu v kontextu je možné si dvojici výrazů vyhledat dotazem do korpusu InterCorp kliknutím na ekvivalent. Počet výskytů se však může lišit – paralelní dotaz najde i konkordance, v nichž potenciální ekvivalent odpovídá jinému slovu.");?></p>

                        <p><?php echo T8r::tr("Treq vychází z textů 9. vydání paralelního korpusu InterCorp. Originální a překladové texty jsou nejprve na základě statistických výpočtů zarovnány po slovech pomocí programu GIZA++ ")?>
                            <a href="#" onclick="toggleInfo('main', 'changelog', true)" title="<?php echo T8r::tr("Info o verzi");?>">(Och–Ney 2003)</a>. 
                            <?php echo T8r::tr("Zarovnané dvojice slov jsou pak setříděny a sumarizovány. Výsledek automatické excerpce není nijak revidován, jako ukazatel relevance překladového ekvivalentu však může posloužit relativní frekvence příslušné dvojice slov. Čím častěji se ekvivalent zadaného slova vyskytl ve srovnání s ostatními ekvivalenty, tím větší je pravděpodobnost, že je funkční.");?></p>
                    </div>
    <?php // TODO: v okamžiku, kdy bude k dispozici anglická wiki tak je potřeba odkazy přeložit pomocí T8r pro anlgickou wiki?>
<!--                    <div id="titleinfo_footnote">
                        <p>1) <a href="http://wiki.korpus.cz/doku.php/pojmy:lemma" target="blank"><?php echo T8r::tr("Co je to lemma?"); ?></a></p>
                        <p>2) <a href="http://wiki.korpus.cz/doku.php/cnk:intercorp:verze8#morfosyntakticka_anotace" target="blank"><?php echo T8r::tr("Jazyky s údajem o lemmatu."); ?></a></p>
                        <p>3) <a href="http://wiki.korpus.cz/doku.php/cnk:intercorp:verze8#obsah_korpusu" target="blank"><?php echo T8r::tr("Co jsou to kolekce?"); ?></a></p>
                    </div>-->
                </div>



                <div id=copyright>
                    &copy; 2015, <a class=link href='http://www.korpus.cz'><?php echo T8r::tr("ÚČNK");?></a> <a class=link style="margin-left:50px" target="_blank" href='https://podpora.korpus.cz/projects/treq/issues/new'> <?php echo T8r::tr("Nahlásit chybu");?></a> 
                    <script>
                        $(document).ready(function(){
                            $("#search-bar-input").select();
                            
                            var configParamsObj = {
                                placeholder: '<?php echo T8r::tr("Hledaný text...");?>', // Place holder text to place in the select
                                minimumResultsForSearch: 5, // Overrides default of 15 set above
                                matcher: function (params, data) {
                                    // If there are no search terms, return all of the data
                                    if ($.trim(params.term) === '') {
                                        return data;
                                    }

                                    // `params.term` should be the term that is used for searching
                                    // `data.text` is the text that is displayed for the data object
                                    if (data.text.toLowerCase().startsWith(params.term.toLowerCase())) {
                                        var modifiedData = $.extend({}, data, true);
                                        modifiedData.text += ' (<?php echo T8r::tr("nalezeno");?>)';

                                        // You can return modified objects from here
                                        // This includes matching the `children` how you want in nested data sets
                                        return modifiedData;
                                    }

                                    // Return `null` if the term should not be displayed
                                    return null;
                                }
                            };
                            $("select.querySelect").select2(configParamsObj);
                            $("#jazyk1").data('pre', $("#jazyk1").val());
    
//                            $("select.querySelect").select2({minimumResultsForSearch: 10});
//                            $("select.querySelect").select2({
//                                    matcher: function(term, text) { return text.toUpperCase().indexOf(term.toUpperCase())==0; }
//                                });
//                            $("select.querySelectMulti").multiselect({
//                                    checkAllText: "<?php echo T8r::tr("Vyber vše");?>",
//                                    uncheckAllText: "<?php echo T8r::tr("Odeber vše");?>",
//                                    noneSelectedText: "<?php echo T8r::tr("Vyber kolekce");?>",
//                                    selectedText: "<?php echo T8r::tr("Kolekce: #");?>",
//                                    selectedList: "0",
//                                    minWidth: "100%"
//                                });
//                              $("select.querySelectMulti").css( "z-index: 20;" );
//                            $("#hledejKde").multiselect("refresh");
                        });
                    </script>
                </div>
            </div>
            
        </div>
    </body>
</html>
