<?php
function schoontxt($input)
{
    return $input;
    return iconv('UTF-8', 'ASCII//TRANSLIT', $input);
}
function tsg($test)
{ // for debug/development only
    echo '<pre>';
    echo print_r($test);
    echo '</pre>';
}
ini_set('display_errors', 'Off');

//tsg($_POST);
$gid = 0;
$uid = 0;

$aantalperstap = array();
for ($f = 1; $f < 11; $f++)
{
    $aantalperstap[$f] = 0;
}
if ($_GET)
{
    //echo "test";
    //global $lid, $tid, $uid;
    //$tid = $_GET['tid'];
    $gid = $_GET['gid'];
    ?>
    <link rel="stylesheet" href="https://localhost/hansei/oko/wp-content/plugins/oko-pm/css/oko-pm.css">
    <?php
    //$bev = $_GET['ja'];
    //$type = haal_schooltype($lid);
    haal_items($gid);
    //tsg($gid);
    //exit;
    //echo "</br>test";
    //echo 'hallo1';
    //haal_scores($gid);
    //haal_max($gid);

    //test();

    //$type = haal_schooltype($lid);
    //haal_items($lid, $type, $tid, $uid);
}
if ($_POST)
{
    if (key_exists("bewaar", $_POST))
    {
        $uid = $_POST['uid'];
        $gid = $_POST['gid'];
        SlaFormOp($uid, $gid, json_encode($_POST));
    }
    elseif (key_exists("haalitems", $_POST))
    {
        global $gid;
        $gid = $_POST['gid'];
        haal_items($gid);

    }
    elseif (key_exists("haalscores", $_POST))
    {
        global $lid, $tid;
        $lid = $_POST['gid'];
        haal_scores_en_vinkjes($gid);
    }
}
function laat_ascii_zien($woorden)
{
    tsg(strlen($woorden));
    for ($i = 0; $i < strlen($woorden); $i++)
    {
        $char = substr($woorden, $i, 1);
        $ascii = ord($char);
        tsg($i . ' ' . $char . " ASCII : " . $ascii . "     ");
    }

}


function haal_items($gem)
{
    global $aantalperstap;
    include_once "interfaceDB.php";
    $it = new PmDb();
    $resultaat = $it->haal_vinkjes($gem);


    $output = '';
    for ($s = 1; $s < 11; $s++)
    {
        $output .= maak_cb_stap($s, $resultaat);
    }

    //tsg($output);
    echo $output;
    exit;
}

function kweer($stap)
{
    $zql = "select ID, stap, volgorde, vooraf_text, item, tip from
            (select ID, post_title as item, post_content as tip from wp_posts where post_type = 'check' and post_status = 'publish') t
            left join
            (select post_id, meta_value as volgorde, meta_key from wp_postmeta where meta_key = 'volgorde') h on t.ID=h.post_id
            left join
            (select post_id, meta_value as stap, meta_key from wp_postmeta where meta_key = 'stap') a on t.ID=a.post_id
            left join 
            (select post_id, meta_value as vooraf_text, meta_key from wp_postmeta where meta_key = 'vooraf_text') v on t.ID=v.post_id 
            where stap = " . $stap . " order by volgorde";

    include_once "interfaceDB.php";
    $it = new PmDb();
    //tsg($zql);
    $data = $it->getArray($zql);
    return $data;
}

function haal_alles_uit_db($gem, $hoe)
{
    include_once "interfaceDB.php";
    $it = new PmDb();
    $data = $it->haal_scores_en_vinkjes($gem);
    //$smax = $it->haal_max_score_per_stap();
    //tsg($data);
    //tsg($smax);
    //exit;
    $gevinkt = array();
    $terug = array();
    $score = array();
    for ($i = 1; $i < 11; $i++)
    {
        //$score[$i]['maks'] = $smax[$i];
        $score[$i]['aantal'] = strval($data['aantal'][$i]);
        if ($hoe == 'array')
        {
            $score[$i]['gevinkt'] = $data['gevinkt'];
        }
    }
    //tsg($hoe);

    if ($hoe == 'json')
    {
        echo (json_encode($score));
        exit;
    }
    //tsg($terug);
    //exit;
    return $score;
}


function maak_cb_stap($stap, $scor)
{
    $dats = kweer($stap);
    //$arrie = json_encode($stap);

    //$scor = $arrie[1]['gevinkt'];

    //tsg($dats);
    //tsg($scor);
    //exit;
    $sam = array();

    $output = '<div id = "stap_' . $stap . '">';

    $voor = '';
    if ($dats)
    {
        foreach ($dats as $row)
        {

            $kie = 'cb' . $stap . '_' . $row["ID"];
            if ($row['vooraf_text'] <> $voor)
            {
                $voor = $row['vooraf_text'];
                $output .= '<div class = "cbvooraf">' . schoontxt($voor) . '</div>';
            }
            $tsjek = '&nbsp;';
            if ($scor)
            {
                if (in_array($kie, $scor))
                {
                    //echo 'zit er in!: ' . $kie . '</br>';
                    $tsjek = 'checked';
                }
            }
            $iid = "'" . $kie . "'";

            $output .= '<div class = "itemverz" onmouseover="tip(' . $iid . ')" onmouseout="ontip(' . $iid . ')"><span class= "blokkie"><input id=' . $iid . ' ' . $tsjek . ' onClick="SlaOp(' . $iid . ');" class="regular-checkbox" name=' . $iid . ' type="checkbox"><label for=' . $iid . '>&nbsp;</label></span>
            <span id=' . $kie . ' class = "textblok">' . trim($row["item"]) . '</span></div>';
        }
    }
    //$output .= '</div>';
    $output .= '</div>';
    //tsg($output);
    //echo $output;
    //ts($output);
    return $output;
}

function SlaFormOp($gebruiker, $locatie, $res)
{
    //$data = json_decode($res, true);
    $today = date("Y-m-d H:i:s");
    $arch = time();
    $zql = "insert into tool_usermeta (user_id, gem_id, meta_key, meta_value, tijdstip, archiefUnix) values (" . $gebruiker . ", " . $locatie . ", 'form', '" . $res . "', '" . $today . "', '" . $arch . "');";
    include_once "interfaceDB.php";
    $it = new PmDb();
    $it->exesql($zql);

}

function log_dit($geb)
{
    //$tz = 'Europe/Amsterdam';
    //$timestamp = time();
    //$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
    //$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
    //$nu = $dt->format('d.m.Y, H:i:s');
    //include_once "interfaceDB.php";
    //$itl = new PmDb();
    //$aa = "insert into logs (gebeurtenis, gebruiker, tijdstip) values ('" . $geb . "', '" . $gebruiker . "', '" . $nu . "');";
    //$itl->exesql($aa);
}


?>