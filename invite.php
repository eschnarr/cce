<?php
require_once "auth.php";
require_once "invitation.php";
require_once "recaptcha/src/autoload.php";

$secret = "6LdtohcUAAAAANmK8OxxoRQhLDEUcrXA72mdXKvQ";

if($countdown <= 0) {
    header('HTTP/1.1 303 See Other');
    header("Location: .");
    exit;
}

$subject = "Invitation to join The Charity Chain";
if(isset($_POST) && isset($_POST['subject']) && $_POST['subject']) {
    $subject = trim($_POST['subject']);
}

$div = '<div style="border:1px solid black; ' .
       'margin:5px; padding:10px; max-width:800px">' .
       PHP_EOL;

$note = "";
if(isset($_POST) && isset($_POST['note']) && $_POST['note']) {
    $note = rtrim($_POST['note']);
    $note = preg_replace('#[ \t]*\r?\n#',"\n", $note);
    $note = preg_replace('#\n+\n#', "\n\n", $note);
    if($note && $note[0] == "\n") {
        $note = preg_replace('#\n+#', "", $note, 1);
    }
    $note = htmlspecialchars($note);
    $note = preg_replace('#\n#', "<br>".PHP_EOL, $note);
    $note = $div.$note.PHP_EOL."</div>".PHP_EOL;
}

$robot = true;
$found = array();
$failed = array(); $sent = 0;
if(isset($_POST['g-recaptcha-response'])) {

    $response = $_POST['g-recaptcha-response'];
    $recaptcha = new \ReCaptcha\ReCaptcha($secret);
    $resp = $recaptcha->verify($response, $_SERVER['REMOTE_ADDR']);
    if($resp->isSuccess()) {
        $robot = false;

        foreach($_POST as $arg => $value) {
            if("to" == substr($arg,0,2)) {
                $to = filter_var($value, FILTER_SANITIZE_EMAIL);
                if(!$to || isset($found[$to])) { continue; }
                $found[$to] = TRUE;

                $headers = "MIME-Version: 1.0\n";
                $headers .= "Content-type: text/html; charset=iso-8859-1\n";
                $headers .= "From: contact@thecharitychain.org\n";
                $headers .= "X-Mailer: PHP/".phpversion();

                $text = invitation($to);
                $text = <<<"END"
{$note}{$div}
$text
</div>
END;

                if(mail($to, $subject, $text, $headers)) {
                    ++$sent;
                } else {
                    $failed[] = $to;
                }
            }
        }
    }
}

$message = "No invitations sent.";
if($robot) {
    $message .= " Are you a robot?";
} else if(0 < count($found)) {
    $message = $sent==1 ? "Invitation sent." : "{$sent} invitations sent.";
    if(0 < count($failed)) {
        $message .= " Failed sending to";
        foreach($failed as $_email) { $message .= " {$_email}"; }
        $message .= ".";
    }
}
if($message) {
    $message = "?message=" . urlencode($message);
}

header('HTTP/1.1 303 See Other');
header("Location: .{$message}");
exit;
?>
