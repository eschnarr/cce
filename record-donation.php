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

$message = "";
if($domain && $value > 0.0) {
    $lock = fopen(LOCK_FILE, 'rw');
    flock($lock, LOCK_EX);

    $charities = load_charities();

    $c = $charities[$domain];
    if($c) {
        $now = new DateTime();
        $c->timestamp = $now->getTimestamp();
        $c->value += $value;

        $message = "$".money_format("%i",$value)." donation recorded.";
        $message .= " Don't forget to visit {$c->name} to actually make your donation.";
    }

    $donations = load_donations($email);
    $donations[$domain] += $value;

    save_donations($email, $donations);
    save_charities($charities);

    flock($lock, LOCK_UN);
    fclose($lock);
}

if($message) {
    $message = "?message={$message}";
}
header('HTTP/1.1 303 See Other');
header("Location: .{$message}");
exit;
?>
