<?php
require_once (__DIR__ . "/SQL-connect.php");
require_once (__DIR__ . "/definitions.php");

class db extends mysqli {

    // single instance of self shared among all instances
    private static $instance = null;
    // db connection config vars
    private $user = SQL_USERNAME;
    private $pass = SQL_PASSWORD;
    private $dbName = SQL_DBNAME;
    private $dbHost = SQL_HOST;
    private $corpus = CORPUS;

    //This method must be static, and must return an instance of the object if the object
    //does not already exist.
    public static function getInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    // private constructor
    private function __construct() {
        parent::__construct($this->dbHost, $this->user, $this->pass, $this->dbName);
        if (mysqli_connect_error()) {
            exit('Connect Error (' . mysqli_connect_errno() . ') '
                    . mysqli_connect_error());
        }
        parent::set_charset('utf-8');
        $this->query("SET character_set_results=utf8;");
        $this->query("SET character_set_connection=utf8;");
        $this->query("SET NAMES utf8;");
    }

    // The clone and wakeup methods prevents external instantiation of copies of the Singleton class,
    // thus eliminating the possibility of duplicate objects.
    public function __clone() {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    public function __wakeup() {
        trigger_error('Deserializing is not allowed.', E_USER_ERROR);
    }

    public function getLeftLangs($primaries, $trans) {
        $tmpLangs = array_merge($primaries);
        
//        echo "SELECT DISTINCT zkratka, nazev FROM jazyky WHERE `trans` = '$trans'"; // Debug
        $result = $this->query("SELECT DISTINCT `zkratka`, `nazev` FROM `jazyky` WHERE `trans` = '$trans'") or die($this->error); //  COLLATE " . ($trans = 'cs'?"utf8_czech_ci":"utf8_general_ci
        while ($row = mysqli_fetch_array($result)) {
            $tmpLangs[$row['zkratka']] = $row['nazev'];
        }
        $collator = new Collator(($trans == 'cs')?'cs_CZ':'en_US');
        $collator->asort($tmpLangs);
        mysqli_free_result($result);
        //asort($tmpLangs, SORT_LOCALE_STRING);
        
        return $tmpLangs;
    }
    
    public function getRightLangs($primary, $trans) {
        $tmpLangs = array();

//        echo "SELECT DISTINCT zkratka, nazev FROM jazyky WHERE `trans` = '$trans' AND `primary` = '$primary'"; // Debug
        $result = $this->query("SELECT DISTINCT `zkratka`, `nazev` FROM `jazyky` WHERE `trans` = '$trans' AND `primary` = '$primary'") or die($this->error); //  COLLATE " . ($trans = 'cs'?"utf8_czech_ci":"utf8_general_ci
        while ($row = mysqli_fetch_array($result)) {
            $tmpLangs[$row['zkratka']] = $row['nazev'];
        }
        $collator = new Collator(($trans == 'cs')?'cs_CZ':'en_US');
        $collator->asort($tmpLangs);
        mysqli_free_result($result);

        return $tmpLangs;
    }

    public function getLemma($lang) {  // TODO: Vymyslet to lépe, než ignorovat primary, mělo by to sice být stejné, ale nemuselo by?
        $result = $this->query("SELECT DISTINCT lemma FROM jazyky WHERE zkratka = '"
                . $lang . "'") or die($this->error);
        if ($result->num_rows > 0) {
            $row = $result->fetch_row();
            mysqli_free_result($result);
            return $row[0];
        } else {
            mysqli_free_result($result);
            return null;
        }
    }
    
    public function getCollections($primary, $lang, $trans) {
        $tmpColl = array();
        $result = $this->query("SELECT data_packs.id, data_packs.name FROM jazyky, data_packs WHERE data_packs.trans='$trans' AND data_packs.id=jazyky.data_pack AND jazyky.primary='$primary' AND jazyky.zkratka='$lang' AND jazyky.trans='$trans' ORDER BY name") or die($this->error);
        while ($row = mysqli_fetch_array($result)) {
            $tmpColl[$row['id']] = $row['name'];
        }
        mysqli_free_result($result);
        asort($tmpColl);
        return $tmpColl;
    }    

//    public function getAbbrev($lang) {
//        $result = $this->query("SELECT zkratka FROM jazyky WHERE id = '"
//                . $lang . "'") or die($this->error);
//        if ($result->num_rows > 0) {
//            $row = $result->fetch_row();
//            mysqli_free_result($result);
//            return $row[0];
//        } else {
//            mysqli_free_result($result);
//            return null;
//        }
//    }
    
    public function getWord($leftColl, $rightColl, $primary, $lang, $multiword, $data_pack_id, $lemma, $searchText, $regular, $caseInsen, $sort) {
        $lines = "";
        if (count($data_pack_id) < 1) {
            return $lines; 
        }
        
        if($caseInsen == 1) {
            $searchText = mb_strtolower($searchText, 'UTF-8');
        }
//        if ($data_pack_id == "All"){
//            $query = "SELECT `data_pack`" .
//                    " FROM `jazyky` WHERE `primary` = '$primary' AND `zkratka` = '$lang' AND trans = 'en';";
//            $result = $this->query($query) or die($this->error);
//            $i = 0;
//            while ($row = mysqli_fetch_array($result)) {
//                $data_pack_id[$i++] = $row[0]; 
//            }
//        } else {
//            $data_pack_id[0] = $data_pack_id;
//        }
            
        $data_packs = null;
        $i = 0;
        foreach ($data_pack_id as $value) {
            // TODO: OPT
            $result = $this->query("SELECT `name` FROM `data_packs` WHERE `id` = '$value' AND `trans` = 'en'") or die($this->error);
            if ($result->num_rows == 1) {
                $row = $result->fetch_row();
                mysqli_free_result($result);
                $data_packs[$i++] = $row[0];
            }
        }
        
        $query = "";
        for($i=0; $i < count($data_packs);$i++) {
            $query .= "SELECT `freq`, `$leftColl`, `$rightColl` FROM `" . $data_packs[$i] . ($multiword ? "_NN" : "" ) . "_$primary-$lang". ($lemma == 1 ? "-lemma" : "") . "` WHERE " . ($caseInsen == 1 ? "LOWER(" : "") . "`$leftColl`" . ($caseInsen == 1 ? ") " : " ") . ($regular? "REGEXP '^" : "LIKE '") . $searchText . ($regular? "$'" : "'");
            if ( ($i+1) < count($data_packs) ) {
                $query .= " UNION ";
            }
        }
        $query2 = "SELECT SUM(`tsum`.`freq`) as `freq` FROM (" . $query . ") AS `tsum`";

        $result = $this->query($query2) or die($this->error);
        $sum = 1;
        if ($result->num_rows == 1) {
            $row = $result->fetch_row();
            $sum = $row[0];
            if ($sum == null) {
                $sum = 1;
            }
        } else {
            die();
        }
        mysqli_free_result($result);
        $query1 = "SELECT SUM(`tsum`.`freq`) as `freq`, ROUND((SUM(`tsum`.`freq`)/$sum)*100,1) as `perc`, `tsum`.`$leftColl`, `tsum`.`$rightColl` FROM (" . $query . ") AS `tsum` GROUP BY `tsum`.`$leftColl`, `tsum`.`$rightColl` ORDER BY $sort";
        $result = $this->query($query1) or die($this->error);
        
        $sum = 0;
        $urlDataPacks = "";
        foreach ($data_packs as $value) {
            $urlDataPacks .= ($value == "Presseurop" ? "PressEurop" : $value) . "+";
        }
        $urlDataPacks = substr($urlDataPacks, 0, -1);
        
        while ($row = mysqli_fetch_array($result)) {
//            ($data_pack_id=="All"?"All":($data_packs[0]=="Presseurop"?"PressEurop":$data_packs[0]))
            $link = "?link=true&corpname=" . $this->corpus . "&left=" . ( $leftColl == "primary" ? $primary : $lang) . "&lemma=$lemma&query1=" . urlencode($row[2]) . "&right=" . ( $rightColl == "primary" ? $primary : $lang) . "&query2=" . urlencode($row[3]) . "&dataPack=" . $urlDataPacks;
            $lines .= "<tr>\n\t<td>" . $row[0] . "</td>\n\t<td>" . $row[1] . "</td>\n\t<td>";
            $sum += $row[0];
//            if($leftColl==="primary") {
            $lines .= $row[2]. "</td>\n\t<td><a href=\"$link\" target=\"_blank\">" . $row[3] . "</a></td>\n</tr>";
//            } else {
//                $lines .= "<a href=\"" . $link . "\" target=\"_blank\">" . $row[2] . "</a></td>\n\t<td>" . $row[3]. "</td>\n</tr>";
//            }
        }
        mysqli_free_result($result);
        if ($sum > 0) {
            $lines .= "<tr> <th>$sum</th> <th></th> <th></th> <th></th> </tr>"; 
        }
        return $lines;
    }    


}


?>

