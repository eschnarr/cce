<?php

define('DATA_DIR', "./data");
define('LOCK_FILE', DATA_DIR."/auto.lock");
define('CHARITIES_DB', DATA_DIR."/charities.db");

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
     $charities = array();

     if(FALSE !== ($fd = fopen(CHARITIES_DB, 'r'))) {
         while(FALSE !== ($csv = fgetcsv($fd))) {
             $charities[$csv[0]] = new Charity(
                 $url = $csv[1], $name = $csv[2], $donate = $csv[3],
                 $value = (float)$csv[4]);
         }
     }

     fclose($fd);

     return $charities;
}

function save_charities($charities)
{
    if(FALSE === ($fd = fopen(CHARITIES_DB, 'w'))) { return FALSE; }
    foreach($charities as $k => $c) {
        fputcsv($fd, array($k, $c->url, $c->name, $c->donate, $c->value));
    }

    fclose($fd);

    return TRUE;
}

function load_donations($email)
{
    $donations = array();

    if(FALSE !== ($fd = fopen(DATA_DIR."/{$email}", 'r'))) {
        while(FALSE !== ($csv = fgetcsv($fd))) {
            $donations[$csv[0]] = (float)$csv[1];
        }
    }

    fclose($fd);

    return $donations;
}

function save_donations($email, $donations)
{
    if(FALSE === ($fd = fopen(DATA_DIR."/{$email}", 'w'))) { return FALSE; }

    foreach($donations as $k => $v) { fputcsv($fd, array($k, $v)); }

    fclose($fd);

    return TRUE;
}

?>
