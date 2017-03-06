<?php
    require_once "auth.php";
    require_once "db.php";

    if($countdown <= 0) {
        header('HTTP/1.1 303 See Other');
        header("Location: index.php");
        exit;
    }

    if(!$auth) {
        header('HTTP/1.1 303 See Other');
        header("Location: invite1.php");
        exit;
    }

    $domain = filter_var($_POST['domain'], FILTER_SANITIZE_URL);
    $value = (float)filter_var($_POST['value'], FILTER_SANITIZE_NUMBER_FLOAT);
    if($value < 0.0) { $value = 0.0; }

    if($domain) {
        $lock = fopen(LOCK_FILE, 'rw');
        flock($lock, LOCK_EX);

        $donations = load_donations($email);
        $old = (float)$donations[$domain];
        if($value > 0.0) {
            $donations[$domain] = $value;
        } else {
            unset($donations[$domain]);
        }

        $charities = load_charities();
        $c = $charities[$domain];
        if($c) {
            $c->value += $value - $old;
            if($c->value < 0.0) { $c->value = 0.0; }
        }

        save_donations($email, $donations);
        save_charities($charities);

        flock($lock, LOCK_UN);
        fclose($lock);
    }

    header('HTTP/1.1 303 See Other');
    header("Location: index.php");
    exit;
?>
