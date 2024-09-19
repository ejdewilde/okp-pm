<?php

/*
OKO procesmonitor
 */

ini_set('display_errors', 'On');

function ts($test)
{ // for debug/development only
    echo '<pre>';
    echo 'tijdelijke debug informatie: </br>';
    echo print_r($test, true);
    echo '</pre>';
}

function schoon($input)
{
    return iconv('UTF-8', 'ASCII//TRANSLIT', $input);
}

class OKO_pm
{
    public function __CONSTRUCT()
    {
        $this->gebruiker_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $user_email = $current_user->data->user_email;
        $this->user_name = $current_user->data->display_name;
        $gemruw = $this->maak_gemeente($user_email);
        //$this->gemeente = $this->maak_gemeente($user_email);
        //ts($this->gebruiker_id);
        $this->gem = array();
        if ($gemruw) {
            $this->gem['id'] = $gemruw[0]['id'];
            $this->gem['naam'] = $gemruw[0]['title'];
        }

        include_once "interfaceDB.php";
        $db = new PmDb();

        $this->faseteksten = array();
        $this->stapteksten = array();
        $this->staptitels = array();
        $this->staptotalen = array();
        $this->scores = array();

        $this->plugindir = plugin_dir_url(__FILE__);
        $this->url = $_SERVER['HTTP_HOST'];
        $this->start_tekst = $this->maak_start_tekst();
        $this->maak_fase_stap_teksten();
        $this->staptotalen = $this->maak_staptotalen();
        $this->scores = $this->maak_scores();
        $this->tipstring = $this->maak_tipstring();
        $this->statusstring = $this->maakstatusstring();

        //ts($this->statusstring);
        //$this->checks = $this->haal_checks();
    }

    public function maakstatusstring()
    {
        $curus = $this->maak_laatste_score();
        //ts($curus);
        //ts($this->gem['naam']);
        //$curus=wp_get_current_user($tijd);
        if ($this->gem['naam']) {
            $stat = '<div class = "status">Procesmonitor van <b>' . $this->gem["naam"] . '</b>';
        } else {
            return '<div class = "status">Niemand ingelogd die voor deze gemeente de procesmonitor kan gebruiken</div>';
        }

        if ($curus) {
            //ts($curus);
            $uid = $curus[0]["user_id"];
            $user_info = get_userdata($uid);

            $timst = $curus[0]["archiefUniX"];
            //ts($timst);
            $naam = $user_info->display_name;
            $stat .= '. Laatst bijgewerkt door ' . $naam;
            $dag = date("j M Y", $timst);
            $stat .= ' op ' . $dag . '</div>';
            return $stat;

        } else {
            return $stat .= '. Nog geen invoer geweest voor deze gemeente</div>';
        }
    }
    public function maak_laatste_score()
    {
        include_once "interfaceDB.php";
        $db = new PmDb();
        $data = $db->haal_laatste_score($this->gem['id']);
        return $data;
    }
    public function maak_gemeente($eml)
    {
        include_once "interfaceDB.php";
        $db = new PmDb();
        $data = $db->haal_gemeente($eml);
        return $data;

    }
    public function maak_staptotalen()
    {
        include_once "interfaceDB.php";
        $db = new PmDb();
        $data = $db->haal_max_score_per_stap();
        return $data;

    }
    public function maak_scores()
    {
        include_once "interfaceDB.php";
        $db = new PmDb();
        $data = $db->haal_scores($this->gem['id']);
        return $data;

    }
    public function maak_tipstring()
    {
        include_once "interfaceDB.php";
        $db = new PmDb();
        $data = $db->haal_tipstring();
        return $data;

    }
    public function haal_gemeente_gebruiker($uid)
    {
        include_once "interfaceDB.php";
        $db = new PmDb();

        return $db->haal_gemeente_gebruiker($uid);
    }

    public function maak_fase_stap_teksten()
    {
        include_once "interfaceDB.php";
        $db = new PmDb();
        $this->staptitels = $db->haal_stap_titels();
        $fasenstappen = $db->haal_fasen_stappen();
        //exit;
        /*fase 0Hier komt een toelichting bij fase0',*/
        foreach ($fasenstappen as $soort => $inhs) {
            switch ($soort) {
                case 1:
                    ksort($inhs);
                    foreach ($inhs as $ind => $txt) {
                        $faseteksten[] = '<h3 class = "tiptitel">fase ' . $ind . '</h3><p class = "tiptekst">' . $txt . '</p>';
                    }
                    $faseteksten[5] = '<h3 class = "tiptitel">Community building</h3><p class = "tiptekst">' . $txt . '</p>';
                    break;
                case 2:
                    ksort($inhs);
                    foreach ($inhs as $ind => $txt) {
                        //$stapteksten[] = '<h3 class = "tiptitel">stap ' . $ind . '</h3><p class = "tiptekst">' . $txt . '</p>';
                        $stapteksten[] = '<p class = "tiptekst">' . $txt . '</p>';

                    }
                    break;
            }
        }

        $this->faseteksten = $faseteksten;
        $this->stapteksten = $stapteksten;
    }

    public function get_interface()
    {

        $output = $this->get_style();
        //header
        $output .= '<div class = "kopjeparent">
                        <div class = "kopjediv1">OKO-fasen</div>
                        <div class = "kopjediv2">OKO-stappen</div>
                        <div class = "kopjediv3">' . $this->statusstring . '</div>
                    </div>';
        $output .= '

                <div class = "hoofd">

                    <div class = "con1" id="container1"></div>

                    <div class = "con2" id="container2"></div>
                    <div class = "staptitel" id="staptitel"></div>
                    <div class = "intro" id="intro"></div>
                    <div class = "item">
                        <form id = "zelfscan" autocomplete="off" action="#" method="POST">
                            <div id = "kop"></div>
                            <div id = "vraag"></div>
                            <div id = "items">' . $this->start_tekst . '</div>
                        </form>
                    </div>
                    <div class = "tip" id = "tip"><p>Hier komen straks tips en toelichtingen te staan wanneer je over de selectie-items beweegt.</p></div>
                    <div class = "faseinfo" id = "faseinfo"></div>

                </div>';
        $output .= '<footer>';

        $output .= $this->get_biebs();
        $output .= '</footer>';
        echo $output;
        //ts('ok');
        return;
    }

    public function maak_start_tekst()
    {
        return "<h2>Hoe gebruik je deze Procestool?</h2>
            <p>
            <ul class = 'tiplijst'>
            <li>Beweeg je muis over de OKO-fasen (links) om meer informatie over de fase te zien. De bijbehorende OKO-stappen (boven) lichten op.</li>
            <li>Wil je aan de slag, selecteer dan een stap. Je ziet dan welke acties je kunt ondernemen.</li>
            <li>Geef bij elke stap aan welke acties je al ondernomen hebt, en bekijk de tips voor de stappen die je nog gaat zetten. Als je je muis over een actie beweegt, verschijnt de tip vanzelf.</li>
            </ul>
            </p>";
    }

    public function get_style()
    {
        wp_enqueue_style('okpmd3', $this->plugindir . 'css/oko-pm.css');
    }

    public function get_biebs()
    {
        $output = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>';
        //$output = '<script src="https://code.jquery.com/jquery-3.7.1.slim.min.js" integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8=" crossorigin="anonymous"></script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">var gemeente =' . json_encode($this->gem) . '</script>';
        $output .= '<script type="text/JavaScript">var uid=' . $this->gebruiker_id . '</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">var staptitels =' . schoon(json_encode($this->staptitels)) . '</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">var tipstring =' . schoon(json_encode($this->tipstring, JSON_INVALID_UTF8_SUBSTITUTE)) . '</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">var stapteksten =' . schoon(json_encode($this->stapteksten)) . '</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">var faseteksten =' . schoon(json_encode($this->faseteksten)) . '</script>';
        $output .= '<script type="text/JavaScript">var staptotalen=' . json_encode($this->staptotalen) . '</script>';
        $output .= '<script type="text/JavaScript">var scores=' . json_encode($this->scores) . '</script>';
        $output .= "<script src='https://d3js.org/d3.v6.min.js' type='text/javascript'></script>";
        $output .= "<script src='" . $this->plugindir . "js/d3.tip.js' type='text/javascript'></script>";
        $output .= "<script src='" . $this->plugindir . "js/oko-pm.js' type='text/javascript'  charset='iso-8859-1'></script>";

        return $output;
    }
}