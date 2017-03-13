<?php

define('LOG_DIR', "./logs");
define('DATA_DIR', "./data");
define('LOCK_FILE', DATA_DIR."/auto.lock");
define('CHARITIES_DB', DATA_DIR."/charities.db");

class Charity {
    public $url = NULL;
    public $name = NULL;
    public $donate = NULL;
    public $value = 0.0;
    public $timestamp = 0;

    function __construct($url="", $name="", $donate="",
                         $value=0.0, $timestamp=NULL)
    {
        $this->url = $url;
        $this->name = $name;
        $this->donate = $donate;
        $this->value = $value;

        if(NULL === $timestamp) {
            $now = new DateTime();
            $timestamp = $now->getTimestamp();
        }
        $this->timestamp = $timestamp;
    }
}

function get_domain($url)
{
    $domain = filter_var($url, FILTER_SANITIZE_URL);
    $domain = preg_replace('#^[a-z]+://#i', '', $domain);
    $domain = preg_replace('#/?(index.[a-z0-9_]+)?(\\?.*)?$#i', '', $domain);
    return $domain;
}

function load_charities()
{
     $charities = array();

     if(FALSE !== ($fd = fopen(CHARITIES_DB, 'r'))) {
         while(FALSE !== ($csv = fgetcsv($fd))) {
             $timestamp = isset($csv[5]) ? (int)$csv[5] : 0;
             $charities[$csv[0]] = new Charity(
                 $url = $csv[1], $name = $csv[2], $donate = $csv[3],
                 $value = (float)$csv[4], $timestamp);
         }
     }

     if(isset($fd)) { fclose($fd); }

     return $charities;
}

function save_charities($charities)
{
    if(FALSE === ($fd = fopen(CHARITIES_DB, 'w'))) { return FALSE; }
    foreach($charities as $k => $c) {
        fputcsv($fd, array($k, $c->url, $c->name, $c->donate,
                           $c->value, $c->timestamp));
    }

    if(isset($fd)) { fclose($fd); }

    return TRUE;
}

function load_donations($email)
{
    $donations = array();

    if(is_file(DATA_DIR."/{$email}") &&
       FALSE !== ($fd = fopen(DATA_DIR."/{$email}", 'r')))
    {
        while(FALSE !== ($csv = fgetcsv($fd))) {
            $donations[$csv[0]] = (float)$csv[1];
        }
    }

    if(isset($fd)) { fclose($fd); }

    return $donations;
}

function save_donations($email, $donations)
{
    $fname = DATA_DIR."/{$email}";

    if(count($donations) > 0) {
        if(FALSE === ($fd = fopen($fname, 'w'))) { return FALSE; }
        foreach($donations as $k => $v) { fputcsv($fd, array($k, $v)); }
        if(isset($fd)) { fclose($fd); }
        return TRUE;
    }

    return unlink($fname);
}

function write_log($name, $rec)
{
    $fname = LOG_DIR . "/{$name}.log";
    if(FALSE == ($fd = fopen($fname, 'a'))) { return; }

    $now = new DateTime();
    array_unshift($rec, $now->getTimestamp());

    fputcsv($fd, $rec);
    fclose($fd);
}

?>
