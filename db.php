<?php

const LOCK_FILE = "data/auto.lock";
const CHARITIES_DB = "data/charities.db";

class Charity {
    public $url = NULL;
    public $name = NULL;
    public $donate = NULL;
    public $value = 0.0;

    function __construct($url="", $name="", $donate="", $value=0.0)
    {
        $this->url = $url;
        $this->name = $name;
        $this->donate = $donate;
        $this->value = $value;
    }
}

function get_domain($url)
{
    $domain = $url;
    if($n = strpos($domain,"://")) { $domain = substr($domain,$n+3); }
    if($n = strpos($domain,"/")) { $domain = substr($domain,0,$n); }
    return $domain;
}

function load_charities()
{
    try {
        $charities = array();

        if(FALSE !== ($fd = fopen(CHARITIES_DB, 'r'))) {
            while(FALSE !== ($csv = fgetcsv($fd))) {
                $charities[$csv[0]] = new Charity(
                    $url = $csv[1],
                    $name = $csv[2],
                    $donate = $csv[3],
                    $value = $csv[4]);
            }
        }

        return $charities;

    } finally {
        if($fd) { fclose($fd); }
    }
}

function save_charities($charities)
{
    try {
        if(FALSE === ($fd = fopen(CHARITIES_DB, 'w'))) { return FALSE; }
        foreach($charities as $k => $c) {
            fputcsv($fd, array($k, $c->url, $c->name, $c->donate, $c->value));
        }
        return TRUE;

    } finally {
        if($fd) { fclose($fd); }
    }
}

?>
