<?php
require_once "auth.php";
require_once "recaptcha/src/autoload.php";

$siteKey = "6LdtohcUAAAAABL79g-YlLX7xGELUVKZatW4W4uh";
$secret = "6LdtohcUAAAAANmK8OxxoRQhLDEUcrXA72mdXKvQ";

function invitation($email="")
{
    if($email) {
        $link = "http://thecharitychain.org?email=" .
                urlencode($email) . "&auth=" . gen_auth($email);
    } else {
        $link = "";
    }

    return <<<"END"
<p>Welcome to the Charity Chain!</p>

<p>This is an experiment in viral giving. By donating a small amount to your
  favorate charity, and inviting others to do the same, you begin a cascade of
  donations greater than you can achieve by working alone. You can make a
  diference.</p>

<p>Below is your personal ticket to join The Charity Chain. Click on the ticket
  get started.</p>

<p><a href="{$link}">
  <button type="button">Ticket to enter The Charity Chain</button>
</a></p>

<p>Thank You,<br>The Charity Chain</p>
END;
}

if($countdown <= 0) {
    header("Location: index.php");
    exit;
}

$n = $_GET ? filter_var($_GET['n'], FILTER_SANITIZE_NUMBER_INT) : '';
if($n <= 0) { $n = 1; }

if(isset($_POST['g-recaptcha-response'])) {

    $response = $_POST['g-recaptcha-response'];
    $recaptcha = new \ReCaptcha\ReCaptcha($secret);
    $resp = $recaptcha->verify($response, $_SERVER['REMOTE_ADDR']);
    if($resp->isSuccess()) {

        $found = array();
        foreach($_POST as $arg => $value) {
            if("email" == substr($arg,0,5)) {
                $to = filter_var($value, FILTER_SANITIZE_EMAIL);
                if(!$to || $found[$to]) { continue; }
                $found[$to] = TRUE;

                $subject = "Invitation to join The Charity Chain";
                $headers = "MIME-Version: 1.0\n";
                $headers .= "Content-type: text/html; charset=iso-8859-1\n";
                $headers .= "From: contact@thecharitychain.org\n";
                $headers .= "X-Mailer: PHP/".phpversion();
                mail($to, $subject, invitation($to), $headers);
            }
        }

        if(count($found) > 0) {
            header("Location: index.php");
            exit;
        }
    }
}

echo <<<"END"
<html>
<head>
<script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<body>

<form action="invite.php?n={$n}" method="post">
<table width=800><tr>
END;
if($n <= 1) {
    echo <<<"END"
  <td colspan=3><p>Use this form to invite someone to join The Charity
    Chain. Or use it to re-invite yourself, if you lost your invitation. Simply
    enter an email address below, click send, and a message like the one shown
    will be sent.</p></td>
END;
} else {
    echo <<<"END"
  <td colspan=3><p>Send invitations to {$n} of your friends. Simply enter
    their email addresses below, click send, and a message like the one shown
    will be sent to each person.</p></td>
END;
}
echo <<<"END"
</tr><tr>
  <td>&nbsp;</td>
END;
for($i=1; $i<=$n; ++$i) {
    $value = isset($_POST["email{$i}"]) ? $_POST["email{$i}"] : "";
    if($value) { $value = " value=\"{$value}\""; }
    echo <<<"END"
</tr><tr>
  <td width=25>To:</td>
  <td><input type="email" name="email{$i}"{$value}></td>
END;
  if($i <= 1) {
      echo <<<"END"
  <td rowspan={$n}>
    <input type="submit" value="Send">
    <div class="g-recaptcha" data-sitekey="6LdtohcUAAAAABL79g-YlLX7xGELUVKZatW4W4uh"></div>
  </td>
END;
  }
}
echo <<<"END"
</tr><tr>
  <td colspan=3 width="800">
END;
echo invitation();
echo <<<"END"
</td>
</tr></table>
</form>

</body></html>
END;
?>
