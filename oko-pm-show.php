<?php

/*
OKO procesmonitor laat resultaten zien
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

class OKO_pm_show
{
    public function __CONSTRUCT()
    {
        //ts($_POST);
        $this->gebruiker_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $user_email = $current_user->data->user_email;
        $this->user_name = $current_user->data->display_name;

        include_once "interfaceDB.php";
        $db = new PmDb();

        $this->plugindir = plugin_dir_url(__FILE__);
        $this->url = $_SERVER['HTTP_HOST'];
        $this->staptotalen = $db->haal_max_score_per_stap();
        //ts($this->maxscores);

        if ($_POST) {
            $this->gemeente = $_POST['gemeente'];
            $this->gemeentenaam = $db->haal_gemeente_naam($this->gemeente);
            $this->invul_info = $db->haal_invul_info($this->gemeente);
            $this->invultabel = $this->maak_invultabel($this->invul_info);
            //ts($this->invul_info);

            $this->stappen = $db->haal_stap_titels();
            $this->data = $db->haal_scores_show($this->gemeente);
            //ts($this->data);

            $this->uit = '<div>' . $this->maak_opsomming($this->data[2]) . '</div>';
        } else {
            $arrgem = $db->haal_gem_invullers();
            $this->uit = $this->maak_knoppen($arrgem);
        }

    }
    public function maak_invultabel($data)
    {
        $terug = '';
        $terug .= '<table class = "pmtabel">';
        $terug .= '<tr><th width = "150px;">bijgewerkt op</th><th>door</th></tr>';
        foreach ($data as $row) {
            $terug .= '<tr><td>' . $row['dag'] . '</td><td>' . $row['display_name'] . '</td></tr>';
        }
        $terug .= '</table>';
        return $terug;

    }
    public function maak_opsomming($data)
    {
        $output = '';
        //ts($data);
        for ($stap = 1; $stap <= 10; $stap++) {
            $output .= '<div class = "stapkop">' . $this->stappen[$stap] . '</div>';
            foreach ($data[$stap] as $key => $val) {
                $iid = $stap . $key;
                $tsjek = '&nbsp;';

                if ($val["check"] > 0) {
                    //echo $iid . '-';
                    $tsjek = 'checked';
                }

                $output .= '<div class = "itemverz2"><input class="regular-checkbox" id=' . $iid . ' ' . $tsjek . ' type="checkbox"><label for=' . $iid . '>&nbsp;</label></span>
                <span class = "textblok">' . trim($val["naam"]) . '</span></div>';
            }
        }
        //$output.= '<p>Totaal aantal punten: '. $data[0]. '</p>';
        //$output.= '<p>Gemiddelde score: '. $data[1]. '</p>';
        //$output.= '<p>Maximale score: '. $data[2]. '</p>';
        return $output;
    }
    public function maak_knoppen($arrgem)
    {
        $outputkn = '';
        foreach ($arrgem as $row) {
            $outputkn .= "<button name = 'gemeente' type = 'submit' class = 'inhoudsknop' value = '" . $row["gem_id"] . "'>" . $row["naam"] . " (" . $row["dag"] . ")</button>";
        }
        $output = '<form action = "#" method = "POST">';
        $output .= $outputkn;
        $output .= '</form>';
        return $output;
    }
    public function maak_laatste_score()
    {
        include_once "interfaceDB.php";
        $db = new PmDb();
        $data = $db->haal_laatste_score($this->gem['id']);
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

    public function get_interface()
    {

        $output = $this->get_style();
        $output .= '<h1>' . $this->gemeentenaam . '</h1>';
        $output .= $this->invultabel;
        $output .= '<div class = "con2" id="container2"></div>';
        $output .= $this->uit;
        $output .= '<footer>';
        $output .= $this->get_biebs();
        $output .= '</footer>';
        echo $output;
        //ts('ok');
        return;
    }

    public function get_style()
    {
        wp_enqueue_style('okpmd3', $this->plugindir . 'css/oko-pm.css');
    }

    public function get_biebs()
    {
        $output = '';
        $output = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>';
        //$output = '<script src="https://code.jquery.com/jquery-3.7.1.slim.min.js" integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8=" crossorigin="anonymous"></script>';
        $output .= '<script type="text/JavaScript">var staptotalen=' . json_encode($this->staptotalen) . '</script>';
        $output .= '<script type="text/JavaScript">var scores=' . json_encode($this->data[1]) . '</script>';
        $output .= "<script src='https://d3js.org/d3.v6.min.js' type='text/javascript'></script>";
        $output .= "<script src='" . $this->plugindir . "js/d3.tip.js' type='text/javascript'></script>";
        $output .= "<script src='" . $this->plugindir . "js/oko-pm-show.js' type='text/javascript'  charset='iso-8859-1'></script>";
        return $output;
    }
}
