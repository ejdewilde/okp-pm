<?php
function tsd($test)
{ // for debug/development only
    echo '<pre>';
    echo print_r($test);
    echo '</pre>';
}
class PmDb
{

    public $db_local;
    public $db_mysqli_local;
    public $utf8_connect = false;

    public function __CONSTRUCT($test = false)
    {
        //echo "IN DE DB!<BR>";
        if ($test) {
            echo "<h2>Testing DB Connection</h2>";
            $this->connect();
            echo "connected to: {$_SERVER["SERVER_NAME"]}";
        }
    }
    public function haal_vinkjes($gid)
    {
        //tsg($gid);
        $zql = "select meta_value as result from tool_usermeta where gem_id= " . $gid . " order by umeta_id desc limit 1 offset 0 ;";
        $data = $this->getArray($zql);
        //tsg($data);
        //exit;
        $gevinkt = array();
        //$gevinkt[] = 0;

        //tsg($data);
        //exit;
        //$terug = json_decode($res[0]["result"], true);
        if ($data[0]['result']) {
            $aa = json_decode($data[0]["result"], true);
            //tsg($aa);
            //exit;
            foreach ($aa as $key => $val) {
                if (substr($key, 0, 2) == 'cb') {
                    $gevinkt[] = $key;
                }
            }
        }
        return $gevinkt;

    }
    public function haal_scores($gem)
    {
        $zql = "select meta_value as result from tool_usermeta where gem_id= " . $gem . " order by umeta_id desc limit 1 offset 0 ;";
        $data = $this->getArray($zql);
        //tsd($data);
        $score = array();
        for ($i = 1; $i < 11; $i++) {
            $score[$i] = 0;
        }
        if ($data) {
            if ($data[0]['result']) {
                $aa = json_decode($data[0]["result"], true);
                foreach ($aa as $key => $val) {
                    if (substr($key, 0, 2) == 'cb') {
                        $stukjes = explode("_", $key);
                        $stap = substr($stukjes[0], 2);
                        $score[$stap]++;
                    }
                }
            }
        }

        return $score;
    }
    public function haal_items()
    {
        $zql = "select ID, post_title, post_name from wp_posts where post_type like '%check%' order by post_name;";
        $data = $this->getArray($zql);
        //tsd($data);
        $terug = array();
        if ($data) {
            foreach ($data as $row) {
                $terug[$row['ID']]['naam'] = $row['post_title'];
                $stukjes = explode("_", $row['post_name']);
                $terug[$row['ID']]['stap'] = $stukjes[1];
                $terug[$row['ID']]['sub'] = $stukjes[2];
                $terug[$row['ID']]['check'] = 0;
            }
        }
        //ts($terug);
        //exit;
        return $terug;
    }
    public function haal_scores_show($gem)
    {
        $zql = "select meta_value as result from tool_usermeta where gem_id= " . $gem . " order by umeta_id desc limit 1 offset 0 ;";
        $data = $this->getArray($zql);
        //tsd($zql);

        //tsd($data);
        //exit;
        $score = array();
        $stapcore = array();
        $labels = array();

        for ($i = 1; $i < 11; $i++) {
            $stapscore[$i] = 0;
        }
        if ($data) {
            $items = $this->haal_items();

            if ($data[0]['result']) {
                $aa = json_decode($data[0]["result"], true);
                foreach ($aa as $key => $val) {
                    if (substr($key, 0, 2) == 'cb') {
                        $stukjes = explode("_", $key);
                        $stap = substr($stukjes[0], 2);
                        $stapscore[$stap]++;
                        $items[$stukjes[1]]['check'] = true;
                    }
                }
            }
        }
        $score[1] = $stapscore;
        $itemsterug = array();
        foreach ($items as $item) {
            $itemsterug[$item['stap']][$item['sub']]['naam'] = $item['naam'];
            $itemsterug[$item['stap']][$item['sub']]['check'] = $item['check'];
        }
        $score[2] = $itemsterug;

        return $score;
    }
    public function haal_laatste_score($gid)
    {
        $zql = "select * from tool_usermeta where gem_id=" . $gid . " and archiefUnix is not null order by umeta_id DESC limit 1;";
        $data = $this->getArray($zql);
        return $data;
    }
    public function haal_max_score_per_stap()
    {
        $terug = array();
        $weer = array();
        $aa = "select count(ID) as maks, CAST(stap AS UNSIGNED) as stap from
                (select ID, post_title as item, post_content as tip from wp_posts where post_type = 'check' and post_status = 'publish') t
                left join
                (select post_id, meta_value as volgorde, meta_key from wp_postmeta where meta_key = 'volgorde') h on t.ID=h.post_id
                left join
                (select post_id, meta_value as stap, meta_key from wp_postmeta where meta_key = 'stap') a on t.ID=a.post_id
                left join
                (select post_id, meta_value as vooraf_text, meta_key from wp_postmeta where meta_key = 'vooraf_text') v on t.ID=v.post_id
                group by stap order by stap, volgorde;";
        $terug = $this->getArray($aa);
        if ($terug) {
            foreach ($terug as $row) {
                $weer[$row["stap"]] = (int) $row["maks"];
            }
        }
        //return $terug;
        return $weer;
    }
    public function haal_tipstring()
    {
        $terug = array();
        $weer = array();
        $aa = "select * from (select ID, post_content as tip from wp_posts where post_type = 'check' and post_status = 'publish') b
               left join
                (select post_id, meta_value as stap from wp_postmeta where meta_key = 'stap') a on b.ID=a.post_id;";
        $terug = $this->getArray($aa);

        foreach ($terug as $row) {
            $weer['cb' . $row["stap"] . '_' . $row["ID"]] = $row["tip"];
        }
        //ts($weer);
        return $weer;
    }
    public function haal_gemeente_gebruiker($uid)
    {

        $zz = "select g.id, g.naam from oko_gemeenten g
                inner join wp_bp_groups b on b.id = g.bp_id
                inner join wp_bp_groups_members m on m.group_id = b.id
                inner join wp_users w on w.ID = m.user_id
                where w.ID = " . $uid . ";";
        ts($zz);
        $bb = $this->getArray($zz);
        ts($bb);
        exit;
        if ($bb) {
            foreach ($bb as $row) {
                if ($row["naam"] == 'OKO team') {
                    return $row;
                }
            }
            return $row;
        }

        return false;
    }
    public function haal_invul_info($gem)
    {
        $zql = 'select distinct gem_id, FROM_UNIXTIME(tijd, "%e/%c/%Y") as dag, display_name from
            (select max(archiefUniX) as tijd, gem_id, user_id from tool_usermeta where gem_id = ' . $gem . ' order by tijd DESC) t
            inner join wp_users w on w.id = t.user_id';
        $aa = $this->getArray($zql);
        return $aa;
    }
    public function haal_gem_invullers()
    {
        $zql = 'select distinct gem_id, o.naam, FROM_UNIXTIME(tijd, "%e/%c/%Y") as dag, display_name from
            (select max(archiefUniX) as tijd, gem_id, user_id from tool_usermeta where user_id>30 group by gem_id order by tijd DESC) t
            inner join wp_users w on w.id = t.user_id
            inner join oko_gemeenten o on t.gem_id = o.id';
        $aa = $this->getArray($zql);
        return $aa;
    }
    public function haal_gemeente($eml)
    {
        $zql = "SELECT title, g.id FROM `wp_fc_subscribers` s
        inner join `wp_fc_subscriber_pivot` p on s.user_id = p.subscriber_id
        inner join `wp_fc_tags` t on p.object_id = t.id
        inner join `oko_gemeenten` g on g.fc_id = t.id
        where s.email = '" . $eml . "';";
        //ts($zql);
        $aa = $this->getArray($zql);
        return $aa;
    }
    public function haal_gemeente_naam($eml)
    {
        $zql = "SELECT naam from oko_gemeenten where id = '" . $eml . "';";
        //ts($zql);
        $aa = $this->getString($zql);
        return $aa;

    }
    public function haal_stap_titels()
    {
        $terug = array();
        $zql = "select * from stappen order by id;";
        $aa = $this->getArray($zql);
        if ($aa) {
            foreach ($aa as $row) {
                $terug[$row["id"]] = $row["tekst"];
            }
        }
        return $terug;
    }
    public function haal_fasen_stappen()
    {
        $terug = array();
        $zql = "select post_title as kop, post_content as inhoud from wp_posts
        where post_type = 'stap' and post_status = 'publish' order by post_title;";
        $aa = $this->getArray($zql);
        foreach ($aa as $row) {
            $nummer = substr($row["kop"], 5);
            if (strpos($row["kop"], "ase") > 0) {
                $terug[1][$nummer] = $row["inhoud"];
            } elseif (strpos($row["kop"], "ommu") > 0) {
                $terug[1][5] = $row["inhoud"];
            } else {
                $terug[2][$nummer] = $row["inhoud"];
            }
        }
        return $terug;
    }

    public function closedb()
    {
        mysqli_close($this->db_mysqli_local);
    }

    // determine locale and connect to DB
    public function connect()
    {
        // local
        if ($_SERVER["SERVER_NAME"] == "localhost") {
            $this->db_mysqli_local = mysqli_connect('localhost', 'user', 'user', 'oko');
        }
        // connect user on server
        else {
            $this->db_mysqli_local = mysqli_connect('192.168.137.189', 'tbweb10025', 'i3NVPfwxYHfX2ewAyQmGmZ--', 'tbweb10025');

        }

        // error? kill script
        if ($this->db_mysqli_local->connect_errno) {
            die('Connect Error: ' . $this->db_mysqli_local->connect_errno);
        }

        if ($this->utf8_connect) {
            $this->db_mysqli_local->set_charset("utf8");
        }

    }

    // lost leesteken problemen op binnen sql/php, maar creeert problemen in drillkaarten
    public function UTF8_Connect_Set($val)
    {
        $this->utf8_connect = $val;
    }

    // execute query
    // return result or false
    // TO BE ADDED TO
    public function exesql($sql)
    {
        $mysqli = $this->connect();
        if (is_string($sql)) {

            $output = $this->db_mysqli_local->query($sql);
        } else {
            $output = false;
        }
        $this->closedb();
        return $output;
    }

    public function getArray($sql)
    {
        //echo "hallo";
        $output = array();
        $this->connect();
        //  print_r($sql);
        $result = $this->exesql($sql);
        if ($result) {
            if ($result->num_rows > 0) {
                // output data of each row
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }

                return $output;
            }
        }
    }

    // return array with string; use when select is for one item only. ie: select 'gemeente' from 'regio'
    // WATCH OUT: DISTINCT
    public function getStrings($field, $table, $selector = "")
    {
        $output = array();
        $sql = "SELECT distinct($field) FROM $table $selector";
        //echo $sql."<br>";
        $this->connect();
        $result = $this->exesql($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row[$field];
            }
        }
        return $output;
    }

    public function getStringsPretty($sql, $field)
    {
        $output = array();
        $this->connect();
        $result = $this->exesql($sql);
        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                $output[] = $row[$field];
            }
        }
        return $output;
    }

    // return single string from query
    public function getString($sql)
    {
        $result = $this->exeSQL($sql);
        if ($result->num_rows > 0) {
            //return ( array_values($result->fetch_assoc())[0]);
            $thisa = $result->fetch_assoc();
            $tor = array_values($thisa);
            return $tor[0];
        }
    }

    // return 2 values as associative array kop=>val
    public function getStringsAArray($sql, $kop, $val)
    {
        $output = array();
        $this->connect();
        $result = $this->exesql($sql);
        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                $output[$row[$kop]] = $row[$val];
            }
        }
        return $output;
    }

    public function getStringsFromArray($sql)
    {
        $output = array();
        $result = array_values($this->getArray($sql));
        foreach ($result as $val) {
            $output[] = array_values($val);

        }
        return $output;
    }

    public function getStringsAsLump($sql)
    {
        $output = array();
        $result = array_values($this->getArray($sql));
        //print_r($result);
        foreach ($result as $val) {
            //  print_r($val);
            //   echo "$<br>";
            $output[] = array_values($val);

        }
        return $output;
    }

} // end class Toegang

//$t=new InterfaceDB();
//$t->getStringsFromArray("select * from AOJ_ouders");
