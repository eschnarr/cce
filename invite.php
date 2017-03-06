<?php
require_once "auth.php";
require_once "invitation.php";
require_once "recaptcha/src/autoload.php";

$secret = "6LdtohcUAAAAANmK8OxxoRQhLDEUcrXA72mdXKvQ";

if($countdown <= 0) {
    header("Location: index.php");
    exit;
}

$subject = "Invitation to join The Charity Chain";
if(isset($_POST) && isset($_POST['subject']) && $_POST['subject']) {
    $subject = trim($_POST['subject']);
}

if(isset($_POST['g-recaptcha-response'])) {

    $response = $_POST['g-recaptcha-response'];
    $recaptcha = new \ReCaptcha\ReCaptcha($secret);
    $resp = $recaptcha->verify($response, $_SERVER['REMOTE_ADDR']);
    if($resp->isSuccess()) {

        $found = array();
        foreach($_POST as $arg => $value) {
            if("to" == substr($arg,0,2)) {
                $to = filter_var($value, FILTER_SANITIZE_EMAIL);
                if(!$to || isset($found[$to])) { continue; }
                $found[$to] = TRUE;

                $headers = "MIME-Version: 1.0\n";
                $headers .= "Content-type: text/html; charset=iso-8859-1\n";
                $headers .= "From: contact@thecharitychain.org\n";
                $headers .= "X-Mailer: PHP/".phpversion();
                mail($to, $subject, invitation($to), $headers);
            }
        }
    }
}

header("Location: index.php");
exit;
?>
