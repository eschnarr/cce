<?php

const CHARITIES_DB = "charities.db";

class Charity {
    public $name = NULL;
    public $url = NULL;
    public $donate = NULL;
    public $value = 0.0;

    function __construct($name, $url, $donate, $value)
    {
        $this->name = $name;
        $this->url = $url;
        $this->donate = $donate;
        $this->value = $value;
    }
}

function load_charities()
{
    try {
        $charities = [];

        if(FALSE !== ($fd = fopen(CHARITIES_DB, 'r'))) {
            while(FALSE !== ($csv = fgetcsv($fd))) {
                $charities[] = new Charity(
                    $name = $csv[0],
                    $url = $csv[1],
                    $donate = $csv[2],
                    $value = $csv[3]);
            }

        } else {
            $charities[] = new Charity(
                $name = "Dane County Humane Society",
                $url = "www.giveshelter.org",
                $donate = "https://www.giveshelter.org/make-a-donation.html",
                $value = 100.0);
            $charities[] = new Charity(
                $name = "American Red Cross",
                $url = "www.redcross.org",
                $donate = "https://www.redcross.org/donate/donation",
                $value = 200.0);
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

        foreach($charities as $c) {
            fputcsv($fd, [ $c->name, $c->url, $c->donate, $c->value ]);
        }

        return TRUE;

    } finally {
        if($fd) { fclose($fd); }
    }
}

?>
