<?php
require_once "auth.php";
require_once "db.php";

if($countdown <= 0 || !$auth) {
    header('HTTP/1.1 303 See Other');
    header("Location: .");
    exit;
}

$domain = filter_var($_POST['domain'], FILTER_SANITIZE_URL);
$value = (float)filter_var($_POST['value'],
                           FILTER_SANITIZE_NUMBER_FLOAT,
                           FILTER_FLAG_ALLOW_FRACTION |
                           FILTER_FLAG_ALLOW_THOUSAND);
if($value < 0.0) { $value = 0.0; }

if($domain) {
    $lock = fopen(LOCK_FILE, 'rw');
    flock($lock, LOCK_EX);

    $donations = load_donations($email);
    $old = isset($donations[$domain]) ? (float)$donations[$domain] : 0.0;
    if($value > 0.0) {
        $donations[$domain] = $value;
    } else {
        unset($donations[$domain]);
    }

    $charities = load_charities();
    $c = $charities[$domain];
    if($c) {
        if($value != $old) {
            $c->timestamp = (new DateTime())->getTimestamp();
        }
        $c->value += $value - $old;
        if($c->value < 0.0) { $c->value = 0.0; }
    }

    save_donations($email, $donations);
    save_charities($charities);

    flock($lock, LOCK_UN);
    fclose($lock);
}

header('HTTP/1.1 303 See Other');
header("Location: .");
exit;
?>
