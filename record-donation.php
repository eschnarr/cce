<?php
    require_once "auth.php";
    require_once "db.php";

var_dump($countdown); echo "<br>";
    if($countdown <= 0) {
        header("Location: index.php");
        exit;
    }

var_dump($auth); echo "<br>";
    if(!$auth) {
        header("Location: invite1.php");
        exit;
    }

    $domain = filter_var($_POST['domain'], FILTER_SANITIZE_URL);
var_dump($domain); echo "<br>";
    $value = (float)filter_var($_POST['value'], FILTER_SANITIZE_NUMBER_FLOAT);
    if($value < 0.0) { $value = 0.0; }

    if($domain && $value > 0.0) {
        $lock = fopen(LOCK_FILE, 'rw');
        flock($lock, LOCK_EX);

        $charities = load_charities();

        $c = $charities[$domain];
        if($c) { $c->value += $value; }

        $donations = load_donations($email);
var_dump($donations); echo "<br>";
        $donations[$domain] += $value;

var_dump($donations); echo "<br>";
        save_donations($email, $donations);
        save_charities($charities);

        flock($lock, LOCK_UN);
        fclose($lock);
    }

    header("Location: index.php");
    exit;
?>
