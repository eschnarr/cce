<?php
    require_once "auth.php";
    require_once "db.php";

    if($countdown <= 0) {
        header("Location: index.php");
        exit;
    }

    if(!$auth) {
        header("Location: invite1.php");
        exit;
    }

    $domain = filter_var($_POST['domain'], FILTER_SANITIZE_URL);
    $value = (float)filter_var($_POST['value'], FILTER_SANITIZE_NUMBER_FLOAT);
    if($value < 0.0) { $value = 0.0; }

    if($domain && $value > 0.0) try {
        $lock = fopen(LOCK_FILE, 'rw');
        flock($lock, LOCK_EX);

        $charities = load_charities();

        $c = $charities[$domain];
        if($c) { $c->value += $value; }

        $donations = load_donations($email);
        $donations[$domain] += $value;

        save_donations($email, $donations);
        save_charities($charities);

    } finally {
        if($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    header("Location: index.php");
    exit;
?>